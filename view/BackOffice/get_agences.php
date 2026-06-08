<?php
if (session_status() === PHP_SESSION_NONE) session_start();
error_reporting(0);
header('Content-Type: application/json');
ob_clean();

if (!isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non connecté']); exit;
}
$role = strtolower($_SESSION['role']);
if (!in_array($role, ['superadmin', 'agent', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']); exit;
}

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/PosteModel.php';

try {
    $pdo = config::getConnexion();
    $model = new PosteModel($pdo);
    
    $id_agence = $_SESSION['id_agence'] ?? null;
    $role = $_SESSION['role'] ?? '';

    if ($role !== 'superadmin' && $id_agence) {
        // Un admin ou agent ne voit que son agence
        $stmt = $pdo->prepare("SELECT id_agence, nom_agence, pays, tel, email, statut, adresse FROM agence WHERE id_agence = ?");
        $stmt->execute([$id_agence]);
        $agences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Le superadmin voit tout
        $agences = $model->getAllAgences();
    }
    
    echo json_encode(['success' => true, 'data' => $agences]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
