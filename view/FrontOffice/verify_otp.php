<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();
require_once __DIR__ . '/../../helpers/SessionGuard.php';
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['otp_user_id'])) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Session expirée']);
        exit;
    }

    require_once __DIR__ . '/../../controller/Client_Con.php';

    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    $code = trim($data['code'] ?? '');

    if (empty($code)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Code requis']);
        exit;
    }

    $controller = new UserController();
    $result = $controller->verifyOTP($code);
    ob_clean();
    echo json_encode($result);
} catch (Throwable $e) {
    ob_clean();
    error_log('verify_otp.php: ' . $e->getMessage());
    error_log('verify_otp error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}

