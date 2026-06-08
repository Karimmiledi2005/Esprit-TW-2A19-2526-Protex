<?php
/**
 * C6: Simulateur de renouvellement
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();

require_once __DIR__ . '/../../controller/ContratController.php';

$idUser = (int) $_SESSION['user_id'];
$idContrat = (int) ($_GET['id'] ?? 0);

if ($idContrat <= 0) {
    header('Location: contrat.php?error=id_invalide');
    exit();
}

$controller = new ContratController();
$contrat = $controller->getById($idContrat);

if (!$contrat || (int)($contrat['id_user'] ?? $contrat['id_client'] ?? 0) !== $idUser) {
    header('Location: contrat.php?error=acces_refuse');
    exit();
}

$statut = strtolower(trim((string)$contrat['statut_contrat']));
if (!in_array($statut, ['actif', 'expiré', 'résilié'], true)) {
    header('Location: contrat.php?error=renouvellement_impossible');
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newFormuleId = (int)($_POST['new_formule'] ?? 0);
    if ($newFormuleId) {
        $db = config::getConnexion();
        $f = $db->prepare("SELECT * FROM formule WHERE id_formule = ?");
        $f->execute([$newFormuleId]);
        $newF = $f->fetch(PDO::FETCH_ASSOC);
        if ($newF) {
            $update = $db->prepare("UPDATE contrat SET id_formule = ?, formule_contrat = ?, prime_contrat = ?, franchise_contrat = ? WHERE id_contrat = ?");
            $update->execute([$newFormuleId, $newF['nom_formule'], $newF['prix_formule'], $newF['franchise_formule'], $idContrat]);
        }
    }
    
    $newId = $controller->renewContrat($idContrat);
    if ($newId) {
        header('Location: contratshow.php?id=' . $newId . '&success=renewal');
    } else {
        header('Location: contrat.php?error=renouvellement_impossible');
    }
    exit();
}

$db = config::getConnexion();
$formules = [];
if (!empty($contrat['id_categorie'])) {
    $stmt = $db->prepare("SELECT * FROM formule WHERE id_categorie = ? ORDER BY prix_formule ASC");
    $stmt->execute([$contrat['id_categorie']]);
    $formules = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$base = defined('BASE_URL') ? BASE_URL : '/assurance';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Simulateur de Renouvellement — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $base ?>/view/FrontOffice/css/theme.css">
    <style>
        body { background: var(--bg-body); color: var(--text-primary); font-family: 'Inter', sans-serif; }
        .sim-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }
        .formule-option {
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .formule-option:hover {
            border-color: rgba(255,107,26,0.3);
            background: rgba(255,107,26,0.02);
        }
        .formule-option input[type="radio"] { display: none; }
        .formule-option input[type="radio"]:checked + .formule-content {
            color: inherit;
        }
        .formule-option.selected {
            border-color: var(--accent);
            background: rgba(255,107,26,0.05);
            box-shadow: 0 4px 15px rgba(255,107,26,0.1);
        }
        .diff-badge {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 8px;
            font-weight: 700;
        }
        .diff-pos { background: rgba(230,57,70,0.1); color: #e63946; }
        .diff-neg { background: rgba(46,213,115,0.1); color: #2ed573; }
        .diff-zero { background: rgba(148,163,184,0.1); color: #94a3b8; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <h1 class="fw-bold m-0"><i class="bi bi-arrow-repeat text-primary"></i> Simulation de renouvellement</h1>
            <a href="contrat.php" class="btn btn-outline-secondary rounded-pill">Annuler</a>
        </div>

        <div class="row">
            <div class="col-lg-5">
                <div class="sim-card h-100">
                    <h3 class="mb-4">Contrat Actuel</h3>
                    <div class="mb-3">
                        <small class="text-muted text-uppercase fw-bold">Numéro</small>
                        <div class="fs-5 fw-bold"><?= htmlspecialchars($contrat['numero_contrat']) ?></div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted text-uppercase fw-bold">Formule actuelle</small>
                        <div class="fs-5 fw-bold text-primary"><?= htmlspecialchars($contrat['nom_formule'] ?? $contrat['formule_contrat'] ?? 'Standard') ?></div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted text-uppercase fw-bold">Prime actuelle</small>
                        <div class="fs-4 fw-bold" id="currentPrime" data-value="<?= (float)($contrat['prime_contrat'] ?? 0) ?>">
                            <?= number_format((float)($contrat['prime_contrat'] ?? 0), 2, ',', ' ') ?> DT / an
                        </div>
                    </div>
                    <div class="alert alert-info mt-4" style="border-radius:12px;">
                        <i class="bi bi-info-circle-fill"></i> Le renouvellement créera une demande <strong>en attente</strong> qui sera validée par notre équipe.
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="sim-card">
                    <h3 class="mb-4">Nouvelle Formule</h3>
                    <form method="POST" id="renewForm">
                        <input type="hidden" name="action" value="renew">
                        <?php if (empty($formules)): ?>
                            <div class="alert alert-warning">Aucune formule disponible pour cette catégorie.</div>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 mt-3 fw-bold w-100">
                                Renouveler avec la formule actuelle
                            </button>
                        <?php else: ?>
                            <?php foreach ($formules as $f): 
                                $currentPrime = (float)($contrat['prime_contrat'] ?? 0);
                                $newPrime = (float)$f['prix_formule'];
                                $diff = $newPrime - $currentPrime;
                                $isCurrent = ($f['id_formule'] == ($contrat['id_formule'] ?? 0));
                            ?>
                            <label class="formule-option d-block <?= $isCurrent ? 'selected' : '' ?>" onclick="selectFormule(this)">
                                <input type="radio" name="new_formule" value="<?= $f['id_formule'] ?>" <?= $isCurrent ? 'checked' : '' ?>>
                                <div class="formule-content d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fs-5 fw-bold mb-1">
                                            <?= htmlspecialchars($f['nom_formule']) ?>
                                            <?php if ($isCurrent): ?><span class="badge bg-primary ms-2" style="font-size:10px;">ACTUELLE</span><?php endif; ?>
                                        </div>
                                        <div class="text-muted small">Franchise: <?= number_format((float)$f['franchise_formule'], 2) ?> DT</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold fs-5"><?= number_format($newPrime, 2, ',', ' ') ?> DT</div>
                                        <?php if ($diff > 0): ?>
                                            <div class="diff-badge diff-pos">+ <?= number_format($diff, 2, ',', ' ') ?> DT</div>
                                        <?php elseif ($diff < 0): ?>
                                            <div class="diff-badge diff-neg">- <?= number_format(abs($diff), 2, ',', ' ') ?> DT</div>
                                        <?php else: ?>
                                            <div class="diff-badge diff-zero">Même prix</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </label>
                            <?php endforeach; ?>
                            <div class="mt-4 text-center">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow-sm" style="background:#FF6B1A; border:none;">
                                    <i class="bi bi-check2-circle"></i> Confirmer le renouvellement
                                </button>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function selectFormule(element) {
            document.querySelectorAll('.formule-option').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
        }
    </script>
</body>
</html>
