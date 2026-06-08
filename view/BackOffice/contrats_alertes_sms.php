<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controller/ContratController.php';
if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
$base = defined('BASE_URL') ? BASE_URL : '';

$controller = new ContratController();
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
$days = max(1, min($days, 365));

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $days = isset($_POST['days']) ? (int)$_POST['days'] : 30;
    $days = max(1, min($days, 365));
    $result = $controller->envoyerAlertesSmsExpiration($days);
}

$contrats = $controller->getContratsExpirantBientot($days);
$alerts = $controller->getSmsAlerts();

function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function dateFr($date): string
{
    if (!$date) return '—';
    return date('d/m/Y', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alertes SMS contrats — Protex</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/variables.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/base.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/layout.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/contrats.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/admin-users.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/animations.css">
    <style>
        .notice { padding:14px 16px; border-radius:16px; margin-bottom:18px; font-weight:700; }
        .notice.ok { background:#e6fffa; color:#047857; border: 1px solid #34d399; }
        .notice.warn { background:#fff7ed; color:#c2410c; border: 1px solid #fdba74; }
        .tools { display:flex; gap:12px; align-items:center; flex-wrap:wrap; padding: 18px 22px; border-bottom: 1px solid var(--border); }
        .tools input { padding:13px 16px; border:1px solid var(--border); border-radius:14px; font-weight:700; min-width:120px; background: rgba(255,255,255,0.05); color: inherit; }
        .badge-orange { background:#ffedd5; color:#c2410c; }
        .badge-blue { background:#dbeafe; color:#1d4ed8; }
        .badge-green { background:#dcfce7; color:#15803d; }
    </style>
</head>
<body>

<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">
    <?php require_once __DIR__.'/assets/includes/sidebar.php'; ?>
    <main class="main">

        <div class="topbar">
            <div>
                <div class="topbar-title">Alertes SMS contrats</div>
                <div class="topbar-sub" id="topbarDate"></div>
            </div>
            <div class="topbar-actions">
                <a href="#" class="topbar-btn" title="Notifications">
                    <i class="bi bi-bell"></i>
                    <span class="notif-dot"></span>
                </a>
                <a href="#" class="topbar-btn" title="Aide">
                    <i class="bi bi-question-circle"></i>
                </a>
            </div>
        </div>

        <div class="content">

            <div class="page-header-bar">
                <div>
                    <div class="page-title">Alertes SMS</div>
                    <div class="page-breadcrumb">
                        <i class="bi bi-house"></i>
                        <a href="dashboard.php">Accueil</a>
                        <i class="bi bi-chevron-right" style="font-size:10px;"></i>
                        <a href="contrats_back.php">Contrats</a>
                        <i class="bi bi-chevron-right" style="font-size:10px;"></i>
                        <span>Alertes SMS</span>
                    </div>
                </div>
                <div>
                    <a class="btn btn-outline" href="contrats_back.php"><i class="bi bi-arrow-left"></i> Retour contrats</a>
                </div>
            </div>

            <?php if ($result): ?>
                <div class="notice ok">
                    <i class="bi bi-check-circle"></i> Traitement terminé : <?= (int)$result['envoyes'] ?> alerte(s) SMS simulée(s),
                    <?= (int)$result['deja_envoyes'] ?> déjà envoyée(s),
                    <?= (int)$result['sans_telephone'] ?> sans téléphone.
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                    <div class="stat-value"><?= count($contrats) ?></div>
                    <div class="stat-label">Contrats à surveiller</div>
                </div>
                <div class="stat-card gold">
                    <div class="stat-icon"><i class="bi bi-chat-dots"></i></div>
                    <div class="stat-value"><?= count($alerts) ?></div>
                    <div class="stat-label">SMS simulés session</div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon"><i class="bi bi-calendar-event"></i></div>
                    <div class="stat-value"><?= (int)$days ?></div>
                    <div class="stat-label">Jours avant expiration</div>
                </div>
                <div class="stat-card red">
                    <div class="stat-icon"><i class="bi bi-phone-vibrate"></i></div>
                    <div class="stat-value">SMS</div>
                    <div class="stat-label">Canal d’alerte</div>
                </div>
            </div>

            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header">
                    <div class="card-title">
                        <i class="bi bi-bell"></i> Lancer les alertes
                    </div>
                </div>
                <form method="POST" class="tools">
                    <label for="days"><strong>Alerter les contrats qui expirent dans</strong></label>
                    <input type="number" name="days" id="days" value="<?= (int)$days ?>" min="1" max="365">
                    <button class="btn btn-primary" type="submit" style="background: linear-gradient(135deg, #FF6B1A, #ff8a3d); border: none;">
                        <i class="bi bi-send"></i> Envoyer / simuler SMS
                    </button>
                    <span class="badge badge-blue">Anti-doublon activé</span>
                </form>
                <div style="padding: 18px 22px;">
                    <div class="notice <?= defined('INFOBIP_API_KEY') && INFOBIP_API_KEY !== 'votre_cle_infobip' ? 'ok' : 'warn' ?>" style="margin-bottom:0">
                        <?php if (defined('INFOBIP_API_KEY') && INFOBIP_API_KEY !== 'votre_cle_infobip'): ?>
                            <i class="bi bi-check-circle"></i> <strong>Mode LIVE activé</strong> : Les SMS sont réellement envoyés via l’API Infobip.
                        <?php else: ?>
                            <i class="bi bi-info-circle"></i> <strong>Mode SIMULATION</strong> : La clé API Infobip n'est pas configurée. Les SMS sont simulés et enregistrés dans la table <code>sms_alerts</code> pour test.
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-list-check"></i> Contrats actifs proches de l’expiration</div>
                </div>
                <div class="table-wrap">
                    <?php if (empty($contrats)): ?>
                        <div style="text-align:center;padding:48px 20px;color:var(--text-secondary);">
                            <i class="bi bi-folder-x" style="font-size:36px;display:block;margin-bottom:10px;opacity:0.3;"></i>
                            <p>Aucun contrat actif proche de l’expiration.</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                            <tr>
                                <th>N° contrat</th><th>Client</th><th>Téléphone</th><th>Catégorie</th><th>Date fin</th><th>Reste</th><th>Statut alerte</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($contrats as $c): ?>
                                <?php $sent = $controller->smsAlertAlreadySent((int)$c['id_contrat']); ?>
                                <tr>
                                    <td style="color:var(--accent);font-weight:700;"><?= h($c['numero_contrat']) ?></td>
                                    <td><?= h(trim(($c['prenom'] ?? '') . ' ' . ($c['nom'] ?? ''))) ?></td>
                                    <td><?= h($c['telephone_final'] ?: '—') ?></td>
                                    <td><span class="badge"><?= h($c['nom_categorie'] ?? '—') ?></span></td>
                                    <td><?= h(dateFr($c['date_fin_contrat'])) ?></td>
                                    <td><span class="badge badge-orange"><?= (int)$c['jours_restants'] ?> jour(s)</span></td>
                                    <td><?= $sent ? '<span class="badge badge-green">Déjà alerté</span>' : '<span class="badge badge-blue">À alerter</span>' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-clock-history"></i> Historique SMS de la session</div>
                </div>
                <div class="table-wrap">
                    <?php if (empty($alerts)): ?>
                        <div style="text-align:center;padding:48px 20px;color:var(--text-secondary);">
                            <i class="bi bi-clock-history" style="font-size:36px;display:block;margin-bottom:10px;opacity:0.3;"></i>
                            <p>Aucune alerte SMS simulée pendant cette session.</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                            <tr><th>Date</th><th>N° contrat</th><th>Téléphone</th><th>Message</th><th>Statut</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($alerts as $a): ?>
                                <tr>
                                    <td><?= h(date('d/m/Y H:i', strtotime($a['date_envoi']))) ?></td>
                                    <td style="color:var(--accent);font-weight:700;"><?= h($a['numero_contrat'] ?? ('#' . $a['id_contrat'])) ?></td>
                                    <td><?= h($a['telephone']) ?></td>
                                    <td style="color:var(--text-secondary); max-width: 300px;"><?= h($a['message']) ?></td>
                                    <td><span class="badge badge-green"><?= h($a['statut']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
    document.getElementById('topbarDate').textContent = new Date().toLocaleDateString('fr-FR', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    });
</script>
</body>
</html>
