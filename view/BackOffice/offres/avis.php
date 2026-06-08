<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers/SessionGuard.php';
require_once __DIR__ . '/../../helpers/RoleHelper.php';
SessionGuard::requireBackoffice();

$db = config::getConnexion();

$message = $_GET['message'] ?? '';
$erreur  = $_GET['erreur'] ?? '';

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$total = (int)$db->query("SELECT COUNT(*) FROM avis_offre")->fetchColumn();
$maxPage = max(1, (int)ceil($total / $limit));

$stmt = $db->prepare("
    SELECT a.*, u.nom, u.prenom, u.email, o.nom_offre
    FROM avis_offre a
    JOIN user u ON a.id_client = u.id_user
    JOIN offre o ON a.id_offre = o.id_offre
    ORDER BY a.date_avis DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$avisList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$BASE_URL = defined('BASE_URL') ? BASE_URL : '';
$backCtrl = $BASE_URL ? BASE_URL . '/controller' : '.';
$backPath = $BASE_URL ? BASE_URL . '/view/BackOffice' : '..';
$pageTitle = 'Modération des avis';
$activePage = 'offres';
require_once __DIR__ . '/../assets/includes/header.php';
require_once __DIR__ . '/../assets/includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <div>
            <h1><i class="bi bi-shield-lock"></i> Modération des avis</h1>
            <p class="page-sub">Gérez les avis clients sur les offres d'assurance</p>
        </div>
        <div class="page-actions">
            <a href="<?= $backCtrl ?>/OffreController.php?action=index" class="btn-action" style="background:rgba(255,255,255,0.08);">
                <i class="bi bi-arrow-left"></i> Retour aux offres
            </a>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($erreur): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <div class="table-card">
        <?php if (empty($avisList)): ?>
        <div style="text-align:center;padding:60px 20px;color:var(--text-secondary);">
            <i class="bi bi-chat-square-text" style="font-size:48px;opacity:0.3;"></i>
            <p style="margin-top:16px;font-size:15px;">Aucun avis pour le moment.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Offre</th>
                        <th>Note</th>
                        <th>Commentaire</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($avisList as $a): ?>
                    <tr class="<?= $a['hidden'] ? 'row-muted' : '' ?>">
                        <td>
                            <strong><?= htmlspecialchars($a['prenom'] . ' ' . $a['nom']) ?></strong>
                            <div style="font-size:11px;color:var(--text-secondary);"><?= htmlspecialchars($a['email']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($a['nom_offre']) ?></td>
                        <td>
                            <span class="stars-inline" style="color:#f59e0b;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star<?= $i <= $a['note'] ? '-fill' : '' ?>"></i>
                                <?php endfor; ?>
                            </span>
                        </td>
                        <td style="max-width:300px;">
                            <span class="comment-preview"><?= htmlspecialchars(mb_substr($a['commentaire'], 0, 80)) ?><?= mb_strlen($a['commentaire']) > 80 ? '…' : '' ?></span>
                            <?php if (mb_strlen($a['commentaire']) > 80): ?>
                            <span class="comment-full" style="display:none;"><?= htmlspecialchars($a['commentaire']) ?></span>
                            <a href="#" onclick="toggleComment(this);return false;" style="font-size:11px;color:var(--accent);">Lire plus</a>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px;white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($a['date_avis'])) ?></td>
                        <td>
                            <?php if ($a['hidden']): ?>
                            <span class="status-badge suspendue"><i class="bi bi-eye-slash"></i> Masqué</span>
                            <?php else: ?>
                            <span class="status-badge active"><i class="bi bi-eye"></i> Visible</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-group">
                                <a href="<?= $backCtrl ?>/OffreController.php?action=toggleAvis&id=<?= (int)$a['id'] ?>&hidden=<?= $a['hidden'] ? 0 : 1 ?>"
                                   class="action-icon <?= $a['hidden'] ? 'play' : 'pause' ?>"
                                   title="<?= $a['hidden'] ? 'Afficher' : 'Masquer' ?>">
                                    <i class="bi bi-<?= $a['hidden'] ? 'eye' : 'eye-slash' ?>"></i>
                                </a>
                                <a href="<?= $backCtrl ?>/OffreController.php?action=deleteAvis&id=<?= (int)$a['id'] ?>"
                                   class="action-icon delete"
                                   title="Supprimer"
                                   onclick="return confirm('Supprimer définitivement cet avis ?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($maxPage > 1): ?>
        <div class="pagination" style="padding:20px;display:flex;justify-content:center;gap:8px;">
            <?php for ($p = 1; $p <= $maxPage; $p++): ?>
            <a href="?page=<?= $p ?>" class="page-link <?= $p === $page ? 'active' : '' ?>"
               style="padding:8px 14px;border-radius:8px;background:<?= $p === $page ? 'var(--accent)' : 'rgba(255,255,255,0.06)' ?>;color:<?= $p === $page ? '#fff' : 'var(--text-secondary)' ?>;text-decoration:none;font-size:13px;">
                <?= $p ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<style>
.row-muted { opacity: 0.55; }
.comment-preview { font-size:13px;color:var(--text-secondary); }
.status-badge { font-size:11px;font-weight:600;padding:4px 10px;border-radius:999px;display:inline-flex;align-items:center;gap:4px; }
.status-badge.active { background:rgba(16,185,129,0.15);color:#34d399; }
.status-badge.suspendue { background:rgba(239,68,68,0.15);color:#f87171; }
.action-group { display:flex;gap:6px; }
.action-icon { width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;color:var(--text-secondary);text-decoration:none;transition:.2s; }
.action-icon:hover { background:rgba(255,255,255,0.1);color:#fff; }
.action-icon.pause:hover { background:rgba(234,179,8,0.2);color:#facc15; }
.action-icon.play:hover { background:rgba(16,185,129,0.2);color:#34d399; }
.action-icon.delete:hover { background:rgba(239,68,68,0.2);color:#f87171; }
</style>

<script>
function toggleComment(el) {
    const row = el.parentElement;
    const preview = row.querySelector('.comment-preview');
    const full = row.querySelector('.comment-full');
    if (full.style.display === 'none') {
        full.style.display = 'inline';
        preview.style.display = 'none';
        el.textContent = 'Réduire';
    } else {
        full.style.display = 'none';
        preview.style.display = 'inline';
        el.textContent = 'Lire plus';
    }
}
</script>

<?php require_once __DIR__ . '/../assets/includes/footer.php'; ?>
