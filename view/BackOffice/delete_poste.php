<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
// FIX 2 — Réservé au superadmin uniquement
if (SessionGuard::role() !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Réservé au Super Administrateur.']);
    exit;
}

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/PosteModel.php';
$pdo = config::getConnexion();
$model = new PosteModel($pdo);

// Helper JSON input
$data = json_decode(file_get_contents('php://input'), true) ?? [];

// Helper réponse JSON
function jsonResponse(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

$idPoste = (int)($data['id_poste'] ?? 0);

    $id_agence_session = $_SESSION['id_agence'] ?? null;
    $role = $_SESSION['role'] ?? '';

    $existingPoste = $model->getPosteById($idPoste);
    if (!$existingPoste) {
        jsonResponse(['success' => false, 'message' => 'Poste introuvable'], 404);
    }

    if ($role !== 'superadmin' && $id_agence_session && (int)$existingPoste['id_agence'] !== (int)$id_agence_session) {
        jsonResponse(['success' => false, 'message' => 'Accès refusé: ce poste appartient à une autre agence'], 403);
    }

try {
    $model->deletePoste($idPoste);

    jsonResponse([
        'success' => true,
        'message' => 'Poste supprimé.'
    ]);
} catch (PDOException $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Erreur SQL : ' . $e->getMessage()
    ], 500);
}
