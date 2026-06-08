<?php
require_once __DIR__ . '/../connexion.php';
require_once __DIR__ . '/../model/Sinistre.php';
require_once __DIR__ . '/../helpers/SessionGuard.php';

if (file_exists(__DIR__ . '/../controller/FraudeService.php')) {
    require_once __DIR__ . '/../controller/FraudeService.php';
} elseif (file_exists(__DIR__ . '/../model/FraudeService.php')) {
    require_once __DIR__ . '/../model/FraudeService.php';
}

if (file_exists(__DIR__ . '/../controller/EmailService.php')) {
    require_once __DIR__ . '/../controller/EmailService.php';
}
if (file_exists(__DIR__ . '/../service/EmailService.php')) {
    require_once __DIR__ . '/../service/EmailService.php';
} elseif (file_exists(__DIR__ . '/../model/EmailService.php')) {
    require_once __DIR__ . '/../model/EmailService.php';
}

class SinistreController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = config::getConnexion();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("
            SELECT s.id_sinistre, s.type, s.description, s.date_declaration, s.statut, s.photo_url,
                   s.id_contrat, s.id_user,
                   CONCAT(u.prenom,' ',u.nom) AS client_nom,
                   COALESCE(c.numero_contrat, CONCAT('CNT-', s.id_contrat)) AS numero_contrat,
                   /* ANTIFRAUD : inclure le score dans la liste */
                   fa.score_global   AS fraud_score,
                   fa.niveau_risque  AS fraud_niveau,
                   fa.suggestion_ia  AS fraud_suggestion
            FROM sinistre s
            LEFT JOIN user u          ON s.id_user    = u.id_user
            LEFT JOIN contrat c       ON s.id_contrat = c.id_contrat
            LEFT JOIN fraud_analysis fa ON s.id_sinistre = fa.id_sinistre
            ORDER BY s.date_declaration DESC
        ");
        $sinistres = [];
        foreach ($stmt->fetchAll() as $row) {
            $sinistre = new Sinistre($row['id_contrat'], $row['id_user'], $row['type'], $row['description']);
            $sinistre->setIdSinistre($row['id_sinistre']);
            $sinistre->setPhotoUrl($row['photo_url']);
            $sinistre->setDateDeclaration($row['date_declaration']);
            $sinistre->setStatut($row['statut']);
            // Enrichissement antifraud
            $sinistre->fraudScore     = $row['fraud_score'];
            $sinistre->fraudNiveau    = $row['fraud_niveau'];
            $sinistre->fraudSuggestion = $row['fraud_suggestion'];
            $sinistre->clientNom      = $row['client_nom'];
            $sinistre->numeroContrat  = $row['numero_contrat'];
            $sinistres[] = $sinistre;
        }
        return $sinistres;
    }

    public function getAllByRole(): array {
        $role   = SessionGuard::role();
        $agence = SessionGuard::agenceId();
        $userId = SessionGuard::userId();

        $select = "
            SELECT s.id_sinistre, s.type, s.description, s.date_declaration, s.statut, s.photo_url,
                   s.id_contrat, s.id_user, s.id_agence, s.id_agent_assigne,
                   CONCAT(u.prenom,' ',u.nom) AS client_nom,
                   COALESCE(c.numero_contrat, CONCAT('CNT-', s.id_contrat)) AS numero_contrat,
                   fa.score_global  AS fraud_score,
                   fa.niveau_risque AS fraud_niveau,
                   fa.suggestion_ia AS fraud_suggestion
            FROM sinistre s
            LEFT JOIN user u            ON s.id_user    = u.id_user
            LEFT JOIN contrat c         ON s.id_contrat = c.id_contrat
            LEFT JOIN fraud_analysis fa ON s.id_sinistre = fa.id_sinistre
        ";

        if ($role === 'superadmin') {
            $stmt = $this->db->query($select . " ORDER BY s.date_declaration DESC");

        } elseif ($role === 'admin') {
            $stmt = $this->db->prepare($select . " WHERE s.id_agence = :agence ORDER BY s.date_declaration DESC");
            $stmt->execute([':agence' => $agence]);

        } else {
            // agent : uniquement ses sinistres assignés
            $stmt = $this->db->prepare($select . "
                WHERE s.id_agent_assigne = :userId
                  AND s.id_agence = :agence
                ORDER BY s.date_declaration DESC
            ");
            $stmt->execute([':userId' => $userId, ':agence' => $agence]);
        }

        $sinistres = [];
        foreach ($stmt->fetchAll() as $row) {
            $sinistre = new Sinistre($row['id_contrat'], $row['id_user'], $row['type'], $row['description']);
            $sinistre->setIdSinistre($row['id_sinistre']);
            $sinistre->setPhotoUrl($row['photo_url'] ?? null);
            $sinistre->setDateDeclaration($row['date_declaration']);
            $sinistre->setStatut($row['statut']);
            // Score visible par les 3 rôles
            if (in_array(SessionGuard::role(), ['superadmin', 'admin', 'agent'], true)) {
                $sinistre->fraudScore      = $row['fraud_score'];
                $sinistre->fraudNiveau     = $row['fraud_niveau'];
                $sinistre->fraudSuggestion = $row['fraud_suggestion'];
            }
            $sinistre->clientNom = $row['client_nom'];
            $sinistre->numeroContrat = $row['numero_contrat'];
            $sinistres[] = $sinistre;
        }
        return $sinistres;
    }

    public function getByUser(int $userId): array
    {
        if (!$userId) return [];
        $stmt = $this->db->prepare("
            SELECT s.id_sinistre, s.type, s.description, s.date_declaration, s.statut, s.photo_url,
                   s.id_contrat, s.id_user,
                   COALESCE(c.numero_contrat, CONCAT('CNT-', s.id_contrat)) AS numero_contrat,
                   fa.score_global   AS fraud_score,
                   fa.niveau_risque  AS fraud_niveau,
                   fa.suggestion_ia  AS fraud_suggestion
            FROM sinistre s
            LEFT JOIN contrat c       ON s.id_contrat = c.id_contrat
            LEFT JOIN fraud_analysis fa ON s.id_sinistre = fa.id_sinistre
            WHERE s.id_user = :uid
            ORDER BY s.date_declaration DESC
        ");
        $stmt->execute([':uid' => $userId]);
        $sinistres = [];
        foreach ($stmt->fetchAll() as $row) {
            $sinistre = new Sinistre($row['id_contrat'], $row['id_user'], $row['type'], $row['description']);
            $sinistre->setIdSinistre($row['id_sinistre']);
            $sinistre->setPhotoUrl($row['photo_url']);
            $sinistre->setDateDeclaration($row['date_declaration']);
            $sinistre->setStatut($row['statut']);
            // Enrichissement antifraud
            $sinistre->fraudScore     = $row['fraud_score'];
            $sinistre->fraudNiveau    = $row['fraud_niveau'];
            $sinistre->fraudSuggestion = $row['fraud_suggestion'];
            $sinistres[] = $sinistre;
        }
        return $sinistres;
    }

    public function getByContrat(int $idContrat): array {
        if (!$idContrat) return [];
        $stmt = $this->db->prepare("
            SELECT s.id_sinistre, s.type, s.description, s.date_declaration, s.statut, s.photo_url,
                   s.id_contrat, s.id_user,
                   CONCAT(u.prenom,' ',u.nom) AS client_nom
            FROM sinistre s
            LEFT JOIN user u ON s.id_user = u.id_user
            WHERE s.id_contrat = :idContrat
            ORDER BY s.date_declaration DESC
        ");
        $stmt->execute([':idContrat' => $idContrat]);
        $sinistres = [];
        foreach ($stmt->fetchAll() as $row) {
            $sinistre = new Sinistre($row['id_contrat'], $row['id_user'], $row['type'], $row['description']);
            $sinistre->setIdSinistre($row['id_sinistre']);
            $sinistre->setPhotoUrl($row['photo_url'] ?? null);
            $sinistre->setDateDeclaration($row['date_declaration']);
            $sinistre->setStatut($row['statut']);
            $sinistres[] = $sinistre;
        }
        return $sinistres;
    }

    public function getById(int $id): ?Sinistre
    {
        if (!$id) return null;

        // FIX 6 — Isolation agence : l'admin/agent ne peut voir que les sinistres de son agence
        $role   = SessionGuard::role();
        $agence = SessionGuard::agenceId();
        $userId = SessionGuard::userId();

        $whereExtra = '';
        $params     = [':id' => $id];

        if ($role === 'admin' && $agence) {
            $whereExtra = ' AND s.id_agence = :agence';
            $params[':agence'] = $agence;
        } elseif ($role === 'agent') {
            $whereExtra = ' AND s.id_agent_assigne = :userId AND s.id_agence = :agence';
            $params[':userId'] = $userId;
            $params[':agence'] = $agence;
        } elseif ($role === 'client') {
            $whereExtra = ' AND s.id_user = :userId';
            $params[':userId'] = $userId;
        }

        $stmt = $this->db->prepare("
            SELECT s.id_sinistre, s.type, s.description, s.date_declaration, s.statut, s.photo_url,
                   s.id_contrat, s.id_user
            FROM sinistre s
            WHERE s.id_sinistre = :id
            $whereExtra
        ");
        $stmt->execute($params);
        $row = $stmt->fetch();

        if (!$row) return null;

        $sinistre = new Sinistre($row['id_contrat'], $row['id_user'], $row['type'], $row['description']);
        $sinistre->setIdSinistre($row['id_sinistre']);
        $sinistre->setPhotoUrl($row['photo_url']);
        $sinistre->setDateDeclaration($row['date_declaration']);
        $sinistre->setStatut($row['statut']);
        return $sinistre;
    }

    public function create(array $data, int $userId, ?array $file = null): array
    {
        $idContrat   = (int)($data['id_contrat']  ?? 0);
        $type        = trim($data['type']         ?? '');
        $description = trim($data['description']  ?? '');

        if (!$userId)      return ['success' => false, 'message' => 'Utilisateur non identifie.'];
        if (!$idContrat)   return ['success' => false, 'message' => 'id_contrat manquant.'];
        if (!$type)        return ['success' => false, 'message' => 'Type de sinistre manquant.'];
        if (!$description) return ['success' => false, 'message' => 'Description manquante.'];

        // ── Récupérer l'agence du client ──────────────────────────────────────
        $idAgence = null;
        $stmtAg = $this->db->prepare("SELECT id_agence FROM client WHERE id_user = :uid");
        $stmtAg->execute([':uid' => $userId]);
        $idAgence = $stmtAg->fetchColumn();

        // ── Vérifier que le contrat existe, appartient à l'utilisateur et est actif ──
        $stmtC = $this->db->prepare("SELECT statut_contrat FROM contrat WHERE id_contrat = :idc AND id_user = :uid");
        $stmtC->execute([':idc' => $idContrat, ':uid' => $userId]);
        $statutContrat = $stmtC->fetchColumn();
        if (!$statutContrat) {
            return ['success' => false, 'message' => 'Contrat introuvable.'];
        }
        if ($statutContrat !== 'actif') {
            return ['success' => false, 'message' => 'Ce contrat n\'est plus actif (statut : ' . $statutContrat . ').'];
        }

        // ── Insertion sinistre ────────────────────────────────────────────────
        $stmt = $this->db->prepare("
            INSERT INTO sinistre (id_contrat, id_user, id_agence, type, description, date_declaration, statut)
            VALUES (:id_contrat, :id_user, :id_agence, :type, :description, CURDATE(), 'en_attente')
        ");
        $stmt->execute([
            ':id_contrat'  => $idContrat,
            ':id_user'     => $userId,
            ':id_agence'   => $idAgence ?: null,
            ':type'        => $type,
            ':description' => $description,
        ]);

        $idSinistre = (int)$this->db->lastInsertId();

        // ── Sauvegarder l'estimation IA si fournie ────────────────────────────
        $aiEstimate = (float)($data['ai_cost_estimate'] ?? 0);
        $aiMin      = (float)($data['ai_cost_min']      ?? 0);
        $aiMax      = (float)($data['ai_cost_max']      ?? 0);
        $aiRembours = (float)($data['ai_remboursement'] ?? 0);
        $aiAnalysis = htmlspecialchars(trim($data['ai_analysis'] ?? ''), ENT_QUOTES);

        if ($aiEstimate > 0) {
            $stmtAI = $this->db->prepare("
                UPDATE sinistre SET
                    ai_cost_min      = :min,
                    ai_cost_max      = :max,
                    ai_cost_estimate = :est,
                    ai_remboursement = :remb,
                    ai_analysis      = :analysis,
                    ai_generated_at  = NOW()
                WHERE id_sinistre = :id
            ");
            $stmtAI->execute([
                ':min'      => $aiMin,
                ':max'      => $aiMax,
                ':est'      => $aiEstimate,
                ':remb'     => $aiRembours,
                ':analysis' => $aiAnalysis,
                ':id'       => $idSinistre,
            ]);
        }

        // ── Upload documents multiples ────────────────────────────────────────
        $firstPhotoUrl = null;
        if ($file && isset($file['name']) && is_array($file['name'])) {
            $uploadDir = __DIR__ . '/../uploads/sinistres/' . $idSinistre . '/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $stmtDoc = $this->db->prepare("
                INSERT INTO sinistre_fichier (id_sinistre, nom_fichier, chemin, type, taille)
                VALUES (:id_sinistre, :filename, :chemin, :mime_type, :taille_kb)
            ");

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $count = count($file['name']);
            // Limiter à 5 fichiers
            $count = min($count, 5);

            for ($i = 0; $i < $count; $i++) {
                if ($file['error'][$i] === UPLOAD_ERR_OK) {
                    $tmpName = $file['tmp_name'][$i];
                    $origName = $file['name'][$i];
                    $sizeKb = (int)round($file['size'][$i] / 1024);

                    // Limiter à 5 Mo
                    if ($sizeKb > 5120) continue;

                    $trueMime = finfo_file($finfo, $tmpName);
                    $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];

                    if (in_array($trueMime, $allowedMime)) {
                        $ext = pathinfo($origName, PATHINFO_EXTENSION);
                        // Fallback if extension is somehow missing
                        if (!$ext) {
                            $extParts = explode('/', $trueMime);
                            $ext = end($extParts);
                        }
                        $filename = 'doc_' . uniqid() . '.' . $ext;
                        $dest = $uploadDir . $filename;

                        if (move_uploaded_file($tmpName, $dest)) {
                            $relativePath = 'uploads/sinistres/' . $idSinistre . '/' . $filename;
                            $stmtDoc->execute([
                                ':id_sinistre' => $idSinistre,
                                ':filename'    => $filename,
                                ':chemin'      => $relativePath,
                                ':mime_type'   => $trueMime,
                                ':taille_kb'   => $sizeKb
                            ]);

                            // Set legacy photo_url to the first uploaded image
                            if ($firstPhotoUrl === null && str_starts_with($trueMime, 'image/')) {
                                $firstPhotoUrl = 'uploads/sinistres/' . $idSinistre . '/' . $filename;
                            }
                        }
                    }
                }
            }
            finfo_close($finfo);
            
            // Update legacy photo_url if an image was uploaded
            if ($firstPhotoUrl !== null) {
                $upd = $this->db->prepare("UPDATE sinistre SET photo_url = :url WHERE id_sinistre = :id");
                $upd->execute([':url' => $firstPhotoUrl, ':id' => $idSinistre]);
            }
        }

        /* 
        // ── AI Fraud Analysis (DISABLED: now triggered manually in Back-Office) ──
        try {
            $fraudeService = new FraudeService($this->db);
            $fraudeService->analyser(
                $idSinistre,
                $idContrat,
                $userId,
                $type,
                $description,
                $photoUrl,
                null // montant
            );
        } catch (Throwable $e) {
            error_log('[SinistreController] Fraud analysis error: ' . $e->getMessage());
        }
        */

        // ── Email : déclaration reçue ─────────────────────────────────────────
        try {
            $emailService = new EmailService($this->db);
            $emailService->sendSinistreEnCours($idSinistre);
        } catch (Throwable $e) {
            error_log('[SinistreController] Email send error: ' . $e->getMessage());
        }

        return [
            'success' => true,
            'message' => 'Sinistre declare avec succes.',
            'id'      => $idSinistre
        ];
    }

    public function update(int $id, array $data): array
    {
        $idContrat   = (int)($data['id_contrat']  ?? 0);
        $type        = trim($data['type']         ?? '');
        $description = trim($data['description']  ?? '');

        if (!$id || !$idContrat || !$type || !$description) {
            return ['success' => false, 'message' => 'Donnees manquantes (id, id_contrat, type, description).'];
        }

        $stmtU = $this->db->prepare("SELECT id_user FROM sinistre WHERE id_sinistre = ?");
        $stmtU->execute([$id]);
        $uid = (int)$stmtU->fetchColumn();

        $role   = RoleHelper::getRole();
        $agence = RoleHelper::getAgenceId();

        if ($role === 'superadmin') {
            $stmt = $this->db->prepare("UPDATE sinistre SET id_contrat=:id_contrat, type=:type, description=:description WHERE id_sinistre=:id");
            $stmt->execute([':id_contrat' => $idContrat, ':type' => $type, ':description' => $description, ':id' => $id]);
        } else {
            $stmt = $this->db->prepare("UPDATE sinistre SET id_contrat=:id_contrat, type=:type, description=:description WHERE id_sinistre=:id AND id_agence = :agence");
            $stmt->execute([':id_contrat' => $idContrat, ':type' => $type, ':description' => $description, ':id' => $id, ':agence' => $agence]);
        }

        if ($stmt->rowCount() > 0 && $uid) {
            $this->db->prepare("INSERT INTO notification (id_user, message, type, lien) VALUES (?, ?, 'sinistre', ?)")
                ->execute([$uid, "Votre sinistre #$id a été modifié.", '/view/FrontOffice/mes-sinistres.php']);
        }

        return $stmt->rowCount() > 0 
            ? ['success' => true, 'message' => 'Sinistre modifie.'] 
            : ['success' => false, 'message' => 'Modification échouée ou accès refusé.'];
    }

    public function updateStatut(int $id, string $statut): array
    {
        if (!$id || !$statut) {
            return ['success' => false, 'message' => 'Donnees manquantes.'];
        }
        $allowed = ['en_attente', 'en_analyse', 'assigne', 'en_cours', 'rembourse', 'refuse', 'cloture'];
        if (!in_array($statut, $allowed)) {
            return ['success' => false, 'message' => 'Statut invalide.'];
        }

        $role   = RoleHelper::getRole();
        $agence = RoleHelper::getAgenceId();

        if ($role === 'superadmin') {
            $stmt = $this->db->prepare("UPDATE sinistre SET statut=:s WHERE id_sinistre=:id");
            $stmt->execute([':s' => $statut, ':id' => $id]);
        } else {
            $stmt = $this->db->prepare("UPDATE sinistre SET statut=:s WHERE id_sinistre=:id AND id_agence = :agence");
            $stmt->execute([':s' => $statut, ':id' => $id, ':agence' => $agence]);
        }

        if ($stmt->rowCount() > 0) {
            $stmtU = $this->db->prepare("SELECT id_user FROM sinistre WHERE id_sinistre = ?");
            $stmtU->execute([$id]);
            $uid = (int)$stmtU->fetchColumn();
            if ($uid) {
                $label = ['en_attente'=>'en attente','en_analyse'=>'en analyse','assigne'=>'assigné','en_cours'=>'en cours','rembourse'=>'remboursé','refuse'=>'refusé','cloture'=>'clôturé'][$statut]??$statut;
                $this->db->prepare("INSERT INTO notification (id_user, message, type, lien) VALUES (?, ?, 'sinistre', ?)")
                    ->execute([$uid, "Votre sinistre #$id est maintenant : $label.", '/view/FrontOffice/mes-sinistres.php']);
            }
            // ── Email : envoyer notification si remboursé ou refusé ───────────────
            try {
                $emailService = new EmailService($this->db);
                if ($statut === 'rembourse') {
                    $emailService->sendSinistreRembourse($id);
                } elseif ($statut === 'refuse') {
                    $emailService->sendSinistreRefuse($id);
                }
            } catch (Throwable $e) {
                error_log('[SinistreController] Email send error on updateStatut: ' . $e->getMessage());
            }
            return ['success' => true, 'message' => 'Statut mis a jour et email envoye.'];
        }

        return ['success' => false, 'message' => 'Mise à jour échouée ou accès refusé.'];
    }

    public function delete(int $id): array
    {
        if (!$id) return ['success' => false, 'message' => 'ID manquant.'];

        $role   = RoleHelper::getRole();
        $agence = RoleHelper::getAgenceId();

        if ($role === 'superadmin') {
            $stmt = $this->db->prepare("DELETE FROM sinistre WHERE id_sinistre=:id");
            $stmt->execute([':id' => $id]);
        } else {
            $stmt = $this->db->prepare("DELETE FROM sinistre WHERE id_sinistre=:id AND id_agence = :agence");
            $stmt->execute([':id' => $id, ':agence' => $agence]);
        }

        return $stmt->rowCount() > 0 
            ? ['success' => true, 'message' => 'Sinistre supprime.'] 
            : ['success' => false, 'message' => 'Suppression échouée ou accès refusé.'];
    }

    public function getStats(): array
    {
        $role   = RoleHelper::getRole();
        $agence = RoleHelper::getAgenceId();

        if ($role === 'superadmin') {
            $stmt = $this->db->query("
                SELECT COUNT(*) AS total,
                       SUM(statut='en_attente') AS en_attente,
                       SUM(statut='en_analyse') AS en_analyse,
                       SUM(statut='assigne')    AS assigne,
                       SUM(statut='en_cours')   AS en_cours,
                       SUM(statut='rembourse')  AS rembourse,
                       SUM(statut='refuse')     AS refuse
                FROM sinistre
            ");
        } else {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) AS total,
                       SUM(statut='en_attente') AS en_attente,
                       SUM(statut='en_analyse') AS en_analyse,
                       SUM(statut='assigne')    AS assigne,
                       SUM(statut='en_cours')   AS en_cours,
                       SUM(statut='rembourse')  AS rembourse,
                       SUM(statut='refuse')     AS refuse
                FROM sinistre
                WHERE id_agence = :agence
            ");
            $stmt->execute([':agence' => $agence]);
        }
        return $stmt->fetch();
    }

    public function getRecentSinistres(): array
    {
        $stmt = $this->db->query("
            SELECT id_sinistre, type, date_declaration, statut 
            FROM sinistre 
            ORDER BY id_sinistre DESC 
            LIMIT 5
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUnreadCount(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM sinistre WHERE is_read = 0");
        return (int)$stmt->fetchColumn();
    }

    public function markAllAsRead(): bool
    {
        $stmt = $this->db->prepare("UPDATE sinistre SET is_read = 1 WHERE is_read = 0");
        return $stmt->execute();
    }

    public function getDocuments(int $idSinistre): array
    {
        $stmt = $this->db->prepare("SELECT id, nom_fichier AS filename, type AS mime_type, taille AS taille_kb, uploaded_at FROM sinistre_fichier WHERE id_sinistre = :id ORDER BY uploaded_at ASC");
        $stmt->execute([':id' => $idSinistre]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
