<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
$base = defined('BASE_URL') ? BASE_URL : '';

$id = (int)($_GET['id'] ?? 0);
$contratC = new ContratController();
$contrat = $contratC->getById($id);
if (!$contrat) {
    die('Contrat introuvable.');
}

$details = [];
if (!empty($contrat['details_contrat'])) {
    $decoded = json_decode($contrat['details_contrat'], true);
    if (is_array($decoded)) {
        $details = $decoded;
    }
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function valueDetails($v) {
    if (!is_array($v)) {
        return (string)$v;
    }
    
    $parts = [];
    foreach ($v as $item) {
        if (is_array($item)) {
            $parts[] = valueDetails($item);
        } else {
            $parts[] = (string)$item;
        }
    }
    return implode(', ', $parts);
}

function isGarantieKey($key): bool {
    return in_array((string)$key, [
        'garanties',
        'garanties_optionnelles',
        'garanties_choisies',
        'garanties_selectionnees'
    ], true);
}

function normalizeGaranties($value): array {
    if (is_array($value)) {
        $result = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $result[] = valueDetails($item);
            } else {
                $str = trim((string)$item);
                if ($str !== '') $result[] = $str;
            }
        }
        return $result;
    }

    $text = trim((string)$value);
    if ($text === '') {
        return [];
    }

    $parts = preg_split('/\s*[,;|]\s*/', $text);
    return array_values(array_filter(array_map('trim', $parts)));
}

function formatLabel($key) {
    $labels = [
        'nom' => 'Nom',
        'prenom' => 'Prénom',
        'email' => 'Email',
        'telephone' => 'Téléphone',
        'date_naissance' => 'Date naissance',
        'nationalite' => 'Nationalité',
        'situation_professionnelle' => 'Situation professionnelle',
        'situation_matrimoniale' => 'Situation matrimoniale',
        'adresse' => 'Adresse',
        'revenu_annuel' => 'Revenu annuel',
        'immatriculation' => 'Immatriculation',
        'marque' => 'Marque',
        'modele' => 'Modèle',
        'puissance' => 'Puissance',
        'date_circulation' => 'Date circulation',
        'estimation_km' => 'Estimation KM',
        'conducteurs' => 'Conducteurs',
        'stationnement' => 'Stationnement',
        'utilisation' => 'Utilisation',
        'trajets_prevus' => 'Trajets prévus',
        'garanties_optionnelles' => 'Garanties optionnelles',
    ];

    return $labels[$key] ?? ucfirst(str_replace('_', ' ', (string)$key));
}

