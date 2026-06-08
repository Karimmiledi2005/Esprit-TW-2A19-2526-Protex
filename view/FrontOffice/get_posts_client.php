<?php
/**
 * get_posts_client.php — Retourne les posts du fil d'actualité pour le FrontOffice
 * R3: Module 4
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../helpers/SessionGuard.php';
SessionGuard::requireClient();
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json; charset=utf-8');

$idUser = (int)($_SESSION['user_id'] ?? 0);
$db = config::getConnexion();

// Filter by hashtag?
$hashtag = trim($_GET['hashtag'] ?? '');
$hashtagSql = '';
$params = [];

if ($hashtag !== '') {
    $hashtagSql = "WHERE p.hidden = 0 AND p.contenu LIKE :hashtag";
    $params[':hashtag'] = '%#' . $hashtag . '%';
} else {
    $hashtagSql = "WHERE p.hidden = 0";
}

$stmt = $db->prepare("
    SELECT 
        p.id_poste, p.contenu, p.date_publication, p.nb_likes, p.nb_commentaires, p.media_url,
        p.auteur AS id_auteur,
        CONCAT(u.prenom, ' ', u.nom) AS auteur_nom,
        u.avatar, u.avatar_url,
        (SELECT type FROM post_reaction WHERE id_post = p.id_poste AND id_user = :id_user LIMIT 1) AS my_reaction,
        (SELECT COUNT(*) FROM post_reaction WHERE id_post = p.id_poste AND type = 'like') AS react_like,
        (SELECT COUNT(*) FROM post_reaction WHERE id_post = p.id_poste AND type = 'love') AS react_love,
        (SELECT COUNT(*) FROM post_reaction WHERE id_post = p.id_poste AND type = 'wow') AS react_wow,
        (SELECT COUNT(*) FROM post_reaction WHERE id_post = p.id_poste AND type = 'sad') AS react_sad
    FROM poste p
    LEFT JOIN user u ON p.auteur = u.id_user
    $hashtagSql
    ORDER BY p.date_publication DESC
    LIMIT 50
");
$params[':id_user'] = $idUser;
$stmt->execute($params);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Parse hashtags & mentions in contenu
// Résoudre les @mentions vers les profils
$mentionUsers = [];
$mentionStmt = $db->query("SELECT id_user, CONCAT(prenom, ' ', nom) AS full_name FROM user");
foreach ($mentionStmt->fetchAll(PDO::FETCH_ASSOC) as $mu) {
    $mentionUsers[strtolower(str_replace(' ', '', $mu['full_name']))] = $mu['id_user'];
    // Aussi par prénom seul
    $parts = explode(' ', $mu['full_name']);
    if (!empty($parts[0])) $mentionUsers[strtolower($parts[0])] = $mu['id_user'];
}

foreach ($posts as &$p) {
    $content = htmlspecialchars($p['contenu'], ENT_QUOTES, 'UTF-8');
    $content = preg_replace('/#(\w+)/u', '<span class="hashtag" onclick="filterByHashtag(\'$1\')">#$1</span>', $content);
    $content = preg_replace_callback('/@(\w+)/u', function($m) use ($mentionUsers) {
        $username = strtolower($m[1]);
        $uid = $mentionUsers[$username] ?? null;
        if ($uid) {
            return '<a href="profil.php?id=' . $uid . '" class="mention">@' . $m[1] . '</a>';
        }
        return '<span class="mention">@' . $m[1] . '</span>';
    }, $content);
    $p['contenu_html'] = $content;
}

echo json_encode(['success' => true, 'data' => $posts]);
