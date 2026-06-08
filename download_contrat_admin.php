<?php
require_once __DIR__ . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
require_once __DIR__ . '/controller/ContratPdfController.php';

$idContrat = (int)($_GET['id'] ?? 0);
if ($idContrat <= 0) {
    die('ID de contrat invalide.');
}

$type = $_GET['type'] ?? 'contrat';
$ctrl = new ContratPdfController();
match ($type) {
    'attestation' => $ctrl->generateAttestation($idContrat),
    default => $ctrl->generate($idContrat),
};
