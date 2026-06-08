<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['image'])) {
    echo json_encode(['success' => false, 'message' => 'Image manquante']);
    exit;
}

$email = isset($data['email']) ? trim($data['email']) : null;
$image = $data['image'];

require_once __DIR__ . '/../../connexion.php';
$db = config::getConnexion();

if ($email && !empty($email)) {
    // Mode Vérification (Email fourni)
    $stmt = $db->prepare("SELECT id_user, nom, prenom, role, statut, face_encoding FROM user WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable']);
        exit;
    }

    if ($user['statut'] === 'bloque') {
        echo json_encode(['success' => false, 'message' => 'Compte bloqué']);
        exit;
    }

    if ($user['face_encoding'] !== 'configured') {
        echo json_encode(['success' => false, 'message' => 'Face ID n\'est pas configuré pour ce compte']);
        exit;
    }

    $api_url = 'http://localhost:5000/verify';
    $post_data = ['user_id' => $user['id_user'], 'image' => $image];
} else {
    // Mode Identification Globale (Pas d'email fourni)
    $api_url = 'http://localhost:5000/identify';
    $post_data = ['image' => $image];
}

// Interroger l'API Python locale
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    echo json_encode(['success' => false, 'message' => 'Erreur de communication avec le moteur IA']);
    exit;
}

$result = json_decode($response, true);

if ($httpcode == 200 && isset($result['success']) && $result['success']) {
    if ($result['match']) {
        // En mode identification, on récupère l'user_id trouvé
        if (!isset($user) || !$user) {
            $stmt = $db->prepare("SELECT id_user, nom, prenom, role, statut FROM user WHERE id_user = :id LIMIT 1");
            $stmt->execute(['id' => $result['user_id']]);
            $user = $stmt->fetch();
            
            if (!$user || $user['statut'] === 'bloque') {
                echo json_encode(['success' => false, 'message' => 'Utilisateur reconnu mais compte inaccessible']);
                exit;
            }
        }

        // Face ID validé !
        // On connecte direct (Face ID est fort)
        
        // Charger id_agence si c'est un admin ou agent
        $id_agence = null;
        if ($user['role'] === 'admin') {
            $r = $db->prepare("SELECT id_agence FROM admin WHERE id_user=:id");
            $r->execute(['id'=>$user['id_user']]);
            $id_agence = $r->fetchColumn() ?: null;
        } elseif ($user['role'] === 'agent') {
            $r = $db->prepare("SELECT id_agence FROM agent WHERE id_user=:id");
            $r->execute(['id'=>$user['id_user']]);
            $id_agence = $r->fetchColumn() ?: null;
        }

        session_regenerate_id(true);
        $_SESSION['user_id']     = $user['id_user'];
        $_SESSION['id_user']     = $user['id_user'];
        $_SESSION['role']        = $user['role'];
        $_SESSION['user_role']   = $user['role'];
        $_SESSION['nom']         = $user['nom'];
        $_SESSION['prenom']      = $user['prenom'];
        $_SESSION['user_nom']    = $user['nom'];
        $_SESSION['user_prenom'] = $user['prenom'];
        $_SESSION['user_email']  = $user['email'] ?? '';
        $_SESSION['id_agence']   = $id_agence;
        $_SESSION['agence_id']   = $id_agence;
        $_SESSION['last_activity'] = time();
        
        $db->prepare("UPDATE user SET last_login=NOW() WHERE id_user=:id")->execute(['id'=>$user['id_user']]);

        echo json_encode(['success' => true, 'role' => $user['role'], 'message' => 'Reconnaissance faciale réussie']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Visage non reconnu (Confiance insuffisante)']);
    }
} else {
    echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Erreur lors de la vérification faciale']);
}

