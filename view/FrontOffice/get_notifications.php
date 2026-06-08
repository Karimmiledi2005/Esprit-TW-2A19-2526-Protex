<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
require_once __DIR__ . '/db.php';

SessionGuard::requireLogin();

$idUser = SessionGuard::userId();

try {
    $stmt = $pdo->prepare("SELECT id_notification, message, type, created_at, is_read FROM notification WHERE id_user = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$idUser]);
    $notifications = $stmt->fetchAll();

    $unread = $pdo->prepare("SELECT COUNT(*) FROM notification WHERE id_user = ? AND is_read = 0");
    $unread->execute([$idUser]);
    $unreadCount = (int)$unread->fetchColumn();

    jsonResponse([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unreadCount
    ]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erreur SQL : ' . $e->getMessage()], 500);
}

