<?php
require_once __DIR__ . '/../../helpers/SessionGuard.php';
SessionGuard::requireBackoffice();

require_once __DIR__ . '/../../controller/ContratController.php';

$id = (int)($_GET['id'] ?? 0);
$statut = trim($_GET['statut'] ?? '');

$controller = new ContratController();
if ($id > 0 && $statut !== '') {
    $controller->updateStatut($id, $statut);
}

header('Location: contrats_back.php?success=statut');
exit();
