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
$friend_id = isset($data['friend_id']) ? (int)$data['friend_id'] : 0;

if (!$friend_id) {
    echo json_encode(["success" => false, "message" => "ID ami manquant"]);
    exit;
}

try {
    if ($action === 'fetch') {
        $messages = $controller->getMessages($my_id, $friend_id);
        // Add is_mine and sent_at for frontend compatibility
        foreach ($messages as &$m) {
            $m['is_mine'] = ($m['sender_id'] == $my_id) ? 1 : 0;
            $m['sent_at'] = $m['created_at'] ?? null;
        }
        unset($m);
        echo json_encode(["success" => true, "messages" => $messages]);

    } elseif ($action === 'send') {
        $content = trim($data['content'] ?? '');
        if ($controller->sendMessage($my_id, $friend_id, $content)) {
            echo json_encode(["success" => true, "message" => "Envoyé"]);
        } else {
            throw new Exception("Erreur lors de l'envoi");
        }
    } elseif ($action === 'unread_count') {
        $controller->ensureMessagesTable();
        $db = config::getConnexion();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM messages WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
        $stmt->execute([$friend_id, $my_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "count" => (int)$row['count']]);
    } else {
        echo json_encode(["success" => false, "message" => "Action invalide"]);
    }
} catch (Exception $e) {
    error_log('chat.php error: ' . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Erreur serveur"]);
}

