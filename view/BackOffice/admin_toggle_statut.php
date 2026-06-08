<?php
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Accès refusé : seuls les administrateurs peuvent modifier le statut."]);
    exit;
}

header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../controller/Client_Con.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success"=>false,"message"=>"Méthode non autorisée"]); exit;
}

$id_user = isset($_POST['id_user']) ? (int)$_POST['id_user'] : 0;
if (!$id_user) { echo json_encode(["success"=>false,"message"=>"ID manquant"]); exit; }

try {
    // Sécurité supplémentaire : l'agent ne peut pas bloquer/débloquer
    if (($_SESSION['role']??'') === 'agent') {
        throw new Exception("Les agents n'ont pas la permission de modifier le statut.");
    }
    $controller = new UserController();
    $newStatut  = $controller->toggleStatutUser($id_user, $_POST['csrf_token'] ?? '');
    AuditLogger::log('toggle_statut', 'user', "ID: $id_user, Nouveau statut: $newStatut");
    echo json_encode(["success"=>true,"statut"=>$newStatut]);
} catch (Exception $e) {
    error_log('admin_toggle_statut error: ' . $e->getMessage());
    echo json_encode(["success"=>false,"message"=>"Erreur serveur"]);
}

