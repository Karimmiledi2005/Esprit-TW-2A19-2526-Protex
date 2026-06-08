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

    // ===== INIT PROGRESS BARS (sans data-width) =====
    document.querySelectorAll('.progress-fill:not([data-width])').forEach(bar => {
        const w = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => { bar.style.width = w; }, 400);
    });

});
