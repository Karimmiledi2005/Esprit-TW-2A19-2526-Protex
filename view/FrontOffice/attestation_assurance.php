<?php
/**
 * C7: Attestation d'assurance en format A5 (pour impression PDF native)
 */
require_once __DIR__ . '/../../bootstrap.php';
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../controller/ContratController.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { die("ID de contrat manquant."); }

$ctrl = new ContratController();
$contrat = $ctrl->getById($id);

if (!$contrat) { die("Contrat introuvable."); }

$statut = strtolower(trim($contrat['statut_contrat'] ?? ''));
if ($statut !== 'actif') {
    die("Seuls les contrats actifs peuvent générer une attestation d'assurance.");
}

$details = [];
if (!empty($contrat['details_contrat'])) {
    $decoded = json_decode($contrat['details_contrat'], true);
    if (is_array($decoded)) {
        $details = $decoded;
    }
}

// Prepare Data
$numero = htmlspecialchars($contrat['numero_contrat'] ?? ('#' . $id));
$clientName = trim(($contrat['prenom'] ?? '') . ' ' . ($contrat['nom'] ?? ''));
if ($clientName === '' && isset($details['prenom']) && isset($details['nom'])) {
    $clientName = trim($details['prenom'] . ' ' . $details['nom']);
}
if ($clientName === '') $clientName = 'Client N°' . ($contrat['id_user'] ?? $contrat['id_client'] ?? 'INCONNU');
$clientName = htmlspecialchars(strtoupper($clientName));

$type = htmlspecialchars($contrat['type_contrat'] ?? '');
$categorie = htmlspecialchars($contrat['nom_categorie'] ?? '');
$dateDebut = htmlspecialchars($contrat['date_debut_contrat'] ?? '');
$dateFin = htmlspecialchars($contrat['date_fin_contrat'] ?? '');

// Vehicule details if Auto
$vehicule = '—';
if (strtolower($type) === 'auto' || strtolower($type) === 'automobile') {
    $marque = $details['marque'] ?? 'Marque N/C';
    $modele = $details['modele'] ?? 'Modèle N/C';
    $immat = $details['immatriculation'] ?? 'Immat. N/C';
    $vehicule = htmlspecialchars(strtoupper("$marque $modele - $immat"));
}

// Habitation details if Habitation
$habitation = '—';
if (strtolower($type) === 'habitation') {
    $adresse = $details['adresse'] ?? 'Adresse N/C';
    $habitation = htmlspecialchars($adresse);
}

// QR Code
$qrSecret = defined('QR_VERIFICATION_SECRET') ? QR_VERIFICATION_SECRET : 'protex_secret_2026';
$qrToken = hash('sha256', $id . $qrSecret);
$qrUrl = (defined('BASE_URL') ? BASE_URL : '') . '/view/FrontOffice/qrcode_contrat.php?id=' . $id . '&token=' . $qrToken;
$qrImgUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($qrUrl);

