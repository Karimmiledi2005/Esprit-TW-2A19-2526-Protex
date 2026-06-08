<?php
require_once __DIR__ . '/../connexion.php';
require_once __DIR__ . '/../service/EmailService.php';

class SinistreStatsController {
    private $db;

    public function __construct() {
        $this->db = config::getConnexion();
    }

    public function getDashboardStats(): array {
        $stats = [
            'total' => 0,
            'en_attente' => 0,
            'rembourse' => 0,
            'refuse' => 0,
            'moyenne_fraude' => 0,
            'historique_30j' => [],
            'statuts' => []
        ];

        try {
            $stats['total'] = (int) $this->db->query("SELECT COUNT(*) FROM sinistre")->fetchColumn();
            $stats['en_attente'] = (int) $this->db->query("SELECT COUNT(*) FROM sinistre WHERE statut IN ('en_attente', 'en_analyse', 'assigne', 'en_cours')")->fetchColumn();
            $stats['rembourse'] = (int) $this->db->query("SELECT COUNT(*) FROM sinistre WHERE statut = 'rembourse'")->fetchColumn();
            $stats['refuse'] = (int) $this->db->query("SELECT COUNT(*) FROM sinistre WHERE statut = 'refuse'")->fetchColumn();
            
            // Average fraud score
            $stats['moyenne_fraude'] = (float) $this->db->query("SELECT COALESCE(AVG(score_global), 0) FROM fraud_analysis")->fetchColumn();

            // Daily history (last 30 days)
            $stmt = $this->db->query("
                SELECT DATE(date_declaration) as jour, COUNT(*) as total 
                FROM sinistre 
                WHERE date_declaration >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                GROUP BY jour 
                ORDER BY jour ASC
            ");
            $stats['historique_30j'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Status distribution
            $stmt = $this->db->query("SELECT statut, COUNT(*) as total FROM sinistre GROUP BY statut");
            $stats['statuts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("getDashboardStats error: " . $e->getMessage());
        }

        return $stats;
    }

    public function getAgentWorkload(int $agentId = 0): array {
        $data = [];
        try {
            // If agentId > 0, we could filter, but typically we want a grid of all agents
            // We assume agents are users with role 'agent'
            $query = "
                SELECT u.id_user, u.nom, u.prenom,
                       COUNT(s.id_sinistre) as total_assignes,
                       SUM(CASE WHEN s.statut IN ('valide', 'rembourse', 'refuse') THEN 1 ELSE 0 END) as total_traites
                FROM `user` u
                LEFT JOIN sinistre s ON u.id_user = s.id_agent_assigne
                WHERE u.role = 'agent'
                GROUP BY u.id_user, u.nom, u.prenom
                ORDER BY total_assignes DESC
            ";
            $stmt = $this->db->query($query);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("getAgentWorkload error: " . $e->getMessage());
        }
        return $data;
    }

    public function exportPdf(array $filters): void {
        // Due to missing extensions (gd, zip) we provide a native HTML print view fallback.
        header('Content-Type: text/html; charset=utf-8');
        echo "<html><head><title>Export PDF (Impression)</title></head><body onload='window.print()'>";
        echo "<h1>Rapport des Sinistres</h1>";
        echo "<table border='1' cellspacing='0' cellpadding='5'><tr><th>ID</th><th>Client</th><th>Date</th><th>Statut</th></tr>";
        try {
            $stmt = $this->db->query("SELECT id_sinistre, id_user, date_declaration, statut FROM sinistre ORDER BY date_declaration DESC LIMIT 100");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr><td>{$row['id_sinistre']}</td><td>Client #{$row['id_user']}</td><td>{$row['date_declaration']}</td><td>{$row['statut']}</td></tr>";
            }
        } catch (Exception $e) {}
        echo "</table></body></html>";
        exit;
    }

    public function exportExcel(array $filters): void {
        // Generate CSV as native fallback for Excel
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=sinistres_export.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID Sinistre', 'ID Utilisateur', 'Date Declaration', 'Statut']);
        try {
            $stmt = $this->db->query("SELECT id_sinistre, id_user, date_declaration, statut FROM sinistre ORDER BY date_declaration DESC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, $row);
            }
        } catch (Exception $e) {}
        fclose($output);
        exit;
    }

    public function addComment(int $sinistreId, int $userId, string $comment): array {
        if (!$sinistreId || !$userId || trim($comment) === '') {
            return ['success' => false, 'message' => 'Données invalides.'];
        }

        try {
            $mentions = [];
            preg_match_all('/@([a-zA-Z0-9_]+)/', $comment, $matches);
            if (!empty($matches[1])) {
                foreach (array_unique($matches[1]) as $mention) {
                    $parts = explode('_', $mention, 2);
                    if (count($parts) == 2) {
                        $search = $this->db->prepare("SELECT id_user, email, prenom, nom FROM `user` WHERE LOWER(prenom) = LOWER(?) AND LOWER(nom) = LOWER(?)");
                        $search->execute([$parts[0], $parts[1]]);
                        $user = $search->fetch(PDO::FETCH_ASSOC);
                        if ($user) {
                            $mentions[] = ['id' => (int)$user['id_user'], 'name' => $user['prenom'] . ' ' . $user['nom']];
                            $emailService = new EmailService();
                            $emailService->sendEmail(
                                $user['email'],
                                "Vous avez été mentionné dans un sinistre",
                                "Bonjour {$user['prenom']},\n\nVous avez été mentionné par un collègue dans le sinistre #$sinistreId.\n\nConsultez le dossier pour plus de détails.\n\nCordialement,\nL'équipe Protex"
                            );
                            // Also insert notification
                            try {
                                $notif = $this->db->prepare("INSERT INTO notification (id_user, message, created_at) VALUES (?, ?, NOW())");
                                $notif->execute([$user['id_user'], "Vous avez été mentionné dans le sinistre #$sinistreId"]);
                            } catch (Throwable $e) {}
                        }
                    }
                }
            }

            $stmt = $this->db->prepare("INSERT INTO sinistre_commentaire (id_sinistre, id_user, commentaire, mentions) VALUES (?, ?, ?, ?)");
            $stmt->execute([$sinistreId, $userId, trim($comment), json_encode($mentions, JSON_UNESCAPED_UNICODE)]);

            return ['success' => true, 'message' => 'Commentaire ajouté.', 'mentions' => $mentions];
        } catch (Exception $e) {
            error_log("addComment error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur serveur.'];
        }
    }

    public function uploadFiles(int $sinistreId, array $files): array {
        if (!$sinistreId || empty($files['name'])) {
            return ['success' => false, 'message' => 'Aucun fichier.'];
        }

        $allowedExts = ['jpg','jpeg','png','gif','pdf','doc','docx','xls','xlsx'];
        $allowedMimes = ['image/jpeg','image/png','image/gif','application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        $maxSize = 10 * 1024 * 1024; // 10MB

        $uploadDir = __DIR__ . '/../uploads/sinistres/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $savedFiles = [];
        try {
            $stmt = $this->db->prepare("INSERT INTO sinistre_fichier (id_sinistre, nom_fichier, chemin, type, taille) VALUES (?, ?, ?, ?, ?)");
            
            // Check if multiple files (array) or single file
            $isMulti = is_array($files['name']);
            $count = $isMulti ? count($files['name']) : 1;

            for ($i = 0; $i < $count; $i++) {
                $name = $isMulti ? $files['name'][$i] : $files['name'];
                $tmpName = $isMulti ? $files['tmp_name'][$i] : $files['tmp_name'];
                $error = $isMulti ? $files['error'][$i] : $files['error'];
                $size = $isMulti ? $files['size'][$i] : $files['size'];

                if ($error === UPLOAD_ERR_OK) {
                    // Validate size
                    if ($size > $maxSize) continue;
                    // Validate extension
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedExts, true)) continue;
                    // Validate MIME type
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $tmpName);
                    finfo_close($finfo);
                    if (!in_array($mime, $allowedMimes, true)) continue;

                    $safeName = uniqid("sin_{$sinistreId}_") . '.' . $ext;
                    $dest = $uploadDir . $safeName;
                    $relativePath = 'uploads/sinistres/' . $safeName;

                    if (move_uploaded_file($tmpName, $dest)) {
                        $stmt->execute([$sinistreId, $name, $relativePath, $mime, $size]);
                        $savedFiles[] = $relativePath;
                    }
                }
            }
            return ['success' => true, 'files' => $savedFiles];
        } catch (Exception $e) {
            error_log("uploadFiles error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur serveur.'];
        }
    }

    public function postMessage(int $sinistreId, int $userId, string $message): array {
        if (!$sinistreId || !$userId || trim($message) === '') {
            return ['success' => false, 'message' => 'Données invalides.'];
        }
        try {
            $stmt = $this->db->prepare("INSERT INTO message_sinistre (id_sinistre, id_user, contenu) VALUES (?, ?, ?)");
            $stmt->execute([$sinistreId, $userId, trim($message)]);
            $msgId = $this->db->lastInsertId();
            return ['success' => true, 'id' => $msgId];
        } catch (Exception $e) {
            error_log("postMessage error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur serveur.'];
        }
    }

    public function fetchMessages(int $sinistreId, int $sinceId = 0): array {
        try {
            $stmt = $this->db->prepare("
                SELECT m.id, m.contenu, m.created_at, u.nom, u.prenom, u.role
                FROM message_sinistre m
                JOIN `user` u ON m.id_user = u.id_user
                WHERE m.id_sinistre = ? AND m.id > ?
                ORDER BY m.id ASC
            ");
            $stmt->execute([$sinistreId, $sinceId]);
            return ['success' => true, 'messages' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            error_log("fetchMessages error: " . $e->getMessage());
            return ['success' => false, 'messages' => []];
        }
    }
}
