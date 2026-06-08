<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../helpers/SessionGuard.php';

if (!SessionGuard::isLoggedIn()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Non authentifié.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Methode non autorisee.']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$statut = trim($_POST['statut'] ?? '');
if (!$id || !$statut) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'ID et statut requis.']);
    exit;
}

$controller = new SinistreController();
$result = $controller->updateStatut($id, $statut);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($result);
?>

