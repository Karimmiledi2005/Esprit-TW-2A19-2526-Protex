<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Préférences de notifications – Protex</title>
    <meta name="description" content="Gérez vos préférences de notifications Protex : email, SMS et alertes in-app.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
    <style>
        /* ── Layout card ── */
        .notif-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .notif-card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .notif-card-header h2 {
            font-family: var(--font-display);
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }
        .notif-card-body {
            padding: 0;
        }

        /* ── Matrix table ── */
        .notif-table {
            width: 100%;
            border-collapse: collapse;
        }
        .notif-table thead th {
            padding: 14px 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--glass-border);
            background: rgba(0,0,0,0.15);
            text-align: center;
        }
        .notif-table thead th:first-child { text-align: left; min-width: 200px; }
        .notif-table tbody tr {
            border-bottom: 1px solid rgba(255,255,255,0.04);
            transition: background 0.2s;
        }
        .notif-table tbody tr:last-child { border-bottom: none; }
        .notif-table tbody tr:hover { background: rgba(0,180,216,0.03); }
        .notif-table tbody td {
            padding: 16px 20px;
            text-align: center;
            vertical-align: middle;
        }
        .notif-table tbody td:first-child { text-align: left; }

        .notif-type-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
        }
        .notif-type-desc {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        /* ── Toggle switch ── */
        .ptx-toggle {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 22px;
        }
        .ptx-toggle input { opacity: 0; width: 0; height: 0; }
        .ptx-slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: rgba(255,255,255,0.1);
            border-radius: 22px;
            transition: 0.3s;
            border: 1px solid rgba(255,255,255,0.12);
        }
        .ptx-slider::before {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            left: 2px;
            bottom: 2px;
            background: #fff;
            border-radius: 50%;
            transition: 0.3s;
        }
        .ptx-toggle input:checked + .ptx-slider {
            background: var(--accent);
            border-color: var(--accent);
        }
        .ptx-toggle input:checked + .ptx-slider::before {
            transform: translateX(18px);
        }

        /* ── Save button ── */
        .btn-save-notif {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            background: linear-gradient(135deg, var(--accent), #0077b6);
            color: #fff;
            border: none;
            border-radius: var(--radius-md);
            font-family: var(--font-body);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: box-shadow 0.25s, transform 0.15s;
        }
        .btn-save-notif:hover {
            box-shadow: 0 6px 20px rgba(0,180,216,0.4);
            transform: translateY(-1px);
        }
        .btn-save-notif:active { transform: translateY(0); }

        /* ── Channel header icons ── */
        .ch-header { display: flex; flex-direction: column; align-items: center; gap: 4px; }
        .ch-header i { font-size: 18px; color: var(--accent); }

        /* ── Toast ── */
        .toast-notif {
            position: fixed; bottom: 24px; right: 24px;
            background: var(--navy-mid); border: 1px solid var(--border);
            border-radius: 12px; padding: 14px 20px;
            display: flex; align-items: center; gap: 10px;
            font-size: 14px; color: var(--text-primary);
            z-index: 9999; opacity: 0; transform: translateY(10px);
            transition: all 0.3s ease; box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }
        .toast-notif.show { opacity: 1; transform: translateY(0); }
        .toast-success i { color: var(--success); font-size: 18px; }
        .toast-warning i { color: var(--gold);    font-size: 18px; }
        .toast-danger  i { color: var(--danger);  font-size: 18px; }

        @keyframes spin { to { transform: rotate(360deg); } }
        .spin { animation: spin 1s linear infinite; display: inline-block; }
    </style>
</head>
<body>
    <div class="background"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="layout">
        <?php require_once __DIR__ . '/assets/includes/navbar.php'; ?>

        <main class="main">
            <!-- En-tête page -->
            <div class="page-header">
                <div>
                    <div class="page-title-main">Préférences de notifications</div>
                    <div class="page-breadcrumb">
                        <i class="bi bi-house"></i>
                        <a href="client.php" style="color:inherit;text-decoration:none;">Accueil</a>
                        <i class="bi bi-chevron-right" style="font-size:10px"></i>
                        <a href="monprofile.php" style="color:inherit;text-decoration:none;">Mon profil</a>
                        <i class="bi bi-chevron-right" style="font-size:10px"></i>
                        <span>Notifications</span>
                    </div>
                </div>
            </div>

            <div class="content">

                <!-- Intro -->
                <div style="background:rgba(0,180,216,0.06); border:1px solid rgba(0,180,216,0.15); border-radius:14px; padding:18px 22px; margin-bottom:28px; display:flex; align-items:center; gap:16px;">
                    <i class="bi bi-info-circle-fill" style="font-size:22px;color:var(--accent);flex-shrink:0;"></i>
                    <div style="font-size:13px;color:var(--text-secondary); line-height:1.6;">
                        Choisissez comment Protex vous contacte pour chaque catégorie d'événement.<br>
                        Vos préférences sont sauvegardées en temps réel et s'appliquent immédiatement.
                    </div>
                </div>

                <!-- Matrice notifications -->
                <div class="notif-card">
                    <div class="notif-card-header">
                        <div style="width:40px;height:40px;border-radius:12px;background:rgba(0,180,216,0.12);display:flex;align-items:center;justify-content:center;">
                            <i class="bi bi-bell-fill" style="color:var(--accent);font-size:20px;"></i>
                        </div>
                        <h2>Canaux de notification par catégorie</h2>
                    </div>
                    <div class="notif-card-body">
                        <table class="notif-table" id="notifMatrix">
                            <thead>
                                <tr>
                                    <th>Catégorie</th>
                                    <th>
                                        <div class="ch-header">
                                            <i class="bi bi-envelope-fill"></i>
                                            <span>Email</span>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="ch-header">
                                            <i class="bi bi-phone-fill"></i>
                                            <span>SMS</span>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="ch-header">
                                            <i class="bi bi-app-indicator"></i>
                                            <span>App</span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="notifRows">
                                <!-- Injected by JS -->
                                <tr><td colspan="4" style="padding:40px;text-align:center;color:var(--text-secondary);font-size:13px;"><i class="bi bi-arrow-repeat spin"></i> Chargement...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Vie privée -->
                <div class="notif-card" style="margin-top:24px;">
                    <div class="notif-card-header">
                        <div style="width:40px;height:40px;border-radius:12px;background:rgba(0,180,216,0.12);display:flex;align-items:center;justify-content:center;">
                            <i class="bi bi-eye-slash" style="color:var(--accent);font-size:20px;"></i>
                        </div>
                        <h2>Vie privée en ligne</h2>
                    </div>
                    <div class="notif-card-body" style="padding:16px 24px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <div>
                                <div style="font-size:14px;font-weight:600;color:var(--text-primary);">Masquer mon statut en ligne</div>
                                <div style="font-size:12px;color:var(--text-secondary);">Les autres utilisateurs ne verront pas quand vous êtes en ligne.</div>
                            </div>
                            <label class="ptx-toggle">
                                <input type="checkbox" id="chkHideOnline">
                                <span class="ptx-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                    <a href="monprofile.php" style="display:inline-flex;align-items:center;gap:8px;color:var(--text-secondary);font-size:13px;text-decoration:none;">
                        <i class="bi bi-arrow-left"></i> Retour au profil
                    </a>
                    <button class="btn-save-notif" id="btnSaveNotif">
                        <i class="bi bi-save2"></i> Enregistrer les préférences
                    </button>
                </div>

            </div>
        </main>
    </div>

    <!-- Toast -->
    <div class="toast-notif" id="toastNotif">
        <i class="bi" id="toastIcon"></i>
        <span id="toastMsg"></span>
    </div>

    <script>
    // ── Définitions des catégories ──
    const NOTIF_TYPES = [
        { key: 'contrats',  label: 'Contrats',          desc: 'Renouvellements, modifications et nouvelles souscriptions',   icon: 'bi-file-earmark-text' },
        { key: 'paiements', label: 'Paiements',          desc: 'Confirmations de paiement, rappels d\'échéance et factures',   icon: 'bi-credit-card' },
        { key: 'sinistres', label: 'Sinistres',          desc: 'Statut des déclarations, remboursements et mises à jour',     icon: 'bi-exclamation-octagon' },
        { key: 'reseau',    label: 'Réseau social',      desc: 'Invitations d\'amis, messages et alertes SOS',               icon: 'bi-people' },
        { key: 'offres',    label: 'Offres & promotions', desc: 'Nouvelles offres, promotions et programmes de fidélité',     icon: 'bi-tag' },
        { key: 'securite',  label: 'Sécurité du compte', desc: 'Connexions suspectes, changements de mot de passe et 2FA',   icon: 'bi-shield-lock' },
    ];

    // Current preferences loaded from API
    let currentPrefs = {};

    function showToast(msg, type) {
        const icons = { success: 'bi-check-circle-fill', danger: 'bi-x-circle-fill', warning: 'bi-exclamation-triangle-fill' };
        const toast = document.getElementById('toastNotif');
        document.getElementById('toastMsg').textContent = msg;
        const icon = document.getElementById('toastIcon');
        icon.className = 'bi ' + (icons[type] || icons.warning);
        toast.className = 'toast-notif toast-' + type;
        void toast.offsetWidth;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3500);
    }

    function buildRow(type, prefs) {
        const p = prefs[type.key] || { email: false, sms: false, app: false };
        return `
        <tr data-type="${type.key}">
            <td>
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:36px;height:36px;border-radius:10px;background:rgba(0,180,216,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi ${type.icon}" style="color:var(--accent);font-size:16px;"></i>
                    </div>
                    <div>
                        <div class="notif-type-name">${type.label}</div>
                        <div class="notif-type-desc">${type.desc}</div>
                    </div>
                </div>
            </td>
            <td>
                <label class="ptx-toggle" title="Email : ${type.label}">
                    <input type="checkbox" data-channel="email" ${p.email ? 'checked' : ''}>
                    <span class="ptx-slider"></span>
                </label>
            </td>
            <td>
                <label class="ptx-toggle" title="SMS : ${type.label}">
                    <input type="checkbox" data-channel="sms" ${p.sms ? 'checked' : ''}>
                    <span class="ptx-slider"></span>
                </label>
            </td>
            <td>
                <label class="ptx-toggle" title="App : ${type.label}">
                    <input type="checkbox" data-channel="app" ${p.app ? 'checked' : ''}>
                    <span class="ptx-slider"></span>
                </label>
            </td>
        </tr>`;
    }

    function renderMatrix(prefs) {
        const tbody = document.getElementById('notifRows');
        tbody.innerHTML = NOTIF_TYPES.map(t => buildRow(t, prefs)).join('');
    }

    function collectPrefs() {
        const prefs = {};
        document.querySelectorAll('#notifRows tr[data-type]').forEach(row => {
            const key = row.dataset.type;
            prefs[key] = {
                email: row.querySelector('[data-channel="email"]').checked,
                sms:   row.querySelector('[data-channel="sms"]').checked,
                app:   row.querySelector('[data-channel="app"]').checked,
            };
        });
        return prefs;
    }

    // ── Chargement ──
    fetch('../../api.php?action=get_notif_prefs')
        .then(r => r.json())
        .then(data => {
            currentPrefs = data.prefs || {};
            renderMatrix(currentPrefs);
        })
        .catch(() => {
            renderMatrix({});
            showToast('Erreur de chargement des préférences', 'danger');
        });

    // ── Chargement vie privée ──
    fetch('../../api.php?action=get_online_privacy')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('chkHideOnline').checked = data.hide_online_status == 1;
            }
        })
        .catch(() => {});

    // ── Sauvegarde ──
    document.getElementById('btnSaveNotif').addEventListener('click', function () {
        const btn = this;
        const origHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Enregistrement...';

        const prefs = collectPrefs();
        Promise.all([
            fetch('../../api.php?action=save_notif_prefs', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ prefs })
            }),
            fetch('../../api.php?action=save_online_privacy', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ hide_online_status: document.getElementById('chkHideOnline').checked })
            })
        ])
        .then(([r1, r2]) => Promise.all([r1.json(), r2.json()]))
        .then(([data]) => {
            btn.innerHTML = origHTML;
            btn.disabled = false;
            if (data.success) {
                showToast(data.message || 'Préférences sauvegardées', 'success');
                currentPrefs = prefs;
            } else {
                showToast(data.message || 'Erreur lors de la sauvegarde', 'danger');
            }
        })
        .catch(() => {
            btn.innerHTML = origHTML;
            btn.disabled = false;
            showToast('Erreur réseau, réessayez', 'danger');
        });
    });
    </script>

    <script src="assets/js/main.js"></script>
</body>
</html>
