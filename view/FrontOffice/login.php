<?php
// Bloquer toute sortie HTML avant le JSON
ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();

require_once __DIR__ . '/../../helpers/SessionGuard.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Content-Type: text/html; charset=UTF-8');
        ob_end_clean();
        readfile(__DIR__ . '/login.html');
        exit;
    }

    require_once __DIR__ . '/../../controller/Client_Con.php';


    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    $controller = new UserController();
    $result     = $controller->login($email, $password);
    ob_clean(); // jeter toute sortie parasite (warnings/notices)
    echo json_encode($result);
} catch (Throwable $e) {
    ob_clean();
    http_response_code(500);
    error_log('login.php: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    error_log('login.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}

