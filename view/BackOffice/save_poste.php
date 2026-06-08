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

ob_start();
// Helper JSON input
$data = json_decode(file_get_contents('php://input'), true) ?? [];

// Helper réponse JSON
function jsonResponse(array $payload, int $code = 200): void {
    if (ob_get_length()) ob_clean();
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

$idPoste = isset($data['id_poste']) ? (int)$data['id_poste'] : 0;
$contenu = trim($data['contenu'] ?? '');
$auteur = trim($data['auteur'] ?? '');
$idAgence = (int)($data['id_agence'] ?? 0);
$datePublication = trim($data['date_publication'] ?? '');

$errors = [];

if ($contenu === '') {
    $errors['contenu'] = 'Veuillez remplir le contenu du poste.';
}

if ($auteur === '') {
    $errors['auteur'] = 'Veuillez remplir le nom de l\'auteur.';
} elseif (preg_match('/\d/', $auteur)) {
    $errors['auteur'] = 'Le nom de l\'auteur ne doit pas contenir de chiffres.';
}

if ($idAgence <= 0) {
    $errors['id_agence'] = 'Veuillez choisir une agence.';
}

if ($datePublication === '') {
    $errors['date_publication'] = 'Veuillez choisir une date valide.';
}

    $id_agence_session = $_SESSION['id_agence'] ?? null;
    $role = $_SESSION['role'] ?? '';

    if ($role !== 'superadmin' && $id_agence_session) {
        $idAgence = (int)$id_agence_session;
        $data['id_agence'] = $idAgence;
    }

    if ($idPoste > 0) {
        $existingPoste = $model->getPosteById($idPoste);
        if (!$existingPoste) {
            jsonResponse(['success' => false, 'message' => 'Poste introuvable'], 404);
        }
        if ($role !== 'superadmin' && $id_agence_session && (int)$existingPoste['id_agence'] !== (int)$id_agence_session) {
            jsonResponse(['success' => false, 'message' => 'Accès refusé: ce poste appartient à une autre agence'], 403);
        }
    }

if (!empty($errors)) {
    jsonResponse([
        'success' => false,
        'errors' => $errors
    ], 422);
}

try {
    if ($idPoste > 0) {
        $model->updatePoste($data);
        jsonResponse([
            'success' => true,
            'message' => 'Poste modifié avec succès.'
        ]);
    } else {
        $model->createPoste($data);
        jsonResponse([
            'success' => true,
            'message' => 'Poste ajouté avec succès.'
        ]);
    }
} catch (PDOException $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Erreur SQL : ' . $e->getMessage()
    ], 500);
}
