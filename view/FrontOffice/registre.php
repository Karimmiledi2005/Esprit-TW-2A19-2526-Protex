<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../controller/Client_Con.php';


if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Méthode non autorisée"]);
    exit;
}

$nom            = trim($_POST['nom']            ?? '');
$prenom         = trim($_POST['prenom']         ?? '');
$email          = trim($_POST['email']          ?? '');
$password       =      $_POST['password']       ?? '';
$telephone      = trim($_POST['telephone']      ?? '');
$cin            = trim($_POST['cin']            ?? '');
$adresse        = trim($_POST['adresse']        ?? '');
$date_naissance = trim($_POST['date_naissance'] ?? '');
$referral_code  = strtoupper(trim($_POST['referral_code']  ?? ''));
$id_agence      = isset($_POST['id_agence']) ? (int)$_POST['id_agence'] : null;

try {
    $user = new User(
        $nom,
        $prenom,
        $email,
        $password,
        $telephone  ?: null,
        $adresse    ?: null,
        !empty($date_naissance) ? new DateTime($date_naissance) : null,
        $cin ?: null,
        '',
        'client',
        'actif',
        null,
        new DateTime()
    );

    $controller = new UserController();
    $controller->addclient($user, $referral_code ?: null, $id_agence);
    echo json_encode(["success" => true, "otp_required" => true]); 
} catch (Exception $e) {
    error_log('registre.php error: ' . $e->getMessage());
    echo json_encode(["success" => false, "message" => 'Erreur serveur']);
    exit;
}

