<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_user']) && !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté.']);
    exit;
}

require_once __DIR__ . '/../../bootstrap.php';

$userId = (int)($_SESSION['id_user'] ?? $_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Methode non autorisee.']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'ID manquant.']);
    exit;
}

$controller = new SinistreController();
$result = $controller->update($id, $_POST);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result);
?>

