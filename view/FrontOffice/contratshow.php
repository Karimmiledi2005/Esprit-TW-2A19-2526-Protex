<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../controller/ContratController.php';
if (!class_exists('config')) {
    require_once __DIR__ . '/../../connexion.php';
}

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function formatDateFr($date){ $t = strtotime((string)$date); return $t ? date('d/m/Y', $t) : h($date); }
function statusClass($statut){
    $s = strtolower(trim((string)$statut));
    return match($s){
        'actif','active' => 'active',
        'en attente','pending' => 'waiting',
        'expiré','expire','résilié','resilie','inactive' => 'expired',
        'refusé','refuse' => 'refused',
        default => 'waiting'
    };
}
function typeIcon($type){
    $t = strtolower(trim((string)$type));
    return match($t){
        'auto' => ['icon'=>'bi-car-front-fill','class'=>'auto'],
        'habitation' => ['icon'=>'bi-house-door-fill','class'=>'habitation'],
        'sante','santé' => ['icon'=>'bi-heart-pulse-fill','class'=>'sante'],
        'protection' => ['icon'=>'bi-shield-check','class'=>'protection'],
        default => ['icon'=>'bi-file-earmark-text','class'=>'default']
    };
}
function labelize($key){ return mb_convert_case(str_replace('_',' ',(string)$key), MB_CASE_TITLE, 'UTF-8'); }
function normTxt($v){ return mb_strtolower(trim((string)$v), 'UTF-8'); }
function detailToString($value): string
{
    if (is_array($value)) {
        $parts = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $parts[] = detailToString($item);
            } elseif ($item !== null && $item !== '') {
                $parts[] = (string)$item;
            }
        }
        return implode(', ', array_filter($parts, fn($v) => trim((string)$v) !== ''));
    }
    if ($value === null || $value === '') return 'é';
    return (string)$value;
}

$id = (int)($_GET['id'] ?? 0);
$controller = new ContratController();
$contrat = $controller->getById($id);
if (!$contrat) { header('Location: contrat.php?error=introuvable'); exit(); }
$userId = (int)($_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 0);
$ownerId = isset($contrat['id_client']) ? (int)$contrat['id_client'] : (isset($contrat['id_user']) ? (int)$contrat['id_user'] : 0);
if ($userId !== $ownerId) { header('Location: contrat.php?error=acces_refuse'); exit(); }

$details = [];
if (!empty($contrat['details_contrat'])) {
    $decoded = json_decode($contrat['details_contrat'], true);
    if (is_array($decoded)) $details = $decoded;
}

$selectedGaranties = [];
$garantieKeys = ['garanties', 'garanties_choisies', 'garanties_selectionnees', 'garanties_optionnelles'];
foreach ($garantieKeys as $gKey) {
    if (!empty($details[$gKey])) {
        $gValues = is_array($details[$gKey]) ? $details[$gKey] : explode(',', (string)$details[$gKey]);
        foreach ($gValues as $gv) {
            if (is_array($gv)) {
                $gv = detailToString($gv);
            }
            $gv = normTxt($gv);
            if ($gv !== '') $selectedGaranties[] = $gv;
        }
    }
}
$selectedGaranties = array_values(array_unique($selectedGaranties));
$garantiesFormule = $controller->getGarantiesByContrat($id);
$typeData = typeIcon($contrat['type_contrat'] ?? '');
$statutContratNormalise = strtolower(trim((string)($contrat['statut_contrat'] ?? '')));
$isContratActif = in_array($statutContratNormalise, ['actif', 'active'], true);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
$qrSecret = defined('QR_VERIFICATION_SECRET') ? QR_VERIFICATION_SECRET : 'protex_secret_2026';
$qrToken = hash('sha256', $id . $qrSecret);
$qrTargetUrl = $baseUrl . '/qrcode_contrat.php?id=' . $id . '&token=' . $qrToken;
$qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($qrTargetUrl);

