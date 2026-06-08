<?php
require_once __DIR__ . '/db.php';

$data = getJsonInput();
$idCommentaire = (int)($data['id_commentaire'] ?? 0);

if ($idCommentaire <= 0) {
    jsonResponse(['success' => false, 'message' => 'Commentaire invalide.'], 422);
}

try {
    $stmt = $pdo->prepare("SELECT id_commentaire, hidden, id_client FROM commentaire WHERE id_commentaire = ?");
    $stmt->execute([$idCommentaire]);
    $comment = $stmt->fetch();

    if (!$comment) {
        jsonResponse(['success' => false, 'message' => 'Commentaire introuvable.'], 404);
    }

    $newHidden = $comment['hidden'] ? 0 : 1;

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE commentaire SET hidden = ? WHERE id_commentaire = ?");
    $stmt->execute([$newHidden, $idCommentaire]);

    if ($newHidden) {
        addNotification($pdo, (int)$comment['id_client'],
            'Votre commentaire a été masqué conformément à notre politique de modération en raison de termes non conformes. Nous vous invitons à reformuler votre message dans le respect de nos directives.',
            'hidden');
        $toProcess = [$idCommentaire];
        while (!empty($toProcess)) {
            $current = array_shift($toProcess);
            $stmt = $pdo->prepare("SELECT id_commentaire FROM commentaire WHERE id_commentaire_parent = ?");
            $stmt->execute([$current]);
            $children = $stmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($children as $childId) {
                $stmt = $pdo->prepare("UPDATE commentaire SET hidden = 1 WHERE id_commentaire = ?");
                $stmt->execute([$childId]);
                $toProcess[] = $childId;
            }
        }
    }

    $pdo->commit();

    jsonResponse([
        'success' => true,
        'message' => $newHidden ? 'Commentaire masqué.' : 'Commentaire affiché.',
        'hidden' => $newHidden
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    jsonResponse(['success' => false, 'message' => 'Erreur SQL : ' . $e->getMessage()], 500);
}

