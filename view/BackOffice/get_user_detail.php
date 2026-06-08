<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';
require_once '../../controller/Client_Con.php';

$ctrl = new UserController();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['success'=>false]); exit; }

$user = $ctrl->getUserById($id);
if (!$user) { echo json_encode(['success'=>false]); exit; }

// Agency isolation: admin_agence can only see users in their agency
if ($_SESSION['role'] === 'admin') {
    $sessionAgence = $_SESSION['id_agence'] ?? null;
    $userAgence = $user['client_id_agence'] ?? $user['id_agence'] ?? $user['admin_id_agence'] ?? null;
    if (!$sessionAgence || ($userAgence && (int)$userAgence !== (int)$sessionAgence)) {
        echo json_encode(['success'=>false,'message'=>'Accès refusé: utilisateur d\'une autre agence']); exit;
    }
}

// Agent can only see client details from same agency
if ($_SESSION['role'] === 'agent') {
    $sessionAgence = $_SESSION['id_agence'] ?? null;
    $userAgence = $user['client_id_agence'] ?? $user['id_agence'] ?? $user['admin_id_agence'] ?? null;
    if ($user['role'] !== 'client' || !$sessionAgence || ($userAgence && (int)$userAgence !== (int)$sessionAgence)) {
        echo json_encode(['success'=>false,'message'=>'Accès refusé: client hors de votre agence']); exit;
    }
}

$db = config::getConnexion();

$friends = $db->prepare("SELECT COUNT(*) FROM friendships WHERE (sender_id=? OR receiver_id=?) AND status='accepted'");
$friends->execute([$id,$id]);

$sos = $db->prepare("SELECT COUNT(*) FROM sos_alerts WHERE user_id=?");
$sos->execute([$id]);

$logins = $db->prepare("SELECT ip, statut, created_at FROM login_attempts WHERE email=? ORDER BY created_at DESC LIMIT 5");
$logins->execute([$user['email']]);

// U3 - KPIs supplémentaires pour les clients
$nb_contrats_actifs = 0;
$nb_sinistres_declares = 0;
$montant_total_paye = 0.0;
$score_fraude_moyen = 0.0;
$last_login = null;
$statut_compte = $user['statut'] ?? 'actif';

if ($user['role'] === 'client') {
    // Nombre de contrats actifs
    $contrats_stmt = $db->prepare("SELECT COUNT(*) FROM contrat WHERE id_user = ? AND statut_contrat = 'actif'");
    $contrats_stmt->execute([$id]);
    $nb_contrats_actifs = (int)$contrats_stmt->fetchColumn();

    // Nombre de sinistres déclarés
    $sinistres_stmt = $db->prepare("SELECT COUNT(*) FROM sinistre WHERE id_user = ?");
    $sinistres_stmt->execute([$id]);
    $nb_sinistres_declares = (int)$sinistres_stmt->fetchColumn();

    // Montant total payé
    $paiement_stmt = $db->prepare("
        SELECT COALESCE(SUM(p.montant), 0)
        FROM paiement p
        INNER JOIN devis d ON p.id_devis = d.id_devis
        WHERE d.email = ? AND p.statut = 'valide'
    ");
    $paiement_stmt->execute([$user['email']]);
    $montant_total_paye = (float)$paiement_stmt->fetchColumn();

    // Score de fraude moyen
    $fraud_stmt = $db->prepare("SELECT COALESCE(AVG(score_global), 0) FROM fraud_analysis WHERE id_user = ?");
    $fraud_stmt->execute([$id]);
    $score_fraude_moyen = round((float)$fraud_stmt->fetchColumn(), 1);

    // Dernière connexion & statut du compte
    $user_extra_stmt = $db->prepare("SELECT last_login, statut FROM user WHERE id_user = ?");
    $user_extra_stmt->execute([$id]);
    $extra = $user_extra_stmt->fetch(PDO::FETCH_ASSOC);
    if ($extra) {
        $last_login = $extra['last_login'];
        $statut_compte = $extra['statut'];
    }
}

echo json_encode([
    'success'    => true,
    'user'       => $user,
    'nb_friends' => (int)$friends->fetchColumn(),
    'nb_sos'     => (int)$sos->fetchColumn(),
    'logins'     => $logins->fetchAll(PDO::FETCH_ASSOC),
    // U3 KPIs
    'kpis' => [
        'nb_contrats_actifs'   => $nb_contrats_actifs,
        'nb_sinistres_declares' => $nb_sinistres_declares,
        'montant_total_paye'   => $montant_total_paye,
        'score_fidelite'       => (int)($user['points_parrainage'] ?? 0),
        'score_fraude_moyen'   => $score_fraude_moyen,
        'last_login'           => $last_login,
        'statut_compte'        => $statut_compte
    ]
]);

