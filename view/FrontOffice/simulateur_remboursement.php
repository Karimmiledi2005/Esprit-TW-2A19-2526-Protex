<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/config.php';

$id_contrat = (int)($_GET['contrat'] ?? 0);
$id_garantie = (int)($_GET['garantie'] ?? 0);

if (!$id_contrat || !$id_garantie) {
    die("Paramètres manquants.");
}

$db = config::getConnexion();

// Fetch contract, garantie and overrides
$stmt = $db->prepare("
    SELECT c.numero_contrat, g.nom_garantie, fg.plafond_formule, fg.franchise_formule, 
           cgo.plafond_custom, cgo.franchise_custom, g.plafond_couvert_garantie
    FROM contrat c
    JOIN formule_garantie fg ON c.id_formule = fg.id_formule AND fg.id_garantie = ?
    JOIN garantie g ON g.id_garantie = fg.id_garantie
    LEFT JOIN contrat_garantie_override cgo ON cgo.id_contrat = c.id_contrat AND cgo.id_garantie = g.id_garantie
    WHERE c.id_contrat = ?
");
$stmt->execute([$id_garantie, $id_contrat]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Données introuvables ou garantie non liée à ce contrat.");
}

$plafond = $data['plafond_custom'] ?? $data['plafond_formule'] ?? $data['plafond_couvert_garantie'] ?? 0;
$franchise = $data['franchise_custom'] ?? $data['franchise_formule'] ?? 0;

$montant_declare = isset($_POST['montant']) ? (float)$_POST['montant'] : null;
$remboursement = null;
$reste_charge = null;

if ($montant_declare !== null && $montant_declare > 0) {
    // Calcul
    $apres_franchise = max(0, $montant_declare - $franchise);
    $remboursement = min($apres_franchise, $plafond);
    $reste_charge = $montant_declare - $remboursement;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Simulateur Remboursement — Protex</title>
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

        .container {
            max-width: 600px;
            margin: 60px auto;
            padding: 0 20px;
        }

        .sim-card {
            background: #fff;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(26,58,122,0.08);
            border: 1px solid var(--border);
        }

        .sim-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .sim-header h1 {
            font-family: 'Sora', sans-serif;
            font-size: 24px;
            color: var(--primary);
            margin: 0 0 8px;
        }
        .sim-header p {
            color: var(--muted);
            font-size: 14px;
            margin: 0;
        }

        .context-box {
            background: rgba(26,58,122,0.03);
            border: 1px dashed var(--border);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 30px;
            font-size: 13px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .context-item span { color: var(--muted); display: block; margin-bottom: 4px; text-transform: uppercase; font-weight: 700; font-size: 11px; }
        .context-item strong { color: var(--text); font-size: 14px; }

        .form-group {
            margin-bottom: 24px;
        }
        .form-label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text);
        }
        .input-wrapper {
            position: relative;
        }
        .input-wrapper input {
            width: 100%;
            padding: 16px 20px;
            padding-right: 60px;
            font-size: 18px;
            border: 2px solid var(--border);
            border-radius: 12px;
            outline: none;
            transition: all 0.2s;
            box-sizing: border-box;
            font-weight: 600;
        }
        .input-wrapper input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(26,58,122,0.1);
        }
        .input-currency {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-weight: 700;
        }

        .btn-submit {
            width: 100%;
            background: var(--accent);
            color: #fff;
            border: none;
            padding: 16px;
            font-size: 16px;
            font-weight: 700;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }
        .btn-submit:hover {
            background: #e65a10;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255,107,26,0.3);
        }

        .result-box {
            margin-top: 30px;
            border-top: 1px solid var(--border);
            padding-top: 30px;
            animation: fadeIn 0.4s ease-out;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .result-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .result-row:last-child { border: none; }
        .result-label { font-size: 14px; color: var(--muted); }
        .result-val { font-weight: 700; font-size: 16px; }

        .result-highlight {
            background: #e7f7ec;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-top: 20px;
        }
        .result-highlight .val {
            font-family: 'Sora', sans-serif;
            font-size: 32px;
            font-weight: 900;
            color: var(--success);
            margin: 8px 0;
        }
        
        .btn-back {
            display: inline-flex; align-items: center; gap: 8px;
            margin-top: 24px; color: var(--muted); text-decoration: none; font-size: 14px; font-weight: 600;
        }
        .btn-back:hover { color: var(--primary); }
    </style>
</head>
<body>

<div class="top-bar">
    <a href="index.php" class="logo">Prot<span>ex</span></a>
</div>

<div class="container">
    <div class="sim-card">
        <div class="sim-header">
            <h1>Simulateur de Remboursement</h1>
            <p>Estimez votre reste à charge en fonction de votre contrat.</p>
        </div>

        <div class="context-box">
            <div class="context-item">
                <span>Garantie</span>
                <strong><?= htmlspecialchars($data['nom_garantie']) ?></strong>
            </div>
            <div class="context-item">
                <span>Contrat</span>
                <strong><?= htmlspecialchars($data['numero_contrat']) ?></strong>
            </div>
            <div class="context-item">
                <span>Plafond Max</span>
                <strong><?= number_format($plafond, 2, ',', ' ') ?> TND</strong>
            </div>
            <div class="context-item">
                <span>Franchise</span>
                <strong><?= number_format($franchise, 2, ',', ' ') ?> TND</strong>
            </div>
        </div>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Montant déclaré (facture)</label>
                <div class="input-wrapper">
                    <input type="number" step="0.01" min="0" name="montant" required placeholder="0.00" value="<?= htmlspecialchars($_POST['montant'] ?? '') ?>">
                    <span class="input-currency">TND</span>
                </div>
            </div>
            <button type="submit" class="btn-submit"><i class="bi bi-magic"></i> Calculer mon remboursement</button>
        </form>

        <?php if ($remboursement !== null): ?>
            <div class="result-box">
                <div class="result-row">
                    <span class="result-label">Montant de la facture</span>
                    <span class="result-val"><?= number_format($montant_declare, 2, ',', ' ') ?> TND</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Franchise déduite</span>
                    <span class="result-val" style="color:var(--danger)">- <?= number_format(min($montant_declare, $franchise), 2, ',', ' ') ?> TND</span>
                </div>
                <?php if ($montant_declare - $franchise > $plafond): ?>
                    <div class="result-row">
                        <span class="result-label">Dépassement de plafond</span>
                        <span class="result-val" style="color:var(--danger)">- <?= number_format(($montant_declare - $franchise) - $plafond, 2, ',', ' ') ?> TND</span>
                    </div>
                <?php endif; ?>

                <div class="result-highlight">
                    <div style="font-size:13px; font-weight:700; color:var(--success); text-transform:uppercase;">Remboursement estimé</div>
                    <div class="val"><?= number_format($remboursement, 2, ',', ' ') ?> TND</div>
                    <div style="font-size:12px; color:var(--muted); margin-top:8px;">
                        Reste à votre charge : <strong><?= number_format($reste_charge, 2, ',', ' ') ?> TND</strong>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div style="margin-top: 24px; background: #fff8f0; border: 1px solid #ffd9a8; border-radius: 12px; padding: 16px; font-size: 12px; color: #8c5e2a; line-height: 1.6;">
            <i class="bi bi-exclamation-triangle-fill" style="margin-right: 6px;"></i>
            <strong>Disclaimer :</strong> Ce simulateur est indicatif. Le montant exact du remboursement dépend de l'expertise du sinistre et des conditions particulières de votre contrat.
        </div>

        <div style="text-align:center;">
            <a href="garantie_detail.php?id=<?= $id_garantie ?>&contrat=<?= $id_contrat ?>" class="btn-back"><i class="bi bi-arrow-left"></i> Retour aux détails de la garantie</a>
        </div>
    </div>
</div>

</body>
</html>
