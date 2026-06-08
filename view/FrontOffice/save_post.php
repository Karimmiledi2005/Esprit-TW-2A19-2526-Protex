<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../helpers/SessionGuard.php';
SessionGuard::requireLogin();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../../model/PosteModel.php';
$model = new PosteModel($pdo);

$data = getJsonInput();

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

