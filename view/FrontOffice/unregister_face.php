<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
header('Content-Type: application/json');

if (!SessionGuard::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$user_id = SessionGuard::userId();
require_once __DIR__ . '/../../connexion.php';
$db = config::getConnexion();

// Supprimer le modèle YML
$model_path = __DIR__ . '/../../face_api/models/user_' . $user_id . '.yml';
if (file_exists($model_path)) {
    unlink($model_path);
}

// Mettre à jour la base de données
$db->prepare("UPDATE user SET face_encoding = NULL WHERE id_user = :id")->execute(['id' => $user_id]);

echo json_encode(['success' => true, 'message' => 'Face ID désactivé avec succès']);
