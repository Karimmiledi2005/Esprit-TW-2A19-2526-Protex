<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}
require_once __DIR__ . '/../../config.php';
require_once '../../controller/Client_Con.php';

$body = json_decode(file_get_contents('php://input'), true);
$my_id = (int)$_SESSION['user_id'];

// Champs autorisés
$allowed = ['telephone', 'adresse', 'prenom', 'nom', 'date_naissance', 'email'];

try {
    $db = config::getConnexion();
    
    // Cas 1: Mise à jour globale (plusieurs champs)
    if (!isset($body['field'])) {
        $controller = new UserController();
        $user = $controller->getUserById($my_id);
        if (!$user) throw new Exception("Utilisateur introuvable");

        $nom      = trim($body['nom'] ?? $user['nom']);
        $prenom   = trim($body['prenom'] ?? $user['prenom']);
        $email    = trim($body['email'] ?? $user['email']);
        $tel      = trim($body['telephone'] ?? $user['telephone']);
        $adr      = trim($body['adresse'] ?? $user['adresse']);
        $dn       = trim($body['date_naissance'] ?? $user['date_naissance']);

        $controller->updateClient($my_id, $nom, $prenom, $email, $tel, $adr, $dn);
        echo json_encode(['success' => true, 'message' => 'Profil mis à jour']);
        exit;
    }

    // Cas 2: Mise à jour d'un seul champ (inline edit)
    $field = $body['field'] ?? '';
    $value = trim($body['value'] ?? '');

    if (!in_array($field, $allowed, true)) {
        echo json_encode(['success' => false, 'message' => 'Champ non autorisé : ' . $field]);
        exit;
    }

    if ($field === 'telephone' && !preg_match('/^[\d\s\-\+\(\)]{4,20}$/', $value)) {
        throw new Exception('Téléphone invalide');
    }
    if (in_array($field, ['nom', 'prenom']) && strlen($value) < 2) {
        throw new Exception('Minimum 2 caractères');
    }
    if ($field === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email invalide');
    }

    $stmt = $db->prepare("UPDATE user SET $field = ? WHERE id_user = ?");
    $stmt->execute([$value, $my_id]);

    echo json_encode(['success' => true, 'message' => 'Champ mis à jour']);

} catch (Exception $e) {
    error_log('update_user error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}



