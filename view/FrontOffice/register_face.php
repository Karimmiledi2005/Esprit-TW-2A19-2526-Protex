<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
header('Content-Type: application/json');

if (!SessionGuard::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['images']) || !is_array($data['images'])) {
    echo json_encode(['success' => false, 'message' => 'Images manquantes']);
    exit;
}

$user_id = SessionGuard::userId();

// Appel de l'API Python locale
$ch = curl_init('http://localhost:5000/register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'user_id' => $user_id,
    'images' => $data['images']
]));

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    echo json_encode(['success' => false, 'message' => 'Erreur de communication avec le moteur IA']);
    exit;
}

$result = json_decode($response, true);
if ($httpcode == 200 && isset($result['success']) && $result['success']) {
    // Marquer dans la base de données que le Face ID est configuré
    require_once __DIR__ . '/../../connexion.php';
    $db = config::getConnexion();
    $db->prepare("UPDATE user SET face_encoding = 'configured' WHERE id_user = :id")->execute(['id' => $user_id]);
    
    echo json_encode(['success' => true, 'message' => 'Face ID configuré avec succès !']);
} else {
    echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Erreur lors de la configuration']);
}

