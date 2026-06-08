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

$id_user = isset($_POST['id_user']) ? (int)$_POST['id_user'] : 0;
if (!$id_user) { echo json_encode(["success"=>false,"message"=>"ID manquant"]); exit; }
if ($id_user === (int)$_SESSION['user_id']) {
    echo json_encode(["success"=>false,"message"=>"Impossible de supprimer votre propre compte"]); exit;
}

try {
    // FIX 5 : l'agent ne peut pas supprimer
    if (($_SESSION['role'] ?? '') === 'agent') {
        echo json_encode(["success" => false, "message" => "Les agents n'ont pas la permission de supprimer."]);
        exit;
    }

    $pdo = config::getConnexion();

    // FIX 8 : empêcher la suppression du dernier superadmin
    $stmtRole = $pdo->prepare("SELECT role FROM user WHERE id_user = ?");
    $stmtRole->execute([$id_user]);
    $targetRole = $stmtRole->fetchColumn();

    if ($targetRole === 'superadmin') {
        $countStmt = $pdo->query("SELECT COUNT(*) FROM user WHERE role = 'superadmin'");
        $superAdminCount = (int)$countStmt->fetchColumn();
        if ($superAdminCount <= 1) {
            echo json_encode(["success" => false, "message" => "Impossible de supprimer le dernier Super Administrateur."]);
            exit;
        }
    }

    $controller = new UserController();
    $controller->deleteUser($id_user, $_POST['csrf_token'] ?? '');
    AuditLogger::log('suppression_user', 'user', "ID: $id_user");
    echo json_encode(["success" => true, "message" => "Utilisateur supprimé"]);
} catch (Exception $e) {
    error_log('admin_delete_user error: ' . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Erreur serveur"]);
}

