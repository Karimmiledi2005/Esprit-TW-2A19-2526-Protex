/**
 * api.js — Protex
 * Helper centralisé pour communiquer avec les API PHP (dans view/) via fetch()
 *
 * Les fichiers *_api.php se trouvent dans view/FrontOffice/ et appellent eux-mêmes les controllers.
 */

const Api = {
    /**
     * Appel générique vers un fichier _api.php situé dans le même dossier view/FrontOffice/
     */
    async call(apiFile, action, method = 'GET', body = null) {
        const url = `${apiFile}?action=${action}`;
        const options = { method, credentials: 'include' };

        if (body) {
            if (body instanceof FormData) {
                options.body = body; // multipart (upload)
            } else {
                options.headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
                options.body = new URLSearchParams(body).toString();
            }
        }

        const res = await fetch(url, options);
        return res.json();
    },

    // ── Auth ──────────────────────────────────────────────────────────────────

    login(email, password) {
        return this.call('auth_api.php', 'login', 'POST', { email, password });
    },

    register(nom, prenom, email, password, telephone = '') {
        return this.call('auth_api.php', 'register', 'POST', { nom, prenom, email, password, telephone });
    },

    logout() {
        return this.call('auth_api.php', 'logout', 'GET');
    },

    session() {
        return this.call('auth_api.php', 'session', 'GET');
    },

    // ── Contrats ──────────────────────────────────────────────────────────────

    getMyContrats() {
        return this.call('contrat_list_client.php', null, 'GET');
    },

    getAllContrats() {
        return this.call('contrat_list.php', null, 'GET');
    },

    findContrat(id) {
        return fetch(`contrat_find.php?id=${encodeURIComponent(id)}`, { method: 'GET', credentials: 'include' }).then(r => r.json());
    },

    // ── Sinistres ─────────────────────────────────────────────────────────────

    getMySinistres() {
        return this.call('sinistre_list_user.php', null, 'GET');
    },

    getAllSinistres() {
        return this.call('sinistre_list.php', null, 'GET');
    },

    getSinistreStats() {
        return this.call('sinistre_stats.php', null, 'GET');
    },

    createSinistre(formData) {
        return fetch('sinistre_create.php', { method: 'POST', credentials: 'include', body: formData }).then(r => r.json());
    },

    updateSinistre(id, data) {
        return fetch('sinistre_update.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ id, ...data }).toString()
        }).then(r => r.json());
    },

    deleteSinistre(id) {
        return fetch(`sinistre_delete.php?id=${encodeURIComponent(id)}`, {
            method: 'GET', credentials: 'include'
        }).then(r => r.json());
    },
};

// ── Utilitaires globaux ────────────────────────────────────────────────────────

function showToast(message, type = 'success') {
    const icons = { success: 'bi-check-circle-fill', warning: 'bi-exclamation-triangle-fill', danger: 'bi-x-circle-fill' };
    let toast = document.getElementById('toast-global');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast-global';
        toast.className = 'toast-notif';
        document.body.appendChild(toast);
    }
    toast.className = `toast-notif toast-${type}`;
    toast.innerHTML = `<i class="bi ${icons[type]}"></i> ${message}`;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3500);
}

async function requireLogin(redirectTo = 'login.html') {
    const res = await Api.session();
    if (!res.loggedIn) {
        window.location.href = redirectTo;
        return null;
    }
    return res.user;
}

function fillUserInfo(user) {
    document.querySelectorAll('[data-user]').forEach(el => {
        const key = el.dataset.user;
        if (user[key] !== undefined) el.textContent = user[key];
    });
}
