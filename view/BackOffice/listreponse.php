<?php
require_once __DIR__ . '/../../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

RoleHelper::requireRole(['superadmin', 'admin', 'agent']);

try {
    $ctrl  = new ReponseController();
    $page  = max(1, (int)($_GET['page'] ?? 1));
    $rows  = $ctrl->listAllReclamations($page, 20);
    $total = $ctrl->countAllReclamations();
    echo json_encode(['success' => true, 'rows' => $rows, 'total' => $total, 'page' => $page]);
} catch (Exception $e) {
    http_response_code(500);
    error_log('listreponse error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
