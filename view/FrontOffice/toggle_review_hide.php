<?php
require_once __DIR__ . '/db.php';

$data = getJsonInput();
$idAvis = (int)($data['id_avis'] ?? 0);

if ($idAvis <= 0) {
    jsonResponse(['success' => false, 'message' => 'Avis invalide.'], 422);
}

try {
    $stmt = $pdo->prepare("SELECT id AS id_avis, hidden, id_client FROM avis_agence WHERE id = ?");
    $stmt->execute([$idAvis]);
    $avis = $stmt->fetch();

    if (!$avis) {
        jsonResponse(['success' => false, 'message' => 'Avis introuvable.'], 404);
    }

    $newHidden = $avis['hidden'] ? 0 : 1;

    $stmt = $pdo->prepare("UPDATE avis_agence SET hidden = ? WHERE id = ?");
    $stmt->execute([$newHidden, $idAvis]);

    if ($newHidden) {
        addNotification($pdo, (int)$avis['id_client'],
            'Votre avis a été masqué conformément à notre politique de modération en raison de termes non conformes. Nous vous remercions de votre compréhension et vous invitons à soumettre un nouvel avis respectant nos directives.',
            'hidden');
    }

    jsonResponse([
        'success' => true,
        'message' => $newHidden ? 'Avis masqué.' : 'Avis affiché.',
        'hidden' => $newHidden
    ]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erreur SQL : ' . $e->getMessage()], 500);
}

