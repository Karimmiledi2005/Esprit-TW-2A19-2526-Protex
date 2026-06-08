// ===== DÉCLARER SINISTRE PAGE LOGIC =====

const AUTH_API            = 'auth_api.php';
const SINISTRE_LIST_API   = 'sinistre_list_user.php';
const SINISTRE_CREATE_API = 'sinistre_create.php';
const CONTRAT_API         = 'contrat_list_client.php';

let currentUserId = null;
let contratsData  = [];

const TYPE_ICONS = {
    'Accident auto':   'bi-car-front',
    'Incendie':        'bi-fire',
    'Vol':             'bi-shield-x',
    'Degat des eaux':  'bi-droplet-fill',
};
const STATUT_LABELS = { en_attente:'En attente', rembourse:'Remboursé', refuse:'Refusé' };
const STATUT_CLASS  = { en_attente:'badge-attente', rembourse:'badge-rembourse', refuse:'badge-refuse' };

// ── Boot ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    await loadSession();
    initCharCounter();
    initDragDrop();
});

async function loadSession() {
    // ── DEV MODE: pas besoin d'AuthController pour le moment ──
    // Remplacez la valeur ci-dessous par l'ID de l'utilisateur dans votre BDD
    currentUserId = 1;
    document.getElementById('avatarBtn').textContent = 'CL';
    document.getElementById('ddAvatar').textContent  = 'CL';
    document.getElementById('ddName').textContent    = 'Client Test';
    document.getElementById('ddEmail').textContent   = 'client@test.com';
    await Promise.all([loadContrats(), loadHistory()]);
    // ── FIN DEV MODE ──
    // Quand l'auth sera intégrée, remplacer par :
    // const res = await fetch(AUTH_API + '?action=session'); ...
}

// ── Load contrats ─────────────────────────────────────────────────────────────
async function loadContrats() {
    try {
        const url  = CONTRAT_API + '?id_user=' + currentUserId;
        const res  = await fetch(url);
        const json = await res.json();
        const sel  = document.getElementById('fContrat');

        if (json.success && json.data && json.data.length) {
            contratsData = json.data;
            json.data.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id_contrat;
                opt.textContent = (c.numero_contrat || 'CNT-' + c.id_contrat) + ' — ' + (c.type_contrat || c.nom_categorie || 'Contrat');
                sel.appendChild(opt);
            });
        } else {
            // No contracts or endpoint missing — show manual input fallback
            showContratFallback();
        }
    } catch(e) {
        showContratFallback();
    }
}

function showContratFallback() {
    const group = document.getElementById('fContrat').closest('.form-group');
    group.innerHTML = `
        <label>Numéro de contrat <span class="req">*</span></label>
        <input type="number" class="form-control" id="fContrat" placeholder="Ex: 1199" min="1">
        <div class="form-error" id="errContrat">Veuillez entrer un numéro de contrat valide.</div>
        <div style="font-size:11px;color:var(--text-secondary);margin-top:5px;">
            <i class="bi bi-info-circle"></i> Retrouvez votre numéro de contrat dans la section <a href="contrat.html" style="color:#FF6B1A;">Contrats</a>.
        </div>`;
}

function onContratChange() {
    const sel     = document.getElementById('fContrat');
    const preview = document.getElementById('contratPreview');
    const id      = parseInt(sel.value);
    clearErr('fContrat', 'errContrat');

    if (!id) { preview.style.display = 'none'; return; }

    const c = contratsData.find(x => x.id_contrat == id);
    if (!c) { preview.style.display = 'none'; return; }

    const today     = new Date().toISOString().split('T')[0];
    const expired   = c.date_fin_contrat && c.date_fin_contrat < today;
    const statutCls = c.statut_contrat === 'actif' && !expired ? '' : 'danger';
    const statutLbl = expired ? 'Expiré' : (c.statut_contrat || '—');

    preview.style.display = 'block';
    preview.innerHTML = `
        <div class="contrat-preview-row">
            <div class="contrat-preview-item">
                <span class="contrat-preview-label">Type</span>
                <span class="contrat-preview-val">${c.type_contrat || c.nom_categorie || '—'}</span>
            </div>
            <div class="contrat-preview-item">
                <span class="contrat-preview-label">Fin de contrat</span>
                <span class="contrat-preview-val ${expired ? 'danger' : ''}">${formatDate(c.date_fin_contrat)}</span>
            </div>
            <div class="contrat-preview-item">
                <span class="contrat-preview-label">Statut</span>
                <span class="contrat-preview-val ${statutCls}">${statutLbl}</span>
            </div>
            ${c.prime_contrat ? `<div class="contrat-preview-item">
                <span class="contrat-preview-label">Prime</span>
                <span class="contrat-preview-val">${parseFloat(c.prime_contrat).toLocaleString('fr-FR')} DT</span>
            </div>` : ''}
        </div>
        ${expired ? '<div style="margin-top:8px;font-size:11px;color:var(--danger);"><i class="bi bi-exclamation-circle"></i> Ce contrat est expiré. Veuillez contacter votre agence.</div>' : ''}`;
}

