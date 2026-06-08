<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../connexion.php';
require_once __DIR__ . '/../../helpers/RoleHelper.php';
require_once __DIR__ . '/../../controller/TraitementController.php';
header('Content-Type: application/json');

RoleHelper::requireRole(['superadmin', 'admin', 'admin_agence']);

$id = (int)($_POST['id_traitement'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID manquant.']);
    exit;
}

$controller = new TraitementController();
$result = $controller->valider($id);

echo json_encode($result);
