<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
require_once __DIR__ . '/../../controller/SinistreController.php';

SessionGuard::requireBackoffice();

// Heartbeat: Mise à jour de l'état "en ligne" de l'utilisateur
if (SessionGuard::isLoggedIn()) {
    $db = config::getConnexion();
    $db->prepare("UPDATE user SET last_seen = NOW() WHERE id_user = ?")->execute([SessionGuard::userId()]);
}

$controller = new SinistreController();
$recent = $controller->getRecentSinistres();
$unread = $controller->getUnreadCount();

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    'notifications' => $recent,
    'unread_count' => $unread
]);
?>
