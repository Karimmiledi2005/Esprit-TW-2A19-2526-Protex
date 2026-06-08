<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../helpers/SessionGuard.php';
SessionGuard::requireLogin();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../../model/PosteModel.php';
$model = new PosteModel($pdo);

$data = getJsonInput();
$idPoste = (int)($data['id_poste'] ?? 0);

if ($idPoste <= 0) {
    jsonResponse([
        'success' => false,
        'message' => 'Identifiant du poste invalide.'
    ], 422);
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

