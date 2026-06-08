/* =============================================
   main.js — Scripts globaux Protex
   Handles Navbar, Dropdowns, and User Session
   ============================================= */

(function() {
    function init() {
        console.log('[Protex] Global Initialization...');
        initNavbar();
        loadUserSession();
        initUIHelpers();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function initNavbar() {
        // Logique maintenant gérée dans navbar.php pour éviter les conflits multi-scripts
    }

    function loadUserSession() {
        const els = {
            name: document.getElementById('dropdownName'),
            email: document.getElementById('dropdownEmail'),
            role: document.getElementById('dropdownRole'),
            initials: document.getElementById('avatarInitials'),
            avatar: document.getElementById('dropdownAvatar'),
            welcome: document.getElementById('welcome')
        };

        fetch('get_user.php')
            .then(r => r.json())
            .then(data => {
                if (data.success && data.user) {
                    const u = data.user;
                    if (els.name) els.name.textContent = u.prenom + ' ' + u.nom;
                    if (els.email) els.email.textContent = u.email;
                    if (els.role) els.role.textContent = u.role || 'Client';
                    if (els.welcome) els.welcome.textContent = 'Bonjour, ' + u.prenom + ' 👋';

                    const initials = ((u.prenom || '').charAt(0) + (u.nom || '').charAt(0)).toUpperCase() || '??';
                    
                    [els.initials, els.avatar].forEach(el => {
                        if (!el) return;
                        el.textContent = initials;
                        el.style.display = 'flex';
                        el.style.alignItems = 'center';
                        el.style.justifyContent = 'center';
                    });

                    // Handle real image if exists
                    let src = '';
                    if (u.avatar_url) src = u.avatar_url;
                    else if (u.avatar && u.avatar !== 'default.png') src = '../../uploads/avatars/' + u.avatar;
                    else if (u.photo && u.photo !== 'default.png') src = '../../uploads/avatars/' + u.photo;

                    if (src) {
                        [els.initials, els.avatar].forEach(el => {
                            if (!el) return;
                            el.innerHTML = '<img src="' + src + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%" onerror="this.parentElement.textContent=\'' + initials + '\'">';
                        });
                    }
                }
            })
            .catch(err => console.error('[Protex] Session Error:', err));
    }

    function initUIHelpers() {
        const bars = document.querySelectorAll('.progress-fill[data-width]');
        setTimeout(() => {
            bars.forEach(bar => {
                bar.style.width = bar.getAttribute('data-width') + '%';
            });
        }, 300);

        window.showToast = function(msg, type) {
            type = type || 'success';
            const toast = document.createElement('div');
            toast.className = 'toast-notif toast-' + type;
            toast.innerHTML = '<span>' + msg + '</span>';
            document.body.appendChild(toast);
            setTimeout(() => toast.classList.add('show'), 50);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        };
    }
})();
