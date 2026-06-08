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

$idAgence = isset($data['id_agence']) ? (int)$data['id_agence'] : 0;
$nomAgence = trim($data['nom_agence'] ?? '');
$pays = trim($data['pays'] ?? '');
$tel = trim($data['tel'] ?? '');
$email = trim($data['email'] ?? '');

$errors = [];

if ($nomAgence === '') {
    $errors['nom_agence'] = 'Veuillez remplir le nom de l\'agence.';
}

if ($pays === '') {
    $errors['pays'] = 'Veuillez remplir le pays.';
}

if ($tel !== '' && !preg_match('/^\d{8}$/', $tel)) {
    $errors['tel'] = 'Le numéro de téléphone doit contenir exactement 8 chiffres.';
}

if ($email === '') {
    $errors['email'] = 'Veuillez remplir l\'email.';
} elseif (strpos($email, '@protex.tn') === false) {
    $errors['email'] = 'L\'email doit contenir "@protex.tn".';
}

if (!empty($errors)) {
    jsonResponse([
        'success' => false,
        'errors' => $errors
    ], 422);
}

try {
    if ($idAgence > 0) {
        $model->updateAgence($data);
        jsonResponse([
            'success' => true,
            'message' => 'Agence modifiée avec succès.'
        ]);
    } else {
        $model->createAgence($data);
        jsonResponse([
            'success' => true,
            'message' => 'Agence ajoutée avec succès.'
        ]);
    }
} catch (PDOException $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Erreur SQL : ' . $e->getMessage()
    ], 500);
}