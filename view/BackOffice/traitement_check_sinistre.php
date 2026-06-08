<?php
require_once __DIR__ . '/../../bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'ID sinistre manquant.']);
    exit;
}

$controller = new TraitementController();
$result = $controller->checkSinistre($id);
if (!$result) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Sinistre introuvable.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'data' => $result]);
?>
