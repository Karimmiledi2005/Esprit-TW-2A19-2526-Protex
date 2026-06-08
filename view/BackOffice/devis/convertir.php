<?php
/**
 * view/BackOffice/devis/convertir.php
 * Convertir un devis accepté en contrat — Back-office Protex
 */

if (!defined('BASE_URL')) define('BASE_URL', (defined('BASE_URL') ? BASE_URL : ''));
$base = (defined('BASE_URL') ? BASE_URL : '');

function cE($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function cRef($id): string { return 'DEV-2026-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Convertir Devis <?= cE(cRef($devis['id_devis'] ?? 0)) ?> en Contrat — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/variables.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/base.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/layout.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/admin-users.css">
    <style>
        .convert-hero {
            padding: 30px; border-radius: 26px;
            background: radial-gradient(circle at 80% 20%, rgba(25,135,84,.14), transparent 30%),
                        radial-gradient(circle at 20% 80%, rgba(0,194,255,.08), transparent 30%),
                        linear-gradient(135deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
            border: 1px solid rgba(255,255,255,.08); margin-bottom: 24px;
            display: flex; align-items: center; gap: 24px; flex-wrap: wrap;
        }
        .convert-icon {
            width: 80px; height: 80px; border-radius: 24px;
            display: grid; place-items: center; font-size: 36px;
            background: linear-gradient(135deg, rgba(25,135,84,.95), rgba(34,197,94,.85));
            box-shadow: 0 16px 32px rgba(25,135,84,.25);
        }
        .convert-body { flex: 1; }
        .convert-title { font-size: 26px; font-weight: 800; color: #fff; margin-bottom: 6px; }
        .convert-sub { color: var(--text-secondary); font-size: 14px; }

        .convert-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
        @media (max-width: 900px) { .convert-grid { grid-template-columns: 1fr; } }

        .convert-card {
            border-radius: 22px; padding: 24px;
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.03);
        }
        .convert-card-title {
            font-size: 15px; font-weight: 800; color: #fff; margin-bottom: 18px;
            display: flex; align-items: center; gap: 10px;
            padding-bottom: 14px; border-bottom: 1px solid rgba(255,255,255,.06);
        }
        .convert-card-title i { color: var(--accent); font-size: 17px; }

        .conv-pairs { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .conv-pair { padding: 14px; border-radius: 14px; background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.06); }
        .conv-pair-label { color: var(--text-secondary); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 7px; }
        .conv-pair-value { color: #fff; font-size: 14px; font-weight: 700; }

        .period-options { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px; }
        .period-opt {
            padding: 16px; border-radius: 14px; cursor: pointer;
            border: 1px solid rgba(255,255,255,.1);
            background: rgba(255,255,255,.03); text-align: center; transition: all .2s;
        }
        .period-opt:hover { border-color: rgba(255,107,26,.3); }
        .period-opt.selected { border-color: var(--accent); background: rgba(255,107,26,.08); }
        .period-opt input { display: none; }
        .period-opt .po-name { font-size: 14px; font-weight: 700; color: #fff; margin-bottom: 4px; }
        .period-opt .po-price { font-size: 22px; font-weight: 900; color: #fff; }
        .period-opt.selected .po-price { color: var(--accent); }

        .convert-amount {
            padding: 20px; border-radius: 16px;
            background: rgba(25,135,84,.08); border: 1px solid rgba(25,135,84,.2);
            text-align: center; margin-bottom: 20px;
        }
        .convert-amount-label { color: var(--text-secondary); font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; margin-bottom: 6px; }
        .convert-amount-value { font-size: 36px; font-weight: 900; color: #90f1bc; }
        .convert-amount-sub { color: var(--text-secondary); font-size: 12px; margin-top: 4px; }

        .notes-field {
            width: 100%; padding: 12px 14px; border-radius: 12px;
            border: 1px solid rgba(255,255,255,.1); background: rgba(255,255,255,.04);
            color: #fff; font-size: 13px; font-family: 'DM Sans', sans-serif;
            resize: vertical; min-height: 80px; margin-bottom: 18px;
        }
        .notes-field::placeholder { color: rgba(255,255,255,.25); }
        .notes-field:focus { outline: none; border-color: var(--accent); }

        .convert-actions { display: flex; gap: 12px; flex-wrap: wrap; justify-content: flex-end; }
        .btn-convert {
            padding: 14px 32px; border-radius: 14px; border: none; cursor: pointer;
            background: linear-gradient(135deg, #198754, #22c55e); color: #fff;
            font-size: 15px; font-weight: 800; font-family: 'Sora', sans-serif;
            box-shadow: 0 8px 24px rgba(25,135,84,.25);
        }
        .btn-convert:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(25,135,84,.35); }
        .btn-back {
            padding: 14px 24px; border-radius: 14px;
            border: 1px solid rgba(255,255,255,.15); background: rgba(255,255,255,.05);
            color: #fff; font-size: 14px; font-weight: 700; cursor: pointer;
            text-decoration: none; font-family: 'Sora', sans-serif;
        }
        .btn-back:hover { background: rgba(255,255,255,.1); }

        .action-bar { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; padding: 16px 20px; border-radius: 18px; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.07); }
        .btn-nav { padding: 9px 18px; border-radius: 12px; border: 1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.05); color: #fff; text-decoration: none; font-size: 13px; font-weight: 700; }
    </style>
</head>
<body>
<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="layout">
    <?php include __DIR__ . '/../assets/includes/sidebar.php'; ?>

    <main class="main">
        <div class="topbar">
            <h1 class="topbar-title">📄 Convertir en contrat</h1>
        </div>

        <div class="content">
            <div class="action-bar">
                <a href="<?= $base ?>/controller/DevisController.php?action=details&id=<?= (int)$devis['id_devis'] ?>" class="btn-nav"><i class="bi bi-arrow-left"></i> Détail devis</a>
                <a href="<?= $base ?>/controller/DevisController.php?action=index" class="btn-nav"><i class="bi bi-grid"></i> Liste des devis</a>
            </div>

            <div class="convert-hero">
                <div class="convert-icon">📄</div>
                <div class="convert-body">
                    <div class="convert-title">Devis accepté → Contrat</div>
                    <div class="convert-sub"><?= cE(cRef($devis['id_devis'])) ?> — <?= cE(($devis['prenom'] ?? '') . ' ' . ($devis['nom'] ?? '')) ?></div>
                </div>
            </div>

            <div class="convert-grid">
                <div class="convert-card">
                    <div class="convert-card-title"><i class="bi bi-file-earmark-text"></i> Résumé du devis</div>
                    <div class="conv-pairs">
                        <div class="conv-pair">
                            <div class="conv-pair-label">Client</div>
                            <div class="conv-pair-value"><?= cE(($devis['prenom'] ?? '') . ' ' . ($devis['nom'] ?? '')) ?></div>
                        </div>
                        <div class="conv-pair">
                            <div class="conv-pair-label">Email</div>
                            <div class="conv-pair-value"><?= cE($devis['email'] ?? '—') ?></div>
                        </div>
                        <div class="conv-pair">
                            <div class="conv-pair-label">Offre</div>
                            <div class="conv-pair-value"><?= cE($devis['nom_offre'] ?? '—') ?></div>
                        </div>
                        <div class="conv-pair">
                            <div class="conv-pair-label">Type</div>
                            <div class="conv-pair-value"><?= cE(ucfirst($devis['type_assurance'] ?? '')) ?></div>
                        </div>
                        <div class="conv-pair">
                            <div class="conv-pair-label">Montant estimé</div>
                            <div class="conv-pair-value"><?= $devis['montant_estime'] ? number_format((float)$devis['montant_estime'], 3, '.', ' ') . ' DT' : '—' ?></div>
                        </div>
                        <div class="conv-pair">
                            <div class="conv-pair-label">Date demande</div>
                            <div class="conv-pair-value"><?= date('d/m/Y', strtotime($devis['date_demande'])) ?></div>
                        </div>
                    </div>
                </div>

                <div class="convert-card">
                    <div class="convert-card-title"><i class="bi bi-file-earmark-check"></i> Configurer le contrat</div>
                    <form method="post" id="convertForm">
                        <div class="period-options">
                            <?php
                            $mensuel = (float)($devis['prix_mensuel'] ?? 0);
                            $annuel  = (float)($devis['prix_annuel'] ?? 0);
                            if ($mensuel <= 0) $mensuel = (float)($devis['montant_estime'] ?? 0);
                            if ($annuel <= 0) $annuel = $mensuel * 12;
                            ?>
                            <label class="period-opt selected" id="opt-mensuel" onclick="selectPeriod('mensuel')">
                                <input type="radio" name="periodicite" value="mensuel" checked>
                                <div class="po-name"><i class="bi bi-calendar3"></i> Mensuel</div>
                                <div class="po-price"><?= $mensuel > 0 ? number_format($mensuel, 3, '.', ' ') . ' DT' : '—' ?></div>
                            </label>
                            <label class="period-opt" id="opt-annuel" onclick="selectPeriod('annuel')">
                                <input type="radio" name="periodicite" value="annuel">
                                <div class="po-name"><i class="bi bi-calendar-check"></i> Annuel</div>
                                <div class="po-price"><?= $annuel > 0 ? number_format($annuel, 3, '.', ' ') . ' DT' : '—' ?></div>
                            </label>
                        </div>

                        <label style="display:block;font-size:12px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;">Notes (optionnel)</label>
                        <textarea name="notes" class="notes-field" placeholder="Ajouter des notes au contrat..."></textarea>

                        <div class="convert-amount" id="amountDisplay">
                            <div class="convert-amount-label">Montant du contrat</div>
                            <div class="convert-amount-value" id="amountValue"><?= $mensuel > 0 ? number_format($mensuel, 3, '.', ' ') . ' DT' : '—' ?></div>
                            <div class="convert-amount-sub" id="amountSub">par mois</div>
                        </div>

                        <div class="convert-actions">
                            <a href="<?= $base ?>/controller/DevisController.php?action=details&id=<?= (int)$devis['id_devis'] ?>" class="btn-back">Annuler</a>
                            <button type="submit" class="btn-convert"><i class="bi bi-check-lg"></i> Créer le contrat</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
const PRIX_MENSUEL = <?= $mensuel ?>;
const PRIX_ANNUEL  = <?= $annuel ?>;

function selectPeriod(p) {
    document.getElementById('opt-mensuel').classList.toggle('selected', p === 'mensuel');
    document.getElementById('opt-annuel').classList.toggle('selected', p === 'annuel');
    document.querySelectorAll('.period-opt input').forEach(r => r.checked = r.value === p);

    const montant = p === 'annuel' ? PRIX_ANNUEL : PRIX_MENSUEL;
    document.getElementById('amountValue').textContent = formatDt(montant);
    document.getElementById('amountSub').textContent = p === 'annuel' ? 'par an' : 'par mois';
}

function formatDt(v) {
    if (v <= 0) return '—';
    return v.toFixed(3).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' DT';
}
</script>
</body>
</html>

