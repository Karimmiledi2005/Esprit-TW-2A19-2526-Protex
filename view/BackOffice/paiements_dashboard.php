<?php
/**
 * MODULE 7 — P1 — Dashboard Financier Paiements
 * BackOffice financial KPI dashboard with charts
 */

session_start();
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../helpers/SessionGuard.php';
require_once __DIR__ . '/../../helpers/RoleHelper.php';

// Protection: BackOffice only
SessionGuard::requireBackoffice();
$user = $_SESSION['user'];
$isAdmin = RoleHelper::hasRole($user['role'], ['admin', 'superadmin']);
$idAgence = $isAdmin ? (int)($_GET['agence_id'] ?? $user['id_agence']) : $user['id_agence'];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Financier — Paiements</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .kpi-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .kpi-card.alt1 { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .kpi-card.alt2 { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .kpi-card.alt3 { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .kpi-icon {
            font-size: 32px;
            opacity: 0.8;
        }
        .kpi-content h3 {
            font-size: 14px;
            font-weight: 600;
            opacity: 0.9;
            margin: 0;
        }
        .kpi-value {
            font-size: 28px;
            font-weight: 700;
            margin: 8px 0 0;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 30px;
        }
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-radius: 8px;
            margin-bottom: 24px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px 8px 0 0;
            font-weight: 600;
            padding: 16px;
        }
        .refresh-btn {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        .last-updated {
            font-size: 12px;
            color: #999;
            margin-top: 16px;
        }
        table.top-clients {
            font-size: 14px;
        }
        table.top-clients th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">💳 Tableau de Bord Financier</h1>
            <button class="btn btn-outline-primary refresh-btn" id="refreshBtn">
                <i class="bi bi-arrow-clockwise"></i> Actualiser
            </button>
        </div>

        <!-- KPI Cards Row -->
        <div class="row mb-4" id="kpiCards">
            <div class="col-md-3 mb-3">
                <div class="kpi-card">
                    <i class="bi bi-cash-coin kpi-icon"></i>
                    <div class="kpi-content">
                        <h3>CA ce mois</h3>
                        <div class="kpi-value" id="kpi-ca-mois">0 DT</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="kpi-card alt1">
                    <i class="bi bi-graph-up kpi-icon"></i>
                    <div class="kpi-content">
                        <h3>CA cumulé année</h3>
                        <div class="kpi-value" id="kpi-ca-annuel">0 DT</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="kpi-card alt2">
                    <i class="bi bi-exclamation-triangle kpi-icon"></i>
                    <div class="kpi-content">
                        <h3>Paiements en retard</h3>
                        <div class="kpi-value" id="kpi-retard-count">0</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="kpi-card alt3">
                    <i class="bi bi-percent kpi-icon"></i>
                    <div class="kpi-content">
                        <h3>Taux de recouvrement</h3>
                        <div class="kpi-value" id="kpi-taux-recouvrement">0%</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        📈 CA Mensuel (Derniers 12 mois)
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartCaMensuel"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        📊 Répartition par Type d'Offre
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartOffreType"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="row mb-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        ⏰ Paiements À temps vs En retard (par mois)
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 250px;">
                            <canvas id="chartPaiementStatus"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Clients Table -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        🏆 Top 5 Clients par Chiffre d'Affaires
                    </div>
                    <div class="card-body">
                        <table class="table table-hover top-clients">
                            <thead>
                                <tr>
                                    <th>Rang</th>
                                    <th>Nom du Client</th>
                                    <th>Contrats Actifs</th>
                                    <th>CA Total</th>
                                    <th>CA ce mois</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="topClientsBody">
                                <tr><td colspan="6" class="text-center">Chargement...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="last-updated">
            Dernière mise à jour : <span id="lastUpdate">--:--</span>
        </div>
    </div>

    <script>
        let chartCaMensuel, chartOffreType, chartPaiementStatus;

        async function loadDashboard() {
            try {
                const response = await fetch(`/api.php?action=paiement_dashboard_stats&agence_id=${<?php echo $idAgence; ?>}`);
                const data = await response.json();

                if (!data.success) {
                    console.error('Error:', data.message);
                    return;
                }

                const stats = data.data;

                // Update KPI cards
                document.getElementById('kpi-ca-mois').textContent = formatNumber(stats.ca_mois) + ' DT';
                document.getElementById('kpi-ca-annuel').textContent = formatNumber(stats.ca_annuel) + ' DT';
                document.getElementById('kpi-retard-count').textContent = stats.retard_count || 0;
                document.getElementById('kpi-taux-recouvrement').textContent = (stats.taux_recouvrement || 0) + '%';

                // CA Mensuel Chart
                const ctxCa = document.getElementById('chartCaMensuel').getContext('2d');
                if (chartCaMensuel) chartCaMensuel.destroy();
                chartCaMensuel = new Chart(ctxCa, {
                    type: 'line',
                    data: {
                        labels: stats.ca_mensuel_labels,
                        datasets: [{
                            label: 'CA (DT)',
                            data: stats.ca_mensuel_data,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: '#667eea'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });

                // Offre Type Chart
                const ctxOffre = document.getElementById('chartOffreType').getContext('2d');
                if (chartOffreType) chartOffreType.destroy();
                chartOffreType = new Chart(ctxOffre, {
                    type: 'doughnut',
                    data: {
                        labels: stats.offre_type_labels,
                        datasets: [{
                            data: stats.offre_type_data,
                            backgroundColor: ['#667eea', '#f093fb', '#4facfe', '#43e97b', '#ffa502']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });

                // Paiement Status Chart
                const ctxStatus = document.getElementById('chartPaiementStatus').getContext('2d');
                if (chartPaiementStatus) chartPaiementStatus.destroy();
                chartPaiementStatus = new Chart(ctxStatus, {
                    type: 'bar',
                    data: {
                        labels: stats.paiement_status_labels,
                        datasets: [
                            {
                                label: 'À temps',
                                data: stats.paiement_status_ontime,
                                backgroundColor: '#43e97b'
                            },
                            {
                                label: 'En retard',
                                data: stats.paiement_status_late,
                                backgroundColor: '#f5576c'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { x: { stacked: false }, y: { stacked: false } }
                    }
                });

                // Top Clients Table
                const tbody = document.getElementById('topClientsBody');
                tbody.innerHTML = '';
                stats.top_clients.forEach((client, idx) => {
                    tbody.innerHTML += `
                        <tr>
                            <td><strong>${idx + 1}</strong></td>
                            <td>${client.nom} ${client.prenom}</td>
                            <td>${client.contrats_actifs}</td>
                            <td>${formatNumber(client.ca_total)} DT</td>
                            <td>${formatNumber(client.ca_mois)} DT</td>
                            <td>
                                <a href="/view/BackOffice/admin-users.php?view=user&id=${client.id_user}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    `;
                });

                // Update timestamp
                const now = new Date();
                document.getElementById('lastUpdate').textContent = now.toLocaleTimeString('fr-FR');
            } catch (error) {
                console.error('Error loading dashboard:', error);
            }
        }

        function formatNumber(num) {
            return (num || 0).toLocaleString('fr-FR', { maximumFractionDigits: 0 });
        }

        // Refresh button
        document.getElementById('refreshBtn').addEventListener('click', loadDashboard);

        // Initial load
        loadDashboard();

        // Auto-refresh every 60 seconds
        setInterval(loadDashboard, 60000);
    </script>
</body>
</html>
