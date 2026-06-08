<?php
/**
 * MODULE 8 — RC1+RC4 — Liste des Réclamations avec SLA Tracking
 * BackOffice reclamations list with SLA alerts and client tracker
 */

session_start();
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../helpers/SessionGuard.php';
require_once __DIR__ . '/../../helpers/RoleHelper.php';

SessionGuard::requireBackoffice();
$user = $_SESSION['user'];

$db = config::getConnexion();

// Get all reclamations with SLA status
$stmt = $db->query("
    SELECT 
        r.id_reclamation,
        r.objet,
        r.description,
        r.statut,
        r.priorite,
        r.sla_heures,
        r.date_creation,
        r.date_update,
        r.escalade,
        TIMESTAMPDIFF(HOUR, r.date_creation, NOW()) as heures_ecoulees,
        CASE 
            WHEN r.statut = 'open' AND TIMESTAMPDIFF(HOUR, r.date_creation, NOW()) > r.sla_heures THEN 1
            ELSE 0
        END as sla_depasse,
        CASE 
            WHEN r.statut = 'open' AND TIMESTAMPDIFF(HOUR, r.date_creation, NOW()) > (r.sla_heures * 0.8) THEN 1
            ELSE 0
        END as sla_risque,
        u.nom,
        u.prenom,
        u.email
    FROM reclamation r
    JOIN `user` u ON r.id_client = u.id_user
    ORDER BY r.priorite DESC, r.date_creation DESC
");
$reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate KPI
$total = count($reclamations);
$en_retard = count(array_filter($reclamations, fn($r) => $r['sla_depasse'] == 1));
$a_risque = count(array_filter($reclamations, fn($r) => $r['sla_risque'] == 1));
$dans_delai = $total - $en_retard;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réclamations — SLA Tracking</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sla-depasse { background-color: #fee2e2 !important; }
        .sla-risque { background-color: #fef3c7 !important; }
        .sla-ok { background-color: #dcfce7 !important; }
        .badge-priority-haute { background: #dc2626; }
        .badge-priority-medium { background: #d97706; }
        .badge-priority-basse { background: #0284c7; }
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .kpi-card {
            border-radius: 12px;
            padding: 20px;
            color: white;
            margin-bottom: 20px;
        }
        .kpi-card.en-retard { background: linear-gradient(135deg, #f5576c 0%, #ff7a7a 100%); }
        .kpi-card.a-risque { background: linear-gradient(135deg, #ffa502 0%, #ffb347 100%); }
        .kpi-card.dans-delai { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .kpi-card h3 { font-size: 14px; opacity: 0.9; margin: 0; }
        .kpi-card .value { font-size: 28px; font-weight: 700; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <h1 class="mb-4">⚖️ Gestion des Réclamations — Suivi SLA</h1>

        <!-- KPI Row -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="kpi-card en-retard pulse-animation">
                    <h3>En Retard (SLA Dépassé)</h3>
                    <div class="value"><?php echo $en_retard; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card a-risque">
                    <h3>À Risque (80% SLA)</h3>
                    <div class="value"><?php echo $a_risque; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card dans-delai">
                    <h3>Dans les Délais</h3>
                    <div class="value"><?php echo $dans_delai; ?></div>
                </div>
            </div>
        </div>

        <!-- Filter Options -->
        <div class="card mb-4">
            <div class="card-body">
                <label>
                    <input type="checkbox" id="filterRetard" /> Afficher uniquement en retard
                </label>
            </div>
        </div>

        <!-- Reclamations Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Objet</th>
                        <th>Client</th>
                        <th>Priorité</th>
                        <th>Statut</th>
                        <th>SLA</th>
                        <th>Temps écoulé</th>
                        <th>Escalade</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="reclamationsTable">
                    <?php foreach ($reclamations as $r): 
                        $row_class = $r['sla_depasse'] ? 'sla-depasse' : ($r['sla_risque'] ? 'sla-risque' : 'sla-ok');
                        $priority_badge = 'badge-priority-basse';
                        if ($r['priorite'] === 'haute') $priority_badge = 'badge-priority-haute';
                        elseif ($r['priorite'] === 'medium') $priority_badge = 'badge-priority-medium';
                    ?>
                    <tr class="<?php echo $row_class; ?>">
                        <td><strong>#<?php echo $r['id_reclamation']; ?></strong></td>
                        <td><?php echo htmlspecialchars(substr($r['objet'], 0, 50)); ?></td>
                        <td><?php echo htmlspecialchars($r['nom'] . ' ' . $r['prenom']); ?></td>
                        <td>
                            <span class="badge <?php echo $priority_badge; ?>">
                                <?php echo ucfirst($r['priorite']); ?>
                            </span>
                        </td>
                        <td><?php echo ucfirst($r['statut']); ?></td>
                        <td>
                            <?php if ($r['sla_depasse']): ?>
                                <span class="badge bg-danger pulse-animation">⚠️ DÉPASSÉ</span>
                            <?php elseif ($r['sla_risque']): ?>
                                <span class="badge bg-warning">80% du SLA</span>
                            <?php else: ?>
                                <span class="badge bg-success">✓ OK</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo $r['heures_ecoulees']; ?>h / <?php echo $r['sla_heures']; ?>h</strong>
                        </td>
                        <td>
                            <?php if ($r['escalade']): ?>
                                <span class="badge bg-danger">⬆️ Escaladée</span>
                            <?php else: ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="escalade(<?php echo $r['id_reclamation']; ?>)">
                                    Escalader
                                </button>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/view/BackOffice/listreponse.php?id=<?php echo $r['id_reclamation']; ?>" class="btn btn-sm btn-info">
                                <i class="bi bi-eye"></i> Voir
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.getElementById('filterRetard').addEventListener('change', function() {
            const rows = document.querySelectorAll('#reclamationsTable tr');
            rows.forEach(row => {
                if (this.checked) {
                    row.style.display = row.classList.contains('sla-depasse') ? '' : 'none';
                } else {
                    row.style.display = '';
                }
            });
        });

        function escalade(idReclamation) {
            if (!confirm('Escalader cette réclamation?')) return;
            
            fetch('/api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=escalader_reclamation&id=' + idReclamation
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Réclamation escaladée');
                    location.reload();
                }
            });
        }
    </script>
</body>
</html>
