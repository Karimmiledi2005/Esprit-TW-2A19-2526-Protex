<?php
require_once __DIR__ . '/../../bootstrap.php';

require_once __DIR__ . '/../../helpers/RoleHelper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Methode non autorisee.']);
    exit;
}

// Sécurité : Vérifier le droit de créer un traitement
if (!RoleHelper::canCreateTraitement()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
    exit;
}

$controller = new TraitementController();
$userId = RoleHelper::getUserId();
$result = $controller->create($_POST, $userId);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result);
?>
