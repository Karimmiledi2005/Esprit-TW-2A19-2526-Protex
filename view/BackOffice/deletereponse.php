<?php
require_once __DIR__ . '/../../bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

RoleHelper::requireRole(['superadmin', 'admin']);
CsrfHelper::validate();

try {
    if (!RoleHelper::canSupprimerReponse()) {
        http_response_code(403);
        throw new Exception('Action non autorisée pour votre rôle.');
    }

    $ctrl       = new ReponseController();
    $reponse_id = (int)($_POST['reponse_id'] ?? 0);

    // Vérification agence
    $role = RoleHelper::getRole();
    $idAg = RoleHelper::getAgenceId();
    if (($role === 'admin' || $role === 'agent') && $idAg !== null && $reponse_id > 0) {
        $stmt = $ctrl->getDb()->prepare(
            "SELECT 1 FROM reponse rep 
             LEFT JOIN reclamation r ON rep.reclamation_id = r.id
             LEFT JOIN client c ON r.id_user = c.id_user 
             WHERE rep.id_re = ? AND c.id_agence = ? LIMIT 1"
        );
        $stmt->execute([$reponse_id, $idAg]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            throw new Exception('Accès refusé à cette réponse.');
        }
    }

    if (!$reponse_id) throw new Exception('ID réponse manquant.');

    $ctrl->deleteReponse($reponse_id);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if (http_response_code() < 400) {
        http_response_code(400);
    }
    error_log('deletereponse error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
