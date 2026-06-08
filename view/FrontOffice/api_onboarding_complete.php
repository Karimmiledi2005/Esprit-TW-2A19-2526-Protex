<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$idUser = (int)($_SESSION['user_id'] ?? 0);
if (!$idUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

require_once dirname(__DIR__, 2) . '/config.php';
$db = config::getConnexion();

try {
    // Check if column exists first
    $colCheck = $db->query("SHOW COLUMNS FROM `user` LIKE 'onboarding_done'");
    if (!$colCheck->fetch()) {
        $db->exec("ALTER TABLE `user` ADD COLUMN onboarding_done TINYINT(1) NOT NULL DEFAULT 0");
    }
    $stmt = $db->prepare("UPDATE `user` SET onboarding_done = 1 WHERE id_user = ?");
    $stmt->execute([$idUser]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Fail silently — localStorage is the primary gate
    echo json_encode(['success' => true, 'note' => 'local-only']);
}
