<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../connexion.php';
require_once __DIR__ . '/../../helpers/RoleHelper.php';
require_once __DIR__ . '/../../controller/TraitementController.php';
header('Content-Type: application/json');

// Bloquer tout accès non backoffice
RoleHelper::requireRole(['superadmin', 'admin', 'agent']);

$controller = new TraitementController();
$traitements = $controller->getAllByRole();

$data = [];
foreach ($traitements as $t) {
    $data[] = [
        'id_traitement'     => $t->getIdTraitement(),
        'id_sinistre'       => $t->getIdSinistre(),
        'id_user'           => $t->getIdUser(),
        'nom_agent'         => $t->getNomAgent(),
        'decision'          => $t->getDecision(),
        'montant_indemnise' => $t->getMontantIndemnise(),
        'statut'            => $t->getStatut(),
        'date_traitement'   => $t->getDateTraitement(),
        'message_agent'     => $t->getMessageAgent(),
    ];
}

echo json_encode(['success' => true, 'data' => $data]);
