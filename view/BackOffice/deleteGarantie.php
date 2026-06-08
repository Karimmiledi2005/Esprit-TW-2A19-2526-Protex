<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
require_once dirname(__DIR__, 2) . '/helpers/CsrfHelper.php';
require_once __DIR__ . '/../../controller/GarantieController.php';

SessionGuard::requireBackoffice();
SessionGuard::requireRoles(['superadmin', 'admin']);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    error_log('deleteGarantie.php called without id');
    header('Location: garanties_back.php?error=missing_id');
    exit;
}

$id = (int)$_GET['id'];

$controller = new GarantieController();
$garantie = $controller->showGarantie($id);

if (!$garantie) {
    error_log('deleteGarantie.php: garantie not found id=' . $id);
    header('Location: garanties_back.php?error=not_found');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CsrfHelper::verify($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Jeton CSRF invalide.');
    }

    try {
        $controller->deleteGarantie($id);
        header('Location: garanties_back.php');
        exit();
    } catch (Exception $e) {
        error_log('deleteGarantie.php delete error: ' . $e->getMessage());
        header('Location: garanties_back.php?error=server');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Supprimer garantie</title>
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/admin-users.css">
    <link rel="stylesheet" href="assets/css/contrats.css">
    <link rel="stylesheet" href="assets/css/forms.css">
</head>
<body>
<div class="content" style="padding:40px;">
    <div class="card" style="max-width:700px;margin:auto;">
        <div class="card-header">
            <div class="card-title">Supprimer la garantie</div>
        </div>

        <div style="padding:24px;">
            <div style="margin-bottom:20px; padding:16px; border-radius:14px; background:rgba(255,99,99,0.10); border:1px solid rgba(255,99,99,0.25); color:#ffd6d6;">
                <strong>Attention :</strong> cette action est irréversible.
            </div>

            <div style="display:grid; gap:14px; margin-bottom:24px;">
                <div>
                    <strong>Nom :</strong>
                    <div><?= htmlspecialchars($garantie['nom_garantie']) ?></div>
                </div>

                <div>
                    <strong>Description :</strong>
                    <div><?= htmlspecialchars($garantie['description_garantie']) ?></div>
                </div>

                <div>
                    <strong>Plafond :</strong>
                    <div><?= number_format((float)$garantie['plafond_couvert_garantie'], 2, '.', ' ') ?> DT</div>
                </div>

                <div>
                    <strong>Catégorie :</strong>
                    <div><?= htmlspecialchars($garantie['nom_categorie'] ?? '—') ?></div>
                </div>

                <div>
                    <strong>Formule :</strong>
                    <div><?= htmlspecialchars($garantie['nom_formule'] ?? '—') ?></div>
                </div>
            </div>

            <form method="POST">
                <?= CsrfHelper::field() ?>
                <div class="modal-footer" style="padding:0;border-top:none;">
                    <a href="garanties_back.php" class="btn btn-outline">Annuler</a>
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
