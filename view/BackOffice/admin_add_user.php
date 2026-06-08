<?php
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// SuperAdmin + admin agence peuvent accéder + agent pour créer clients seulement
if (!isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(["success"=>false,"message"=>"Non connecté"]); exit;
}
$role = strtolower($_SESSION['role']);
if (!in_array($role, ['superadmin','admin','agent'])) {
    http_response_code(403);
    echo json_encode(["success"=>false,"message"=>"Accès refusé (" . $role . ")"]); exit;
}

header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success"=>false,"message"=>"Méthode non autorisée"]);
    exit;
}

// Vérification CSRF
require_once __DIR__ . '/../../controller/Client_Con.php';
$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    http_response_code(403);
    echo json_encode(["success"=>false,"message"=>"Token CSRF invalide"]);
    exit;
}

$nom       = trim($_POST['nom']       ?? '');
$prenom    = trim($_POST['prenom']    ?? '');
$email     = trim($_POST['email']     ?? '');
$password  =      $_POST['password']  ?? '';
$telephone = trim($_POST['telephone'] ?? '');
$cin       = trim($_POST['cin']       ?? '');
$role      = strtolower(trim($_POST['role']   ?? 'client'));
$statut    = strtolower(trim($_POST['statut'] ?? 'actif'));

// Seul superadmin et admin peuvent créer des agents
if ($role === 'agent' && !in_array($_SESSION['role'], ['superadmin','admin'])) {
    http_response_code(403);
    echo json_encode(["success"=>false,"message"=>"Vous n'avez pas la permission de créer des agents"]);
    exit;
}

try {
    // Sécurité supplémentaire : l'agent ne peut créer que des clients
    if (($_SESSION['role']??'') === 'agent' && $role !== 'client') {
        throw new Exception("En tant qu'agent, vous ne pouvez créer que des clients.");
    }
    $controller    = new UserController();
    $niveau_acces  = $_POST['niveau_acces'] ?? null;
    $id_agence     = isset($_POST['id_agence']) && $_POST['id_agence'] !== '' ? (int)$_POST['id_agence'] : null;
    $salaire       = isset($_POST['salaire']) && $_POST['salaire'] !== '' ? (float)$_POST['salaire'] : null;
    $numero_client = trim($_POST['numero_client'] ?? '');

    $controller->addUserAdmin($nom,$prenom,$email,$password,$telephone?:null,$cin?:null,$role,$statut,$niveau_acces,$id_agence,$salaire,$numero_client?:null);

    echo json_encode(["success"=>true,"message"=>"Utilisateur ajouté avec succès"]);
} catch (Exception $e) {
    error_log('admin_add_user error: ' . $e->getMessage());
    echo json_encode(["success"=>false,"message"=>"Erreur serveur"]);
}

