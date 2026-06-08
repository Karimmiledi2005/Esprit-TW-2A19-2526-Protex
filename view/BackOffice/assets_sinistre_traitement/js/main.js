/* =============================================
   main.js — Scripts globaux Protex
   ============================================= */

document.addEventListener('DOMContentLoaded', function () {

    // ===== NAVIGATION ACTIVE =====
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function (e) {
            if (this.getAttribute('href') === '#') {
                e.preventDefault();
            }
            navItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // ===== FERMER ALERTES =====
    document.querySelectorAll('.alert-close').forEach(btn => {
        btn.addEventListener('click', function () {
            this.closest('.alert-banner').remove();
        });
    });

    // ===== ANIMATION PROGRESS BARS =====
    const bars = document.querySelectorAll('.progress-fill[data-width]');
    setTimeout(() => {
        bars.forEach(bar => {
            bar.style.width = bar.getAttribute('data-width') + '%';
        });
    }, 300);

    // ===== TOAST NOTIFICATION =====
    window.showToast = function (message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast-notif toast-${type}`;
        toast.innerHTML = `
            <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'x-circle'}"></i>
            <span>${message}</span>
        `;
        document.body.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 100);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    };

    // ===== SIDEBAR MOBILE =====
    const menuBtn = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
    }

    // ===== SMART NOTIFICATIONS DROPDOWN =====
    const bellBtn = document.querySelector('.topbar-btn i.bi-bell')?.parentElement;
    if (bellBtn) {
        // Prepare UI
        bellBtn.querySelectorAll('.notif-dot, .notif-badge').forEach(el => el.remove());
        
        const dot = document.createElement('span');
        dot.className = 'notif-dot';
        dot.id = 'notifDot';
        dot.style.display = 'none'; // Hidden by default
        bellBtn.appendChild(dot);

        const notifPanel = document.createElement('div');
        notifPanel.className = 'notif-panel';
        notifPanel.id = 'notifPanel';
        notifPanel.innerHTML = `
            <div class="notif-header">
                <span>Sinistres récents</span>
                <i class="bi bi-clock-history" style="font-size:12px;opacity:0.6;"></i>
            </div>
            <div class="notif-list" id="notifList">
                <div class="notif-empty">Chargement...</div>
            </div>
            <div class="notif-footer">
                <a href="sinsiter.html">Accéder à tous les sinistres</a>
            </div>
        `;
        bellBtn.parentElement.style.position = 'relative';
        bellBtn.parentElement.appendChild(notifPanel);

        // Fetch Notifications and check for unread
        async function updateNotifications() {
            try {
                const res = await fetch('get_notifications.php');
                const json = await res.json();
                if (json.success) {
                    // Show dot only if there are unread claims
                    if (json.unread_count > 0) {
                        dot.style.display = 'block';
                    } else {
                        dot.style.display = 'none';
                    }

                    // Update list if panel is open
                    if (notifPanel.classList.contains('open')) {
                        renderList(json.notifications);
                    }
                }
            } catch (e) {}
        }

        function renderList(notifications) {
            const list = document.getElementById('notifList');
            if (notifications && notifications.length > 0) {
                list.innerHTML = notifications.map(item => `
                    <a href="sinsiter.html" class="notif-item">
                        <span class="notif-item-title">#${item.id_sinistre} — ${item.type}</span>
                        <span class="notif-item-meta">${item.date_declaration} • <span style="color:var(--accent)">${item.statut}</span></span>
                    </a>
                `).join('');
            } else {
                list.innerHTML = '<div class="notif-empty">Aucun sinistre trouvé</div>';
            }
        }

        async function markAsRead() {
            try {
                await fetch('mark_notifications_read.php');
                dot.style.display = 'none';
            } catch (e) {}
        }

        // Toggle logic
        bellBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const isOpen = notifPanel.classList.toggle('open');
            if (isOpen) {
                markAsRead();
                updateNotifications(); // Refresh list immediately
            }
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!bellBtn.contains(e.target) && !notifPanel.contains(e.target)) {
                notifPanel.classList.remove('open');
            }
        });

        // Periodic check (every 30s)
        updateNotifications();
        setInterval(updateNotifications, 30000);
    }

});
document.addEventListener('DOMContentLoaded', () => {
    const links = document.querySelectorAll('.dock-link');

    links.forEach(link => {
        link.addEventListener('click', function() {
            // Effet de pression (scale down)
            this.style.transform = 'scale(0.9)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);

            // Changement de classe active
            links.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });
});
