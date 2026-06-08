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

$idAgence = (int)($data['id_agence'] ?? 0);

if ($idAgence <= 0) {
    jsonResponse([
        'success' => false,
        'message' => 'Identifiant de l\'agence invalide.'
    ], 422);
}

try {
    // Count dependent records that will be cascade-deleted
    $stmtPostes = $pdo->prepare("SELECT COUNT(*) FROM poste WHERE id_agence = ?");
    $stmtPostes->execute([$idAgence]);
    $nbPostes = (int)$stmtPostes->fetchColumn();

    $stmtAvis = $pdo->prepare("SELECT COUNT(*) FROM avis_agence WHERE id_agence = ?");
    $stmtAvis->execute([$idAgence]);
    $nbAvis = (int)$stmtAvis->fetchColumn();

    $stmtUsers = $pdo->prepare("SELECT COUNT(*) FROM user WHERE id_agence = ?");
    $stmtUsers->execute([$idAgence]);
    $nbUsers = (int)$stmtUsers->fetchColumn();

    // Block deletion if users are assigned
    if ($nbUsers > 0) {
        jsonResponse([
            'success' => false,
            'message' => "Impossible de supprimer : $nbUsers utilisateur(s) sont encore rattachés à cette agence."
        ], 409);
    }

    // Perform deletion (CASCADE will remove postes, comments, likes, avis)
    $model->deleteAgence($idAgence);

    $details = [];
    if ($nbPostes > 0) $details[] = "$nbPostes poste(s)";
    if ($nbAvis > 0) $details[] = "$nbAvis avis";

    $msg = 'Agence supprimée.';
    if (!empty($details)) {
        $msg .= ' Suppression en cascade : ' . implode(', ', $details) . '.';
    }

    jsonResponse([
        'success' => true,
        'message' => $msg,
        'cascade' => [
            'postes' => $nbPostes,
            'avis' => $nbAvis
        ]
    ]);
} catch (PDOException $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Erreur SQL : ' . $e->getMessage()
    ], 500);
}