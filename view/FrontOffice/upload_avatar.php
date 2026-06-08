<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Non connecté"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Méthode non autorisée"]);
    exit;
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "message" => "Erreur lors du téléchargement"]);
    exit;
}

$file = $_FILES['avatar'];
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 2 * 1024 * 1024; // 2MB

if (!in_array($file['type'], $allowed)) {
    echo json_encode(["success" => false, "message" => "Format non autorisé. Utilisez JPG, PNG, GIF ou WebP"]);
    exit;
}

// Validate real MIME type, not just the one sent by client
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$realMimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($realMimeType, $allowed)) {
    echo json_encode(["success" => false, "message" => "Type MIME invalide (fichier corrompu ou type déguisé). Utilisez JPG, PNG, GIF ou WebP"]);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(["success" => false, "message" => "Fichier trop volumineux. Maximum 2MB"]);
    exit;
}

require_once __DIR__ . '/../../config.php';

$userId = $_SESSION['user_id'];
$uploadDir = dirname(__DIR__, 2) . '/uploads/avatars/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$newFileName = 'avatar_' . $userId . '_' . time() . '.' . $extension;
$targetPath = $uploadDir . $newFileName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $avatarPath = $newFileName;
    
    $db = config::getConnexion();
    $stmt = $db->prepare("UPDATE user SET avatar = :avatar WHERE id_user = :id");
    $stmt->execute(['avatar' => $avatarPath, 'id' => $userId]);
    
    echo json_encode([
        "success" => true, 
        "message" => "Avatar mis à jour",
        "avatar" => $avatarPath
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Erreur lors de l'enregistrement"]);
}


