<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../helpers/SessionGuard.php';
SessionGuard::requireLogin();
require_once __DIR__ . '/db.php';

try {
    $stmt = $pdo->query("
        SELECT
            a.id AS id_avis,
            a.note,
            a.commentaire,
            a.date_avis,
            a.hidden,
            a.id_client,
            a.id_agence,
            ag.nom_agence,
            CONCAT(u.prenom, ' ', u.nom) AS auteur
        FROM avis_agence a
        INNER JOIN agence ag ON ag.id_agence = a.id_agence
        INNER JOIN user u ON u.id_user = a.id_client
        ORDER BY a.date_avis DESC, a.id DESC
    ");

    $reviews = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'reviews' => $reviews
    ]);
} catch (PDOException $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Erreur SQL : ' . $e->getMessage()
    ], 500);
}

