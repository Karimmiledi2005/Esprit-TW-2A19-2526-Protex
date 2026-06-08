<?php
require_once __DIR__ . '/helpers/SessionGuard.php';
SessionGuard::requireClient();
require_once __DIR__ . '/controller/ContratPdfController.php';

$idContrat = (int)($_GET['id'] ?? 0);
if ($idContrat <= 0) {
    die('ID de contrat invalide.');
}

$db = config::getConnexion();
$stmt = $db->prepare("SELECT id_user FROM contrat WHERE id_contrat = :id");
$stmt->execute([':id' => $idContrat]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || (int)$row['id_user'] !== SessionGuard::userId()) {
    die('Accès refusé.');
}

$type = $_GET['type'] ?? 'contrat';
$ctrl = new ContratPdfController();
match ($type) {
    'attestation' => $ctrl->generateAttestation($idContrat),
    default => $ctrl->generate($idContrat),
};
