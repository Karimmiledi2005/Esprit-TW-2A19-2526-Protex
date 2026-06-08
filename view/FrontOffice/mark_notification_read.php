<?php
require_once __DIR__ . '/db.php';

$data = getJsonInput();
$idNotification = (int)($data['id_notification'] ?? 0);

if ($idNotification <= 0) {
    jsonResponse(['success' => false, 'message' => 'Notification invalide.'], 422);
}

try {
    $stmt = $pdo->prepare("UPDATE notification SET is_read = 1 WHERE id_notification = ?");
    $stmt->execute([$idNotification]);

    jsonResponse(['success' => true, 'message' => 'Notification marquée comme lue.']);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erreur SQL : ' . $e->getMessage()], 500);
}

