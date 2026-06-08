<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../helpers/SessionGuard.php';
SessionGuard::requireClient();
require_once __DIR__ . '/db.php';

$data = getJsonInput();
$idPoste = (int)($data['id_poste'] ?? 0);
$idClient = SessionGuard::userId();

if ($idPoste <= 0 || $idClient <= 0) {
    jsonResponse([
        'success' => false,
        'message' => 'Données invalides.'
    ], 422);
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO like_post (id_poste, id_client)
        VALUES (?, ?)
    ");
    $stmt->execute([$idPoste, $idClient]);
} catch (PDOException $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Vous avez déjà aimé ce poste.'
    ], 409);
}

syncPostStats($pdo, $idPoste);

jsonResponse([
    'success' => true,
    'message' => 'Like ajouté.'
]);