$clientFullName = trim(($contrat['prenom'] ?? '') . ' ' . ($contrat['nom'] ?? ''));
if ($clientFullName === '') {
    $clientFullName = '#' . ($contrat['id_user'] ?? $contrat['id_client'] ?? '-');
}

$pdfContrat = [
    'numero' => $contrat['numero_contrat'] ?? '-',
    'type' => $contrat['type_contrat'] ?? '-',
    'categorie' => $contrat['nom_categorie'] ?? ($contrat['type_contrat'] ?? '-'),
    'formule' => $contrat['nom_formule'] ?? ($contrat['formule_contrat'] ?? '-'),
    'date_debut' => formatDateFr($contrat['date_debut_contrat'] ?? ''),
    'date_fin' => formatDateFr($contrat['date_fin_contrat'] ?? ''),
    'prime' => number_format((float)($contrat['prime_contrat'] ?? 0), 2) . ' DT',
    'franchise' => number_format((float)($contrat['franchise_contrat'] ?? 0), 2) . ' DT',
    'statut' => $contrat['statut_contrat'] ?? '-',
    'client' => $clientFullName,
    'email' => $contrat['email'] ?? '-'
];

$pdfDetails = [];
foreach ($details as $key => $value) {
    if ($key === 'garanties') {
        continue;
    }
    $pdfDetails[] = [
        'label' => labelize($key),
        'value' => detailToString($value)
    ];
}


$garantiesChoisies = [];
$garantiesConnues = [];
foreach ($garantiesFormule as $g) {
    $nom = (string)($g['nom_garantie'] ?? '');
    if ($nom === '') continue;

    $niveau = normTxt($g['niveau_couvert_garantie'] ?? 'basique');
    $isSelected = in_array(normTxt($nom), $selectedGaranties, true);
    $garantiesConnues[] = normTxt($nom);

    if ($niveau === 'basique') {
        $label = 'Incluse';
        $class = 'basique';
        $icon = 'bi-shield-check';
    } elseif ($niveau === 'option' && $isSelected) {
        $label = 'Option choisie';
        $class = 'option-selected';
        $icon = 'bi-plus-circle-fill';
    } elseif ($niveau === 'option') {
        $label = 'Option non choisie';
        $class = 'option-off';
        $icon = 'bi-circle';
    } elseif ($niveau === 'non disponible' || $niveau === 'non_disponible') {
        $label = 'Non disponible';
        $class = 'no';
        $icon = 'bi-x-circle';
    } else {
        $label = ucfirst((string)($g['niveau_couvert_garantie'] ?? 'Garantie'));
        $class = 'basique';
        $icon = 'bi-shield-check';
    }

    $garantiesChoisies[] = [
        'id' => $g['id_garantie'] ?? null,
        'nom' => $nom,
        'description' => $g['description_garantie'] ?? '',
        'plafond' => number_format((float)($g['plafond_couvert_garantie'] ?? 0), 2) . ' DT',
        'niveau' => $label,
        'class' => $class,
        'icon' => $icon
    ];
}

// Si une garantie a été cochée dans le formulaire mais n'existe pas dans formule_garantie,
// on l'affiche quand méme pour ne pas perdre le choix du client.
foreach ($selectedGaranties as $garantieSaisie) {
    if ($garantieSaisie !== '' && !in_array($garantieSaisie, $garantiesConnues, true)) {
        $garantiesChoisies[] = [
            'nom' => mb_convert_case($garantieSaisie, MB_CASE_TITLE, 'UTF-8'),
            'description' => 'Garantie choisie dans le formulaire client.',
            'plafond' => '-',
            'niveau' => 'Option choisie',
            'class' => 'option-selected',
            'icon' => 'bi-plus-circle-fill'
        ];
    }
}

$pdfGarantiesChoisies = $garantiesChoisies;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Détail contrat é Protex</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">

    <!-- Style commun FrontOffice -->
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
    <link rel="stylesheet" href="assets/css/animations.css">

    <!-- Style spécifique contrats -->
    <link rel="stylesheet" href="user/assets_contrats/css/contrat.css">

