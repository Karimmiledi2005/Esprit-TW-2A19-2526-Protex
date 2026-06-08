<?php
/**
 * API endpoint to get user email from session
 * GET /api_get_user_email.php
 * Returns JSON with user email
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
    $db = config::getConnexion();
    $stmt = $db->prepare("SELECT email FROM user WHERE id_user = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        http_response_code(200);
        echo json_encode(['success' => true, 'email' => $user['email']]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable']);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log('api_get_user_email error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}

