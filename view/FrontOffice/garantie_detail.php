<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once dirname(__DIR__, 2) . '/config.php';

$id_garantie = (int)($_GET['id'] ?? 0);
$id_contrat = (int)($_GET['contrat'] ?? 0);

if (!$id_garantie) {
    header("Location: index.php");
    exit;
}

$db = config::getConnexion();

// Fetch garantie
$stmt = $db->prepare("
    SELECT g.*, c.nom_categorie 
    FROM garantie g
    LEFT JOIN categorie c ON g.id_categorie = c.id_categorie
    WHERE g.id_garantie = ?
");
$stmt->execute([$id_garantie]);
$garantie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$garantie) {
    die("Garantie introuvable");
}

$plafond = (float)$garantie['plafond_couvert_garantie'];
$franchise = 0; // Default if not linked

// If contract context is provided, try to fetch override or formule specific values
if ($id_contrat) {
    $stmtC = $db->prepare("
        SELECT fg.plafond_formule, fg.franchise_formule, cgo.plafond_custom, cgo.franchise_custom
        FROM contrat c
        JOIN formule_garantie fg ON fg.id_formule = c.id_formule AND fg.id_garantie = ?
        LEFT JOIN contrat_garantie_override cgo ON cgo.id_contrat = c.id_contrat AND cgo.id_garantie = ?
        WHERE c.id_contrat = ?
    ");
    $stmtC->execute([$id_garantie, $id_garantie, $id_contrat]);
    $context = $stmtC->fetch(PDO::FETCH_ASSOC);

    if ($context) {
        $plafond = $context['plafond_custom'] ?? $context['plafond_formule'] ?? $plafond;
        $franchise = $context['franchise_custom'] ?? $context['franchise_formule'] ?? 0;
    }
}

// Hardcoded examples based on category or name keywords
$cat = strtolower($garantie['nom_categorie'] ?? '');
$name = strtolower($garantie['nom_garantie'] ?? '');

$situations_couvertes = [];
$situations_exclues = [];

if (str_contains($cat, 'auto') || str_contains($name, 'véhicule') || str_contains($name, 'bris')) {
    $situations_couvertes = [
        "Fissure sur le pare-brise suite à une projection de gravier.",
        "Remplacement de la vitre latérale brisée après une tentative de vol.",
        "Dommages accidentels aux phares en verre."
    ];
    $situations_exclues = [
        "Rayures mineures qui n'altèrent pas la visibilité.",
        "Dégâts causés intentionnellement par l'assuré.",
        "Remplacement des rétroviseurs (couvert par une autre garantie)."
    ];
} elseif (str_contains($cat, 'santé') || str_contains($name, 'maladie')) {
    $situations_couvertes = [
        "Consultation chez un médecin généraliste ou spécialiste.",
        "Achat de médicaments prescrits remboursables.",
        "Frais d'hospitalisation suite à un accident ou une maladie soudaine."
    ];
    $situations_exclues = [
        "Interventions de chirurgie esthétique non réparatrice.",
        "Médicaments non prescrits (automédication).",
        "Cures thermales de confort."
    ];
} else {
    $situations_couvertes = [
        "Dommages directs liés au risque spécifié dans les conditions générales.",
        "Frais annexes de première nécessité (selon formule).",
        "Intervention rapide de nos experts en cas de sinistre."
    ];
    $situations_exclues = [
        "Dommages résultant de la vétusté ou du manque d'entretien.",
        "Fautes intentionnelles ou dolosives de l'assuré.",
        "Guerres, émeutes, ou catastrophes non reconnues officiellement."
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($garantie['nom_garantie']) ?> — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #1A3A7A;
            --accent: #FF6B1A;
            --bg: #f4f6fb;
            --card: #fff;
            --text: #15233C;
            --muted: #6B7A90;
            --success: #10b981;
            --danger: #ef4444;
            --border: rgba(26,58,122,0.1);
        }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 0; }
        .top-bar {
            background: linear-gradient(135deg, #0f2557 0%, #1A3A7A 100%);
            padding: 14px 32px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 100; box-shadow: 0 4px 20px rgba(15,37,87,0.3);
        }
        .logo { font-family: 'Sora', sans-serif; font-size: 22px; font-weight: 900; color: #fff; text-decoration: none; }
        .logo span { color: var(--accent); }
        
        .hero {
            background: #fff;
            padding: 60px 20px;
            text-align: center;
            border-bottom: 1px solid var(--border);
        }
        .hero-cat {
            display: inline-block;
            background: rgba(26,58,122,0.1);
            color: var(--primary);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
        }
        .hero-title {
            font-family: 'Sora', sans-serif;
            font-size: 38px;
            font-weight: 900;
            margin-bottom: 16px;
            color: var(--text);
        }
        .hero-desc {
            font-size: 16px;
            color: var(--muted);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Gauges */
        .gauge-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 30px;
            display: flex;
            gap: 40px;
            box-shadow: 0 10px 30px rgba(26,58,122,0.05);
        }
        @media(max-width: 600px) { .gauge-card { flex-direction: column; gap: 20px; } }
        .gauge-col { flex: 1; }
        .gauge-label { font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; margin-bottom: 8px; }
        .gauge-val { font-family: 'Sora', sans-serif; font-size: 28px; font-weight: 900; color: var(--primary); margin-bottom: 12px; }
        .gauge-bar-bg { background: #f0f2f8; height: 8px; border-radius: 4px; overflow: hidden; }
        .gauge-bar-fill { background: var(--accent); height: 100%; border-radius: 4px; }
        .gauge-bar-fill.success { background: var(--success); }

        /* Scenarios */
        .scenarios-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 40px;
        }
        @media(max-width: 768px) { .scenarios-grid { grid-template-columns: 1fr; } }
        
        .scenario-box {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
        }
        .scenario-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .scenario-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
            font-size: 14px;
            color: var(--muted);
            line-height: 1.5;
        }
        .scenario-item:last-child { margin-bottom: 0; }
        .icon-check { color: var(--success); font-size: 18px; margin-top: -2px; }
        .icon-cross { color: var(--danger); font-size: 18px; margin-top: -2px; }

        /* Accordion */
        .accordion {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 40px;
        }
        .acc-header {
            padding: 20px 24px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9ff;
        }
        .acc-content {
            padding: 24px;
            border-top: 1px solid var(--border);
            font-size: 14px;
            color: var(--muted);
            line-height: 1.6;
        }
        .acc-content ol { padding-left: 20px; margin: 0; }
        .acc-content li { margin-bottom: 12px; }
        
        .btn-back {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 24px; background: rgba(26,58,122,0.1); color: var(--primary);
            text-decoration: none; font-weight: 600; border-radius: 12px;
            transition: all 0.2s;
        }
        .btn-back:hover { background: rgba(26,58,122,0.15); }
    </style>
</head>
<body>

<div class="top-bar">
    <a href="index.php" class="logo">Prot<span>ex</span></a>
    <div>
        <a href="client.php" style="color:#fff; text-decoration:none; font-size:14px; font-weight:600;"><i class="bi bi-person-circle"></i> Espace Client</a>
    </div>
</div>

<div class="hero">
    <div class="hero-cat"><i class="bi bi-folder2-open"></i> <?= htmlspecialchars($garantie['nom_categorie'] ?? 'Garantie') ?></div>
    <h1 class="hero-title"><?= htmlspecialchars($garantie['nom_garantie']) ?></h1>
    <div class="hero-desc"><?= htmlspecialchars($garantie['description_garantie']) ?></div>
</div>

<div class="container">
    
    <?php if($id_contrat): ?>
        <div style="background: rgba(255,107,26,0.1); color: #c04f0c; padding: 12px 16px; border-radius: 12px; font-size: 13px; font-weight: 600; margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
            <i class="bi bi-info-circle-fill"></i> Ces montants sont personnalisés pour votre contrat #<?= $id_contrat ?>.
        </div>
    <?php endif; ?>

    <div class="gauge-card">
        <div class="gauge-col">
            <div class="gauge-label">Plafond de remboursement</div>
            <div class="gauge-val"><?= number_format($plafond, 2, ',', ' ') ?> TND</div>
            <div class="gauge-bar-bg">
                <div class="gauge-bar-fill success" style="width: 100%;"></div>
            </div>
            <div style="font-size: 11px; color: var(--muted); margin-top: 8px;">Montant maximum couvert par sinistre/an.</div>
        </div>
        <div class="gauge-col">
            <div class="gauge-label">Franchise applicable</div>
            <div class="gauge-val"><?= number_format($franchise, 2, ',', ' ') ?> TND</div>
            <div class="gauge-bar-bg">
                <!-- Visual width relative to 1000 for demo -->
                <div class="gauge-bar-fill" style="width: <?= min(100, ($franchise/1000)*100) ?>%;"></div>
            </div>
            <div style="font-size: 11px; color: var(--muted); margin-top: 8px;">Montant restant à votre charge.</div>
        </div>
    </div>

    <div class="scenarios-grid">
        <div class="scenario-box">
            <div class="scenario-title"><i class="bi bi-shield-check" style="color:var(--success)"></i> Situations couvertes</div>
            <?php foreach($situations_couvertes as $sit): ?>
                <div class="scenario-item">
                    <i class="bi bi-check-circle-fill icon-check"></i>
                    <div><?= htmlspecialchars($sit) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="scenario-box">
            <div class="scenario-title"><i class="bi bi-shield-x" style="color:var(--danger)"></i> Situations exclues</div>
            <?php foreach($situations_exclues as $sit): ?>
                <div class="scenario-item">
                    <i class="bi bi-x-circle-fill icon-cross"></i>
                    <div><?= htmlspecialchars($sit) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="accordion">
        <div class="acc-header" onclick="document.getElementById('acc-body').style.display = document.getElementById('acc-body').style.display === 'none' ? 'block' : 'none';">
            <span><i class="bi bi-megaphone" style="margin-right:8px; color:var(--primary);"></i> Comment déclarer un sinistre pour cette garantie ?</span>
            <i class="bi bi-chevron-down"></i>
        </div>
        <div class="acc-content" id="acc-body" style="display:none;">
            <ol>
                <li><strong>Sécurisez les lieux :</strong> Assurez-vous d'être en sécurité avant toute démarche.</li>
                <li><strong>Rassemblez les preuves :</strong> Prenez des photos détaillées des dommages et notez la date et l'heure exactes.</li>
                <li><strong>Connectez-vous à votre Espace Client :</strong> Allez dans la rubrique "Mes Sinistres" et cliquez sur "Déclarer un sinistre".</li>
                <li><strong>Sélectionnez la garantie :</strong> Choisissez "<?= htmlspecialchars($garantie['nom_garantie']) ?>" et décrivez les circonstances.</li>
                <li><strong>Envoyez les justificatifs :</strong> Téléchargez vos photos et tout document utile (facture, constat).</li>
            </ol>
            <div style="margin-top: 16px; font-weight: 600; color: var(--text);">
                Besoin d'aide ? Contactez notre assistance 24/7.
            </div>
        </div>
    </div>

    <div style="text-align: center; margin-bottom: 60px;">
        <a href="javascript:history.back()" class="btn-back"><i class="bi bi-arrow-left"></i> Retour</a>
        <?php if($id_contrat): ?>
            <a href="simulateur_remboursement.php?contrat=<?= $id_contrat ?>&garantie=<?= $id_garantie ?>" class="btn-back" style="background:var(--accent); color:#fff;"><i class="bi bi-calculator"></i> Simuler un remboursement</a>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
