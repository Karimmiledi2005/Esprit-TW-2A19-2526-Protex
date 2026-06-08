<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
header('Content-Type: application/json');
require_once __DIR__ . '/../../connexion.php';
require_once __DIR__ . '/../../controller/Client_Con.php';

try {
    $controller = new UserController();
    $days = isset($_GET['days']) ? (int)$_GET['days'] : null;
    $stats = $controller->getAdvancedStats($days);
    echo json_encode(['success'=>true,'data'=>$stats]);
} catch (Exception $e) {
    error_log('get_advanced_stats error: ' . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Erreur serveur']);
}
