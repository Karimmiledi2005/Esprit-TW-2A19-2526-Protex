/* =============================================
   main.js — Scripts globaux Protex
   FrontOffice — Version corrigée & améliorée
   ============================================= */

document.addEventListener('DOMContentLoaded', function () {

    // ===== DATE DYNAMIQUE BREADCRUMB =====
    const breadcrumbSpan = document.querySelector('.page-breadcrumb span');
    if (breadcrumbSpan) {
        const now = new Date();
        const dateStr = now.toLocaleDateString('fr-FR', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
        // Capitalise la première lettre
        breadcrumbSpan.textContent = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);
    }

    // ===== NAVIGATION ACTIVE (basée sur l'URL courante) =====
    const currentPage = location.pathname.split('/').pop() || 'client.html';
    document.querySelectorAll('.nav-link').forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });

    // ===== AVATAR DROPDOWN =====
    const avatarBtn = document.getElementById('avatarBtn');
    const avatarDropdown = document.getElementById('avatarDropdown');

    if (avatarBtn && avatarDropdown) {
        avatarBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            avatarDropdown.classList.toggle('open');
        });

        document.addEventListener('click', function (e) {
            if (!avatarDropdown.contains(e.target) && e.target !== avatarBtn) {
                avatarDropdown.classList.remove('open');
            }
        });

        // Fermer avec Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                avatarDropdown.classList.remove('open');
            }
        });
    }

    // ===== UPLOAD PHOTO PROFIL =====
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    const fileName = document.querySelector('.file-name');

    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                // Mettre à jour le nom du fichier affiché
                if (fileName) fileName.textContent = file.name;

                // Créer un img dynamiquement dans la div preview
                const reader = new FileReader();
                reader.onload = (ev) => {
                    avatarPreview.innerHTML = '';
                    const img = document.createElement('img');
                    img.src = ev.target.result;
                    img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:inherit;';
                    avatarPreview.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // ===== TOGGLE MOT DE PASSE =====
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

    // ===== VALIDATION FORMULAIRE PROFIL =====
    const saveButtons = document.querySelectorAll('.btn-save-profile');
    saveButtons.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const emailInput = document.querySelector('input[type="email"]');
            const telInput = document.querySelector('input[type="tel"]');
            let valid = true;

            // Validation email
            if (emailInput) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailInput.value)) {
                    emailInput.style.borderColor = 'var(--danger)';
                    showToast('Email invalide', 'danger');
                    valid = false;
                } else {
                    emailInput.style.borderColor = '';
                }
            }

            // Validation téléphone tunisien
            if (telInput) {
                const telRegex = /^(\+216\s?)?[2-9]\d{7}$/;
                const telClean = telInput.value.replace(/\s/g, '');
                if (!telRegex.test(telClean)) {
                    telInput.style.borderColor = 'var(--danger)';
                    showToast('Numéro de téléphone invalide', 'danger');
                    valid = false;
                } else {
                    telInput.style.borderColor = '';
                }
            }

            if (valid) {
                // État loading
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Enregistrement...';
                btn.disabled = true;

                // Simuler une requête (à remplacer par fetch PHP)
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                    showToast('Profil mis à jour avec succès', 'success');
                }, 1200);
            }
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

    document.querySelectorAll('.progress-fill:not([data-width])').forEach(bar => {
        const w = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => { bar.style.width = w; }, 400);
    });

    // ===== TOAST NOTIFICATION =====
    window.showToast = function (message, type = 'success') {
        const icons = { success: 'check-circle', warning: 'exclamation-triangle', danger: 'x-circle' };
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

});