$clientName = trim(($contrat['prenom'] ?? '') . ' ' . ($contrat['nom'] ?? ''));
$clientFallbackId = $contrat['id_user'] ?? $contrat['id_client'] ?? '—';
$clientDisplay = $clientName !== '' ? $clientName : ('ID ' . $clientFallbackId);
$status = strtolower((string)($contrat['statut_contrat'] ?? ''));
$statusClass = match ($status) {
    'actif' => 'status-active',
    'refusé', 'refuse' => 'status-refused',
    'résilié', 'resilie' => 'status-ended',
    default => 'status-pending',
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détail contrat — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/admin-users.css">
    <link rel="stylesheet" href="assets/css/validation.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/variables.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/base.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/layout.css">
    <style>
        body {
            min-height: 100vh;
            overflow-x: hidden;
        }

        .show-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 42px 18px;
            position: relative;
        }

        .show-backdrop {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 15% 10%, rgba(0, 198, 255, 0.18), transparent 30%),
                radial-gradient(circle at 90% 80%, rgba(255, 107, 26, 0.22), transparent 34%),
                rgba(2, 8, 23, 0.72);
            backdrop-filter: blur(13px);
            z-index: 1;
        }

        .show-modal {
            position: relative;
            z-index: 2;
            width: min(920px, 96vw);
            max-height: 92vh;
            overflow: auto;
            background: linear-gradient(180deg, rgba(8, 22, 52, 0.98), rgba(5, 17, 42, 0.98));
            border: 1px solid rgba(80, 132, 255, 0.24);
            border-radius: 24px;
            box-shadow: 0 32px 90px rgba(0, 0, 0, 0.42);
            padding: 0;
            color: #fff;
        }

        .show-modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            padding: 28px 32px 22px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .show-title-wrap {
            display: flex;
            gap: 14px;
            align-items: center;
        }

        .show-icon {
            width: 48px;
            height: 48px;
            border-radius: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ff6b1a, #ff3d3d);
            box-shadow: 0 16px 34px rgba(255, 107, 26, 0.25);
            font-size: 24px;
            flex: 0 0 auto;
        }

        .show-title {
            margin: 0;
            font-size: 26px;
            line-height: 1.15;
            font-weight: 800;
            color: #fff;
        }

        .show-subtitle {
            margin-top: 6px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.65);
            font-weight: 700;
        }

        .show-close {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.06);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 18px;
            transition: .2s ease;
            flex: 0 0 auto;
        }

        .show-close:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-1px);
        }

        .show-modal-body {
            padding: 26px 32px 8px;
        }

        .show-section {
            margin-bottom: 24px;
        }

        .show-section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 17px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 16px;
        }

        .show-section-title i {
            color: #00c6ff;
            font-size: 20px;
        }

        .show-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .show-field {
            min-height: 72px;
            background: rgba(255, 255, 255, 0.055);
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 16px;
            padding: 14px 16px;
        }

        .show-label {
            display: block;
            margin-bottom: 8px;
            font-size: 11px;
            letter-spacing: .5px;
            color: rgba(255, 255, 255, 0.56);
            text-transform: uppercase;
            font-weight: 800;
        }

        .show-value {
            color: #fff;
            font-size: 15px;
            font-weight: 750;
            line-height: 1.35;
            word-break: break-word;
        }

        .show-empty {
            background: rgba(255, 255, 255, 0.045);
            border: 1px dashed rgba(255, 255, 255, 0.14);
            border-radius: 16px;
            padding: 18px;
            color: rgba(255, 255, 255, 0.62);
            font-size: 13px;
        }

        .show-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 9px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 850;
            border: 1px solid transparent;
        }

        .status-active { background: rgba(46, 213, 115, .14); color: #2ed573; border-color: rgba(46, 213, 115, .24); }
        .status-pending { background: rgba(255, 193, 7, .15); color: #ffd166; border-color: rgba(255, 193, 7, .24); }
        .status-refused { background: rgba(255, 71, 87, .14); color: #ff6b7a; border-color: rgba(255, 71, 87, .24); }
        .status-ended { background: rgba(148, 163, 184, .17); color: #cbd5e1; border-color: rgba(148, 163, 184, .24); }

        .show-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 22px 32px 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            flex-wrap: wrap;
        }

        .show-btn {
            height: 44px;
            padding: 0 18px;
            border-radius: 13px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            line-height: 1;
            text-decoration: none;
            font-weight: 800;
            font-size: 14px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            transition: .2s ease;
            cursor: pointer;
            white-space: nowrap;
        }

        .show-btn:hover {
            transform: translateY(-1px);
        }

        .btn-return {
            color: rgba(255, 255, 255, .78);
            background: rgba(255, 255, 255, 0.05);
        }

        .btn-edit, .btn-valid {
            color: #fff;
            background: linear-gradient(135deg, #00c6ff, #0891b2);
            border-color: transparent;
        }

        .btn-refuse {
            color: #ffd166;
            background: rgba(255, 193, 7, .08);
            border-color: rgba(255, 193, 7, .18);
        }

        .btn-end {
            color: #cbd5e1;
            background: rgba(148, 163, 184, .08);
            border-color: rgba(148, 163, 184, .16);
        }

        .btn-delete {
            color: #fff;
            background: linear-gradient(135deg, #ff4757, #b91c1c);
            border-color: transparent;
        }

        .garantie-field {
            grid-column: 1 / -1;
        }

        .garantie-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 4px;
        }

        .garantie-item {
            min-height: 48px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.055);
            border: 1px solid rgba(0, 198, 255, 0.18);
            color: #fff;
            font-weight: 800;
            line-height: 1.25;
        }

        .garantie-item i {
            width: 24px;
            height: 24px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            color: #2ed573;
            background: rgba(46, 213, 115, 0.12);
            border: 1px solid rgba(46, 213, 115, 0.25);
            font-size: 14px;
        }

        @media (max-width: 720px) {
            .show-modal-header,
            .show-modal-body,
            .show-footer {
                padding-left: 20px;
                padding-right: 20px;
            }

            .show-grid {
                grid-template-columns: 1fr;
            }

            .garantie-list {
                grid-template-columns: 1fr;
            }

            .show-footer .show-btn {
                flex: 1 1 100%;
            }
        }
    </style>
</head>
<body>
<div class="layout">
    <?php require_once __DIR__.'/assets/includes/sidebar.php'; ?>
    <main class="main">
<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="show-page">
    <div class="show-backdrop"></div>

    <div class="show-modal" role="dialog" aria-modal="true" aria-labelledby="showContratTitle">
        <div class="show-modal-header">
            <div class="show-title-wrap">
                <div class="show-icon"><i class="bi bi-file-earmark-text"></i></div>
                <div>
                    <h1 class="show-title" id="showContratTitle">Détail du contrat</h1>
                    <div class="show-subtitle">N° <?= h($contrat['numero_contrat']) ?></div>
                </div>
            </div>

            <a class="show-close" href="contrats_back.php" title="Fermer"><i class="bi bi-x"></i></a>
        </div>

        <div class="show-modal-body">
            <div class="show-section">
                <div class="show-section-title"><i class="bi bi-shield-check"></i> Informations contrat</div>

                <div class="show-grid">
                    <div class="show-field">
                        <span class="show-label">N° contrat</span>
                        <div class="show-value"><?= h($contrat['numero_contrat']) ?></div>
                    </div>
                    <div class="show-field">
                        <span class="show-label">Statut</span>
                        <div class="show-value"><span class="show-status <?= h($statusClass) ?>"><?= h($contrat['statut_contrat'] ?? '—') ?></span></div>
                    </div>
                    <div class="show-field">
                        <span class="show-label">Catégorie</span>
                        <div class="show-value"><?= h($contrat['nom_categorie'] ?? $contrat['type_contrat'] ?? '—') ?></div>
                    </div>
                    <div class="show-field">
                        <span class="show-label">Formule</span>
                        <div class="show-value"><?= h($contrat['nom_formule'] ?? $contrat['formule_contrat'] ?? '—') ?></div>
                    </div>
                    <div class="show-field">
                        <span class="show-label">Prime</span>
                        <div class="show-value"><?= h($contrat['prime_contrat'] ?? '—') ?> DT</div>
                    </div>
                    <div class="show-field">
                        <span class="show-label">Franchise</span>
                        <div class="show-value"><?= h($contrat['franchise_contrat'] ?? '—') ?> DT</div>
                    </div>
                    <div class="show-field">
                        <span class="show-label">Date début</span>
                        <div class="show-value"><?= h($contrat['date_debut_contrat'] ?? '—') ?></div>
                    </div>
                    <div class="show-field">
                        <span class="show-label">Date fin</span>
                        <div class="show-value"><?= h($contrat['date_fin_contrat'] ?? '—') ?></div>
                    </div>
                    <div class="show-field">
                        <span class="show-label">Client</span>
                        <div class="show-value"><?= h($clientDisplay) ?></div>
                    </div>
                    <div class="show-field">
                        <span class="show-label">Email</span>
                        <div class="show-value"><?= h($contrat['email'] ?? '—') ?></div>
                    </div>
                </div>
            </div>

            <div class="show-section">
                <div class="show-section-title"><i class="bi bi-list-check"></i> Informations remplies par le client</div>

                <?php if (empty($details)): ?>
                    <div class="show-empty">Aucun détail spécifique enregistré.</div>
                <?php else: ?>
                    <div class="show-grid">
                        <?php foreach ($details as $key => $value): ?>
                            <?php if (isGarantieKey($key)): ?>
                                <?php $garanties = normalizeGaranties($value); ?>
                                <div class="show-field garantie-field">
                                    <span class="show-label"><?= h(formatLabel($key)) ?></span>

                                    <?php if (empty($garanties)): ?>
                                        <div class="show-value">—</div>
                                    <?php else: ?>
                                        <div class="garantie-list">
                                            <?php foreach ($garanties as $garantie): ?>
                                                <div class="garantie-item">
                                                    <i class="bi bi-check2"></i>
                                                    <span><?= h($garantie) ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="show-field">
                                    <span class="show-label"><?= h(formatLabel($key)) ?></span>
                                    <div class="show-value"><?= h(valueDetails($value)) ?></div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- G2: SECTION PERSONNALISATION DES GARANTIES -->
            <div class="show-section">
                <div class="show-section-title"><i class="bi bi-shield-lock"></i> Personnalisation des garanties</div>
                <?php
                // Fetch guarantees for this contract's formule
                if (!isset($db)) $db = config::getConnexion();
                $stmtG = $db->prepare("
                    SELECT g.id_garantie, g.nom_garantie, fg.plafond_formule, fg.franchise_formule,
                           cgo.plafond_custom, cgo.franchise_custom
                    FROM formule_garantie fg
                    JOIN garantie g ON fg.id_garantie = g.id_garantie
                    LEFT JOIN contrat_garantie_override cgo ON cgo.id_contrat = ? AND cgo.id_garantie = g.id_garantie
                    WHERE fg.id_formule = ?
                ");
                $stmtG->execute([$id, $contrat['id_formule'] ?? 0]);
                $garantiesList = $stmtG->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($garantiesList)): ?>
                    <div class="show-empty">Aucune garantie liée à cette formule.</div>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table style="width:100%; border-collapse: collapse; margin-top: 8px;">
                            <thead>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); text-align: left;">
                                    <th style="padding:12px; font-size:11px; color:rgba(255,255,255,0.5); text-transform:uppercase;">Garantie</th>
                                    <th style="padding:12px; font-size:11px; color:rgba(255,255,255,0.5); text-transform:uppercase;">Plafond Std</th>
                                    <th style="padding:12px; font-size:11px; color:rgba(255,255,255,0.5); text-transform:uppercase;">Plafond Custom</th>
                                    <th style="padding:12px; font-size:11px; color:rgba(255,255,255,0.5); text-transform:uppercase;">Franchise Std</th>
                                    <th style="padding:12px; font-size:11px; color:rgba(255,255,255,0.5); text-transform:uppercase;">Franchise Custom</th>
                                    <th style="padding:12px; font-size:11px; color:rgba(255,255,255,0.5); text-transform:uppercase;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($garantiesList as $gItem): 
                                    $hasOverride = ($gItem['plafond_custom'] !== null || $gItem['franchise_custom'] !== null);
                                ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                        <td style="padding:12px; font-size:14px; font-weight:600;">
                                            <a href="<?= $base ?>/view/FrontOffice/garantie_detail.php?id=<?= $gItem['id_garantie'] ?>&contrat=<?= $id ?>" style="color:inherit; text-decoration:underline; text-underline-offset:3px;"><?= h($gItem['nom_garantie']) ?></a>
                                            <?php if($hasOverride): ?>
                                                <span class="nav-badge accent" style="margin-left:8px; font-size:10px;">Modifié</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding:12px; font-size:13px; color:rgba(255,255,255,0.7);"><?= $gItem['plafond_formule'] !== null ? number_format($gItem['plafond_formule'], 2) : '—' ?></td>
                                        <td style="padding:12px;">
                                            <input type="number" step="0.01" id="p_custom_<?= $gItem['id_garantie'] ?>" value="<?= $gItem['plafond_custom'] !== null ? $gItem['plafond_custom'] : '' ?>" style="width:100px; padding:6px; background:rgba(0,0,0,0.2); border:1px solid var(--glass-border); color:#fff; border-radius:4px; font-size:13px;" placeholder="Std">
                                        </td>
                                        <td style="padding:12px; font-size:13px; color:rgba(255,255,255,0.7);"><?= $gItem['franchise_formule'] !== null ? number_format($gItem['franchise_formule'], 2) : '—' ?></td>
                                        <td style="padding:12px;">
                                            <input type="number" step="0.01" id="f_custom_<?= $gItem['id_garantie'] ?>" value="<?= $gItem['franchise_custom'] !== null ? $gItem['franchise_custom'] : '' ?>" style="width:100px; padding:6px; background:rgba(0,0,0,0.2); border:1px solid var(--glass-border); color:#fff; border-radius:4px; font-size:13px;" placeholder="Std">
                                        </td>
                                        <td style="padding:12px;">
                                            <button onclick="saveOverride(<?= $id ?>, <?= $gItem['id_garantie'] ?>)" class="show-btn" style="height:32px; padding:0 12px; font-size:12px;"><i class="bi bi-save"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SECTION SINISTRES -->
            <div class="show-section">
                <div class="show-section-title"><i class="bi bi-exclamation-triangle"></i> Sinistres liés</div>
                <?php
                $sc = new SinistreController();
                $sinistres = $sc->getByContrat($id);
                if (empty($sinistres)): ?>
                    <div class="show-empty">Aucun sinistre déclaré pour ce contrat.</div>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table style="width:100%; border-collapse: collapse; margin-top: 8px;">
                            <thead>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); text-align: left;">
                                    <th style="padding:12px; font-size:11px; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:0.5px;">ID</th>
                                    <th style="padding:12px; font-size:11px; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:0.5px;">Type</th>
                                    <th style="padding:12px; font-size:11px; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:0.5px;">Date</th>
                                    <th style="padding:12px; font-size:11px; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:0.5px;">Statut</th>
                                    <th style="padding:12px; font-size:11px; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:0.5px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sinistres as $s): ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                        <td style="padding:12px; font-size:14px; font-family:monospace; color:#00c6ff;">#<?= $s->getIdSinistre() ?></td>
                                        <td style="padding:12px; font-size:14px; font-weight:700;"><?= h($s->getType()) ?></td>
                                        <td style="padding:12px; font-size:14px; color:rgba(255,255,255,0.7);"><?= h($s->getDateDeclaration()) ?></td>
                                        <td style="padding:12px;">
                                            <span class="show-status <?= match($s->getStatut()) { 'rembourse'=>'status-active', 'refuse'=>'status-refused', default=>'status-pending' } ?>" style="padding:4px 12px; font-size:11px;">
                                                <?php $statutLabel = match($s->getStatut()) { 'rembourse' => 'Remboursé', 'refuse' => 'Refusé', 'en_traitement' => 'En cours', 'en_attente' => 'En attente', 'en_analyse' => 'En analyse', 'assigne' => 'Assigné', 'en_cours' => 'En cours', 'cloture' => 'Clôturé', default => $s->getStatut() }; ?>
                                                <?= $statutLabel ?>
                                            </span>
                                        </td>
                                        <td style="padding:12px;">
                                            <a href="sinistre_details.php?id=<?= $s->getIdSinistre() ?>" class="show-btn" style="height:32px; padding:0 12px; font-size:12px; background:rgba(255,255,255,0.05);"><i class="bi bi-eye"></i> Voir</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- C3: SECTION HISTORIQUE DES MODIFICATIONS -->
            <div class="show-section">
                <div class="show-section-title"><i class="bi bi-clock-history"></i> Historique des modifications</div>
                <div id="historyContainer" style="max-height:340px;overflow-y:auto;">
                    <div class="show-empty" style="text-align:center;"><i class="bi bi-arrow-repeat" style="animation:spin .8s linear infinite;display:inline-block;"></i> Chargement…</div>
                </div>
            </div>

            <!-- C2: QR CODE section -->
            <div class="show-section">
                <div class="show-section-title"><i class="bi bi-qr-code"></i> QR Code de vérification</div>
                <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                    <?php
                    $qrSecret = defined('QR_VERIFICATION_SECRET') ? QR_VERIFICATION_SECRET : 'protex_secret_2026';
                    $qrToken = hash('sha256', $id . $qrSecret);
                    $qrUrl = (defined('BASE_URL') ? BASE_URL : '') . '/view/FrontOffice/qrcode_contrat.php?id=' . $id . '&token=' . $qrToken;
                    $qrImgUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($qrUrl);
                    ?>
                    <div style="background:#fff;padding:8px;border-radius:12px;">
                        <img src="<?= h($qrImgUrl) ?>" alt="QR Code" width="180" height="180" style="display:block;">
                    </div>
                    <div>
                        <div style="font-size:13px;color:rgba(255,255,255,0.6);margin-bottom:8px;">Scannez ce code pour vérifier l'authenticité du contrat.</div>
                        <a href="<?= h($qrUrl) ?>" target="_blank" class="show-btn" style="height:34px;padding:0 14px;font-size:12px;background:rgba(255,255,255,0.05);color:#00c6ff;text-decoration:none;">
                            <i class="bi bi-box-arrow-up-right"></i> Page de vérification
                        </a>
                    </div>
                </div>
            </div>

        </div>

<script>
// C3: Load modification history
(async function() {
    try {
        const res = await fetch('<?= $base ?>/api.php?action=contrat_history&id_contrat=<?= $id ?>');
        const json = await res.json();
        const container = document.getElementById('historyContainer');

        if (!json.success || !json.data || json.data.length === 0) {
            container.innerHTML = '<div class="show-empty">Aucune modification enregistrée.</div>';
            return;
        }

        const fieldColors = {
            'statut_contrat': '#00c6ff',
            'prime_contrat': '#ff6b1a',
            'franchise_contrat': '#ff6b1a',
        };

        container.innerHTML = json.data.map(h => {
            const color = fieldColors[h.champ_modifie] || '#94a3b8';
            const auteur = (h.prenom || '') + ' ' + (h.nom || '') || 'Système';
            const date = h.created_at ? new Date(h.created_at).toLocaleString('fr-FR') : '—';
            const label = h.champ_modifie.replace(/_/g, ' ').replace(/contrat/g, '').trim();
            return `
                <div style="display:flex;gap:14px;align-items:flex-start;padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.05);">
                    <div style="width:8px;height:8px;border-radius:50%;background:${color};margin-top:6px;flex-shrink:0;"></div>
                    <div style="flex:1;">
                        <div style="font-size:13px;font-weight:700;color:#fff;">
                            ${label} : <span style="color:#e63946;text-decoration:line-through;">${h.ancienne_valeur || '—'}</span>
                            → <span style="color:#2ed573;">${h.nouvelle_valeur || '—'}</span>
                        </div>
                        <div style="font-size:11px;color:rgba(255,255,255,0.5);margin-top:4px;">
                            <i class="bi bi-person"></i> ${auteur} · <i class="bi bi-clock"></i> ${date}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    } catch(e) {
        document.getElementById('historyContainer').innerHTML = '<div class="show-empty">Erreur de chargement de l\'historique.</div>';
    }
})();

async function saveOverride(idContrat, idGarantie) {
    const pInput = document.getElementById('p_custom_' + idGarantie).value;
    const fInput = document.getElementById('f_custom_' + idGarantie).value;
    
    const formData = new FormData();
    formData.append('action', 'save_garantie_override');
    formData.append('id_contrat', idContrat);
    formData.append('id_garantie', idGarantie);
    formData.append('plafond_custom', pInput);
    formData.append('franchise_custom', fInput);
    
    try {
        const res = await fetch('../../api.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            alert('Erreur: ' + (data.message || 'Inconnue'));
        }
    } catch(e) {
        alert('Erreur réseau');
    }
}
</script>

        <?php
            $statutContrat = mb_strtolower(trim((string)($contrat['statut_contrat'] ?? '')), 'UTF-8');
            $isEnAttente = in_array($statutContrat, ['en attente', 'en_attente', 'pending'], true);
            $isActif = in_array($statutContrat, ['actif', 'active'], true);
            $isFinal = in_array($statutContrat, ['résilié', 'resilié', 'resilie', 'refusé', 'refuse'], true);
        ?>

        <div class="show-footer">
            <a href="contrats_back.php" class="show-btn btn-return"><i class="bi bi-arrow-left"></i> Retour</a>
            <a href="<?= $base ?>/download_contrat_admin.php?id=<?= (int)$contrat['id_contrat'] ?>" class="show-btn" style="background:rgba(0,198,255,0.12);color:#00c6ff;text-decoration:none;" target="_blank"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
            <a href="<?= $base ?>/download_contrat_admin.php?id=<?= (int)$contrat['id_contrat'] ?>&type=attestation" class="show-btn" style="background:rgba(255,255,255,0.05);text-decoration:none;" target="_blank"><i class="bi bi-file-earmark-check"></i> Attestation</a>

            <?php if ($isEnAttente): ?>
                <a href="statutContrat.php?id=<?= (int)$contrat['id_contrat'] ?>&statut=actif" class="show-btn btn-valid"><i class="bi bi-check2-circle"></i> Valider</a>
                <a href="statutContrat.php?id=<?= (int)$contrat['id_contrat'] ?>&statut=refusé" class="show-btn btn-refuse"><i class="bi bi-x-circle"></i> Refuser</a>
                <a href="updateContrat.php?id=<?= (int)$contrat['id_contrat'] ?>" class="show-btn btn-edit"><i class="bi bi-pencil"></i> Modifier</a>
                <form method="POST" action="deleteContrat.php" style="display:inline;" onsubmit="return confirm('Supprimer ce contrat ?');">
                    <input type="hidden" name="id" value="<?= (int)$contrat['id_contrat'] ?>">
                    <?= CsrfHelper::field() ?>
                    <button type="submit" class="show-btn btn-delete" style="border:none;background:none;"><i class="bi bi-trash3"></i> Supprimer</button>
                </form>

            <?php elseif ($isActif): ?>
                <a href="statutContrat.php?id=<?= (int)$contrat['id_contrat'] ?>&statut=résilié" class="show-btn btn-end"><i class="bi bi-slash-circle"></i> Résilier</a>
                <a href="updateContrat.php?id=<?= (int)$contrat['id_contrat'] ?>" class="show-btn btn-edit"><i class="bi bi-pencil"></i> Modifier</a>

            <?php elseif ($isFinal): ?>
                <form method="POST" action="deleteContrat.php" style="display:inline;" onsubmit="return confirm('Supprimer ce contrat ?');">
                    <input type="hidden" name="id" value="<?= (int)$contrat['id_contrat'] ?>">
                    <?= CsrfHelper::field() ?>
                    <button type="submit" class="show-btn btn-delete" style="border:none;background:none;"><i class="bi bi-trash3"></i> Supprimer</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
    </main>
</div>
</body>
</html>