// Signature Image (mock)
$signatureUrl = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="40"><path d="M10,30 Q30,10 50,30 T90,20" stroke="%230A1931" stroke-width="2" fill="none"/></svg>';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Attestation d'Assurance - <?= $numero ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f0f4f8; color: #1a202c; display: flex; flex-direction: column; align-items: center; padding: 20px; }
        
        /* A5 Landscape: 210mm x 148mm */
        .page-a5 {
            width: 210mm;
            height: 148mm;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            padding: 15mm;
            display: flex;
            flex-direction: column;
        }

        /* Print settings */
        @media print {
            body { background: transparent; padding: 0; }
            .page-a5 { margin: 0; box-shadow: none; border: none; page-break-after: always; }
            .no-print { display: none !important; }
            @page { size: A5 landscape; margin: 0; }
        }

        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 100px;
            font-family: 'Playfair Display', serif;
            color: rgba(0, 198, 255, 0.04);
            font-weight: 800;
            white-space: nowrap;
            z-index: 0;
            pointer-events: none;
            user-select: none;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #00c6ff;
            padding-bottom: 8mm;
            margin-bottom: 8mm;
            z-index: 1;
            position: relative;
        }

        .brand-box { display: flex; align-items: center; gap: 15px; }
        .logo { width: 50px; height: 50px; background: #0A1931; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 800; color: #00c6ff; }
        .brand-name { font-size: 24px; font-weight: 800; color: #0A1931; letter-spacing: -0.5px; }
        .brand-sub { font-size: 10px; color: #718096; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }

        .doc-title-box { text-align: right; }
        .doc-title { font-size: 22px; font-family: 'Playfair Display', serif; color: #0A1931; font-weight: 700; margin-bottom: 5px; }
        .doc-ref { font-size: 12px; color: #e63946; font-weight: 700; letter-spacing: 1px; font-family: monospace; }

        /* Content */
        .content {
            display: flex;
            gap: 10mm;
            flex: 1;
            z-index: 1;
            position: relative;
        }

        .col-left { flex: 1; }
        .col-right { width: 45mm; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; gap: 10mm; }

        .info-group { margin-bottom: 6mm; }
        .info-label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #718096; font-weight: 700; margin-bottom: 2px; }
        .info-value { font-size: 13px; color: #0A1931; font-weight: 700; }
        .info-value.highlight { font-size: 15px; color: #00c6ff; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 5mm; margin-bottom: 6mm; }

        .qr-box { background: #fff; padding: 3mm; border: 1px solid #e2e8f0; border-radius: 8px; width: 35mm; height: 35mm; }
        .qr-box img { width: 100%; height: 100%; display: block; }
        .qr-text { font-size: 8px; text-align: center; color: #718096; margin-top: 2mm; line-height: 1.3; }

        .signature-box { text-align: center; }
        .signature-label { font-size: 9px; color: #0A1931; font-weight: 700; margin-bottom: 2px; }
        .signature-img { width: 30mm; opacity: 0.8; }

        /* Footer */
        .footer {
            margin-top: auto;
            border-top: 1px solid #e2e8f0;
            padding-top: 4mm;
            font-size: 8px;
            color: #a0aec0;
            text-align: center;
            z-index: 1;
            position: relative;
        }

        .print-btn {
            background: linear-gradient(135deg, #00c6ff, #0072ff);
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(0, 198, 255, 0.3);
            margin-bottom: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
        }
        .print-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(0, 198, 255, 0.4); }
    </style>
</head>
<body>

<button class="print-btn no-print" onclick="window.print()">
    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
        <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
        <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
    </svg>
    Imprimer l'attestation
</button>

<div class="page-a5">
    <div class="watermark">PROTEX</div>
    
    <div class="header">
        <div class="brand-box">
            <div class="logo">P</div>
            <div>
                <div class="brand-name">Protex</div>
                <div class="brand-sub">Assurance Digitale</div>
            </div>
        </div>
        <div class="doc-title-box">
            <div class="doc-title">Attestation d'Assurance</div>
            <div class="doc-ref">Réf: <?= $numero ?></div>
        </div>
    </div>

    <div class="content">
        <div class="col-left">
            <div class="info-group">
                <div class="info-label">Assuré(e)</div>
                <div class="info-value highlight"><?= $clientName ?></div>
            </div>

            <div class="grid-2">
                <div class="info-group">
                    <div class="info-label">Produit d'assurance</div>
                    <div class="info-value"><?= $type ?> (<?= $categorie ?>)</div>
                </div>
                <?php if (strtolower($type) === 'auto' || strtolower($type) === 'automobile'): ?>
                    <div class="info-group">
                        <div class="info-label">Véhicule assuré</div>
                        <div class="info-value"><?= $vehicule ?></div>
                    </div>
                <?php elseif (strtolower($type) === 'habitation'): ?>
                    <div class="info-group">
                        <div class="info-label">Bien assuré</div>
                        <div class="info-value" style="font-size:10px;line-height:1.2;"><?= $habitation ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="grid-2">
                <div class="info-group">
                    <div class="info-label">Date d'effet</div>
                    <div class="info-value"><?= $dateDebut ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label">Date d'échéance</div>
                    <div class="info-value"><?= $dateFin ?></div>
                </div>
            </div>

            <div class="info-group">
                <div class="info-label">Garanties souscrites</div>
                <div class="info-value" style="font-size:10px;font-weight:400;line-height:1.4;">
                    Le présent document atteste que l'assuré(e) bénéficie des garanties stipulées aux conditions particulières et générales du contrat référencé ci-dessus, dûment payé et valide.
                </div>
            </div>
        </div>

        <div class="col-right">
            <div>
                <div class="qr-box">
                    <img src="<?= $qrImgUrl ?>" alt="QR Code">
                </div>
                <div class="qr-text">Scannez ce QR Code<br>pour vérifier la validité.</div>
            </div>
            
            <div class="signature-box">
                <div class="signature-label">La Direction</div>
                <img src="<?= $signatureUrl ?>" class="signature-img" alt="Signature">
            </div>
        </div>
    </div>

    <div class="footer">
        Protex Assurance SA au capital de 10 000 000 DT - Siège social: Centre Urbain Nord, Tunis<br>
        Document généré le <?= date('d/m/Y') ?>. Cette attestation ne vaut pas preuve de paiement de la prime à l'échéance suivante.
    </div>
</div>

</body>
</html>
