<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
require_once dirname(__DIR__, 2) . '/helpers/RoleHelper.php';
require_once dirname(__DIR__, 2) . '/controller/AgenceController.php';
SessionGuard::requireBackoffice();

$idAgence = RoleHelper::isSuperAdmin() ? (int)($_GET['id'] ?? 0) : (RoleHelper::getAgenceId() ?: 0);
if (!$idAgence) { http_response_code(404); die('Agence non trouvée'); }

$ctrl = new AgenceController();
$db = $ctrl->getDb();
$agency = $ctrl->getKPIs($idAgence);
$agents = $ctrl->getAgentStats($idAgence);
$monthlyCA = $ctrl->getMonthlyCA($idAgence);
$topClients = $ctrl->getTopClients($idAgence);

if (!$agency) { http_response_code(404); die('Agence non trouvée'); }

// Build monthly CA array (12 months)
$caByMonth = array_fill(0, 12, 0);
$monthNames = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
foreach ($monthlyCA as $m) {
    $idx = (int)$m['mois'] - 1;
    $caByMonth[$idx] = (float)$m['total'];
}

// Reviews for A6
$stmtRev = $db->prepare("
    SELECT av.*, u.nom, u.prenom
    FROM agence_avis av
    JOIN `user` u ON av.id_client = u.id_user
    WHERE av.id_agence = ?
    ORDER BY av.created_at DESC
");
$stmtRev->execute([$idAgence]);
$reviews = $stmtRev->fetchAll(PDO::FETCH_ASSOC);

// Opening hours
$stmtH = $db->prepare("SELECT * FROM agence_horaires WHERE id_agence = ? ORDER BY jour");
$stmtH->execute([$idAgence]);
$horaires = $stmtH->fetchAll(PDO::FETCH_ASSOC);
$hByDay = [];
foreach ($horaires as $h) { $hByDay[(int)$h['jour']] = $h; }

$dayNames = ['', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agence — <?php echo htmlspecialchars($agency['nom_agence']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .kpi-card { border-radius:12px; padding:20px; color:#fff; margin-bottom:16px; }
        .kpi-card h3 { font-size:12px; opacity:.9; text-transform:uppercase; margin:0; }
        .kpi-card .value { font-size:28px; font-weight:700; margin-top:8px; }
        .agent-card { border-radius:12px; padding:16px; background:#f8f9fa; text-align:center; margin-bottom:12px; }
        .agent-avatar { width:44px; height:44px; border-radius:50%; background:linear-gradient(135deg,#667eea,#764ba2); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; margin:0 auto 8px; }
        .chart-container { position:relative; height:280px; }
        .review-card { border:1px solid #e2e8f0; border-radius:10px; padding:14px; margin-bottom:10px; }
        .review-card .stars { color:#f59e0b; }
        .horaires-row { display:flex; align-items:center; gap:8px; padding:4px 0; }
        .badge-ouvert { background:#dcfce7; color:#16a34a; }
        .badge-ferme { background:#fee2e2; color:#dc2626; }
    </style>
</head>
<body>
<div class="container-fluid p-4">
    <div class="row mb-3">
        <div class="col-md-8">
            <h1><i class="bi bi-building"></i> <?php echo htmlspecialchars($agency['nom_agence']); ?></h1>
            <p class="text-muted">
                📍 <?php echo htmlspecialchars($agency['adresse'] ?? ''); ?>
                | 📞 <?php echo htmlspecialchars($agency['telephone'] ?? ''); ?>
                | 📧 <?php echo htmlspecialchars($agency['email'] ?? ''); ?>
                | 🕐 <a href="#horaires">Horaires</a>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="admin-agences.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Retour</a>
            <a href="agenda.php?agence=<?php echo $idAgence; ?>" class="btn btn-outline-primary"><i class="bi bi-calendar-event"></i> Agenda</a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row mb-3">
        <div class="col-md-3"><div class="kpi-card" style="background:linear-gradient(135deg,#667eea,#764ba2);"><h3>Clients</h3><div class="value"><?php echo $agency['nb_clients'] ?? 0; ?></div></div></div>
        <div class="col-md-3"><div class="kpi-card" style="background:linear-gradient(135deg,#f093fb,#f5576c);"><h3>CA Total</h3><div class="value"><?php echo number_format($agency['ca_total'] ?? 0, 0, ',', ' '); ?> DT</div></div></div>
        <div class="col-md-3"><div class="kpi-card" style="background:linear-gradient(135deg,#4facfe,#00f2fe);"><h3>CA Ce Mois</h3><div class="value"><?php echo number_format($agency['ca_ce_mois'] ?? 0, 0, ',', ' '); ?> DT</div></div></div>
        <div class="col-md-3"><div class="kpi-card" style="background:linear-gradient(135deg,#43e97b,#38f9d7);"><h3>Satisfaction</h3><div class="value"><?php echo number_format($agency['satisfaction_moyenne'] ?? 0, 1); ?> ⭐</div></div></div>
    </div>
    <div class="row mb-4">
        <div class="col-md-3"><div class="kpi-card" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><h3>Contrats Actifs</h3><div class="value"><?php echo $agency['nb_contrats_actifs'] ?? 0; ?></div></div></div>
        <div class="col-md-3"><div class="kpi-card" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);"><h3>Agents</h3><div class="value"><?php echo $agency['nb_agents'] ?? 0; ?></div></div></div>
        <div class="col-md-3"><div class="kpi-card" style="background:linear-gradient(135deg,#ef4444,#dc2626);"><h3>Sinistres en cours</h3><div class="value"><?php echo $agency['nb_sinistres_en_cours'] ?? 0; ?></div></div></div>
        <div class="col-md-3"><div class="kpi-card" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);"><h3>Score Fraude</h3><div class="value"><?php echo $agency['taux_fraude'] ?? 0; ?></div></div></div>
    </div>

    <!-- Agents + Top Clients row -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header fw-bold">👥 Équipe (<?php echo count($agents); ?>)</div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($agents as $a): ?>
                        <div class="col-md-6">
                            <div class="agent-card">
                                <div class="agent-avatar"><?php echo strtoupper(substr($a['nom'],0,1).substr($a['prenom'],0,1)); ?></div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($a['prenom'].' '.$a['nom']); ?></h6>
                                <small class="text-muted">Sinistres: <strong><?php echo $a['sinistres_traites'] ?? 0; ?></strong> · Délai: <strong><?php echo round($a['delai_moyen'] ?? 0); ?>j</strong> · Taux: <strong><?php echo $a['taux_resolution'] ?? 0; ?>%</strong></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header fw-bold">🏆 Top Clients</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>#</th><th>Client</th><th>Contrats</th><th>CA Total</th></tr></thead>
                        <tbody>
                            <?php $i=1; foreach ($topClients as $tc): ?>
                            <tr><td><?php echo $i++; ?></td><td><?php echo htmlspecialchars($tc['prenom'].' '.$tc['nom']); ?><br><small class="text-muted"><?php echo htmlspecialchars($tc['email']); ?></small></td><td><?php echo $tc['nb_contrats']; ?></td><td><strong><?php echo number_format($tc['ca_total'] ?? 0, 0, ',', ' '); ?> DT</strong></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- CA Chart -->
    <div class="card mb-4">
        <div class="card-header fw-bold">📈 Chiffre d'Affaires Mensuel (12 mois)</div>
        <div class="card-body">
            <div class="chart-container"><canvas id="caChart"></canvas></div>
        </div>
    </div>

    <!-- Opening Hours (A3) -->
    <div class="card mb-4" id="horaires">
        <div class="card-header fw-bold">🕐 Horaires d'ouverture</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <?php for ($d = 1; $d <= 7; $d++):
                        $h = $hByDay[$d] ?? null;
                        $ferme = $h && $h['ferme'];
                        $ouvert = $h && !$ferme;
                    ?>
                    <div class="horaires-row">
                        <span style="width:100px;font-weight:600;"><?php echo $dayNames[$d]; ?></span>
                        <?php if ($ferme): ?>
                            <span class="badge badge-ferme">Fermé</span>
                        <?php elseif ($ouvert): ?>
                            <span class="badge badge-ouvert">🟢 <?php echo substr($h['heure_ouverture'], 0, 5); ?> — <?php echo substr($h['heure_fermeture'], 0, 5); ?></span>
                        <?php else: ?>
                            <span class="text-muted">Non défini</span>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
                <div class="col-md-4 text-end">
                    <?php if (RoleHelper::isSuperAdmin()): ?>
                    <a href="admin-agences.php?edit=<?php echo $idAgence; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Modifier horaires</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews (A6) -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold"><i class="bi bi-star-fill" style="color:#f59e0b;"></i> Avis Clients (<?php echo count($reviews); ?>)</span>
            <span>Moyenne: <strong><?php echo number_format($agency['satisfaction_moyenne'] ?? 0, 1); ?> ⭐</strong></span>
        </div>
        <div class="card-body">
            <?php if ($reviews): ?>
                <?php foreach ($reviews as $rv): ?>
                <div class="review-card" id="review-<?php echo $rv['id']; ?>">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong><?php echo htmlspecialchars($rv['prenom'].' '.$rv['nom']); ?></strong>
                            <span class="stars ms-2"><?php echo str_repeat('⭐', (int)$rv['note']); ?></span>
                            <small class="text-muted ms-2"><?php echo date('d/m/Y', strtotime($rv['created_at'])); ?></small>
                        </div>
                        <div>
                            <?php if ($rv['reponse_admin']): ?>
                                <span class="badge bg-success">Répondu</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($rv['commentaire']): ?>
                        <p class="mt-2 mb-1"><?php echo nl2br(htmlspecialchars($rv['commentaire'])); ?></p>
                    <?php endif; ?>
                    <?php if ($rv['reponse_admin']): ?>
                        <div style="background:#f0fdf4;border-left:3px solid #22c55e;padding:10px;border-radius:6px;margin-top:8px;">
                            <small class="text-muted"><i class="bi bi-reply-fill"></i> Réponse de l'agence:</small>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($rv['reponse_admin'])); ?></p>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="../api.php" style="margin-top:8px;" onsubmit="replyToReview(event, <?php echo $rv['id']; ?>, this)">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" name="reponse_admin" placeholder="Répondre à cet avis..." required>
                                <button class="btn btn-outline-success" type="submit"><i class="bi bi-reply"></i></button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">Aucun avis pour le moment.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('caChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($monthNames); ?>,
        datasets: [{
            label: 'CA Mensuel (DT)',
            data: <?php echo json_encode($caByMonth); ?>,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102,126,234,0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { callback: function(v) { return v.toLocaleString('fr-FR') + ' DT'; } } } }
    }
});

function replyToReview(e, id, form) {
    e.preventDefault();
    var input = form.querySelector('input[name="reponse_admin"]');
    var reponse = input.value.trim();
    if (!reponse) return;
    var fd = new FormData();
    fd.append('action', 'repondre_agence_avis');
    fd.append('id', id);
    fd.append('reponse_admin', reponse);
    fetch('../api.php', { method:'POST', body:fd })
    .then(function(r){ return r.json(); })
    .then(function(data){
        if (data.success) { location.reload(); }
        else { alert('Erreur'); }
    })
    .catch(function(){ alert('Erreur réseau'); });
}
</script>
</body>
</html>
