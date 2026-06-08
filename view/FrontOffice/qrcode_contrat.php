<?php
/**
 * C2: Public contract verification page (scanned from QR code)
 * No session required — public page.
 */
require_once __DIR__ . '/../../config.php';

$id    = (int)($_GET['id'] ?? 0);
$token = $_GET['token'] ?? '';
$secret = defined('QR_VERIFICATION_SECRET') ? QR_VERIFICATION_SECRET : 'protex_secret_2026';
$expected = hash('sha256', $id . $secret);

if (!$id || $token !== $expected) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Accès refusé</title>
    <style>body{font-family:Inter,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f8f9ff;color:#15233C;}
    .card{text-align:center;padding:40px;border-radius:20px;background:#fff;box-shadow:0 8px 30px rgba(0,0,0,0.08);max-width:400px;}
    .icon{font-size:48px;color:#e63946;margin-bottom:16px;}</style></head>
    <body><div class="card"><div class="icon">🚫</div><h2>Accès refusé</h2><p>Ce lien de vérification est invalide ou a expiré.</p></div></body></html>';
    exit;
}

$db = config::getConnexion();
$stmt = $db->prepare("
    SELECT c.*, cat.nom_categorie, u.nom, u.prenom
    FROM contrat c
    LEFT JOIN categorie cat ON c.id_categorie = cat.id_categorie
    LEFT JOIN user u ON c.id_user = u.id_user
    WHERE c.id_contrat = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$contrat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contrat) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Contrat introuvable</title></head><body><p>Contrat introuvable.</p></body></html>';
    exit;
}

$statut = strtolower(trim($contrat['statut_contrat'] ?? ''));
$statusLabel = match($statut) {
    'actif' => '✅ Actif',
    'expiré', 'expire' => '⚠️ Expiré',
    'résilié', 'resilie' => '❌ Résilié',
    'refusé', 'refuse' => '❌ Refusé',
    default => '⏳ En attente',
};
$statusColor = match($statut) {
    'actif' => '#2ed573',
    'expiré', 'expire' => '#ff6b1a',
    default => '#e63946',
};
$clientName = trim(($contrat['prenom'] ?? '') . ' ' . ($contrat['nom'] ?? ''));
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de contrat — Protex</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&display=swap">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Sora', sans-serif; background: linear-gradient(135deg, #f0f4ff 0%, #e8ecf7 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { background: #fff; border-radius: 24px; box-shadow: 0 12px 40px rgba(26,58,122,0.10); max-width: 480px; width: 100%; overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #1A3A7A, #0A1931); padding: 28px 28px 22px; color: #fff; display: flex; align-items: center; gap: 14px; }
        .logo { width: 44px; height: 44px; background: rgba(255,255,255,0.12); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 800; color: #00c6ff; }
        .brand { font-size: 22px; font-weight: 800; }
        .brand-sub { font-size: 11px; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }
        .status-banner { padding: 16px 28px; text-align: center; font-size: 18px; font-weight: 800; border-bottom: 1px solid rgba(26,58,122,0.08); }
        .card-body { padding: 24px 28px; }
        .field { margin-bottom: 16px; }
        .field-label { font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: #8b9dc3; font-weight: 700; margin-bottom: 4px; }
        .field-value { font-size: 15px; font-weight: 700; color: #15233C; }
        .card-footer { padding: 16px 28px; border-top: 1px solid rgba(26,58,122,0.06); text-align: center; }
        .card-footer p { font-size: 11px; color: #8b9dc3; }
        .verified-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 999px; background: rgba(46,213,115,0.08); border: 1px solid rgba(46,213,115,0.25); font-size: 12px; font-weight: 700; color: #2ed573; margin-bottom: 8px; }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <div class="logo">P</div>
        <div>
            <div class="brand">Protex</div>
            <div class="brand-sub">Assurance Digitale</div>
        </div>
    </div>

    <div class="status-banner" style="color:<?= $statusColor ?>; background: <?= $statusColor ?>12;">
        <?= $statusLabel ?>
    </div>

    <div class="card-body">
        <div class="verified-badge"><span>🔒</span> Document vérifié par Protex</div>

        <div class="field">
            <div class="field-label">N° Contrat</div>
            <div class="field-value"><?= $h($contrat['numero_contrat']) ?></div>
        </div>
        <div class="field">
            <div class="field-label">Client</div>
            <div class="field-value"><?= $h($clientName ?: '—') ?></div>
        </div>
        <div class="field">
            <div class="field-label">Type</div>
            <div class="field-value"><?= $h($contrat['type_contrat'] ?? '—') ?> — <?= $h($contrat['nom_categorie'] ?? '') ?></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="field">
                <div class="field-label">Début</div>
                <div class="field-value"><?= $h($contrat['date_debut_contrat'] ?? '—') ?></div>
            </div>
            <div class="field">
                <div class="field-label">Fin</div>
                <div class="field-value"><?= $h($contrat['date_fin_contrat'] ?? '—') ?></div>
            </div>
        </div>
        <div class="field">
            <div class="field-label">Prime</div>
            <div class="field-value"><?= number_format((float)($contrat['prime_contrat'] ?? 0), 2, ',', ' ') ?> DT</div>
        </div>
    </div>

    <div class="card-footer">
        <p>Vérifié le <?= date('d/m/Y à H:i') ?> — © Protex Assurance</p>
    </div>
</div>
</body>
</html>
