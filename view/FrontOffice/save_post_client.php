<?php
/**
 * save_post_client.php — Sauvegarde un post FrontOffice avec image + hashtag/mention
 * R3: Module 4
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../helpers/SessionGuard.php';
SessionGuard::requireClient();
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json; charset=utf-8');

$idUser = (int)($_SESSION['user_id'] ?? 0);
if (!$idUser) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$db = config::getConnexion();
$contenu = trim($_POST['contenu'] ?? '');
if ($contenu === '') {
    echo json_encode(['success' => false, 'error' => 'Le contenu est requis.']);
    exit;
}

// Gestion upload image
$mediaUrl = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $ftype = mime_content_type($_FILES['image']['tmp_name']);
    if (!in_array($ftype, $allowed, true)) {
        echo json_encode(['success' => false, 'error' => 'Type de fichier non autorisé.']);
        exit;
    }
    $uploadDir = __DIR__ . '/../../uploads/reseau/' . $idUser . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = 'post_' . uniqid() . '.' . $ext;
    move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
    $mediaUrl = 'uploads/reseau/' . $idUser . '/' . $filename;
}

// Parser hashtags → JSON pour futur usage
preg_match_all('/#(\w+)/u', $contenu, $hashtagMatches);
$hashtags = array_unique($hashtagMatches[1] ?? []);

// Récupérer l'agence du user
$stmtUser = $db->prepare("SELECT u.id_agence, u.nom, u.prenom FROM user u WHERE u.id_user = ?");
$stmtUser->execute([$idUser]);
$userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
$idAgence = (int)($userRow['id_agence'] ?? 0);
$auteur = trim(($userRow['prenom'] ?? '') . ' ' . ($userRow['nom'] ?? ''));

$stmt = $db->prepare("INSERT INTO poste (contenu, auteur, date_publication, id_agence, media_url, nb_likes, nb_commentaires, hidden, signalements) VALUES (?, ?, NOW(), ?, ?, 0, 0, 0, 0)");
$stmt->execute([$contenu, $idUser, $idAgence, $mediaUrl]);
$idPoste = (int)$db->lastInsertId();

echo json_encode(['success' => true, 'id_poste' => $idPoste, 'hashtags' => $hashtags, 'media_url' => $mediaUrl]);
