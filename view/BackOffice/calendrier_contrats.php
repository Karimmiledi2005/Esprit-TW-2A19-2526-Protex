<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();

require_once __DIR__ . '/../../controller/ContratController.php';
$contratC = new ContratController();

if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
$base = defined('BASE_URL') ? BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendrier des contrats — Protex Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/admin-users.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <style>
        .fc { --fc-border-color: rgba(255,255,255,0.08); --fc-today-bg-color: rgba(0,198,255,0.06); color: #fff; font-family: inherit; }
        .fc .fc-toolbar-title { font-size: 20px; font-weight: 800; color: #fff; }
        .fc .fc-button { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); color: #fff; font-size: 13px; font-weight: 600; border-radius: 10px; padding: 6px 14px; transition: .2s; }
        .fc .fc-button:hover { background: rgba(0,198,255,0.18); }
        .fc .fc-button-active { background: rgba(0,198,255,0.22) !important; border-color: rgba(0,198,255,0.5) !important; color: #00c6ff !important; }
        .fc .fc-col-header-cell { background: rgba(255,255,255,0.03); }
        .fc .fc-col-header-cell-cushion { color: rgba(255,255,255,0.65); font-size: 11px; text-transform: uppercase; letter-spacing: .5px; font-weight: 700; }
        .fc .fc-daygrid-day-number { color: rgba(255,255,255,0.65); font-size: 13px; font-weight: 600; }
        .fc-event { border-radius: 6px !important; font-size: 11px !important; font-weight: 600 !important; padding: 2px 5px !important; cursor: pointer; border: none !important; }
        .fc .fc-day-today .fc-daygrid-day-number { color: #00c6ff; font-weight: 800; }
        .fc .fc-scrollgrid { border-radius: 16px; overflow: hidden; }
        .fc .fc-list-event:hover td { background: rgba(0,198,255,0.08) !important; }
        .fc .fc-list-sticky .fc-list-day > * { background: rgba(10,25,49,0.95); }
        .fc .fc-list-day-cushion { color: #fff; }

        .cal-legend { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
        .cal-legend-item { display: flex; align-items: center; gap: 6px; font-size: 12px; color: rgba(255,255,255,0.7); font-weight: 600; }
        .cal-legend-dot { width: 12px; height: 12px; border-radius: 4px; }

        /* Event detail modal */
        .evt-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); z-index: 9998; display: none; align-items: center; justify-content: center; }
        .evt-modal-overlay.open { display: flex; }
        .evt-modal { background: linear-gradient(180deg, rgba(8,22,52,0.98), rgba(5,17,42,0.98)); border: 1px solid rgba(80,132,255,0.24); border-radius: 20px; width: min(500px, 92vw); padding: 0; box-shadow: 0 24px 60px rgba(0,0,0,0.5); color: #fff; }
        .evt-modal-header { padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; justify-content: space-between; align-items: center; }
        .evt-modal-title { font-size: 18px; font-weight: 800; }
        .evt-modal-close { width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.06); color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; }
        .evt-modal-body { padding: 20px 24px; }
        .evt-field { margin-bottom: 14px; }
        .evt-label { font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: rgba(255,255,255,0.5); font-weight: 700; margin-bottom: 4px; }
        .evt-value { font-size: 14px; font-weight: 700; color: #fff; }
        .evt-modal-footer { padding: 16px 24px; border-top: 1px solid rgba(255,255,255,0.08); display: flex; gap: 8px; flex-wrap: wrap; }
        .evt-btn { padding: 8px 16px; border-radius: 10px; font-size: 12px; font-weight: 700; cursor: pointer; border: 1px solid rgba(255,255,255,0.12); display: inline-flex; align-items: center; gap: 6px; transition: .2s; }
        .evt-btn:hover { transform: translateY(-1px); }
        .evt-btn-view { background: rgba(0,198,255,0.15); color: #00c6ff; }
        .evt-btn-renew { background: rgba(46,213,115,0.15); color: #2ed573; }
        .evt-btn-email { background: rgba(255,107,26,0.15); color: #ff6b1a; }

        /* Bulk reminder modal */
        .bulk-modal { width: min(440px, 92vw); }
        .bulk-modal input[type="date"] { width: 100%; padding: 10px 14px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.15); background: rgba(255,255,255,0.05); color: #fff; font-size: 14px; margin-bottom: 12px; }
        .bulk-modal input[type="date"]:focus { border-color: rgba(0,198,255,0.5); outline: none; }
        .bulk-modal label { display: block; font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.6); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px; }
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
                <div class="topbar-title">Calendrier des échéances</div>
                <div class="topbar-sub" id="topbarDate"></div>
            </div>
        </div>

        <div class="content">
            <div class="page-header-bar">
                <div>
                    <div class="page-title">📅 Calendrier des contrats</div>
                    <div class="page-breadcrumb">
                        <i class="bi bi-house"></i>
                        <a href="admin.php">Accueil</a>
                        <i class="bi bi-chevron-right" style="font-size:10px;"></i>
                        <a href="contrats_back.php">Contrats</a>
                        <i class="bi bi-chevron-right" style="font-size:10px;"></i>
                        <span>Calendrier</span>
                    </div>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="openBulkModal()">
                        <i class="bi bi-megaphone"></i> Rappels en masse
                    </button>
                </div>
            </div>

            <div class="card" style="padding:24px;">
                <div class="cal-legend">
                    <div class="cal-legend-item"><div class="cal-legend-dot" style="background:#e63946;"></div> Expire &lt; 30 jours</div>
                    <div class="cal-legend-item"><div class="cal-legend-dot" style="background:#ff6b1a;"></div> 30 – 60 jours</div>
                    <div class="cal-legend-item"><div class="cal-legend-dot" style="background:#EF9F27;"></div> 60 – 90 jours</div>
                    <div class="cal-legend-item"><div class="cal-legend-dot" style="background:#00c6ff;"></div> Actif (&gt; 90 j.)</div>
                </div>
                <div id="calendar"></div>
            </div>
        </div>
    </main>
</div>

<!-- Event Detail Modal -->
<div class="evt-modal-overlay" id="evtModal">
    <div class="evt-modal">
        <div class="evt-modal-header">
            <div class="evt-modal-title" id="evtTitle"></div>
            <button class="evt-modal-close" onclick="closeEvtModal()"><i class="bi bi-x"></i></button>
        </div>
        <div class="evt-modal-body" id="evtBody"></div>
        <div class="evt-modal-footer" id="evtFooter"></div>
    </div>
</div>

<!-- Bulk Reminder Modal -->
<div class="evt-modal-overlay" id="bulkModal">
    <div class="evt-modal bulk-modal">
        <div class="evt-modal-header">
            <div class="evt-modal-title"><i class="bi bi-megaphone"></i> Rappels en masse</div>
            <button class="evt-modal-close" onclick="closeBulkModal()"><i class="bi bi-x"></i></button>
        </div>
        <div class="evt-modal-body">
            <p style="font-size:13px;color:rgba(255,255,255,0.7);margin-bottom:16px;">
                Envoyez un email de rappel à tous les clients dont le contrat expire dans la période sélectionnée.
            </p>
            <label>Date de début</label>
            <input type="date" id="bulkFrom">
            <label>Date de fin</label>
            <input type="date" id="bulkTo">
            <div id="bulkResult" style="margin-top:12px;font-size:13px;"></div>
        </div>
        <div class="evt-modal-footer">
            <button class="evt-btn evt-btn-email" onclick="sendBulkReminders()">
                <i class="bi bi-send"></i> Envoyer les rappels
            </button>
        </div>
    </div>
</div>

<script>
document.getElementById('topbarDate').textContent =
    new Date().toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long', year:'numeric' });

let calendarInstance;

document.addEventListener('DOMContentLoaded', () => {
    const calEl = document.getElementById('calendar');
    calendarInstance = new FullCalendar.Calendar(calEl, {
        locale: 'fr',
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listMonth'
        },
        buttonText: {
            today: 'Aujourd\'hui',
            month: 'Mois',
            week: 'Semaine',
            list: 'Liste'
        },
        height: 'auto',
        events: fetchEvents,
        eventClick: function(info) {
            info.jsEvent.preventDefault();
            showEventDetail(info.event);
        },
        eventDidMount: function(info) {
            info.el.title = info.event.title + ' — ' + (info.event.extendedProps.client || '');
        }
    });
    calendarInstance.render();

    // Set default bulk dates
    const today = new Date();
    document.getElementById('bulkFrom').value = today.toISOString().split('T')[0];
    const in30 = new Date(today.getTime() + 30 * 86400000);
    document.getElementById('bulkTo').value = in30.toISOString().split('T')[0];
});

async function fetchEvents(info, successCallback, failureCallback) {
    try {
        const res = await fetch('<?= $base ?>/api.php?action=contrats_calendar');
        const data = await res.json();
        if (Array.isArray(data)) {
            successCallback(data);
        } else {
            successCallback([]);
        }
    } catch(e) {
        console.error('Calendar fetch error:', e);
        failureCallback(e);
    }
}

function showEventDetail(event) {
    const ep = event.extendedProps;
    document.getElementById('evtTitle').innerHTML = `<i class="bi bi-file-earmark-text" style="color:#00c6ff;"></i> ${event.title}`;
    document.getElementById('evtBody').innerHTML = `
        <div class="evt-field"><div class="evt-label">Client</div><div class="evt-value">${ep.client || '—'}</div></div>
        <div class="evt-field"><div class="evt-label">Type de contrat</div><div class="evt-value">${ep.type || '—'}</div></div>
        <div class="evt-field"><div class="evt-label">Prime</div><div class="evt-value">${ep.prime ? parseFloat(ep.prime).toLocaleString('fr-FR') + ' DT' : '—'}</div></div>
        <div class="evt-field"><div class="evt-label">Date d'expiration</div><div class="evt-value">${event.startStr || '—'}</div></div>
        <div class="evt-field"><div class="evt-label">Jours restants</div><div class="evt-value">${ep.jours_restants ?? '—'} jour(s)</div></div>
    `;
    document.getElementById('evtFooter').innerHTML = `
        <a href="showContrat.php?id=${ep.id_contrat}" class="evt-btn evt-btn-view"><i class="bi bi-eye"></i> Voir détails</a>
        <button class="evt-btn evt-btn-email" onclick="sendReminder(${ep.id_contrat})"><i class="bi bi-envelope"></i> Rappel Email</button>
        <button class="evt-btn evt-btn-renew" onclick="alert('Fonctionnalité de renouvellement à venir')"><i class="bi bi-arrow-repeat"></i> Renouveler</button>
    `;
    document.getElementById('evtModal').classList.add('open');
}

function closeEvtModal() { document.getElementById('evtModal').classList.remove('open'); }
function openBulkModal() { document.getElementById('bulkModal').classList.add('open'); }
function closeBulkModal() { document.getElementById('bulkModal').classList.remove('open'); }

async function sendReminder(idContrat) {
    const res = await fetch('<?= $base ?>/api.php?action=contrat_send_reminder', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_contrat: idContrat })
    });
    const data = await res.json();
    alert(data.message || 'Rappel envoyé !');
}

async function sendBulkReminders() {
    const from = document.getElementById('bulkFrom').value;
    const to = document.getElementById('bulkTo').value;
    if (!from || !to) { alert('Veuillez sélectionner les dates.'); return; }

    document.getElementById('bulkResult').innerHTML = '<i class="bi bi-arrow-repeat" style="animation:spin .8s linear infinite;"></i> Envoi en cours...';

    const res = await fetch('<?= $base ?>/api.php?action=contrat_bulk_reminder', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ date_from: from, date_to: to })
    });
    const data = await res.json();
    document.getElementById('bulkResult').innerHTML = `
        <div style="padding:12px;border-radius:10px;background:rgba(46,213,115,0.1);border:1px solid rgba(46,213,115,0.3);color:#2ed573;">
            ✅ ${data.envoyes || 0} rappel(s) envoyé(s) sur ${data.total || 0} contrat(s) détecté(s).
        </div>
    `;
}

// Close modals on ESC
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeEvtModal();
        closeBulkModal();
    }
});
</script>

<script src="assets/js/main.js"></script>
</body>
</html>
