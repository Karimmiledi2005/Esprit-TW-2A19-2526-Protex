<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_user']) && !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté.']);
    exit;
}

require_once __DIR__ . '/../../bootstrap.php';

$userId = (int)($_SESSION['id_user'] ?? $_SESSION['user_id']);
$cc = new ContratController();
$contrats = $cc->getByClient($userId);

$data = [];
foreach ($contrats as $c) {
    $data[] = [
        'id_contrat'         => $c->getIdContrat(),
        'numero_contrat'     => $c->getNumeroContrat(),
        'type_contrat'       => $c->getTypeContrat(),
        'date_debut_contrat' => $c->getDateDebutContrat(),
        'date_fin_contrat'   => $c->getDateFinContrat(),
        'prime_contrat'      => $c->getPrimeContrat(),
        'franchise_contrat'  => $c->getFranchiseContrat(),
        'statut_contrat'     => $c->getStatutContrat(),
    ];
}

echo json_encode(['success' => true, 'data' => $data]);
?>