<style>
.show-wrap{max-width:1180px;margin:34px auto;padding:0 22px}.show-card{background:rgba(255,255,255,.88);border:1px solid rgba(226,232,240,.9);border-radius:30px;padding:28px;box-shadow:0 24px 70px rgba(10,25,49,.12);backdrop-filter:blur(18px)}.show-head{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:24px;padding:22px;border-radius:24px;background:linear-gradient(135deg,#0A1931,#123d70);color:#fff}.show-title{display:flex;align-items:center;gap:16px}.show-head h1{margin:0;color:#fff;font-size:32px}.show-head p{margin:6px 0 0;color:#dbeafe;font-weight:800}.show-icon{width:76px;height:76px;border-radius:23px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:32px;background:linear-gradient(135deg,#ff8a3d,#ff4f1a);box-shadow:0 18px 35px rgba(255,107,26,.25)}.show-icon.sante{background:linear-gradient(135deg,#2ecc71,#17b86a)}.show-icon.habitation{background:linear-gradient(135deg,#f5b21b,#d99000)}.show-icon.protection{background:linear-gradient(135deg,#5578ff,#2f5bff)}.show-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:20px 0}.show-info{border:1px solid rgba(226,232,240,.9);border-radius:20px;padding:16px;background:linear-gradient(180deg,#fff,#f8fbff)}.show-info span{display:block;color:#667085;font-size:12px;text-transform:uppercase;letter-spacing:.05em;margin-bottom:7px;font-weight:900}.show-info strong{font-size:16px;color:#0A1931}.section-block{margin-top:24px}.section-heading{display:flex;align-items:center;gap:10px;font-size:22px;font-weight:950;color:#0A1931;margin:0 0 14px}.section-heading i{color:#ff6b1a}.details-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:12px}.detail-item{border:1px solid rgba(226,232,240,.9);border-radius:18px;padding:15px;background:linear-gradient(180deg,#fff,#f8fbff)}.detail-item span{display:block;color:#667085;font-size:12px;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;font-weight:900}.detail-item strong{color:#0A1931;font-size:15px;word-break:break-word}.garanties-box{margin:10px 0 24px;border:1px solid rgba(226,232,240,.9);border-radius:24px;padding:18px;background:linear-gradient(180deg,#fff,#f8fbff)}.garanties-title{display:flex;align-items:center;gap:8px;margin-bottom:14px;font-weight:950;color:#0A1931;font-size:20px}.garanties-title i{color:#ff6b1a}.garanties-list,.selected-garanties-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.garantie-line,.selected-garantie-card{display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center;gap:12px;padding:14px;border-radius:18px;background:#fff;border:1px solid rgba(226,232,240,.9);font-weight:800;color:#0A1931;box-shadow:0 12px 28px rgba(10,25,49,.06)}.selected-garantie-card{border-color:rgba(255,107,26,.35);background:linear-gradient(135deg,#fff7ed,#fff)}.garantie-line i,.selected-garantie-card i{font-size:20px}.garantie-line.basique i,.selected-garantie-card i{color:#16a064}.garantie-line.option-selected i{color:#ff6b1a}.garantie-line.option-off{opacity:.58}.garantie-line.option-off i{color:#8894a8}.garantie-line.no{opacity:.7}.garantie-line.no i{color:#a1aabc}.garantie-line small,.selected-garantie-card small{font-weight:950;color:#ff6b1a;margin-left:4px}.garantie-desc{display:block;margin-top:4px;color:#667085;font-size:12px;font-weight:700;line-height:1.35}.garantie-plafond{white-space:nowrap;padding:8px 10px;border-radius:999px;background:rgba(0,180,216,.12);border:1px solid rgba(0,180,216,.25);color:#0A1931;font-size:12px}.btn-pdf-protex{background:linear-gradient(135deg,#0A1931,#001F3F);color:#fff;border:1px solid rgba(0,180,216,.35)}.btn-pdf-protex:hover{color:#fff;transform:translateY(-1px)}.btn-pdf-protex:disabled,.btn-pdf-protex.disabled{opacity:.55;cursor:not-allowed;background:#9ca3af;border-color:#9ca3af;box-shadow:none;transform:none}.actions{display:flex;gap:12px;justify-content:flex-end;align-items:center;margin-top:24px}.actions .btn-protex{height:48px;min-width:112px;padding:0 22px;display:inline-flex;align-items:center;justify-content:center;gap:8px;line-height:1;text-decoration:none;white-space:nowrap;vertical-align:middle}.actions .btn-protex i{font-size:16px;line-height:1;display:inline-flex;align-items:center}.empty-note{padding:16px;border:1px dashed rgba(148,163,184,.7);border-radius:16px;color:#667085;background:#fff}.qr-card{display:grid;grid-template-columns:auto minmax(0,1fr);gap:18px;align-items:center;margin:8px 0 24px;padding:18px;border:1px solid rgba(0,180,216,.25);border-radius:22px;background:rgba(0,180,216,.07)}.qr-card img{width:145px;height:145px;padding:8px;border-radius:16px;background:#fff;border:1px solid rgba(0,0,0,.12)}.qr-card h3{margin:0 0 7px;color:#0A1931}.qr-card p{margin:0 0 8px;color:#667085;font-weight:700}.qr-link{display:block;word-break:break-all;color:#667085;font-size:12px}.qr-warning{margin-top:8px;font-size:12px;color:#ff6b1a;font-weight:900}.status-badge{padding:9px 14px;border-radius:999px;font-weight:950;text-transform:capitalize;background:#eef2ff;color:#1e3a8a}.status-badge.active{background:#dcfce7;color:#166534}.status-badge.waiting{background:#fef3c7;color:#92400e}.status-badge.expired,.status-badge.refused{background:#fee2e2;color:#991b1b}@media(max-width:900px){.show-grid,.details-grid,.garanties-list,.selected-garanties-grid{grid-template-columns:1fr}.show-head{align-items:flex-start;flex-direction:column}.actions{justify-content:flex-start;flex-wrap:wrap}}
</style>

    <!-- FrontOffice unifie - surcharge théme camarades dark-navy -->
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/light-theme.css"></head>
<body>
<div class="background"></div><div class="orb orb-1"></div><div class="orb orb-2"></div><div class="orb orb-3"></div>
<div class="layout">
<?php require_once __DIR__.'/assets/includes/navbar.php'; ?>
<main class="main">
<div class="show-wrap"><div class="show-card">
<div class="show-head"><div class="show-title"><div class="show-icon <?= h($typeData['class']) ?>"><i class="bi <?= h($typeData['icon']) ?>"></i></div><div><h1>Contrat <?= h($contrat['type_contrat'] ?? '') ?></h1><p>Né <?= h($contrat['numero_contrat'] ?? '') ?></p></div></div><span class="status-badge <?= h(statusClass($contrat['statut_contrat'] ?? '')) ?>"><?= h($contrat['statut_contrat'] ?? '') ?></span></div>
<div class="show-grid">
<div class="show-info"><span>Catégorie</span><strong><?= h($contrat['nom_categorie'] ?? $contrat['type_contrat'] ?? '-') ?></strong></div>
<div class="show-info"><span>Formule</span><strong><?= h($contrat['nom_formule'] ?? $contrat['formule_contrat'] ?? '-') ?></strong></div>
<div class="show-info"><span>Date début</span><strong><?= formatDateFr($contrat['date_debut_contrat'] ?? '') ?></strong></div>
<div class="show-info"><span>Date fin</span><strong><?= formatDateFr($contrat['date_fin_contrat'] ?? '') ?></strong></div>
<div class="show-info"><span>Prime</span><strong><?= h(number_format((float)($contrat['prime_contrat'] ?? 0), 2)) ?> DT</strong></div>
<div class="show-info"><span>Franchise</span><strong><?= h(number_format((float)($contrat['franchise_contrat'] ?? 0), 2)) ?> DT</strong></div>
<div class="show-info"><span>Client</span><strong><?= h(trim(($contrat['prenom'] ?? '') . ' ' . ($contrat['nom'] ?? '')) ?: ('#' . ($contrat['id_user'] ?? $contrat['id_client'] ?? '-'))) ?></strong></div>
<div class="show-info"><span>Email</span><strong><?= h($contrat['email'] ?? '-') ?></strong></div>
</div>

<div class="qr-card">
    <img src="<?= h($qrCodeUrl) ?>" alt="QR Code du contrat">
    <div>
        <h3><i class="bi bi-qr-code"></i> QR Code du contrat</h3>
        <p>Scannez ce code pour ouvrir la fiche de ce contrat.</p>
        <span class="qr-link"><?= h($qrTargetUrl) ?></span>
        <?php if (str_contains($qrTargetUrl, 'localhost')): ?>
            <div class="qr-warning">Sur téléphone, localhost ne séouvre pas. Utilise ce site pour scanner https://qrscanner.net/fr .</div>
        <?php endif; ?>
    </div>
</div>


<div class="section-block">
    <h2 class="section-heading"><i class="bi bi-check2-circle"></i> Garanties choisies dans le formulaire</h2>
    <?php if (!empty($garantiesChoisies)): ?>
        <div class="garanties-box">
            <div class="garanties-list">
                <?php foreach ($garantiesChoisies as $g): ?>
                    <div class="garantie-line <?= h($g['class'] ?? 'basique') ?>">
                        <i class="bi <?= h($g['icon'] ?? 'bi-shield-check') ?>"></i>
                        <span>
                            <?php if (!empty($g['id'])): ?>
                                <a href="garantie_detail.php?id=<?= $g['id'] ?>&contrat=<?= $id ?>" style="color: inherit; text-decoration: underline; text-underline-offset: 4px;"><?= h($g['nom'] ?? '') ?></a>
                            <?php else: ?>
                                <?= h($g['nom'] ?? '') ?>
                            <?php endif; ?>
                            <small>(<?= h($g['niveau'] ?? '') ?>)</small>
                            <?php if (!empty($g['description'])): ?>
                                <em class="garantie-desc"><?= h($g['description']) ?></em>
                            <?php endif; ?>
                        </span>
                        <strong class="garantie-plafond"><?= h($g['plafond'] ?? '-') ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="empty-note">Aucune garantie associée é cette formule.</div>
    <?php endif; ?>
</div>

<div class="section-block"><h2 class="section-heading"><i class="bi bi-list-check"></i> Informations saisies dans le formulaire</h2>
<?php if (!empty($details)): ?>
<div class="details-grid">
<?php foreach ($details as $key => $value): ?>
<?php if (in_array($key, ['garanties','garanties_choisies','garanties_selectionnees','garanties_optionnelles'], true)) continue; ?>
<div class="detail-item"><span><?= h(labelize($key)) ?></span><strong><?= h(detailToString($value)) ?></strong></div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-note">Aucun détail spécifique enregistré.</div>
<?php endif; ?>
</div>
<div class="actions">
    <a href="contrat.php" class="btn-protex btn-light-protex">Retour</a>
    <?php if ($isContratActif): ?>
        <button type="button" class="btn-protex btn-pdf-protex" onclick="exportContratPDF()">
            <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </button>
    <?php else: ?>
        <button type="button" class="btn-protex btn-pdf-protex disabled" disabled title="Export disponible uniquement aprés validation du contrat">
            <i class="bi bi-lock-fill"></i> Export indisponible
        </button>
    <?php endif; ?>
    <a href="contrat_update_client.php?id=<?= urlencode((string)$id) ?>" class="btn-protex btn-primary-protex">Modifier</a>
</div>
</div></div>
</main>
</div>

<script>
const pdfContrat = <?= json_encode($pdfContrat, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const pdfGarantiesChoisies = <?= json_encode($pdfGarantiesChoisies, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const pdfDetails = <?= json_encode($pdfDetails, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const statutContrat = <?= json_encode($statutContratNormalise, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const qrCodeUrl = <?= json_encode($qrCodeUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const qrCodeAbsoluteUrl = qrCodeUrl;

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, function (char) {
        return ({'&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#039;', '"':'&quot;'}[char]);
    });
}

function exportContratPDF() {
    if (statutContrat !== 'actif' && statutContrat !== 'active') {
        alert("L'export PDF est disponible uniquement aprés validation du contrat.");
        return;
    }

    const garantiesChoisiesRows = pdfGarantiesChoisies.length
        ? pdfGarantiesChoisies.map(g => `
            <tr>
                <td><strong>${escapeHtml(g.nom)}</strong><br><span>${escapeHtml(g.description || '-')}</span></td>
                <td><span class="badge">${escapeHtml(g.niveau)}</span></td>
                <td class="amount">${escapeHtml(g.plafond)}</td>
            </tr>
        `).join('')
        : `<tr><td colspan="3" class="muted">Aucune garantie associée é cette formule.</td></tr>`;

    const detailRows = pdfDetails.length
        ? pdfDetails.map(d => `
            <tr>
                <td>${escapeHtml(d.label)}</td>
                <td>${escapeHtml(d.value)}</td>
            </tr>
        `).join('')
        : `<tr><td colspan="2" class="muted">Aucun détail spécifique enregistré.</td></tr>`;

    const win = window.open('', '_blank');
    if (!win) {
        alert("Le navigateur a bloqué la fenétre d'export. Autorise les pop-ups pour ce site.");
        return;
    }
    win.document.write(`
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Contrat ${escapeHtml(pdfContrat.numero)} - Protex</title>
<style>
    *{box-sizing:border-box}body{font-family:Arial,Helvetica,sans-serif;margin:0;padding:28px;background:#f5f7fb;color:#0A1931}.page{background:#fff;border-radius:22px;box-shadow:0 18px 45px rgba(10,25,49,.12);overflow:hidden;border:1px solid #dfe7f2}.header{background:linear-gradient(135deg,#0A1931,#001F3F);color:#fff;padding:24px 28px;display:flex;align-items:center;justify-content:space-between;gap:20px}.brand{display:flex;align-items:center;gap:14px}.brand img{width:54px;height:54px;object-fit:contain}.brand h1{margin:0;font-size:26px;line-height:1.1}.brand p{margin:5px 0 0;color:#9cecff;font-weight:700}.meta{text-align:right;font-size:13px;color:#dbeafe}.meta strong{display:block;font-size:16px;color:#fff;margin-bottom:4px}.content{padding:26px 28px}.section-title{font-size:18px;margin:0 0 14px;color:#0A1931;display:flex;align-items:center;gap:8px}.section-title:before{content:"";width:7px;height:22px;border-radius:10px;background:#ff6b1a;display:inline-block}.summary{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px}.qr-pdf{display:flex;align-items:center;gap:14px;border:1px solid #dfe7f2;border-radius:16px;padding:14px;background:#f8fbff;margin-bottom:24px}.qr-pdf img{width:95px;height:95px;background:#fff;padding:6px;border:1px solid #dfe7f2;border-radius:12px}.qr-pdf strong{display:block;color:#0A1931;margin-bottom:5px}.qr-pdf span{font-size:12px;color:#667085}.card{border:1px solid #dfe7f2;border-radius:16px;padding:14px;background:linear-gradient(180deg,#fff,#f8fbff)}.card span{display:block;text-transform:uppercase;font-size:11px;letter-spacing:.06em;color:#667085;margin-bottom:8px}.card strong{font-size:15px;color:#0A1931}.status{display:inline-block;padding:7px 12px;border-radius:999px;background:#e7f8ee;color:#137847;font-weight:900;text-transform:capitalize}table{width:100%;border-collapse:collapse;margin:8px 0 24px;border:1px solid #dfe7f2;border-radius:14px;overflow:hidden}th{background:#0A1931;color:#fff;text-align:left;padding:12px;font-size:12px;text-transform:uppercase;letter-spacing:.04em}td{padding:12px;border-bottom:1px solid #dfe7f2;font-size:13px;vertical-align:top}tr:nth-child(even) td{background:#f8fbff}td span{color:#667085;font-size:12px}.amount{font-weight:900;color:#ff6b1a}.badge{display:inline-block;padding:6px 10px;border-radius:999px;background:#e7f7fb;color:#007ea7;font-weight:900}.muted{color:#667085;text-align:center}.footer{border-top:1px solid #dfe7f2;padding:16px 28px;color:#667085;font-size:12px;display:flex;justify-content:space-between}.print-actions{text-align:right;margin-bottom:14px}.print-actions button{background:#00b4d8;color:#fff;border:0;border-radius:12px;padding:10px 16px;font-weight:900;cursor:pointer}@media print{body{background:#fff;padding:0}.page{box-shadow:none;border-radius:0;border:0}.print-actions{display:none}.summary{grid-template-columns:repeat(4,1fr)}@page{margin:12mm}}
</style>

    <!-- FrontOffice unifie - surcharge théme camarades dark-navy -->
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/light-theme.css"></head>
<body>
<div class="print-actions"><button onclick="window.print()">Enregistrer en PDF</button></div>
<div class="page">
    <div class="header">
        <div class="brand">
            <img src="${window.location.origin + window.location.pathname.replace(/[^\/]+$/, '') + 'logo.png'}" alt="Protex">
            <div><h1>Contrat déassurance</h1><p>Protex Assurance Digitale</p></div>
        </div>
        <div class="meta"><strong>${escapeHtml(pdfContrat.numero)}</strong>Exporté le ${new Date().toLocaleDateString('fr-FR')}</div>
    </div>
    <div class="content">
        <h2 class="section-title">Résumé du contrat</h2>
        <div class="summary">
            <div class="card"><span>Catégorie</span><strong>${escapeHtml(pdfContrat.categorie)}</strong></div>
            <div class="card"><span>Formule</span><strong>${escapeHtml(pdfContrat.formule)}</strong></div>
            <div class="card"><span>Prime</span><strong>${escapeHtml(pdfContrat.prime)}</strong></div>
            <div class="card"><span>Franchise</span><strong>${escapeHtml(pdfContrat.franchise)}</strong></div>
            <div class="card"><span>Date début</span><strong>${escapeHtml(pdfContrat.date_debut)}</strong></div>
            <div class="card"><span>Date fin</span><strong>${escapeHtml(pdfContrat.date_fin)}</strong></div>
            <div class="card"><span>Client</span><strong>${escapeHtml(pdfContrat.client)}</strong></div>
            <div class="card"><span>Statut</span><strong class="status">${escapeHtml(pdfContrat.statut)}</strong></div>
        </div>

        <div class="qr-pdf">
            <img src="${qrCodeAbsoluteUrl}" alt="QR Code du contrat">
            <div><strong>QR Code du contrat</strong><span>Scannez pour ouvrir la fiche du contrat.</span></div>
        </div>

        <h2 class="section-title">Garanties choisies dans le formulaire</h2>
        <table>
            <thead><tr><th>Garantie</th><th>Niveau</th><th>Plafond couverture</th></tr></thead>
            <tbody>${garantiesChoisiesRows}</tbody>
        </table>

        <h2 class="section-title">Informations client / formulaire</h2>
        <table>
            <thead><tr><th>Champ</th><th>Valeur</th></tr></thead>
            <tbody>${detailRows}</tbody>
        </table>
    </div>
    <div class="footer"><span>Protex Assurance é Front Office</span><span>Document généré automatiquement</span></div>
</div>
<script>window.onload = function(){ setTimeout(function(){ window.print(); }, 400); };<\/script>
</body>
</html>`);
    win.document.close();
    win.focus();
}
</script>


<script src="assets/js/main.js"></script>
<!-- legacy script removed -->
<script src="user/assets_contrats/js/main.js"></script>
</body>
</html>



