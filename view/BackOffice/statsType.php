<?php
require_once __DIR__ . '/../../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

RoleHelper::requireRole(['superadmin', 'admin', 'agent']);

try {
    $ctrl = new ReponseController();
    $stats = $ctrl->getStatsByType();
    echo json_encode($stats);
} catch (Exception $e) {
    http_response_code(500);
    error_log('statsType error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
