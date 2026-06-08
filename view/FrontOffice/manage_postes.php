<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/PosteModel.php';

$pdo = config::getConnexion();
$model = new PosteModel($pdo);
$postes = $model->getAllPostes();
$agences = $model->getAllAgences();

$editPoste = null;
if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $editPoste = $model->getPosteById((int)$_GET['edit']);
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Postes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">

    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">

    <style>
        .posts-wrapper {
            max-width: 1100px;
            margin: 0 auto;
            padding: 32px 20px;
        }
        .posts-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .posts-title {
            font-family: var(--font-display);
            font-size: 28px;
            color: var(--text-primary);
            margin: 0;
        }
        .posts-subtitle {
            color: var(--text-secondary);
            font-size: 14px;
            margin-top: 6px;
        }
        .post-form {
            background: #fff;
            border: 1px solid var(--glass-border);
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 12px 30px rgba(26,58,122,0.06);
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-group.full {
            grid-column: 1 / -1;
        }
        .form-group label {
            font-size: 13px;
            color: var(--text-primary);
            font-weight: 600;
        }
        .form-control {
            width: 100%;
            min-height: 44px;
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            background: rgba(26, 58, 122, 0.03);
            color: var(--text-primary);
            padding: 10px 12px;
            font-size: 14px;
            outline: none;
        }
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
        }
        .btn-main {
            border: none;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: #fff;
            padding: 11px 16px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-light {
            border: none;
            background: rgba(26,58,122,0.08);
            color: var(--text-primary);
            padding: 11px 16px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 18px;
        }
        .post-card {
            background: #fff;
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            box-shadow: 0 10px 30px rgba(26, 58, 122, 0.06);
            overflow: hidden;
        }
        .post-card-head {
            padding: 18px 20px 12px;
            border-bottom: 1px solid var(--glass-border);
        }
        .post-author {
            font-family: var(--font-display);
            font-size: 16px;
            color: var(--text-primary);
            margin: 0 0 4px;
        }
        .post-agency {
            font-size: 13px;
            color: var(--text-secondary);
        }
        .post-card-body {
            padding: 18px 20px 20px;
        }
        .post-content {
            color: var(--text-primary);
            line-height: 1.7;
            margin-bottom: 14px;
            white-space: pre-wrap;
        }
        .post-date {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 12px;
        }
        .post-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
        }
        .post-meta span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            background: rgba(26, 58, 122, 0.05);
            color: var(--text-primary);
            border: 1px solid var(--glass-border);
        }
        .post-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-edit, .btn-delete {
            border: none;
            padding: 10px 14px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }
        .btn-edit {
            background: rgba(255, 107, 26, 0.12);
            color: var(--accent-dark);
        }
        .btn-delete {
            background: rgba(230, 57, 70, 0.10);
            color: var(--danger);
        }
        .inline-delete {
            display: inline;
        }
        .empty-state {
            background: #fff;
            border: 1px dashed var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 32px 20px;
            text-align: center;
            color: var(--text-secondary);
        }
        .success-box {
            background: #ecfdf3;
            color: #166534;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 16px;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="posts-wrapper">
    <div class="posts-header">
        <div>
            <h2 class="posts-title">Gestion des postes</h2>
            <div class="posts-subtitle">Créer, modifier et supprimer les publications</div>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="success-box">
            <?php
            if ($_GET['success'] === 'create') echo 'Poste ajouté avec succès.';
            elseif ($_GET['success'] === 'update') echo 'Poste modifié avec succès.';
            elseif ($_GET['success'] === 'delete') echo 'Poste supprimé avec succès.';
            ?>
        </div>
    <?php endif; ?>

    <form class="post-form" method="post" action="../../controller/PosteController.php?action=<?php echo $editPoste ? 'update' : 'create'; ?>">
        <?php if ($editPoste): ?>
            <input type="hidden" name="id_poste" value="<?php echo h($editPoste['id_poste']); ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div class="form-group full">
                <label for="contenu">Contenu</label>
                <textarea id="contenu" name="contenu" class="form-control" required><?php echo h($editPoste['contenu'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="auteur">Auteur</label>
                <input id="auteur" name="auteur" class="form-control" required value="<?php echo h($editPoste['auteur'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="date_publication">Date de publication</label>
                <input id="date_publication" name="date_publication" class="form-control" type="date" required value="<?php echo h($editPoste['date_publication'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="note">Note</label>
                <input id="note" name="note" class="form-control" type="number" min="1" max="5" step="1" value="<?php echo h($editPoste['note'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="nb_likes">Likes</label>
                <input id="nb_likes" name="nb_likes" class="form-control" type="number" value="<?php echo h($editPoste['nb_likes'] ?? 0); ?>">
            </div>

            <div class="form-group">
                <label for="nb_commentaires">Commentaires</label>
                <input id="nb_commentaires" name="nb_commentaires" class="form-control" type="number" value="<?php echo h($editPoste['nb_commentaires'] ?? 0); ?>">
            </div>

            <div class="form-group">
                <label for="id_agence">Agence</label>
                <select id="id_agence" name="id_agence" class="form-control" required>
                    <option value="">Choisir une agence</option>
                    <?php foreach ($agences as $agence): ?>
                        <option value="<?php echo h($agence['id_agence']); ?>"
                            <?php echo ((string)($editPoste['id_agence'] ?? '') === (string)$agence['id_agence']) ? 'selected' : ''; ?>>
                            <?php echo h($agence['nom_agence']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn-main" type="submit">
                <?php echo $editPoste ? 'Mettre à jour' : 'Ajouter poste'; ?>
            </button>

            <?php if ($editPoste): ?>
                <a class="btn-light" href="postes.php">Annuler</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (count($postes) > 0): ?>
        <div class="posts-grid">
            <?php foreach ($postes as $p): ?>
                <div class="post-card">
                    <div class="post-card-head">
                        <div>
                            <div class="post-author"><?php echo h($p['auteur']); ?></div>
                            <div class="post-agency"><?php echo h($p['agence']); ?></div>
                        </div>
                    </div>

                    <div class="post-card-body">
                        <div class="post-content"><?php echo h($p['contenu']); ?></div>
                        <div class="post-date"><?php echo h($p['date_publication']); ?></div>

                        <div class="post-meta">
                            <span>⭐ <?php echo h($p['note']); ?></span>
                            <span>❤️ <?php echo h($p['nb_likes']); ?></span>
                            <span>💬 <?php echo h($p['nb_commentaires']); ?></span>
                        </div>

                        <div class="post-actions">
                            <a class="btn-edit" href="postes.php?edit=<?php echo h($p['id_poste']); ?>">Modifier</a>

                            <form class="inline-delete" method="post" action="../../controller/PosteController.php?action=delete" onsubmit="return confirm('Voulez-vous vraiment supprimer ce poste ?');">
                                <input type="hidden" name="id_poste" value="<?php echo h($p['id_poste']); ?>">
                                <button class="btn-delete" type="submit">Supprimer</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">Aucun poste disponible.</div>
    <?php endif; ?>
</div>

</body>
</html>

