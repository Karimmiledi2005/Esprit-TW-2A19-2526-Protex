<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../helpers/SessionGuard.php';
SessionGuard::requireClient();
require_once __DIR__ . '/db.php';

$data = getJsonInput();

$idPoste = (int)($data['id_poste'] ?? 0);
$idClient = SessionGuard::userId();
$contenu = trim($data['contenu'] ?? '');

if ($idPoste <= 0 || $idClient <= 0 || $contenu === '') {
    jsonResponse([
        'success' => false,
        'message' => 'Commentaire invalide.'
    ], 422);
}

$stmt = $pdo->prepare("
    INSERT INTO commentaire (contenu, id_poste, id_client, id_commentaire_parent)
    VALUES (?, ?, ?, NULL)
");
$stmt->execute([$contenu, $idPoste, $idClient]);

syncPostStats($pdo, $idPoste);

addNotification($pdo, $idClient,
    'Nous vous remercions pour votre commentaire. Votre retour est précieux pour améliorer nos services.',
    'thanks');

jsonResponse([
    'success' => true,
    'message' => 'Commentaire ajouté.'
]);

