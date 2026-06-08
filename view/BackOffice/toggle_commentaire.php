<?php
/**
 * view/BackOffice/toggle_commentaire.php
 * Endpoint AJAX — Afficher/masquer un commentaire (toggle hidden)
 */
if (session_status() === PHP_SESSION_NONE) session_start();
error_reporting(0);
header('Content-Type: application/json');
ob_clean();

// Restriction rôle
$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['superadmin', 'admin'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Accès refusé."]);
    exit;
}

require_once __DIR__ . '/../../config.php';

// Lecture JSON
$data = json_decode(file_get_contents('php://input'), true);
$id_commentaire = $data['id_commentaire'] ?? 0;

if (!$id_commentaire) {
    echo json_encode(["success" => false, "message" => "ID commentaire manquant."]);
    exit;
}

try {
    $pdo = config::getConnexion();

    // Récupérer le statut actuel
    $stmt = $pdo->prepare("SELECT hidden FROM commentaire WHERE id_commentaire = ?");
    $stmt->execute([$id_commentaire]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(["success" => false, "message" => "Commentaire introuvable."]);
        exit;
    }

    $newHidden = $row['hidden'] ? 0 : 1;

    $update = $pdo->prepare("UPDATE commentaire SET hidden = ? WHERE id_commentaire = ?");
    $update->execute([$newHidden, $id_commentaire]);

    echo json_encode([
        "success" => true,
        "hidden"  => $newHidden,
        "message" => $newHidden ? "Commentaire masqué." : "Commentaire affiché."
    ]);

} catch (Exception $e) {
    error_log('toggle_commentaire error: ' . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Erreur serveur"]);
}
