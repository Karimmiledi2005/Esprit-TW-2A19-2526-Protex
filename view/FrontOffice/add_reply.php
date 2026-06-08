<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../helpers/SessionGuard.php';
SessionGuard::requireClient();
require_once __DIR__ . '/db.php';

$data = getJsonInput();

$idPoste = (int)($data['id_poste'] ?? 0);
$idClient = SessionGuard::userId();
$idParent = (int)($data['id_commentaire_parent'] ?? 0);
$contenu = trim($data['contenu'] ?? '');

if ($idPoste <= 0 || $idClient <= 0 || $idParent <= 0 || $contenu === '') {
    jsonResponse([
        'success' => false,
        'message' => 'Réponse invalide.'
    ], 422);
}

// Vérifier que le parent existe
$stmtCheck = $pdo->prepare("SELECT id_commentaire FROM commentaire WHERE id_commentaire = ?");
$stmtCheck->execute([$idParent]);
if (!$stmtCheck->fetch()) {
    jsonResponse(['success' => false, 'message' => 'Commentaire parent introuvable.'], 404);
}

$stmt = $pdo->prepare("
    INSERT INTO commentaire (contenu, id_poste, id_client, id_commentaire_parent)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$contenu, $idPoste, $idClient, $idParent]);

syncPostStats($pdo, $idPoste);

addNotification($pdo, $idClient, 
    'Vous avez répondu à un commentaire. Merci pour votre interaction !', 
    'interaction');

jsonResponse([
    'success' => true,
    'message' => 'Réponse ajoutée.'
]);

