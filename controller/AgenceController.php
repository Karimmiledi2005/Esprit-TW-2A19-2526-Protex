<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/PosteModel.php';

class AgenceController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = config::getConnexion();
    }

    public function getDb(): PDO { return $this->db; }

    public function getKPIs(int $idAgence): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                a.id_agence,
                a.nom_agence,
                a.adresse,
                a.tel AS telephone,
                a.email,
                COUNT(DISTINCT u.id_user) as nb_agents,
                COUNT(DISTINCT c.id_user) as nb_clients,
                COUNT(DISTINCT ct.id_contrat) as nb_contrats_actifs,
                COALESCE(SUM(CASE WHEN p.statut = 'valide' THEN p.montant ELSE 0 END), 0) as ca_total,
                COALESCE(SUM(CASE WHEN p.statut = 'valide' AND MONTH(p.date_paiement) = MONTH(NOW()) AND YEAR(p.date_paiement) = YEAR(NOW()) THEN p.montant ELSE 0 END), 0) as ca_ce_mois,
                COUNT(DISTINCT s.id_sinistre) as nb_sinistres,
                COALESCE(SUM(CASE WHEN s.statut IN ('en_attente','en_analyse','assigne','en_cours') THEN 1 ELSE 0 END), 0) as nb_sinistres_en_cours,
                COALESCE(AVG(aa.note), 0) as satisfaction_moyenne,
                COALESCE(SUM(CASE WHEN fa.score_global > 70 THEN 1 ELSE 0 END), 0) as taux_fraude
            FROM agence a
            LEFT JOIN `user` u ON a.id_agence = u.id_agence AND u.role = 'agent'
            LEFT JOIN client c ON a.id_agence = c.id_agence
            LEFT JOIN contrat ct ON c.id_user = ct.id_user
            LEFT JOIN paiement p ON ct.id_contrat = p.id_offre
            LEFT JOIN sinistre s ON ct.id_contrat = s.id_contrat
            LEFT JOIN agence_avis aa ON a.id_agence = aa.id_agence
            LEFT JOIN fraud_analysis fa ON s.id_sinistre = fa.id_sinistre
            WHERE a.id_agence = ?
            GROUP BY a.id_agence
        ");
        $stmt->execute([$idAgence]);
        $kpi = $stmt->fetch(PDO::FETCH_ASSOC);
        return $kpi ?: [];
    }

    public function getMonthlyCA(int $idAgence): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                MONTH(p.date_paiement) as mois,
                YEAR(p.date_paiement) as annee,
                COALESCE(SUM(p.montant), 0) as total
            FROM paiement p
            JOIN contrat ct ON p.id_offre = ct.id_contrat
            JOIN client c ON ct.id_user = c.id_user
            WHERE c.id_agence = ? 
              AND p.date_paiement >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
              AND p.statut = 'valide'
            GROUP BY YEAR(p.date_paiement), MONTH(p.date_paiement)
            ORDER BY annee ASC, mois ASC
        ");
        $stmt->execute([$idAgence]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopClients(int $idAgence, int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                u.id_user,
                u.nom,
                u.prenom,
                u.email,
                COUNT(ct.id_contrat) as nb_contrats,
                COALESCE(SUM(p.montant), 0) as ca_total
            FROM client c
            JOIN `user` u ON c.id_user = u.id_user
            JOIN contrat ct ON c.id_user = ct.id_user
            LEFT JOIN paiement p ON ct.id_contrat = p.id_offre AND p.statut = 'valide'
            WHERE c.id_agence = ?
            GROUP BY u.id_user
            ORDER BY ca_total DESC
            LIMIT ?
        ");
        $stmt->execute([$idAgence, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAgentStats(int $idAgence): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                u.id_user, u.nom, u.prenom, u.avatar,
                COUNT(DISTINCT t.id_sinistre) as sinistres_traites,
                COALESCE(AVG(DATEDIFF(COALESCE(t.date_resolution, NOW()), t.date_creation)), 0) as delai_moyen,
                CASE WHEN COUNT(*) > 0 
                    THEN ROUND(COUNT(CASE WHEN t.statut IN ('clôturé','résolu') THEN 1 END) / COUNT(*) * 100, 1)
                    ELSE 0 
                END as taux_resolution
            FROM `user` u
            LEFT JOIN traitement t ON u.id_user = t.id_agent
            WHERE u.id_agence = ? AND u.role = 'agent'
            GROUP BY u.id_user
        ");
        $stmt->execute([$idAgence]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listAllAgencies(): array
    {
        $stmt = $this->db->query("
            SELECT 
                a.id_agence, a.nom_agence, a.adresse,
                COUNT(DISTINCT c.id_user) as nb_clients,
                COUNT(DISTINCT ct.id_contrat) as nb_contrats,
                COALESCE(SUM(CASE WHEN p.statut = 'valide' THEN p.montant ELSE 0 END), 0) as ca_total,
                COALESCE(AVG(aa.note), 0) as satisfaction,
                COUNT(DISTINCT s.id_sinistre) as nb_sinistres
            FROM agence a
            LEFT JOIN client c ON a.id_agence = c.id_agence
            LEFT JOIN contrat ct ON c.id_user = ct.id_user
            LEFT JOIN paiement p ON ct.id_contrat = p.id_offre
            LEFT JOIN agence_avis aa ON a.id_agence = aa.id_agence
            LEFT JOIN sinistre s ON ct.id_contrat = s.id_contrat
            GROUP BY a.id_agence
            ORDER BY ca_total DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Legacy routing for direct HTTP calls
$action = $_GET['action'] ?? '';
if ($action) {
    $pdo = config::getConnexion();
    $model = new PosteModel($pdo);

    try {
        switch ($action) {
            case 'create':
                $model->createAgence($_POST);
                header('Location: ../view/BackOffice/admin-agences.php?success=create');
                exit;
            case 'update':
                $model->updateAgence($_POST);
                header('Location: ../view/BackOffice/admin-agences.php?success=update');
                exit;
            case 'delete':
                $model->deleteAgence((int)($_POST['id_agence'] ?? 0));
                header('Location: ../view/BackOffice/admin-agences.php?success=delete');
                exit;
            default:
                http_response_code(400);
                echo 'Action invalide';
                exit;
        }
    } catch (Throwable $e) {
        error_log('AgenceController error: ' . $e->getMessage());
        http_response_code(500);
        echo 'Erreur interne serveur';
        exit;
    }
}
