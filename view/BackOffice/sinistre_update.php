<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers/RoleHelper.php';
require_once __DIR__ . '/../../controller/SinistreController.php';
header('Content-Type: application/json');

// Autorisé pour superadmin et admin_agence
RoleHelper::requireRole(['superadmin', 'admin', 'admin_agence']);

$id = (int)($_POST['id_sinistre'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID manquant.']);
    exit;
}

$controller = new SinistreController();
$result = $controller->update($id, $_POST);

echo json_encode($result);

