<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_user']) && !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté.']);
    exit;
}

require_once __DIR__ . '/../../bootstrap.php';

$userId = (int)($_SESSION['id_user'] ?? $_SESSION['user_id']);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'ID manquant.']);
    exit;
}

// Vérifier que le sinistre appartient à l'utilisateur connecté
$sinistreCtrl = new SinistreController();
$sinistre = $sinistreCtrl->getById($id);
$ownerId = isset($sinistre['id_client']) ? (int)$sinistre['id_client'] : (isset($sinistre['id_user']) ? (int)$sinistre['id_user'] : 0);
if ($ownerId > 0 && $ownerId !== $userId) {
    echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
    exit;
}

$controller = new SinistreController();
$result = $controller->delete($id);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result);
?>

