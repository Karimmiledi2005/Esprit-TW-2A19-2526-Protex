<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
require_once dirname(__DIR__, 2) . '/config.php';

SessionGuard::requireBackoffice();

header('Content-Type: application/json; charset=utf-8');

if (!in_array(SessionGuard::role(), ['admin', 'superadmin', 'agent'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

$db = config::getConnexion();

// POST : action resolve
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    if (($body['action'] ?? '') === 'resolve') {
        $db->prepare("UPDATE sos_alerts SET statut = 'resolu' WHERE id = ?")->execute([(int)$body['id']]);
        echo json_encode(['success' => true]); exit;
    }
}

// GET : liste des alertes (filtrées par agence)
$agenceId = (int)($_SESSION['id_agence'] ?? 0);
if ($agenceId <= 0) {
    $uid = (int)($_SESSION['user_id'] ?? $_SESSION['id_user'] ?? 0);
    if ($uid > 0) {
        $stmtAg = $db->prepare("SELECT id_agence FROM user WHERE id_user = ?");
        $stmtAg->execute([$uid]);
        $agenceId = (int)$stmtAg->fetchColumn();
    }
}
if ($agenceId > 0) {
    $stmt = $db->prepare("SELECT sa.*, u.nom, u.prenom, u.avatar_url 
                        FROM sos_alerts sa 
                        JOIN user u ON sa.user_id = u.id_user 
                        WHERE u.id_agence = ?
                        ORDER BY sa.created_at DESC 
                        LIMIT 20");
    $stmt->execute([$agenceId]);
} else {
    $stmt = $db->query("SELECT sa.*, u.nom, u.prenom, u.avatar_url 
                        FROM sos_alerts sa 
                        JOIN user u ON sa.user_id = u.id_user 
                        ORDER BY sa.created_at DESC 
                        LIMIT 20");
}
echo json_encode(['success'=>true, 'alerts'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
