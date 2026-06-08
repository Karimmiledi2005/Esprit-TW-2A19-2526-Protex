<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();
require_once dirname(__DIR__, 2) . '/controller/ContratController.php';

$idUser = SessionGuard::userId();
$controller = new ContratController();
$contrats = $controller->getByClient($idUser);

if (!is_array($contrats)) {
    $contrats = [];
}

// Normaliser objets Contrat → tableaux pour le template
$contrats = array_map(static function ($c): array {
    if ($c instanceof Contrat) {
        return [
            'id_contrat'     => $c->getIdContrat(),
            'date_effet'     => $c->getDateDebutContrat(),
            'type_contrat'   => $c->getTypeContrat(),
            'statut'         => $c->getStatutContrat(),
            'couverture'     => $c->getNomFormule() ?: $c->getFormuleContrat(),
            'prime_annuelle' => $c->getPrimeContrat(),
        ];
    }
    if (is_array($c)) {
        return [
            'id_contrat'     => $c['id_contrat'] ?? null,
            'date_effet'     => $c['date_debut_contrat'] ?? ($c['date_effet'] ?? null),
            'type_contrat'   => $c['type_contrat'] ?? null,
            'statut'         => $c['statut_contrat'] ?? ($c['statut'] ?? null),
            'couverture'     => $c['nom_formule'] ?? ($c['formule_contrat'] ?? ($c['couverture'] ?? null)),
            'prime_annuelle' => $c['prime_contrat'] ?? ($c['prime_annuelle'] ?? null),
        ];
    }
    return [];
}, $contrats);

// Trier par date d'effet croissante
usort($contrats, static function (array $a, array $b): int {
    return strtotime((string)($a['date_effet'] ?? '')) <=> strtotime((string)($b['date_effet'] ?? ''));
});

$base = defined('BASE_URL') ? BASE_URL : '/assurance';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Timeline des Contrats — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $base ?>/view/FrontOffice/css/theme.css">
    <style>
        body { background: var(--bg-body); color: var(--text-primary); font-family: 'Inter', sans-serif; }
        .timeline {
            position: relative;
            padding: 2rem 0;
            margin: 0 auto;
            max-width: 800px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            width: 4px;
            height: 100%;
            background: var(--accent);
            transform: translateX(-50%);
            border-radius: 2px;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
            width: 50%;
            padding: 0 2rem;
        }
        .timeline-item:nth-child(odd) {
            left: 0;
            text-align: right;
        }
        .timeline-item:nth-child(even) {
            left: 50%;
            text-align: left;
        }
        .timeline-dot {
            position: absolute;
            top: 20px;
            width: 20px;
            height: 20px;
            background: var(--bg-card);
            border: 4px solid var(--accent);
            border-radius: 50%;
            z-index: 2;
        }
        .timeline-item:nth-child(odd) .timeline-dot {
            right: -10px;
        }
        .timeline-item:nth-child(even) .timeline-dot {
            left: -10px;
        }
        .timeline-content {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            transition: transform 0.3s ease;
        }
        .timeline-content:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        .timeline-date {
            color: var(--accent);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .timeline-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        @media (max-width: 768px) {
            .timeline::before { left: 20px; }
            .timeline-item { width: 100%; left: 0 !important; text-align: left !important; padding-left: 50px; padding-right: 0; }
            .timeline-item:nth-child(odd) .timeline-dot,
            .timeline-item:nth-child(even) .timeline-dot { left: 10px; }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
            <h1 class="fw-bold m-0" style="color: var(--text-primary);"><i class="bi bi-clock-history" style="color: var(--accent);"></i> Historique de mes contrats</h1>
            <a href="client.php" class="btn rounded-pill fw-bold" style="background: var(--accent); color: white;"><i class="bi bi-arrow-left"></i> Retour au tableau de bord</a>
        </div>
        
        <div class="timeline">
            <?php if (empty($contrats)): ?>
                <div class="text-center w-100" style="color: var(--text-secondary);">Aucun contrat trouvé.</div>
            <?php else: ?>
                <?php foreach ($contrats as $c): 
                    $statut = strtolower(trim((string)$c['statut']));
                    $badgeClass = match($statut) {
                        'actif', 'active' => 'bg-success',
                        'expiré', 'resilie', 'résilié' => 'bg-danger',
                        default => 'bg-warning text-dark'
                    };
                ?>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-date"><?= date('d/m/Y', strtotime($c['date_effet'])) ?></div>
                        <h3 class="timeline-title" style="color: var(--text-primary);">Contrat <?= htmlspecialchars($c['type_contrat'] ?? 'Standard') ?></h3>
                        <span class="badge <?= $badgeClass ?> mb-3"><?php $label = match($statut) { 'actif'=>'Actif', 'en attente'=>'En attente', 'expiré'=>'Expiré', 'résilié'=>'Résilié', 'refusé'=>'Refusé', default=>$c['statut'] }; ?><?= $label ?></span>
                        <p class="mb-0" style="color: var(--text-secondary);">Couverture: <?= htmlspecialchars($c['couverture'] ?? 'Standard') ?></p>
                        <p class="fw-bold mt-2 mb-3" style="color: var(--accent);"><?= number_format((float)($c['prime_annuelle'] ?? 0), 2, ',', ' ') ?> DT / an</p>
                        <a href="<?= $base ?>/download_pdf.php?id=<?= $c['id_contrat'] ?>" class="btn btn-sm btn-outline-primary rounded-pill" target="_blank">
                            <i class="bi bi-file-earmark-pdf"></i> Télécharger PDF
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
