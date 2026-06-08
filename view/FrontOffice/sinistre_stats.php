<?php
require_once __DIR__ . '/../../controller/SinistreController.php';

$controller = new SinistreController();
$stats = $controller->getStats();

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'data' => $stats]);
?>

