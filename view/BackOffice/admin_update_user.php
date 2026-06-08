<?php
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['superadmin','admin','agent'])) {
    http_response_code(403);
    echo json_encode(["success"=>false,"message"=>"Accès refusé"]);
    exit;
}

header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../controller/Client_Con.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success"=>false,"message"=>"Méthode non autorisée"]); exit;
}

// Vérification CSRF
$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    http_response_code(403);
    echo json_encode(["success"=>false,"message"=>"Token CSRF invalide"]);
    exit;
}

$id_user = isset($_POST['id_user']) ? (int)$_POST['id_user'] : 0;
if (!$id_user) { echo json_encode(["success"=>false,"message"=>"ID utilisateur manquant"]); exit; }

$nom       = trim($_POST['nom']       ?? '');
$prenom    = trim($_POST['prenom']    ?? '');
$email     = trim($_POST['email']     ?? '');
$telephone = trim($_POST['telephone'] ?? '');
$cin       = trim($_POST['cin']       ?? '');
$role      = strtolower(trim($_POST['role']   ?? 'client'));
$statut    = strtolower(trim($_POST['statut'] ?? 'actif'));

try {
    // Sécurité supplémentaire pour l'agent (ne peut modifier que des clients)
    if (($_SESSION['role'] ?? '') === 'agent' && $role !== 'client') {
        throw new Exception("Les agents ne peuvent modifier que des comptes clients.");
    }
    $controller   = new UserController();
    $niveau_acces = $_POST['niveau_acces'] ?? null;
    $id_agence    = isset($_POST['id_agence']) && $_POST['id_agence'] !== '' ? (int)$_POST['id_agence'] : null;
    $salaire      = isset($_POST['salaire']) && $_POST['salaire'] !== '' ? (float)$_POST['salaire'] : null;
    $num_client   = trim($_POST['numero_client'] ?? '');

    $controller->updateUserAdmin($id_user,$nom,$prenom,$email,$telephone?:null,$cin?:null,$role,$statut,$niveau_acces,$id_agence,$salaire,$num_client?:null);
    AuditLogger::log('modification_user', 'user', "ID: $id_user");
    echo json_encode(["success"=>true,"message"=>"Utilisateur mis à jour"]);
} catch (Exception $e) {
    echo json_encode(["success"=>false,"message"=>$e->getMessage()]);
}

