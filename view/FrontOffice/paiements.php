<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}

require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();

$db = config::getConnexion();
$userId = (int)($_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 0);
$statutFilter = $_GET['statut'] ?? '';

$where = 'WHERE c.id_user = :user';
$params = [':user' => $userId];
if ($statutFilter !== '' && in_array($statutFilter, ['en_attente','valide','refuse','rembourse'])) {
    $where .= ' AND p.statut = :statut';
    $params[':statut'] = $statutFilter;
}

$stmt = $db->prepare("
    SELECT p.*, c.numero_contrat, c.type_contrat, c.nom_categorie
    FROM paiement p
    JOIN contrat c ON p.id_offre = c.id_contrat
    $where
    ORDER BY p.date_paiement DESC
");
$stmt->execute($params);
$paiements = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statutBadge = [
    'en_attente' => 'badge-warning',
    'valide' => 'badge-success',
    'refuse' => 'badge-danger',
    'rembourse' => 'badge-info',
];

$methodeIcone = [
    'carte' => 'bi-credit-card',
    'virement' => 'bi-bank',
    'mobile' => 'bi-phone',
    'stripe' => 'bi-stripe',
];
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Mes Paiements — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
    <style>
        .payment-card { background: #fff; border-radius: 16px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.04); transition: transform .2s, box-shadow .2s; }
        .payment-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
        .payment-ref { font-size: 13px; font-weight: 700; color: var(--accent); font-family: monospace; }
        .payment-date { font-size: 12px; color: var(--text-secondary); }
        .payment-montant { font-size: 18px; font-weight: 800; color: var(--text-primary); }
        .statut-pill { display: inline-flex; align-items: center; gap: 4px; padding: 4px 12px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
        .statut-pill.valide { background: #d1fae5; color: #059669; }
        .statut-pill.en_attente { background: #fef3c7; color: #d97706; }
        .statut-pill.refuse { background: #fee2e2; color: #dc2626; }
        .statut-pill.rembourse { background: #e0f2fe; color: #0284c7; }
        .filter-btn { padding: 6px 16px; border-radius: 999px; font-size: 12px; font-weight: 600; border: 1px solid #e2e8f0; background: #fff; color: var(--text-secondary); cursor: pointer; transition: all .2s; text-decoration: none; }
        .filter-btn:hover, .filter-btn.active { background: var(--accent); color: #fff; border-color: var(--accent); }
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state i { font-size: 48px; color: #cbd5e1; margin-bottom: 16px; }
        .empty-state p { color: var(--text-secondary); font-size: 14px; }
    </style>
</head>
<body>
<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">
    <?php require_once __DIR__.'/assets/includes/navbar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title-main">Mes Paiements</div>
                <div class="page-breadcrumb"><i class="bi bi-house"></i> Accueil <i class="bi bi-chevron-right"></i> Paiements</div>
            </div>
        </div>
        <div class="content">
            <div class="section-header">
                <div>
                    <div class="section-title">Historique des paiements</div>
                    <div class="section-sub">Vos transactions récentes</div>
                </div>
            </div>

            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:24px;">
                <a href="paiements.php" class="filter-btn <?= $statutFilter === '' ? 'active' : '' ?>">Tous</a>
                <a href="paiements.php?statut=valide" class="filter-btn <?= $statutFilter === 'valide' ? 'active' : '' ?>">Validés</a>
                <a href="paiements.php?statut=en_attente" class="filter-btn <?= $statutFilter === 'en_attente' ? 'active' : '' ?>">En attente</a>
                <a href="paiements.php?statut=refuse" class="filter-btn <?= $statutFilter === 'refuse' ? 'active' : '' ?>">Refusés</a>
                <a href="paiements.php?statut=rembourse" class="filter-btn <?= $statutFilter === 'rembourse' ? 'active' : '' ?>">Remboursés</a>
            </div>

            <?php if (empty($paiements)): ?>
                <div class="empty-state">
                    <i class="bi bi-credit-card"></i>
                    <p>Aucun paiement trouvé.</p>
                </div>
            <?php else: ?>
                <div style="display:grid; gap:12px;">
                    <?php foreach ($paiements as $p): ?>
                        <?php
                            $badgeClass = $statutBadge[$p['statut']] ?? 'badge-secondary';
                            $icone = $methodeIcone[$p['methode']] ?? 'bi-credit-card';
                        ?>
                        <div class="payment-card" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
                            <div style="display:flex; align-items:center; gap:16px; flex:1; min-width:200px;">
                                <div style="width:44px; height:44px; border-radius:12px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; font-size:20px; color:var(--accent);">
                                    <i class="<?= $icone ?>"></i>
                                </div>
                                <div>
                                    <div class="payment-ref"><?= htmlspecialchars($p['reference'] ?? '') ?></div>
                                    <div class="payment-date"><?= date('d/m/Y H:i', strtotime($p['date_paiement'] ?? '')) ?></div>
                                </div>
                            </div>
                            <div style="flex:1; min-width:120px;">
                                <div style="font-size:12px; color:var(--text-secondary);"><?= htmlspecialchars($p['type_contrat'] ?? '') ?></div>
                                <div style="font-size:12px; color:var(--text-secondary);"><?= htmlspecialchars($p['numero_contrat'] ?? '') ?></div>
                            </div>
                            <div style="text-align:right;">
                                <div class="payment-montant"><?= number_format((float)$p['montant'], 3, ',', ' ') ?> <span style="font-size:11px; color:var(--text-secondary);">DT</span></div>
                                <div style="margin-top:6px;">
                                    <span class="statut-pill <?= $p['statut'] ?>"><?= match($p['statut']) { 'valide'=>'✅ Validé', 'en_attente'=>'⏳ En attente', 'refuse'=>'❌ Refusé', 'rembourse'=>'↩️ Remboursé', default=>$p['statut'] } ?></span>
                                </div>
                            </div>
                            <div style="display:flex; gap:8px;">
                                <?php if (in_array($p['statut'], ['valide', 'rembourse'])): ?>
                                    <a href="recu_paiement.php?id=<?= (int)$p['id_paiement'] ?>" class="btn btn-sm btn-outline-primary rounded-pill" target="_blank" style="font-size:12px; padding:6px 14px; text-decoration:none;">
                                        <i class="bi bi-download"></i> Reçu
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
