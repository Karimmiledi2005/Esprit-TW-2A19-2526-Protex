<?php
require_once __DIR__ . '/../../controller/ContratController.php';

$controller = new ContratController();
$contrats = $controller->getAll();

$data = [];
foreach ($contrats as $c) {
    $data[] = [
        'id_contrat' => $c->getIdContrat(),
        'numero_contrat' => $c->getNumeroContrat(),
        'type_contrat' => $c->getTypeContrat(),
        'date_debut_contrat' => $c->getDateDebutContrat(),
        'date_fin_contrat' => $c->getDateFinContrat(),
        'prime_contrat' => $c->getPrimeContrat(),
        'franchise_contrat' => $c->getFranchiseContrat(),
        'statut_contrat' => $c->getStatutContrat(),
        'id_client' => $c->getIdClient(),
        'id_categorie' => $c->getIdCategorie(),
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'data' => $data]);
?>