// ── Load history ──────────────────────────────────────────────────────────────
async function loadHistory() {
    const list = document.getElementById('historyList');
    const count = document.getElementById('historyCount');
    try {
        const url  = SINISTRE_LIST_API + '?id_user=' + currentUserId;
        const res  = await fetch(url);
        const json = await res.json();

        if (json.success && json.data && json.data.length) {
            const items = json.data.slice(0, 5); // show last 5
            count.textContent = json.data.length + ' sinistre' + (json.data.length > 1 ? 's' : '') + ' au total';
            list.innerHTML = items.map(s => `
                <div class="history-item">
                    <div class="history-top">
                        <div class="history-type">
                            <i class="bi ${TYPE_ICONS[s.type] || 'bi-shield-exclamation'}"></i>
                            ${s.type}
                        </div>
                        <span class="badge ${STATUT_CLASS[s.statut] || 'badge-attente'}">
                            ${STATUT_LABELS[s.statut] || s.statut}
                        </span>
                    </div>
                    <div class="history-contrat">${s.numero_contrat || 'CNT-' + s.id_contrat} · ${formatDate(s.date_declaration)}</div>
                    <div style="font-size:12px;color:var(--text-secondary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%;">${s.description || ''}</div>
                </div>`).join('');
        } else {
            count.textContent = 'Aucun sinistre';
            list.innerHTML = `<div class="history-empty">
                <i class="bi bi-inbox"></i>
                Vous n'avez aucun sinistre déclaré.
            </div>`;
        }
    } catch(e) {
        count.textContent = '';
        list.innerHTML = `<div class="history-empty">
            <i class="bi bi-wifi-off"></i>
            Impossible de charger l'historique.
        </div>`;
    }
}

// ── Type selector ─────────────────────────────────────────────────────────────
function selectType(btn) {
    document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('typeHidden').value = btn.dataset.val;
    clearErr('typeHidden', 'errType');
}

// ── Char counter ──────────────────────────────────────────────────────────────
function initCharCounter() {
    const ta = document.getElementById('fDescription');
    ta.addEventListener('input', () => {
        document.getElementById('charCount').textContent = ta.value.length;
        if (ta.value.length >= 20) clearErr('fDescription', 'errDescription');
    });
}

// ── Drag & drop upload ────────────────────────────────────────────────────────
function initDragDrop() {
    const zone = document.getElementById('uploadZone');
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag'));
    zone.addEventListener('drop', e => {
        e.preventDefault(); zone.classList.remove('drag');
        const file = e.dataTransfer.files[0];
        if (file) applyFile(file);
    });
}

function onFileChange(input) {
    if (input.files[0]) applyFile(input.files[0]);
}

function applyFile(file) {
    const maxMB = 5;
    if (file.size > maxMB * 1024 * 1024) {
        showToast('Fichier trop volumineux (max ' + maxMB + ' Mo).', 'warning'); return;
    }
    const allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!allowed.includes(file.type)) {
        showToast('Format non supporté (JPG, PNG, GIF, WEBP uniquement).', 'warning'); return;
    }
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('previewName').textContent = file.name + ' (' + (file.size/1024).toFixed(0) + ' Ko)';
        document.getElementById('uploadPreview').style.display = 'block';
        document.getElementById('uploadZone').style.display = 'none';
    };
    reader.readAsDataURL(file);
}

function removePhoto() {
    document.getElementById('fPhoto').value = '';
    document.getElementById('uploadPreview').style.display = 'none';
    document.getElementById('uploadZone').style.display = 'block';
}

// ── Submit ────────────────────────────────────────────────────────────────────
async function submitSinistre() {
    if (!validateSinistreForm()) return;

    const btn  = document.getElementById('btnSubmit');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Envoi en cours…';
    btn.disabled  = true;

    try {
        const formData = new FormData();
        formData.append('id_contrat',  contratVal);
        formData.append('type',        type);
        formData.append('description', desc);
        if (currentUserId) formData.append('id_user', currentUserId);
        const photoFile = document.getElementById('fPhoto').files[0];
        if (photoFile) formData.append('photo', photoFile);

        const res  = await fetch(SINISTRE_CREATE_API, { method: 'POST', body: formData });
        const json = await res.json();

        if (json.success) {
            // Show success state
            document.getElementById('formBody').style.display = 'none';
            document.getElementById('successState').style.display = 'block';
            document.getElementById('successId').textContent = 'Référence : SIN-' + String(json.id).padStart(4, '0');
            await loadHistory(); // refresh history panel
        } else {
            showToast(json.message || 'Erreur lors de la déclaration.', 'danger');
        }
    } catch(e) {
        showToast('Erreur réseau. Vérifiez votre connexion.', 'danger');
    } finally {
        btn.innerHTML = orig;
        btn.disabled  = false;
    }
}

// ── Reset form ────────────────────────────────────────────────────────────────
function resetForm() {
    document.getElementById('sinistreForm').reset();
    document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('selected'));
    document.getElementById('typeHidden').value = '';
    document.querySelectorAll('.form-error').forEach(e => e.classList.remove('show'));
    document.querySelectorAll('.form-control').forEach(e => e.classList.remove('error'));
    document.getElementById('contratPreview').style.display = 'none';
    document.getElementById('charCount').textContent = '0';
    removePhoto();
    document.getElementById('formBody').style.display = 'block';
    document.getElementById('successState').style.display = 'none';
}

// ── Utilities ─────────────────────────────────────────────────────────────────
function formatDate(d) {
    if (!d) return '—';
    const p = d.split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
}

function showToast(message, type) {
    type = type || 'success';
    const icons = { success:'check-circle', warning:'exclamation-triangle', danger:'x-circle' };
    const t = document.createElement('div');
    t.className = 'toast-notif toast-' + type;
    t.innerHTML = '<i class="bi bi-' + (icons[type] || 'check-circle') + '"></i><span>' + message + '</span>';
    document.body.appendChild(t);
    setTimeout(() => t.classList.add('show'), 50);
    setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 3500);
}
