<?php
require_once __DIR__ . '/../../bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

RoleHelper::requireRole(['superadmin', 'admin']);
CsrfHelper::validate();

try {
    if (!RoleHelper::canModifierReponse()) {
        http_response_code(403);
        throw new Exception('Action non autorisée pour votre rôle.');
    }

    $ctrl       = new ReponseController();
    $reponse_id = (int)($_POST['reponse_id'] ?? 0);
    $contenu    = trim($_POST['contenu'] ?? '');

    if (!$reponse_id) throw new Exception('ID réponse manquant.');
    if (!$contenu)    throw new Exception('Le contenu est requis.');

    $ctrl->updateReponse($reponse_id, $contenu);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if (http_response_code() < 400) {
        http_response_code(400);
    }
    error_log('updatereponse error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
