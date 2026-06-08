<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../connexion.php';
require_once __DIR__ . '/../../helpers/RoleHelper.php';
require_once __DIR__ . '/../../controller/TraitementController.php';
header('Content-Type: application/json');

RoleHelper::requireRole(['superadmin', 'admin', 'admin_agence', 'agent']);

$id = (int)($_POST['id_traitement'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID manquant.']);
    exit;
}

$db = config::getConnexion();
$stmt = $db->prepare("SELECT id_user, est_valide FROM traitement WHERE id_traitement = :id");
$stmt->execute([':id' => $id]);
$trait = $stmt->fetch();

if (!$trait) {
    echo json_encode(['success' => false, 'message' => 'Traitement introuvable.']);
    exit;
}

// Vérifier les permissions
if (!RoleHelper::canModifyTraitement((int)$trait['id_user'])) {
    echo json_encode(['success' => false, 'message' => 'Action non autorisée.']);
    exit;
}

// Un agent ne peut pas modifier si c'est déjà validé
if (RoleHelper::isAgent() && $trait['est_valide']) {
    echo json_encode(['success' => false, 'message' => 'Ce traitement a été validé et ne peut plus être modifié par un agent.']);
    exit;
}

$controller = new TraitementController();
$result = $controller->update($id, $_POST);

echo json_encode($result);
