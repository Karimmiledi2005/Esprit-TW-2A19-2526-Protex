/* =============================================
   main.js — Scripts globaux Protex
   FrontOffice — Version corrigée & améliorée
   ============================================= */

document.addEventListener('DOMContentLoaded', function () {

    // ===== 1. INITIALIZE NAVBAR & DROPDOWNS =====
    initNavbar();

    // ===== 2. LOAD USER SESSION DATA =====
    loadUserSession();

    // ===== 3. GLOBAL UI HELPERS =====
    initUIHelpers();

});

/**
 * Handles Navbar toggles and dropdowns
 */
function initNavbar() {
    // Mobile Menu Toggle
    const menuBtn = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
    }

    // Avatar Dropdown Toggle
    // Centralisé dans navbar.php pour éviter les conflits

    // Auto-Active Nav Links
    const currentPage = window.location.pathname.split('/').pop() || 'client.html';
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage || (currentPage === '' && href === 'client.html')) {
            link.classList.add('active');
        } else {
            if (href === 'contrat.php' && currentPage.startsWith('contrat_')) {
                link.classList.add('active');
            }
        }
    });
}

/**
 * Fetches user data and populates navbar elements
 */
async function loadUserSession() {
    if (window.ProtexUser) return;

    try {
        const res = await fetch('get_user.php');
        if (!res.ok) return;
        const payload = await res.json();
        if (payload && window.ProtexUser) {
            window.ProtexUser.applyUser(payload);
        }
    } catch (e) {
        console.error('[Protex] Failed to load user session:', e);
    }
}

/**
 * Other common UI features
 */
function initUIHelpers() {
    // Progress Bars Animation
    const bars = document.querySelectorAll('.progress-fill[data-width]');
    setTimeout(() => {
        bars.forEach(bar => {
            bar.style.width = bar.getAttribute('data-width') + '%';
        });
    }, 300);

    // Toast Global
    window.showToast = function (message, type = 'success') {
        const icons = { success: 'check-circle', warning: 'exclamation-triangle', danger: 'x-circle', info: 'info-circle' };
        const toast = document.createElement('div');
        toast.className = `toast-notif toast-${type}`;
        toast.innerHTML = `
            <i class="bi bi-${icons[type] || 'info-circle'}"></i>
            <span>${message}</span>
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 50);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    };

    // Date in breadcrumb
    const breadcrumbSpan = document.querySelector('.page-breadcrumb span');
    if (breadcrumbSpan && !breadcrumbSpan.textContent.trim()) {
        const now = new Date();
        const dateStr = now.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        breadcrumbSpan.textContent = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);
    }

    // Password Toggle Helper
    window.togglePassword = function () {
        const pwd = document.getElementById('password');
        const icon = document.querySelector('[onclick="togglePassword()"] i');
        if (!pwd) return;
        if (pwd.type === 'password') {
            pwd.type = 'text';
            if (icon) { icon.className = 'bi bi-eye-slash'; }
        } else {
            pwd.type = 'password';
            if (icon) { icon.className = 'bi bi-eye'; }
        }
    };
}