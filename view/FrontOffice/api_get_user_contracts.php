<?php
/**
 * API endpoint to fetch user contracts for form dropdown
 * GET /api_get_user_contracts.php
 * Returns JSON array of user contracts
 */

require_once __DIR__ . '/../../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// Check session
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? $_SESSION['id_user'] ?? 0);
if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID utilisateur invalide']);
    exit;
}

try {
    $contratC = new ContratController();
    $contrats = $contratC->getByClient($userId);

    $data = [];
    foreach ($contrats as $contrat) {
        $data[] = [
            'id_contrat'     => $contrat->getIdContrat(),
            'numero_contrat' => $contrat->getNumeroContrat(),
            'type_contrat'   => $contrat->getTypeContrat(),
            'statut'         => $contrat->getStatutContrat(),
        ];
    }

    http_response_code(200);
    echo json_encode(['success' => true, 'contracts' => $data]);
} catch (Exception $e) {
    error_log('api_get_user_contracts.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}

