<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';
$code = strtoupper(trim($_GET['code'] ?? ''));
if (!preg_match('/^PRTX-[A-Z0-9]{6,8}$/', $code)) {
    echo json_encode(['valid' => false]);
    exit;
}
$db = config::getConnexion();
$stmt = $db->prepare("SELECT nom, prenom FROM user WHERE referral_code = ?");
$stmt->execute([$code]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    echo json_encode(['valid' => true, 'parrain' => $row['prenom'] . ' ' . $row['nom']]);
} else {
    echo json_encode(['valid' => false]);
}


