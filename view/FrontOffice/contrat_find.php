<?php
require_once __DIR__ . '/../../controller/ContratController.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'ID manquant.']);
    exit;
}

$controller = new ContratController();
$contrat = $controller->findById($id);
if (!$contrat) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Contrat introuvable.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'data' => [
    'id_contrat' => $contrat->getIdContrat(),
    'numero_contrat' => $contrat->getNumeroContrat(),
    'type_contrat' => $contrat->getTypeContrat(),
    'date_debut_contrat' => $contrat->getDateDebutContrat(),
    'date_fin_contrat' => $contrat->getDateFinContrat(),
    'prime_contrat' => $contrat->getPrimeContrat(),
    'franchise_contrat' => $contrat->getFranchiseContrat(),
    'statut_contrat' => $contrat->getStatutContrat(),
    'id_client' => $contrat->getIdClient(),
    'id_categorie' => $contrat->getIdCategorie(),
]]);
?>

