<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

try {
if (!isset($_SESSION['otp_user_id'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Session expirée']);
    exit;
}

require_once __DIR__ . '/../../controller/Client_Con.php';
require_once __DIR__ . '/../../config.php';

$id_user = $_SESSION['otp_user_id'];
$nom     = $_SESSION['otp_nom'];

$db = config::getConnexion();
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));

$db->prepare("DELETE FROM otp_codes WHERE id_user = :id")->execute(['id' => $id_user]);
$db->prepare("INSERT INTO otp_codes (id_user, code, expires_at) VALUES (:id, :code, :expires)")
   ->execute(['id' => $id_user, 'code' => $otp, 'expires' => $expiresAt]);

$stmt = $db->prepare("SELECT email FROM user WHERE id_user = :id");
$stmt->execute(['id' => $id_user]);
$user = $stmt->fetch();

try {
    (new Mailer())->sendOTP($user['email'], $nom, $otp);
    ob_clean();
    echo json_encode(['success' => true, 'message' => 'OTP renvoyé']);
} catch (Throwable $e) {
    ob_clean();
    error_log('resend_otp send: ' . $e->getMessage());
    error_log('resend_otp error (send): ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur envoi email']);
}

} catch (Throwable $eGlobal) {
    ob_clean();
    error_log('resend_otp.php: ' . $eGlobal->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}

