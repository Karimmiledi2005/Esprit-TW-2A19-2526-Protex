<?php
/**
 * MODULE 8 — RC2 — Gestion Templates de Réponse
 * BackOffice template management for predefined responses
 */

session_start();
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../helpers/SessionGuard.php';
require_once __DIR__ . '/../../helpers/RoleHelper.php';

SessionGuard::requireBackoffice();
$user = $_SESSION['user'];

$db = config::getConnexion();

// Handle CRUD operations
$action = $_POST['action'] ?? '';

if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? '';
    if ($csrf !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    $titre = $_POST['titre'] ?? '';
    $contenu = $_POST['contenu'] ?? '';
    $categorie = $_POST['categorie'] ?? '';

    if ($id) {
        // Update
        $db->prepare("UPDATE reponse_template SET titre = ?, contenu = ?, categorie = ? WHERE id = ? AND (created_by = ? OR ? = 1)")
            ->execute([$titre, $contenu, $categorie, $id, $user['id_user'], RoleHelper::isSuperAdmin()]);
    } else {
        // Create
        $db->prepare("INSERT INTO reponse_template (titre, contenu, categorie, created_by) VALUES (?, ?, ?, ?)")
            ->execute([$titre, $contenu, $categorie, $user['id_user']]);
    }
    
    $_SESSION['success'] = $id ? 'Modèle mis à jour' : 'Modèle créé';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $db->prepare("DELETE FROM reponse_template WHERE id = ? AND (created_by = ? OR ? = 1)")
        ->execute([$id, $user['id_user'], RoleHelper::isSuperAdmin()]);
    $_SESSION['success'] = 'Modèle supprimé';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Get all templates
$stmt = $db->prepare("
    SELECT t.*, u.nom, u.prenom
    FROM reponse_template t
    JOIN `user` u ON t.created_by = u.id_user
    ORDER BY t.categorie, t.created_at DESC
");
$stmt->execute();
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get template for editing if requested
$editTemplate = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM reponse_template WHERE id = ?");
    $stmt->execute([$editId]);
    $editTemplate = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Templates de Réponse</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .template-card {
            background: white;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            border-left: 4px solid #667eea;
        }
        .template-card h5 {
            margin-bottom: 8px;
        }
        .template-card p {
            font-size: 13px;
            color: #666;
            margin-bottom: 0;
        }
        .form-container {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .badge {
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <div class="row">
            <div class="col-md-8">
                <h1 class="mb-4">📋 Templates de Réponse</h1>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <!-- Templates by category -->
                <?php 
                $categories = ['accusé', 'refus', 'complement', 'resolution', 'autre'];
                $catLabels = ['accusé' => 'Accusé de réception', 'refus' => 'Refus couverture', 'complement' => 'Demande compléments', 'resolution' => 'Résolution', 'autre' => 'Autre'];
                
                foreach ($categories as $cat): 
                    $catTemplates = array_filter($templates, fn($t) => $t['categorie'] === $cat);
                    if (empty($catTemplates)) continue;
                ?>
                    <div class="mb-4">
                        <h4 class="badge bg-secondary"><?php echo $catLabels[$cat]; ?></h4>
                        <?php foreach ($catTemplates as $t): ?>
                            <div class="template-card">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5><?php echo htmlspecialchars($t['titre']); ?></h5>
                                        <p><?php echo htmlspecialchars(substr($t['contenu'], 0, 100)); ?>...</p>
                                        <small class="text-muted">Par <?php echo $t['nom'] . ' ' . $t['prenom']; ?> le <?php echo date('d/m/Y', strtotime($t['created_at'])); ?></small>
                                    </div>
                                    <div>
                                        <a href="?edit=<?php echo $t['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                            <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="col-md-4">
                <div class="form-container">
                    <h4><?php echo $editTemplate ? 'Modifier' : 'Nouveau'; ?> Template</h4>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                        <?php if ($editTemplate): ?>
                            <input type="hidden" name="id" value="<?php echo $editTemplate['id']; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Titre</label>
                            <input type="text" name="titre" class="form-control" value="<?php echo htmlspecialchars($editTemplate['titre'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catégorie</label>
                            <select name="categorie" class="form-select" required>
                                <option value="">Sélectionnez...</option>
                                <option value="accusé" <?php echo ($editTemplate['categorie'] ?? '') === 'accusé' ? 'selected' : ''; ?>>Accusé de réception</option>
                                <option value="refus" <?php echo ($editTemplate['categorie'] ?? '') === 'refus' ? 'selected' : ''; ?>>Refus couverture</option>
                                <option value="complement" <?php echo ($editTemplate['categorie'] ?? '') === 'complement' ? 'selected' : ''; ?>>Demande compléments</option>
                                <option value="resolution" <?php echo ($editTemplate['categorie'] ?? '') === 'resolution' ? 'selected' : ''; ?>>Résolution</option>
                                <option value="autre" <?php echo ($editTemplate['categorie'] ?? '') === 'autre' ? 'selected' : ''; ?>>Autre</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contenu</label>
                            <textarea name="contenu" class="form-control" rows="8" required><?php echo htmlspecialchars($editTemplate['contenu'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save"></i> <?php echo $editTemplate ? 'Modifier' : 'Créer'; ?>
                        </button>
                        
                        <?php if ($editTemplate): ?>
                            <a href="?" class="btn btn-secondary w-100 mt-2">Annuler</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
