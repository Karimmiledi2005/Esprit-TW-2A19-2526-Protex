<?php
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
header('Content-Type: application/json');

$sessionUserId = (int)($_SESSION['user_id'] ?? $_SESSION['id_user'] ?? 0);
if ($sessionUserId <= 0) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Non connecté"]);
    exit;
}

require_once __DIR__ . '/../../controller/Client_Con.php';

try {
    $controller = new UserController();
    $user = $controller->getAdminProfile($sessionUserId);

    if (!$user) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Utilisateur introuvable"]);
        exit;
    }

    $user['csrf_token'] = UserController::getCsrfToken();
    echo json_encode($user);

} catch (Exception $e) {
    http_response_code(500);
    error_log('get_admin error: ' . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Erreur serveur"]);
}
