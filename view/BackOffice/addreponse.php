<?php
require_once __DIR__ . '/../../bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

RoleHelper::requireRole(['superadmin', 'admin', 'agent']);
CsrfHelper::validate();

try {
    $ctrl   = new ReponseController();
    $action = trim($_POST['action'] ?? '');
    $reclamation_id = (int)($_POST['reclamation_id'] ?? 0);

    // Vérification de l'appartenance à l'agence (pour Admin/Agent)
    $role = RoleHelper::getRole();
    $idAg = RoleHelper::getAgenceId();
    if (($role === 'admin' || $role === 'agent') && $idAg !== null && $reclamation_id > 0) {
        $stmt = $ctrl->getDb()->prepare(
            "SELECT 1 FROM reclamation r LEFT JOIN client c ON r.id_user = c.id_user WHERE r.id = ? AND c.id_agence = ? LIMIT 1"
        );
        $stmt->execute([$reclamation_id, $idAg]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            throw new Exception('Accès refusé à cette réclamation.');
        }
    }

    // ── REJET ─────────────────────────────────────────────────────────────────
    if ($action === 'rejeter') {
        if (!RoleHelper::canRejeterReclamation()) {
            http_response_code(403);
            throw new Exception('Action non autorisée pour votre rôle.');
        }

        $motif          = trim($_POST['motif'] ?? '');
        $emailClient    = trim($_POST['email_client'] ?? '');

        if (!$reclamation_id) throw new Exception('ID réclamation manquant.');
        if (!$motif)          throw new Exception('Le motif du rejet est requis.');
        if ($emailClient && !filter_var($emailClient, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Adresse email client invalide.');
        }

        $result = $ctrl->rejeterAvecEmail($reclamation_id, $motif, $emailClient);
        echo json_encode($result);
        exit;
    }

    // ── AJOUTER UNE RÉPONSE ───────────────────────────────────────────────────
    if (!RoleHelper::canRepondreReclamation()) {
        http_response_code(403);
        throw new Exception('Action non autorisée pour votre rôle.');
    }

    $reclamation_id = (int)($_POST['reclamation_id'] ?? 0);
    $contenu        = trim($_POST['contenu'] ?? '');
    $emailClient    = trim($_POST['email_client'] ?? '');

    if (!$reclamation_id) throw new Exception('ID réclamation manquant.');
    if (!$contenu)        throw new Exception('Le contenu de la réponse est requis.');
    if ($emailClient && !filter_var($emailClient, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Adresse email invalide.');
    }

    $result = $ctrl->addReponseAvecEmail($reclamation_id, $contenu, $emailClient);
    echo json_encode($result);

} catch (Exception $e) {
    if (http_response_code() < 400) {
        http_response_code(400);
    }
    error_log('addreponse.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur: données invalides']);
}
