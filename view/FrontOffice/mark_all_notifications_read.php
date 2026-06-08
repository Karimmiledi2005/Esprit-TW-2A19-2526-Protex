<?php
require_once __DIR__ . '/db.php';

$data = getJsonInput();
$idUser = (int)($data['id_user'] ?? 0);

if ($idUser <= 0) {
    jsonResponse(['success' => false, 'message' => 'Utilisateur invalide.'], 422);
}

try {
    $stmt = $pdo->prepare("UPDATE notification SET is_read = 1 WHERE id_user = ? AND is_read = 0");
    $stmt->execute([$idUser]);

    jsonResponse(['success' => true, 'message' => 'Toutes les notifications marquées comme lues.']);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erreur SQL : ' . $e->getMessage()], 500);
}

