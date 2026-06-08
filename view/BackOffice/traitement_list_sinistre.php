<?php
require_once __DIR__ . '/../../controller/TraitementController.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'ID sinistre manquant.']);
    exit;
}

$controller = new TraitementController();
$traitements = $controller->getBySinistre($id);

$data = [];
foreach ($traitements as $t) {
    $data[] = [
        'id_traitement' => $t->getIdTraitement(),
        'id_sinistre' => $t->getIdSinistre(),
        'id_user' => $t->getIdUser(),
        'nom_agent' => $t->getNomAgent(),
        'decision' => $t->getDecision(),
        'montant_indemnise' => $t->getMontantIndemnise(),
        'statut' => $t->getStatut(),
        'date_traitement' => $t->getDateTraitement(),
        'message_agent' => $t->getMessageAgent(),
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'data' => $data]);
?>
