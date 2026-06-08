<?php
/**
 * view/BackOffice/get_commentaires.php
 * Endpoint AJAX — Récupère les commentaires d'un poste
 */
if (session_status() === PHP_SESSION_NONE) session_start();
error_reporting(0); // Prevent warnings from corrupting JSON
header('Content-Type: application/json');
ob_clean(); // Clear any previous output (warnings, spaces)

require_once __DIR__ . '/../../helpers/SessionGuard.php';
SessionGuard::requireBackoffice();

require_once __DIR__ . '/../../config.php';

$id_poste = isset($_GET['id_poste']) ? (int)$_GET['id_poste'] : 0;

if (!$id_poste) {
    echo json_encode(['success' => false, 'message' => 'ID poste manquant.']);
    exit;
}

try {
    $pdo = config::getConnexion();
    $stmt = $pdo->prepare("
        SELECT 
            c.id_commentaire,
            c.contenu,
            c.date_commentaire,
            c.id_poste,
            c.id_client,
            c.id_commentaire_parent,
            c.hidden,
            CONCAT(u.prenom, ' ', u.nom) AS auteur_nom
        FROM commentaire c
        LEFT JOIN user u ON c.id_client = u.id_user
        WHERE c.id_poste = ?
        ORDER BY c.date_commentaire ASC
    ");
    $stmt->execute([$id_poste]);
    $commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $commentaires]);
} catch (Exception $e) {
    error_log('get_commentaires error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
