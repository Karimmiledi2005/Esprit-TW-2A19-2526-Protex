<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Non connecté"]);
    exit;
}

require_once __DIR__ . '/../../controller/Client_Con.php';
$my_id = $_SESSION['user_id'];
$controller = new UserController();

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (empty($data)) $data = $_GET;

$action = $data['action'] ?? '';

try {
    if ($action === 'toggle_trust') {
        $friend_id = (int)$data['friend_id'];
        $result = $controller->toggleTrust($my_id, $friend_id);
        echo json_encode($result);

    } elseif ($action === 'trigger') {
        $lat      = isset($data['lat'])      ? (float)$data['lat']      : null;
        $lng      = isset($data['lng'])      ? (float)$data['lng']      : null;
        $accuracy = isset($data['accuracy']) ? (int)$data['accuracy']   : null;
        $result = $controller->triggerSOS($my_id, $lat, $lng, $accuracy);
        echo json_encode($result);

    } elseif ($action === 'history') {
        $result = $controller->getSOSHistory($my_id);
        echo json_encode($result);

    } else {
        echo json_encode(["success" => false, "message" => "Action invalide"]);
    }
} catch (Exception $e) {
    error_log('sos.php error: ' . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Erreur serveur"]);
}

