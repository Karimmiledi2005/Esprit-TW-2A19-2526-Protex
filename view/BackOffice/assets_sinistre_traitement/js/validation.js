// ===== BACK OFFICE VALIDATION UTILITIES =====

/**
 * Show error for input field
 * @param {string} inputId - Input element ID
 * @param {string} errId - Error element ID
 * @param {string} msg - Error message (optional)
 */
function showErr(inputId, errId, msg) {
    const input = document.getElementById(inputId);
    const err = document.getElementById(errId);
    if (input) input.classList.add('error');
    if (err) {
        err.textContent = msg || 'Champ requis';
        err.classList.add('show');
    }
}

/**
 * Clear error for input field
 * @param {string} inputId - Input element ID
 * @param {string} errId - Error element ID
 */
function clearErr(inputId, errId) {
    const input = document.getElementById(inputId);
    const err = document.getElementById(errId);
    if (input) input.classList.remove('error');
    if (err) err.classList.remove('show');
}

/**
 * Clear all form errors
 */
function clearAllErrors() {
    document.querySelectorAll('.form-error').forEach(e => e.classList.remove('show'));
    document.querySelectorAll('.form-control').forEach(e => e.classList.remove('error'));
}

/**
 * Show error with toggle (for admin-users.html)
 * @param {string} inputId - Input element ID
 * @param {string} errId - Error element ID
 * @param {boolean} show - Whether to show or hide error
 */
function showErrToggle(inputId, errId, show) {
    document.getElementById(inputId).classList.toggle('error', show);
    const errEl = document.getElementById(errId);
    if (errEl) errEl.classList.toggle('show', show);
}

/**
 * Validate sinistre ID with real-time API check
 * @returns {Promise<boolean>} - True if valid, false otherwise
 */
async function validateSinistreId() {
    const val = document.getElementById('fSinistre').value.trim();
    const preview = document.getElementById('sinistrePreview');

    if (!val) {
        showErr('fSinistre', 'errSinistre', "L'ID du sinistre est requis.");
        preview.style.display = 'none';
        return false;
    }

    const id = parseInt(val);
    if (isNaN(id) || id <= 0) {
        showErr('fSinistre', 'errSinistre', 'Entrez un nombre entier positif.');
        preview.style.display = 'none';
        return false;
    }

    try {
        const res = await fetch(TRAIT_CHECK_API + '?id=' + id);
        const json = await res.json();

        if (json.success) {
            clearErr('fSinistre', 'errSinistre');
            const s = json.data;
            const statutColors = { en_attente: '#f4a261', rembourse: '#2ec4b6', refuse: '#e63946' };
            const statutLabels = { en_attente: 'En attente', rembourse: 'Remboursé', refuse: 'Refusé' };

            // Check if treatment already exists for this sinistre
            if (!editingId) {
                const checkRes = await fetch(TRAIT_BY_SINISTRE_API + '?id=' + id);
                const checkJson = await checkRes.json();
                if (checkJson.success && checkJson.data && checkJson.data.length > 0) {
                    showErr('fSinistre', 'errSinistre',
                        'Le sinistre #' + id + ' a déjà un traitement enregistré. Vous ne pouvez pas en ajouter un deuxième.');
                    preview.style.display = 'none';
                    return false;
                }
            }

            preview.style.display = 'block';
            preview.innerHTML =
                '<div style="background:rgba(0,180,216,0.08);border:1px solid rgba(0,180,216,0.25);border-radius:10px;padding:12px 14px;margin-top:6px;">' +
                    '<div style="display:flex;align-items:center;gap:10px;">' +
                        '<i class="bi bi-shield-check" style="color:var(--accent);font-size:18px;"></i>' +
                        '<div>' +
                            '<div style="font-size:13px;font-weight:600;color:#fff;">Sinistre #' + s.id_sinistre + ' trouvé ✓</div>' +
                            '<div style="font-size:12px;color:var(--text-secondary);margin-top:2px;">' +
                                'Type : <strong style="color:var(--text-primary);">' + s.type + '</strong> &nbsp;·&nbsp; ' +
                                'Client : <strong style="color:var(--text-primary);">' + (s.client_nom || '—') + '</strong> &nbsp;·&nbsp; ' +
                                'Statut : <strong style="color:' + (statutColors[s.statut] || '#fff') + ';">' + (statutLabels[s.statut] || s.statut) + '</strong>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            return true;
        } else {
            showErr('fSinistre', 'errSinistre', json.message);
            preview.style.display = 'none';
            return false;
        }
    } catch (e) {
        showErr('fSinistre', 'errSinistre', 'Erreur réseau lors de la vérification.');
        preview.style.display = 'none';
        return false;
    }
}

/**
 * Validate user form (admin-users.html)
 * @returns {boolean} - True if valid, false otherwise
 */
function validateUserForm() {
    let ok = true;
    const nom = document.getElementById('fNom').value.trim();
    const prenom = document.getElementById('fPrenom').value.trim();
    const email = document.getElementById('fEmail').value.trim();
    const pwd = document.getElementById('fPassword').value;

    if (!nom) { showErrToggle('fNom', 'errNom', true); ok = false; } else showErrToggle('fNom', 'errNom', false);
    if (!prenom) { showErrToggle('fPrenom', 'errPrenom', true); ok = false; } else showErrToggle('fPrenom', 'errPrenom', false);
    if (!isValidEmail(email)) { showErrToggle('fEmail', 'errEmail', true); ok = false; } else showErrToggle('fEmail', 'errEmail', false);
    if (!editingId && pwd.length < 8) { showErrToggle('fPassword', 'errPassword', true); ok = false; } else showErrToggle('fPassword', 'errPassword', false);

    return ok;
}

/**
 * Validate email format
 * @param {string} email - Email to validate
 * @returns {boolean} - True if valid email format
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Check password strength (admin version)
 * @param {string} val - Password value
 */
function checkStrength(val) {
    const fill = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');
    if (!fill || !label) return;
    const cfg = val.length === 0 ? { w: '0%', c: '', t: 'Entrez un mot de passe' }
        : val.length < 6 ? { w: '25%', c: 'var(--danger)', t: 'Trop court' }
        : val.length < 10 ? { w: '55%', c: 'var(--gold)', t: 'Moyen' }
        : { w: '100%', c: 'var(--success)', t: 'Fort' };
    fill.style.width = cfg.w;
    fill.style.background = cfg.c;
    label.textContent = cfg.t;
    label.style.color = cfg.c || 'var(--text-secondary)';
}

/**
 * Show toast notification
 * @param {string} message - Message to display
 * @param {string} type - Type of toast (success, danger, warning)
 */
function showToast(message, type = 'success') {
    const icons = { success: 'check-circle', warning: 'exclamation-triangle', danger: 'x-circle' };
    const toast = document.createElement('div');
    toast.className = 'toast-notif toast-' + type;
    toast.innerHTML = '<i class="bi bi-' + (icons[type] || 'check-circle') + '"></i><span>' + message + '</span>';
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 50);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}