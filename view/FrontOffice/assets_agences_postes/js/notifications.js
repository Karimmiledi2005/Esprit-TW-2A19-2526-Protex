function escapeHtml(t){return String(t??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');}

const NOTIF_API = '../../model/api/get_notifications.php';
const NOTIF_MARK_ALL_API = '../../model/api/mark_all_notifications_read.php';
let notifPollInterval = null;

function initNotifications(userId) {
    if (!userId) return;
    const btn = document.getElementById('notifBtn');
    const dropdown = document.getElementById('notifDropdown');
    if (!btn || !dropdown) return;

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = dropdown.classList.contains('open');
        if (!isOpen) {
            fetchNotifications(userId);
            dropdown.classList.add('open');
        } else {
            dropdown.classList.remove('open');
        }
    });

    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
            dropdown.classList.remove('open');
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') dropdown.classList.remove('open');
    });

    fetchNotifications(userId);
    if (notifPollInterval) clearInterval(notifPollInterval);
    notifPollInterval = setInterval(function () { fetchNotifications(userId, true); }, 15000);
}

async function fetchNotifications(userId, silent) {
    try {
        const r = await fetch(NOTIF_API + '?id_user=' + userId + '&_=' + Date.now());
        const j = await r.json();
        if (!j.success) return;
        renderNotifications(j.notifications || [], j.unread_count || 0);
    } catch (e) {
        if (silent) return;
    }
}

function renderNotifications(list, unreadCount) {
    const badge = document.getElementById('notifBadge');
    const dot = document.getElementById('notifDot');
    const listEl = document.getElementById('notifList');
    if (!listEl) return;

    if (badge) {
        if (unreadCount > 0) {
            badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
            badge.style.display = '';
        } else {
            badge.style.display = 'none';
        }
    }
    if (dot) {
        dot.style.display = unreadCount > 0 ? '' : 'none';
    }

    if (!list.length) {
        listEl.innerHTML = '<div class="notif-empty">Aucune notification</div>';
        return;
    }

    listEl.innerHTML = list.map(function (n) {
        var icons = { thanks: 'check-circle', hidden: 'shield-exclamation', info: 'info-circle' };
        var icon = icons[n.type] || 'info-circle';
        var isUnread = Number(n.is_read) === 0;
        var timeAgo = formatNotifTime(n.created_at);
        return '<div class="notif-item' + (isUnread ? ' unread' : '') + '">' +
            '<div class="notif-icon"><i class="bi bi-' + icon + '"></i></div>' +
            '<div class="notif-content">' +
            '<div class="notif-text">' + escapeHtml(n.message) + '</div>' +
            '<div class="notif-time">' + timeAgo + '</div>' +
            '</div></div>';
    }).join('');
}

function formatNotifTime(dateStr) {
    if (!dateStr) return '';
    var d = new Date(dateStr);
    var now = new Date();
    var diff = Math.floor((now - d) / 1000);
    if (diff < 60) return 'À l\'instant';
    if (diff < 3600) return 'Il y a ' + Math.floor(diff / 60) + ' min';
    if (diff < 86400) return 'Il y a ' + Math.floor(diff / 3600) + 'h';
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
}

async function markAllNotifRead() {
    var userId = window._notifUserId;
    if (!userId) return;
    try {
        var r = await fetch(NOTIF_MARK_ALL_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_user: userId })
        });
        var j = await r.json();
        if (j.success) {
            fetchNotifications(userId);
            document.getElementById('notifDropdown').classList.remove('open');
        }
    } catch (e) { /* ignore */ }
}
