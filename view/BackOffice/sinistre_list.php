<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers/RoleHelper.php';
require_once __DIR__ . '/../../controller/SinistreController.php';
header('Content-Type: application/json');

// Bloquer tout accès non backoffice
RoleHelper::requireRole(['superadmin', 'admin', 'agent']);

$controller = new SinistreController();
$sinistres = $controller->getAllByRole();

$data = [];
foreach ($sinistres as $s) {
    $data[] = [
        'id_sinistre'      => $s->getIdSinistre(),
        'type'             => $s->getType(),
        'description'      => $s->getDescription(),
        'date_declaration' => $s->getDateDeclaration(),
        'statut'           => $s->getStatut(),
        'photo_url'        => $s->getPhotoUrl(),
        'id_contrat'       => $s->getIdContrat(),
        'id_user'          => $s->getIdUser(),
        'client_nom'       => $s->clientNom ?? '—',
        'numero_contrat'   => $s->numeroContrat ?? '—',
        'fraud_score'      => $s->fraudScore,
        'fraud_niveau'     => $s->fraudNiveau,
        'fraud_suggestion' => $s->fraudSuggestion,
    ];
}

echo json_encode(['success' => true, 'data' => $data]);

