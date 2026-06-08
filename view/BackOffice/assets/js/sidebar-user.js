/**
 * sidebar-user.js — Avatar / nom utilisateur (BackOffice + FrontOffice)
 */
(function (global) {
    'use strict';

    function initials(data) {
        const n = (data.nom || '').trim();
        const p = (data.prenom || '').trim();
        if (n || p) {
            return (n.charAt(0) + p.charAt(0)).toUpperCase();
        }
        return 'CL';
    }

    function resolveAvatarUrl(data) {
        const external = (data.avatar_url || '').trim();
        if (/^https?:\/\//i.test(external)) {
            return external;
        }

        const av = (data.avatar || '').trim();
        if (!av || av === 'default.png' || av === 'default') {
            return '';
        }

        const base = (global.BASE_URL || '').replace(/\/$/, '');

        if (/^https?:\/\//i.test(av)) {
            return av;
        }
        if (av.includes('/')) {
            return base + '/' + av.replace(/^\//, '');
        }
        return base + '/uploads/avatars/' + encodeURIComponent(av);
    }

    function normalizeUser(data) {
        if (!data || data.error || data.success === false) {
            return null;
        }
        if (data.user && typeof data.user === 'object') {
            return data.user;
        }
        return data;
    }

    function roleLabel(data) {
        const role = (data.role || 'client').toString();
        const labels = {
            superadmin: 'Super Admin',
            admin: 'Administrateur',
            agent: 'Agent',
            client: 'Client',
        };
        let label = labels[role.toLowerCase()] || role.charAt(0).toUpperCase() + role.slice(1);
        if (data.nom_agence) {
            label += ' (' + data.nom_agence + ')';
        }
        return label;
    }

    function setAvatarElement(el, init, src) {
        if (!el) {
            return;
        }
        if (src) {
            el.innerHTML =
                '<img src="' + src + '" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;"' +
                ' onerror="this.remove();this.parentElement.textContent=\'' + init.replace(/'/g, '') + '\';">';
        } else {
            el.textContent = init;
        }
    }

    function applyUser(data) {
        const user = normalizeUser(data);
        if (!user) {
            return;
        }

        const init = initials(user);
        const src = resolveAvatarUrl(user);
        const fullName = ((user.prenom || '') + ' ' + (user.nom || '')).trim() || 'Mon compte';

        global.document.querySelectorAll('[data-sidebar-avatar], [data-navbar-avatar]').forEach(function (el) {
            setAvatarElement(el, init, src);
        });

        global.document.querySelectorAll('.sidebar-user .user-name').forEach(function (el) {
            el.textContent = fullName;
        });

        global.document.querySelectorAll('.sidebar-user .user-role').forEach(function (el) {
            el.textContent = roleLabel(user);
        });

        const dropdownName = global.document.querySelector('.avatar-dropdown .dropdown-name');
        if (dropdownName) {
            dropdownName.textContent = fullName;
        }
        const dropdownEmail = global.document.querySelector('.avatar-dropdown .dropdown-email');
        if (dropdownEmail) {
            dropdownEmail.textContent = user.email || '';
        }
        const dropdownRole = global.document.querySelector('.avatar-dropdown .dropdown-role');
        if (dropdownRole) {
            dropdownRole.textContent = roleLabel(user);
        }
    }

    function refresh() {
        const path = global.location.pathname || '';
        const isBackOffice = path.indexOf('/BackOffice/') !== -1;
        const url = isBackOffice ? 'get_admin.php' : 'get_user.php';

        return fetch(url, { credentials: 'same-origin' })
            .then(function (res) {
                if (res.status === 401) {
                    return null;
                }
                return res.json();
            })
            .then(function (data) {
                const user = normalizeUser(data);
                if (user) {
                    applyUser(user);
                }
                return user;
            });
    }

    const api = {
        applyUser: applyUser,
        refresh: refresh,
        initials: initials,
        resolveAvatarUrl: resolveAvatarUrl,
        normalizeUser: normalizeUser,
    };

    global.ProtexSidebar = api;
    global.ProtexUser = api;
})(window);
