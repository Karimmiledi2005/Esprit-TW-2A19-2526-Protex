<?php
/**
 * view/FrontOffice/chatbot.php
 * Point d'entrée AJAX pour le chatbot IA
 */
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../controller/ChatbotController.php';

try {
    // Lecture de l'entrée JSON
    $raw = file_get_contents('php://input');
    if (!$raw) {
        throw new Exception("Aucune donnée reçue (php://input est vide).");
    }
    
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Format JSON invalide : " . json_last_error_msg());
    }

    $message = $data['message'] ?? '';
    $email   = $data['email'] ?? '';

    // Si l'email est vide, on tente de le récupérer depuis la session
    if (empty($email) && isset($_SESSION['user_email'])) {
        $email = $_SESSION['user_email'];
    }

    $controller = new ChatbotController();
    $result = $controller->handleMessage($message, $email);

    echo json_encode($result);

} catch (Throwable $e) {
    error_log('chatbot.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur'
    ]);
}
