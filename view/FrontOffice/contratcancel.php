<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../controller/ContratController.php';

$id = (int)($_GET['id'] ?? 0);
$idClient = (int)($_SESSION['id_user'] ?? 1);

$controller = new ContratController();
$contrat = $controller->getById($id);

if (!$contrat || (int)$contrat['id_client'] !== $idClient) {
    header('Location: contrat.php?error=introuvable');
    exit();
}

$controller->updateStatut($id, 'résilié');
header('Location: contrat.php?success=resilie');
exit();

