<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
$_boRole     = SessionGuard::role();
$_boIsSuper  = $_boRole === 'superadmin';
$_boIsAdmin  = in_array($_boRole, ['superadmin', 'admin']);
$_boIsAgent  = $_boRole === 'agent';
?><!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>Dashboard — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <style>
        @keyframes shimmer {
            0% {
                background-position: -200% center;
            }

            100% {
                background-position: 200% center;
            }
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: linear-gradient(145deg, rgba(30, 41, 59, .85), rgba(15, 23, 42, .9));
            border: 1px solid rgba(255, 255, 255, .06);
            border-radius: 16px;
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: transform .25s cubic-bezier(.4, 0, .2, 1), box-shadow .25s cubic-bezier(.4, 0, .2, 1);
            cursor: default;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, .35);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            border-radius: 16px 16px 0 0;
        }

        .stat-card.blue::before {
            background: linear-gradient(90deg, #3b82f6, #60a5fa, #3b82f6);
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
        }

        .stat-card.gold::before {
            background: linear-gradient(90deg, #eab308, #facc15, #eab308);
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
        }

        .stat-card.green::before {
            background: linear-gradient(90deg, #22c55e, #4ade80, #22c55e);
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
        }

        .stat-card.purple::before {
            background: linear-gradient(90deg, #a855f7, #c084fc, #a855f7);
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
        }

        .stat-card .stat-bg-icon {
            position: absolute;
            right: -8px;
            bottom: -12px;
            font-size: 80px;
            opacity: .04;
            transform: rotate(-12deg);
            transition: opacity .25s;
        }

        .stat-card:hover .stat-bg-icon {
            opacity: .08;
        }

        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
        }

        .stat-card.blue .stat-icon {
            background: rgba(59, 130, 246, .12);
            color: #60a5fa;
        }

        .stat-card.gold .stat-icon {
            background: rgba(234, 179, 8, .12);
            color: #facc15;
        }

        .stat-card.green .stat-icon {
            background: rgba(34, 197, 94, .12);
            color: #4ade80;
        }

        .stat-card.purple .stat-icon {
            background: rgba(168, 85, 247, .12);
            color: #c084fc;
        }

        .stat-card .stat-value {
            font-family: var(--font-display);
            font-size: 32px;
            font-weight: 800;
            color: #fff;
            line-height: 1;
            margin-bottom: 6px;
            position: relative;
            z-index: 1;
        }

        .stat-card .stat-label {
            font-size: 13px;
            color: var(--text-secondary);
            font-weight: 500;
            position: relative;
            z-index: 1;
        }

        .stat-card .stat-trend {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
            padding: 3px 8px;
            border-radius: 6px;
            position: relative;
            z-index: 1;
        }

        .stat-trend.up {
            background: rgba(34, 197, 94, .1);
            color: #4ade80;
        }

        .stat-trend.down {
            background: rgba(239, 68, 68, .1);
            color: #f87171;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .chart-card {
            background: linear-gradient(145deg, rgba(30, 41, 59, .85), rgba(15, 23, 42, .9));
            border: 1px solid rgba(255, 255, 255, .06);
            border-radius: 16px;
            padding: 24px;
            transition: box-shadow .25s;
        }

        .chart-card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, .25);
        }

        .chart-card h3 {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #fff;
            display: flex;
            align-items: center;
        }

        .chart-card h3 .chart-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 16px;
        }

        .chart-icon.blue {
            background: rgba(59, 130, 246, .12);
            color: #60a5fa;
        }

        .chart-icon.pink {
            background: rgba(244, 114, 182, .12);
            color: #f472b6;
        }

        .chart-icon.amber {
            background: rgba(251, 191, 36, .12);
            color: #fbbf24;
        }

        .chart-wrap {
            position: relative;
            height: 300px;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
        }

        .kpi-card {
            background: rgba(255, 255, 255, .03);
            border: 1px solid rgba(255, 255, 255, .06);
            border-radius: 12px;
            padding: 18px;
            text-align: center;
            transition: background .2s, border-color .2s;
            position: relative;
            overflow: hidden;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            border-radius: 0 4px 4px 0;
        }

        .kpi-card.total::before {
            background: #60a5fa;
        }

        .kpi-card.active::before {
            background: #4ade80;
        }

        .kpi-card.inactive::before {
            background: #f87171;
        }

        .kpi-card:hover {
            background: rgba(255, 255, 255, .05);
            border-color: rgba(255, 255, 255, .1);
        }

        .kpi-card .kpi-value {
            font-size: 28px;
            font-weight: 700;
            color: #fff;
        }

        .kpi-card .kpi-label {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 4px;
            font-weight: 500;
        }

        .page-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 28px;
        }

        .page-title {
            font-family: var(--font-display);
            font-size: 26px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -.02em;
        }

        .page-breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 6px;
        }

        .page-breadcrumb a {
            color: #60a5fa;
            text-decoration: none;
        }

        .page-breadcrumb a:hover {
            text-decoration: underline;
        }

        .greeting {
            font-size: 14px;
            color: var(--text-secondary);
            margin-top: 4px;
            font-weight: 400;
        }

        @media(max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Loading state */
        .chart-loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-secondary);
            font-size: 13px;
        }

        .chart-loading .spinner {
            width: 28px;
            height: 28px;
            border: 3px solid rgba(255, 255, 255, .1);
            border-top-color: #60a5fa;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 10px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Fade-in for cards */
        .stat-card,
        .chart-card {
            animation: fadeSlideIn .5s ease-out both;
        }

        .stat-card:nth-child(1) {
            animation-delay: .05s;
        }

        .stat-card:nth-child(2) {
            animation-delay: .1s;
        }

        .stat-card:nth-child(3) {
            animation-delay: .15s;
        }

        .stat-card:nth-child(4) {
            animation-delay: .2s;
        }

        @keyframes fadeSlideIn {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>

    <div class="background"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="layout">
        <?php require_once __DIR__.'/assets/includes/sidebar.php'; ?>

        <main class="main">
            <div class="topbar">
                <div>
                    <div class="topbar-title">Tableau de bord</div>
                    <div class="topbar-sub" id="topbarDate"></div>
                </div>
            </div>

            <div class="content">
                <div class="page-header-bar">
                    <div>
                        <div class="page-title">Bienvenue 👋</div>
                        <div class="greeting">Voici un aperçu de votre plateforme d'assurance</div>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="stats-grid">
                    <div class="stat-card blue hover-lift">
                        <div class="stat-icon"><i class="bi bi-shield-exclamation"></i></div>
                        <div class="stat-value" id="kpi-sinistres">—</div>
                        <div class="stat-label">Sinistres<?= $_boIsAgent ? ' assignés' : ($_boRole === 'admin' ? ' agence' : '') ?></div>
                    </div>
                    <div class="stat-card gold hover-lift">
                        <div class="stat-icon"><i class="bi bi-chat-dots"></i></div>
                        <div class="stat-value" id="kpi-reclamations">—</div>
                        <div class="stat-label">Réclamations en attente</div>
                    </div>
                    <?php if ($_boIsAdmin): ?>
                    <div class="stat-card green hover-lift">
                        <div class="stat-icon"><i class="bi bi-file-earmark-check"></i></div>
                        <div class="stat-value" id="kpi-contrats">—</div>
                        <div class="stat-label">Contrats actifs</div>
                    </div>
                    <div class="stat-card purple hover-lift">
                        <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                        <div class="stat-value" id="kpi-revenus">—</div>
                        <div class="stat-label">Revenus</div>
                    </div>
                    <?php endif; ?>
                    <?php if ($_boIsSuper): ?>
                    <div class="stat-card red hover-lift">
                        <div class="stat-icon"><i class="bi bi-people"></i></div>
                        <div class="stat-value" id="kpiActifs">—</div>
                        <div class="stat-label">Utilisateurs actifs</div>
                    </div>
                    <div class="stat-card gold hover-lift">
                        <div class="stat-icon"><i class="bi bi-person-plus"></i></div>
                        <div class="stat-value" id="kpiNew">—</div>
                        <div class="stat-label">Nouveaux ce mois</div>
                    </div>
                    <div class="stat-card purple hover-lift">
                        <div class="stat-icon"><i class="bi bi-person-slash"></i></div>
                        <div class="stat-value" id="kpiBloques">—</div>
                        <div class="stat-label">Comptes bloqués</div>
                    </div>
                    <?php endif; ?>
                
                </div>

                <!-- Charts Row 1 -->
                <div class="charts-grid">
                    <div class="chart-card">
                        <h3>
                            <span class="chart-icon blue"><i class="bi bi-graph-up"></i></span>
                            Inscriptions par mois
                        </h3>
                        <div class="chart-wrap">
                            <div class="chart-loading" id="lineLoading">
                                <div class="spinner"></div>Chargement...
                            </div>
                            <canvas id="lineChart" style="display:none;"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <h3>
                            <span class="chart-icon pink"><i class="bi bi-pie-chart"></i></span>
                            Répartition par rôle
                        </h3>
                        <div class="chart-wrap">
                            <div class="chart-loading" id="pieLoading">
                                <div class="spinner"></div>Chargement...
                            </div>
                            <canvas id="pieChart" style="display:none;"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="charts-grid">
                    <div class="chart-card" style="grid-column: span 2;">
                        <h3>
                            <span class="chart-icon amber"><i class="bi bi-calendar-check"></i></span>
                            Connexions hebdomadaires
                        </h3>
                        <div class="chart-wrap" style="height: 250px;">
                            <div class="chart-loading" id="barLoading">
                                <div class="spinner"></div>Chargement...
                            </div>
                            <canvas id="barChart" style="display:none;"></canvas>
                        </div>
                    </div>
                </div>
                <!-- Section SOS & Temps Réel -->
                <div class="charts-grid" style="grid-template-columns: 1.5fr 1fr;">
                    <div class="chart-card">
                        <h3 style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="display:flex; align-items:center;">
                                <span class="chart-icon pink" style="background:rgba(255,71,87,0.15); color:#ff4757;"><i
                                        class="bi bi-exclamation-octagon"></i></span>
                                Alertes SOS Récentes
                            </span>
                            <span id="sosBadge"
                                style="background:#ff4757; color:#fff; font-size:10px; padding:2px 8px; border-radius:10px; display:none;">Actif</span>
                        </h3>
                        <div id="sosAlertList" style="max-height: 350px; overflow-y: auto; padding-right:8px;">
                            <div class="chart-loading">
                                <div class="spinner"></div>Chargement des alertes...
                            </div>
                        </div>
                    </div>
                    <div class="chart-card">
                        <h3>
                            <span class="chart-icon green" style="background:rgba(46,213,115,0.15); color:#2ed573;"><i
                                    class="bi bi-person-pulse"></i></span>
                            Utilisateurs en ligne
                        </h3>
                        <div id="onlineUsersList" style="max-height: 350px; overflow-y: auto;">
                            <div class="chart-loading">
                                <div class="spinner"></div>Calcul de l'activité...
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script src="assets/vendor/chartjs/chart.umd.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Date topbar
        const now = new Date();
        document.getElementById('topbarDate').textContent =
            now.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

        // Sidebar nav
        document.querySelectorAll('.nav-item').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === 'admin.html') link.classList.add('active');
        });

        // Message d'accueil (avatar sidebar géré par sidebar.php)
        fetch("get_admin.php")
            .then(res => res.json())
            .then(data => {
                if (!data || data.error) return;
                const pageTitle = document.querySelector('.page-title');
                if (pageTitle) pageTitle.textContent = `Bienvenue, ${data.prenom || 'Admin'} 👋`;
            });

        // Fetch role-based stats (sinistres, reclamations, contrats, revenus, users)
        fetch('get_stats.php')
            .then(r => r.json())
            .then(data => {
                animateValue('kpi-sinistres', data.sinistres ?? 0);
                animateValue('kpi-reclamations', data.reclamations ?? 0);
                if (document.getElementById('kpi-contrats'))
                    animateValue('kpi-contrats', data.contrats ?? 0);
                if (document.getElementById('kpi-revenus'))
                    document.getElementById('kpi-revenus').textContent =
                        new Intl.NumberFormat('fr-TN', { style: 'currency', currency: 'TND' }).format(data.revenus ?? 0);
                if (document.getElementById('kpi-users'))
                    animateValue('kpi-users', data.users ?? 0);
            });

        // Fetch stats & render charts
        fetch('get_advanced_stats.php')
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;
                const s = data.data;

                // KPI cards
                animateValue('kpiTotal', s.total);
                animateValue('kpiNew', s.new_this_month);
                animateValue('kpiActifs', s.actifs);
                animateValue('kpiBloques', s.bloques);
                const kpiTotal2 = document.getElementById('kpiTotal2');
                const kpiActifs2 = document.getElementById('kpiActifs2');
                const kpiBloques2 = document.getElementById('kpiBloques2');
                if (kpiTotal2) kpiTotal2.textContent = s.total;
                if (kpiActifs2) kpiActifs2.textContent = s.actifs;
                if (kpiBloques2) kpiBloques2.textContent = s.bloques;

                // Chart defaults
                Chart.defaults.color = '#9ca3af';
                Chart.defaults.font.family = "'Outfit', sans-serif";

                // Line chart: inscriptions par mois
                const lineLabels = s.users_by_month.map(r => {
                    if (!r.month) return '';
                    const [y, m] = r.month.split('-');
                    return new Date(y, m - 1).toLocaleDateString('fr-FR', { month: 'short', year: '2-digit' });
                });
                const lineData = s.users_by_month.map(r => parseInt(r.cnt));

                showChart('lineChart', 'lineLoading');
                new Chart(document.getElementById('lineChart'), {
                    type: 'line',
                    data: {
                        labels: lineLabels,
                        datasets: [{
                            label: 'Inscriptions',
                            data: lineData,
                            borderColor: '#60a5fa',
                            backgroundColor: (ctx) => {
                                const chart = ctx.chart;
                                const { ctx: gCtx, chartArea } = chart;
                                if (!chartArea) return 'rgba(96,165,250,.1)';
                                const gradient = gCtx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                                gradient.addColorStop(0, 'rgba(96,165,250,.0)');
                                gradient.addColorStop(1, 'rgba(96,165,250,.25)');
                                return gradient;
                            },
                            fill: true,
                            tension: .4,
                            pointRadius: 4,
                            pointBackgroundColor: '#1e293b',
                            pointBorderColor: '#60a5fa',
                            pointBorderWidth: 2,
                            pointHoverRadius: 7,
                            pointHoverBorderWidth: 3,
                            borderWidth: 3,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }, tooltip: {
                                backgroundColor: 'rgba(15,23,42,.9)',
                                titleColor: '#fff',
                                bodyColor: '#cbd5e1',
                                borderColor: 'rgba(255,255,255,.1)',
                                borderWidth: 1,
                                cornerRadius: 10,
                                padding: 12,
                            }
                        },
                        scales: {
                            x: {
                                grid: { color: 'rgba(255,255,255,.04)', drawBorder: false },
                                ticks: { font: { size: 11 } }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1, font: { size: 11 } },
                                grid: { color: 'rgba(255,255,255,.04)', drawBorder: false }
                            }
                        }
                    }
                });

                // Pie chart: répartition par rôle
                const pieLabels = s.by_role.map(r => r.role.charAt(0).toUpperCase() + r.role.slice(1));
                const pieData = s.by_role.map(r => parseInt(r.cnt));
                const pieColors = { admin: '#f472b6', agent: '#fbbf24', client: '#34d399' };

                showChart('pieChart', 'pieLoading');
                new Chart(document.getElementById('pieChart'), {
                    type: 'doughnut',
                    data: {
                        labels: pieLabels,
                        datasets: [{
                            data: pieData,
                            backgroundColor: pieLabels.map(l => pieColors[l.toLowerCase()] || '#60a5fa'),
                            borderWidth: 0,
                            hoverOffset: 8,
                            spacing: 3,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyle: 'circle', font: { size: 12 } } },
                            tooltip: {
                                backgroundColor: 'rgba(15,23,42,.9)',
                                titleColor: '#fff',
                                bodyColor: '#cbd5e1',
                                borderColor: 'rgba(255,255,255,.1)',
                                borderWidth: 1,
                                cornerRadius: 10,
                                padding: 12,
                            }
                        },
                        cutout: '65%'
                    }
                });

                // Bar chart: connexions par jour (7 derniers jours)
                const barLabels = s.connections_by_day.map(r => {
                    return new Date(r.jour).toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric' });
                });
                const barData = s.connections_by_day.map(r => parseInt(r.cnt));
                const hasData = barLabels.length > 0;

                showChart('barChart', 'barLoading');
                new Chart(document.getElementById('barChart'), {
                    type: 'bar',
                    data: {
                        labels: hasData ? barLabels : ['Aucune donnée'],
                        datasets: [{
                            label: 'Connexions',
                            data: hasData ? barData : [0],
                            backgroundColor: hasData
                                ? barData.map((_, i) => `rgba(59,130,246,${.4 + (i / barData.length) * .6})`)
                                : 'rgba(107,114,128,.2)',
                            borderRadius: 8,
                            borderSkipped: false,
                            maxBarThickness: 50,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }, tooltip: {
                                backgroundColor: 'rgba(15,23,42,.9)',
                                titleColor: '#fff',
                                bodyColor: '#cbd5e1',
                                borderColor: 'rgba(255,255,255,.1)',
                                borderWidth: 1,
                                cornerRadius: 10,
                                padding: 12,
                            }
                        },
                        scales: {
                            x: { grid: { display: false } },
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1, font: { size: 11 } },
                                grid: { color: 'rgba(255,255,255,.04)', drawBorder: false }
                            }
                        }
                    }
                });
            });

        function showChart(canvasId, loadingId) {
            document.getElementById(loadingId).style.display = 'none';
            document.getElementById(canvasId).style.display = 'block';
        }

        function loadSOS() {
            const list = document.getElementById('sosAlertList');
            // Prio 6 : Skeleton
            list.innerHTML = `
                <div class="skeleton" style="height:80px; margin-bottom:10px;"></div>
                <div class="skeleton" style="height:80px; margin-bottom:10px;"></div>
                <div class="skeleton" style="height:80px;"></div>
            `;

            fetch('get_sos_admin.php')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    if (data.alerts.length === 0) {
                        // Prio 5 : État vide
                        list.innerHTML = `
                            <div class="empty-state">
                                <i class="bi bi-shield-check empty-icon"></i>
                                <div class="empty-title">Tout est calme</div>
                                <div class="empty-subtitle">Aucune alerte SOS n'a été déclenchée récemment.</div>
                            </div>
                        `;
                        document.getElementById('sosBadge').style.display = 'none';
                        return;
                    }

                    let activeCount = data.alerts.filter(a => a.statut === 'en_cours').length;
                    if (activeCount > 0) {
                        document.getElementById('sosBadge').style.display = 'inline-block';
                        document.getElementById('sosBadge').textContent = `${activeCount} EN COURS`;
                    } else {
                        document.getElementById('sosBadge').style.display = 'none';
                    }

                    list.innerHTML = data.alerts.map(a => `
            <div style="background:rgba(255,255,255,0.03); border:1px solid ${a.statut === 'en_cours' ? 'rgba(255,71,87,0.3)' : 'rgba(255,255,255,0.06)'}; border-radius:12px; padding:14px; margin-bottom:10px; display:flex; gap:12px; align-items:center;">
                <div style="width:40px; height:40px; border-radius:50%; background:rgba(255,255,255,0.1); overflow:hidden;">
                    <img src="${a.avatar_url || '../FrontOffice/default.png'}" style="width:100%; height:100%; object-fit:cover;">
                </div>
                <div style="flex:1;">
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:#fff; font-weight:700; font-size:14px;">${a.prenom || ''} ${a.nom || ''}</span>
                        <span style="font-size:10px; color:${(a.statut || 'inconnu') === 'en_cours' ? '#ff4757' : '#2ed573'}; font-weight:700;">${(a.statut || 'INCONNU').toUpperCase()}</span>
                    </div>
                    <div style="font-size:12px; color:rgba(255,255,255,0.4); margin-top:2px;">
                        <i class="bi bi-clock"></i> ${new Date(a.created_at).toLocaleString('fr-FR')}
                    </div>
                    <div style="font-size:11px; color:#60a5fa; margin-top:4px;">
                        <i class="bi bi-geo-alt"></i> ${a.lat ? `<a href="https://www.google.com/maps?q=${a.lat},${a.lng}" target="_blank" style="color:inherit; text-decoration:none;">Voir sur Maps</a>` : 'Position non transmise'}
                    </div>
                </div>
                ${a.statut === 'en_cours' ? `
                    <button onclick="resolveSOS(${a.id})" style="background:rgba(46,213,115,0.1); color:#2ed573; border:1px solid rgba(46,213,115,0.2); border-radius:6px; padding:6px 10px; font-size:11px; cursor:pointer;">
                        Résoudre
                    </button>
                ` : ''}
            </div>
        `).join('');
                });
        }

        function resolveSOS(id) {
            fetch('get_sos_admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'resolve', id: id })
            }).then(() => loadSOS());
        }

        function loadOnlineUsers() {
            fetch('get_advanced_stats.php')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    const list = document.getElementById('onlineUsersList');
                    if (!data.data || !data.data.online_users || data.data.online_users.length === 0) {
                        list.innerHTML = '<div style="text-align:center; padding:40px; color:rgba(255,255,255,0.2);">Aucun utilisateur actif</div>';
                        return;
                    }
                    list.innerHTML = data.data.online_users.map(u => {
                        let av = (u.avatar_url && u.avatar_url.startsWith('http')) ? u.avatar_url : (window.BASE_URL + '/uploads/avatars/default.png');
                        if (u.avatar && u.avatar !== 'default.png') av = window.BASE_URL + '/uploads/avatars/' + u.avatar;
                        const rColor = u.role === 'admin' ? '#FF6B1A' : (u.role === 'agent' ? '#00d68f' : '#64748b');
                        
                        return `
                        <div class="online-user-item" style="display:flex; align-items:center; gap:12px; padding:12px 16px; transition: background 0.2s; cursor:default;">
                            <div style="position:relative; flex-shrink:0;">
                                <img src="${av}" style="width:36px; height:36px; border-radius:50%; object-fit:cover; border: 2px solid rgba(255,255,255,0.1);">
                                <div style="position:absolute; bottom:1px; right:1px; width:10px; height:10px; background:#00d68f; border:2px solid #1a1f2e; border-radius:50%;"></div>
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-size:13px; color:#fff; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${u.prenom} ${u.nom}</div>
                                <div style="font-size:11px; color:${rColor}; font-weight:600; text-transform:uppercase; letter-spacing:0.02em;">${u.role}</div>
                            </div>
                            <div style="font-size:10px; color:rgba(255,255,255,0.3); font-weight:500;">En ligne</div>
                        </div>`;
                    }).join('');
                });
        }

        // Init Real-time
        loadSOS();
        loadOnlineUsers();
        setInterval(loadSOS, 10000);
        setInterval(loadOnlineUsers, 15000);

        function animateValue(id, end) {
            const el = document.getElementById(id);
            if (!el || end == null) return;
            const start = 0;
            const duration = 1000;
            const startTime = performance.now();
            function update(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeOut = 1 - Math.pow(1 - progress, 3);
                el.textContent = Math.floor(start + (end - start) * easeOut);
                if (progress < 1) requestAnimationFrame(update);
            }
            requestAnimationFrame(update);
        }
    </script>
</body>

</html>
