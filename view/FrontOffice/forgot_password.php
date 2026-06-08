<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Méthode non autorisée"]);
    exit;
}

require_once __DIR__ . '/../../controller/Client_Con.php';
require_once __DIR__ . '/../../mailer/mailer.php';

try {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    $email = trim($data['email'] ?? '');

    if (empty($email)) {
        echo json_encode(["success" => false, "message" => "L'email est requis"]);
        exit;
    }

    $controller = new UserController();
    $user = $controller->getUserByEmail($email);

    if ($user) {
        $id_user = $user['id_user'];
        
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $db = config::getConnexion();
        
        $db->prepare("DELETE FROM password_resets WHERE email = :email")->execute(['email' => $email]);
        
        $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :exp)")
           ->execute(['email' => $email, 'token' => $token, 'exp' => $expires]);

        try {
            $mailer = new Mailer();
            $stmt = $db->prepare("SELECT prenom FROM user WHERE id_user = :id");
            $stmt->execute(['id' => $id_user]);
            $prenom = $stmt->fetchColumn() ?: 'Utilisateur';
            
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base   = defined('BASE_URL') ? BASE_URL : '';
            $link   = $scheme . '://' . $host . $base . "/view/FrontOffice/reset_password.php?token=" . $token . "&action=magic";
            $mailer->sendMagicLink($email, $prenom, $link);
        } catch (Exception $e) {
            error_log("Erreur Mailer forgot_password: " . $e->getMessage());
        }
    }

    echo json_encode(["success" => true, "message" => "Lien magique envoyé ! Vérifiez votre boîte mail (et vos spams)."]);
    exit;

} catch (Exception $e) {
    error_log("forgot_password.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erreur serveur"]);
}
