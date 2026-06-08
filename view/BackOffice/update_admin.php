<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Non connecté"]);
    exit;
}

require_once __DIR__ . '/../../controller/Client_Con.php';

$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);
if (empty($data)) $data = $_POST;

$id_user = (int) $_SESSION['user_id'];

$nom    = trim($data['nom'] ?? '');
$prenom = trim($data['prenom'] ?? '');

// Validation PHP
if (empty($nom) || empty($prenom)) {
    echo json_encode(["success" => false, "message" => "Le nom et prénom sont obligatoires"]);
    exit;
}
if (strlen($nom) < 2 || strlen($prenom) < 2) {
    echo json_encode(["success" => false, "message" => "Le nom et prénom doivent contenir au moins 2 lettres"]);
    exit;
}
if (preg_match('/[0-9]/', $nom) || preg_match('/[0-9]/', $prenom)) {
    echo json_encode(["success" => false, "message" => "Le nom et prénom ne doivent pas contenir de chiffres"]);
    exit;
}
if (!preg_match('/^[a-zA-ZÀ-ÿ\s\'\-]+$/', $nom) || !preg_match('/^[a-zA-ZÀ-ÿ\s\'\-]+$/', $prenom)) {
    echo json_encode(["success" => false, "message" => "Le nom et prénom ne doivent contenir que des lettres"]);
    exit;
}

try {
    $controller = new UserController();
    $controller->updateAdminProfile(
        $id_user,
        $data['nom'] ?? '',
        $data['prenom'] ?? '',
        $data['email'] ?? '',
        $data['telephone'] ?? null
    );

    // CORRECTION : Sync $_SESSION['nom'] with the new value after update
    $_SESSION['nom'] = $nom;

    echo json_encode(["success" => true, "message" => "Profil mis à jour avec succès"]);

} catch (Exception $e) {
    http_response_code(500);
    error_log('update_admin error: ' . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Erreur serveur"]);
}
