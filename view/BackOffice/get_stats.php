<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
require_once dirname(__DIR__, 2) . '/config.php';

SessionGuard::requireBackoffice();

header('Content-Type: application/json; charset=utf-8');

$db       = config::getConnexion();
$role     = SessionGuard::role();
$idAgence = SessionGuard::agenceId();
$userId   = SessionGuard::userId();

$stats = [];

// Sinistres
if ($role === 'agent') {
    $stmt = $db->prepare("SELECT COUNT(*) FROM sinistre WHERE id_agent_assigne = ?");
    $stmt->execute([$userId]);
} elseif ($role === 'admin' && $idAgence) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM sinistre WHERE id_agence = ?");
    $stmt->execute([$idAgence]);
} else {
    $stmt = $db->query("SELECT COUNT(*) FROM sinistre");
}
$stats['sinistres'] = (int)$stmt->fetchColumn();

// Reclamations en attente
if ($role !== 'superadmin' && $idAgence) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM reclamation r JOIN client c ON r.id_user = c.id_user WHERE r.statut = 'en_attente' AND c.id_agence = ?");
    $stmt->execute([$idAgence]);
} else {
    $stmt = $db->query("SELECT COUNT(*) FROM reclamation WHERE statut = 'en_attente'");
}
$stats['reclamations'] = (int)$stmt->fetchColumn();

// Contrats actifs & Revenus (admin+ only)
if (in_array($role, ['admin', 'superadmin'])) {
    if ($role === 'admin' && $idAgence) {
        // Contrats via client.id_agence
        $stmt = $db->prepare("SELECT COUNT(*) FROM contrat c JOIN client cl ON c.id_user = cl.id_user WHERE c.statut_contrat = 'actif' AND cl.id_agence = ?");
        $stmt->execute([$idAgence]);
        $stats['contrats'] = (int)$stmt->fetchColumn();

        // Revenus via paiement.id_agence
        $stmt = $db->prepare("SELECT COALESCE(SUM(montant), 0) FROM paiement WHERE statut = 'valide' AND id_agence = ?");
        $stmt->execute([$idAgence]);
        $stats['revenus'] = (float)$stmt->fetchColumn();
    } else {
        $stmt = $db->query("SELECT COUNT(*) FROM contrat c WHERE c.statut_contrat = 'actif'");
        $stats['contrats'] = (int)$stmt->fetchColumn();

        $stmt = $db->query("SELECT COALESCE(SUM(montant), 0) FROM paiement WHERE statut = 'valide'");
        $stats['revenus'] = (float)$stmt->fetchColumn();
    }
}

// Users count
if ($role === 'superadmin') {
    $stats['users'] = (int)$db->query("SELECT COUNT(*) FROM user WHERE statut != 'bloque'")->fetchColumn();
} elseif ($role === 'admin' && $idAgence) {
    // Count only users in the admin's agency
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM user u 
        LEFT JOIN client cl ON u.id_user = cl.id_user
        LEFT JOIN agent ag ON u.id_user = ag.id_user
        LEFT JOIN admin ad ON u.id_user = ad.id_user
        WHERE u.statut != 'bloque' 
          AND u.role != 'superadmin'
          AND (cl.id_agence = ? OR ag.id_agence = ? OR ad.id_agence = ?)
    ");
    $stmt->execute([$idAgence, $idAgence, $idAgence]);
    $stats['users'] = (int)$stmt->fetchColumn();
}

echo json_encode($stats);
