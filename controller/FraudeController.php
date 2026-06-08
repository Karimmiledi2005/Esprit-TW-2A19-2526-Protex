<?php
/**
 * FraudeController — Point d'entrée HTTP pour l'antifraud
 * 
 * Endpoints JSON :
 *   GET  fraud_get.php?id_sinistre=X        → récupérer analyse existante
 *   POST fraud_analyse.php                  → lancer/relancer une analyse
 *   GET  fraud_stats.php                    → statistiques globales
 */

// error_reporting(0); // Désactivé pour le debug

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controller/FraudeService.php';
// Ensure both controller-level and service-level mailers are available
if (file_exists(__DIR__ . '/../controller/EmailService.php')) {
    require_once __DIR__ . '/../controller/EmailService.php';
}
if (file_exists(__DIR__ . '/../service/EmailService.php')) {
    require_once __DIR__ . '/../service/EmailService.php';
}

class FraudeController
{
    private PDO $db;
    private FraudeService $service;

    public function __construct()
    {
        $this->db      = config::getConnexion();
        $this->service = new FraudeService($this->db);
    }

    // ─── GET fraud_get.php?id_sinistre=X ─────────────────────────────────────
    public function getAnalyse(): void
    {
        $id = (int)($_GET['id_sinistre'] ?? 0);
        if (!$id) {
            $this->json(['success' => false, 'message' => 'id_sinistre requis.'], 400);
            return;
        }

        $analyse = $this->service->getAnalyse($id);
        if (!$analyse) {
            // Retourner 200 OK avec success:false au lieu de 404 pour éviter l'erreur réseau en console
            $this->json(['success' => false, 'message' => 'Aucune analyse disponible pour ce sinistre.']);
            return;
        }

        $this->json(['success' => true, 'data' => $this->formatAnalyse($analyse)]);
    }

    // ─── POST fraud_analyse.php (id_sinistre dans POST body) ─────────────────
    public function lancerAnalyse(): void
    {
        $idSinistre = (int)($_REQUEST['id_sinistre'] ?? 0);
        if (!$idSinistre) {
            $this->json(['success' => false, 'message' => 'id_sinistre requis.'], 400);
            return;
        }

        try {
            // Récupérer les données du sinistre depuis la DB (avec montant si déjà traité)
            $stmt = $this->db->prepare("
                SELECT s.id_sinistre, s.id_contrat, s.id_user, s.type, s.description, s.photo_url,
                       t.montant_indemnise
                FROM sinistre s
                LEFT JOIN traitement t ON s.id_sinistre = t.id_sinistre
                WHERE s.id_sinistre = :id
            ");
            $stmt->execute([':id' => $idSinistre]);
            $sinistre = $stmt->fetch();

            if (!$sinistre) {
                $this->json(['success' => false, 'message' => 'Sinistre introuvable.'], 404);
                return;
            }

            // Résoudre le chemin photo si disponible
            $photoPath = null;
            if ($sinistre['photo_url']) {
                $absPath = __DIR__ . '/../' . $sinistre['photo_url'];
                if (file_exists($absPath)) {
                    $photoPath = $absPath;
                }
            }

            // Lancer l'analyse
            $result = $this->service->analyser(
                $sinistre['id_sinistre'],
                $sinistre['id_contrat'],
                $sinistre['id_user'],
                $sinistre['type'],
                $sinistre['description'] ?? '',
                $photoPath,
                (float)($sinistre['montant_indemnise'] ?? 0)
            );

            // --- AUTOMATIC REFUSAL IF HIGH RISK ---
            if ($result['suggestion_ia'] === 'refuser') {
                $reason = $result['recommandation_ia'] ?? 'Refus automatique par l\'IA';
                
                // Update Sinistre
                $stmtRefuse = $this->db->prepare("UPDATE sinistre SET statut = 'refuse' WHERE id_sinistre = :id");
                $stmtRefuse->execute([':id' => $idSinistre]);
                
                // Update Traitement (if exists)
                // Note: 'decision' est un enum, on utilise 'message_agent' pour le motif détaillé
                $stmtT = $this->db->prepare("UPDATE traitement SET statut = 'refuse', decision = 'refuse', message_agent = :reason WHERE id_sinistre = :id");
                $stmtT->execute([':reason' => $reason, ':id' => $idSinistre]);

                // ── Email : fraude critique détectée ─────────────────────────────
                try {
                    $emailService = new EmailService($this->db);
                    $emailService->sendFraudeDetectee(
                        $idSinistre,
                        (float)($result['score_global']  ?? 0),
                        $result['niveau_risque'] ?? 'critique',
                        $result['recommandation_ia'] ?? ''
                    );
                } catch (Throwable $e) {
                    error_log('[FraudeController] Email fraud error: ' . $e->getMessage());
                }

                $result['auto_refused'] = true;
            }

            $this->json(['success' => true, 'data' => $result]);
        } catch (Throwable $t) {
            error_log('[FraudeController] Analysis error: ' . $t->getMessage());
            $this->json(['success' => false, 'message' => 'Erreur lors de l\'analyse : ' . $t->getMessage()], 500);
        }
    }

    // ─── GET fraud_stats.php ──────────────────────────────────────────────────
    public function getStats(): void
    {
        $stmt = $this->db->query("
            SELECT
                COUNT(*)                              AS total_analyses,
                SUM(niveau_risque = 'faible')         AS faible,
                SUM(niveau_risque = 'moyen')          AS moyen,
                SUM(niveau_risque = 'eleve')          AS eleve,
                SUM(niveau_risque = 'critique')       AS critique,
                SUM(suggestion_ia = 'accepter')       AS suggestion_accepter,
                SUM(suggestion_ia = 'investiguer')    AS suggestion_investiguer,
                SUM(suggestion_ia = 'refuser')        AS suggestion_refuser,
                ROUND(AVG(score_global), 1)           AS score_moyen,
                SUM(flag_description_vague)           AS nb_descriptions_vagues,
                SUM(flag_sinistres_multiples)         AS nb_multiples,
                SUM(flag_contrat_recent)              AS nb_contrats_recents
            FROM fraud_analysis
        ");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->json(['success' => true, 'data' => $stats]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────
    private function formatAnalyse(array $row): array
    {
        return [
            'id_sinistre'    => (int)$row['id_sinistre'],
            'score_global'   => (int)$row['score_global'],
            'niveau_risque'  => $row['niveau_risque'],
            'suggestion_ia'  => $row['suggestion_ia'],
            'recommandation' => $row['recommandation_ia'],
            'scores_detail'  => [
                'texte'        => (int)$row['score_texte'],
                'comportement' => (int)$row['score_comportement'],
                'contrat'      => (int)$row['score_contrat'],
            ],
            'flags'          => [
                'description_vague'   => (bool)$row['flag_description_vague'],
                'sinistres_multiples' => (bool)$row['flag_sinistres_multiples'],
                'contrat_recent'      => (bool)$row['flag_contrat_recent'],
                'montant_eleve'       => (bool)$row['flag_montant_eleve'],
                'image_suspecte'      => (bool)$row['flag_image_suspecte'],
            ],
            'analyse_texte'        => $row['analyse_texte'],
            'analyse_comportement' => $row['analyse_comportement'],
            'date_analyse'         => $row['date_analyse'],
        ];
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

