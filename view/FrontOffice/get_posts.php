<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../helpers/SessionGuard.php';
SessionGuard::requireLogin();
require_once __DIR__ . '/db.php';

try {
    $sqlPosts = "
        SELECT 
            p.id_poste,
            p.contenu,
            p.date_publication,
            p.note,
            p.auteur,
            p.nb_likes,
            p.nb_commentaires,
            p.id_agence,
            a.nom_agence
        FROM poste p
        LEFT JOIN agence a ON a.id_agence = p.id_agence
        ORDER BY p.date_publication DESC, p.id_poste DESC
    ";
    $posts = $pdo->query($sqlPosts)->fetchAll();

    $sqlComments = "
        SELECT
            c.id_commentaire,
            c.contenu,
            c.date_commentaire,
            c.id_poste,
            c.id_client,
            c.id_commentaire_parent,
            c.hidden,
            CONCAT(u.prenom, ' ', u.nom) AS auteur
        FROM commentaire c
        INNER JOIN user u ON u.id_user = c.id_client
        ORDER BY c.date_commentaire ASC, c.id_commentaire ASC
    ";
    $comments = $pdo->query($sqlComments)->fetchAll();

    $commentsByPost = [];
    $childrenByParent = [];

    foreach ($comments as $comment) {
        $comment['reponses'] = [];

        if ($comment['id_commentaire_parent'] === null) {
            $commentsByPost[$comment['id_poste']][] = $comment;
        } else {
            $childrenByParent[$comment['id_commentaire_parent']][] = $comment;
        }
    }

    function injectReplies(array $comments, array $childrenByParent): array {
        foreach ($comments as &$comment) {
            $children = $childrenByParent[$comment['id_commentaire']] ?? [];
            $comment['reponses'] = injectReplies($children, $childrenByParent);
        }
        return $comments;
    }

    foreach ($posts as &$post) {
        $rootComments = $commentsByPost[$post['id_poste']] ?? [];
        $post['comments'] = injectReplies($rootComments, $childrenByParent);
    }

    jsonResponse([
        'success' => true,
        'posts' => $posts
    ]);
} catch (PDOException $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Erreur SQL : ' . $e->getMessage()
    ], 500);
} catch (Throwable $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Erreur serveur : ' . $e->getMessage()
    ], 500);
}

