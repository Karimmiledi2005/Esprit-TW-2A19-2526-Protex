// ===== DÉCLARER SINISTRE PAGE LOGIC =====

const AUTH_API            = 'auth_api.php';
const SINISTRE_LIST_API   = 'sinistre_list_user.php';
const SINISTRE_CREATE_API = 'sinistre_create.php';
const CONTRAT_API         = 'contrat_list_client.php';

let currentUserId = null;
let contratsData  = [];
let fraudPreviewTimer = null;
let fraudLastScore = 0;

const TYPE_ICONS = {
    'Accident auto':         'bi-car-front',
    'Incendie':              'bi-fire',
    'Vol':                   'bi-shield-x',
    'Degat des eaux':        'bi-droplet-fill',
    'Bris de glace':         'bi-columns-gap',
    'Catastrophe naturelle': 'bi-cloud-lightning-rain',
    'Deces':                 'bi-heart-pulse',
    'Invalidite':            'bi-person-wheelchair',
    'Hospitalisation':       'bi-hospital',
    'Accident':              'bi-bandaid',
    'Maladie':               'bi-thermometer-half',
};
const STATUT_LABELS = { en_attente:'En attente', rembourse:'Remboursé', refuse:'Refusé' };
const STATUT_CLASS  = { en_attente:'badge-attente', rembourse:'badge-rembourse', refuse:'badge-refuse' };

// ── Boot ──────────────────────────────────────────────────────────────────────
let currentStep = 1;

document.addEventListener('DOMContentLoaded', async () => {
    await loadSession();
    initCharCounter();
    initFraudPreview();
    initDragDrop();
    updateStepperUI();

    // Listen for ESC key to close lightbox
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeLightbox();
    });
});

// -- Stepper Logic --------------------------------------------------------------
function nextStep(n) {
    if (n === 2) {
        // Validate Step 1
        const contrat = document.getElementById('fContrat').value;
        const type = document.getElementById('typeHidden').value;
        let ok = true;
        if (!contrat) { showErr('fContrat', 'errContrat'); ok = false; }
        if (!type) { showErr('typeHidden', 'errType'); ok = false; }
        if (!ok) return;
    }
    if (n === 3) {
        // Validate Step 2
        const desc = document.getElementById('fDescription').value.trim();
        if (desc.length < 20) { showErr('fDescription', 'errDescription'); return; }
        renderSummary();
    }
    
    currentStep = n;
    updateStepperUI();
}

function prevStep(n) {
    currentStep = n;
    updateStepperUI();
}

function updateStepperUI() {
    // Content
    document.querySelectorAll('.step-content').forEach(s => s.classList.remove('active'));
    document.getElementById('step' + currentStep).classList.add('active');

    // Header
    document.querySelectorAll('.step').forEach((s, idx) => {
        const stepNum = idx + 1;
        s.classList.remove('active', 'completed');
        if (stepNum === currentStep) s.classList.add('active');
        else if (stepNum < currentStep) s.classList.add('completed');
    });
}

function renderSummary() {
    const sel = document.getElementById('fContrat');
    const contratNum = sel.options[sel.selectedIndex].text;
    const type = document.getElementById('typeHidden').value;
    const desc = document.getElementById('fDescription').value;
    const photo = document.getElementById('fPhoto').files[0];

    const view = document.getElementById('summaryView');
    view.innerHTML = `
        <div class="summary-item">
            <div class="summary-label">Contrat</div>
            <div class="summary-val"><i class="bi bi-file-earmark-text"></i> ${contratNum}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Type de sinistre</div>
            <div class="summary-val"><i class="bi bi-tag"></i> ${type}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Description</div>
            <div class="summary-val" style="line-height:1.5;">${desc}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Justificatif</div>
            <div class="summary-val">
                <i class="bi bi-image"></i> ${photo ? photo.name : '<span style="color:var(--text-secondary);font-style:italic;">Aucune photo jointe</span>'}
                ${photo ? `<div style="margin-top:8px; border-radius:8px; overflow:hidden; border:1px solid rgba(26,58,122,0.1); max-width:150px;">
                    <img src="${document.getElementById('previewImg').src}" style="width:100%; display:block; cursor:pointer;" onclick="openLightbox()">
                </div>` : ''}
            </div>
        </div>
    `;
}

async function loadSession() {
    try {
        const res  = await fetch('get_user.php');
        if (res.status === 401) { window.location.href = 'login.html'; return; }
        let data = await res.json();
        if (data.success && data.user) data = data.user;
        if (data.error) { window.location.href = 'login.html'; return; }

        currentUserId = data.id_user || data.id || null;

        const initials = ((data.prenom ? data.prenom[0] : '') + (data.nom ? data.nom[0] : '')).toUpperCase() || 'CL';
        const fullName = (data.prenom || '') + ' ' + (data.nom || '');

        const avatarBtn = document.getElementById('avatarBtn');
        const ddAvatar  = document.getElementById('dropdownAvatar');
        const ddName    = document.getElementById('dropdownName');
        const ddEmail   = document.getElementById('dropdownEmail');

        if (avatarBtn) avatarBtn.textContent = initials;
        if (ddAvatar)  ddAvatar.textContent  = initials;
        if (ddName)    ddName.textContent    = fullName.trim() || 'Client';
        if (ddEmail)   ddEmail.textContent   = data.email || '';

    } catch(e) {
        currentUserId = null;
    }
    await Promise.all([loadContrats(), loadHistory()]);
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

// -- Contract type -> sinistre types mapping ------------------------------------
const TYPE_MAP = {
    'auto':       [
        { val: 'Accident auto',         icon: 'bi-car-front',            label: 'Accident auto' },
        { val: 'Vol',                   icon: 'bi-shield-x',             label: 'Vol de vehicule' },
        { val: 'Bris de glace',         icon: 'bi-columns-gap',          label: 'Bris de glace' },
        { val: 'Incendie',              icon: 'bi-fire',                 label: 'Incendie vehicule' },
    ],
    'habitation': [
        { val: 'Incendie',              icon: 'bi-fire',                 label: 'Incendie' },
        { val: 'Vol',                   icon: 'bi-shield-x',             label: 'Cambriolage / Vol' },
        { val: 'Degat des eaux',        icon: 'bi-droplet-fill',         label: 'Degat des eaux' },
        { val: 'Catastrophe naturelle', icon: 'bi-cloud-lightning-rain', label: 'Catastrophe naturelle' },
    ],
    'vie':        [
        { val: 'Deces',                 icon: 'bi-heart-pulse',          label: 'Deces' },
        { val: 'Invalidite',            icon: 'bi-person-wheelchair',    label: 'Invalidite' },
        { val: 'Hospitalisation',       icon: 'bi-hospital',             label: 'Hospitalisation' },
    ],
    'sante':      [
        { val: 'Hospitalisation',       icon: 'bi-hospital',             label: 'Hospitalisation' },
        { val: 'Accident',              icon: 'bi-bandaid',              label: 'Accident corporel' },
        { val: 'Maladie',               icon: 'bi-thermometer-half',     label: 'Maladie grave' },
    ],
    'protection': [
        { val: 'Vol',                   icon: 'bi-shield-x',             label: 'Vol / Vandalisme' },
        { val: 'Degat des eaux',        icon: 'bi-droplet-fill',         label: 'Degat des eaux' },
        { val: 'Incendie',              icon: 'bi-fire',                 label: 'Incendie' },
        { val: 'Catastrophe naturelle', icon: 'bi-cloud-lightning-rain', label: 'Catastrophe naturelle' },
    ],
    'default':    [
        { val: 'Accident auto',         icon: 'bi-car-front',            label: 'Accident auto' },
        { val: 'Incendie',              icon: 'bi-fire',                 label: 'Incendie' },
        { val: 'Vol',                   icon: 'bi-shield-x',             label: 'Vol' },
        { val: 'Degat des eaux',        icon: 'bi-droplet-fill',         label: 'Degat des eaux' },
    ],
};

function getContratTypeKey(contrat) {
    const raw = ((contrat.type_contrat || contrat.nom_categorie || '')).toLowerCase();
    if (raw === 'auto' || raw.includes('auto') || raw.includes('voiture') || raw.includes('vehicule')) return 'auto';
    if (raw === 'habitation' || raw.includes('habitation') || raw.includes('maison') || raw.includes('logement')) return 'habitation';
    if (raw === 'sante' || raw.includes('sante') || raw.includes('sant\u00e9') || raw.includes('medical')) return 'sante';
    if (raw === 'protection' || raw.includes('protection')) return 'protection';
    if (raw.includes('vie') || raw.includes('deces') || raw.includes('d\u00e9c\u00e8s')) return 'vie';
    return 'default';
}

function updateTypeButtons(contrat) {
    const key   = contrat ? getContratTypeKey(contrat) : 'default';
    const types = TYPE_MAP[key] || TYPE_MAP['default'];
    const grid  = document.querySelector('.type-grid');
    document.getElementById('typeHidden').value = '';

    const typeName = contrat ? (contrat.type_contrat || contrat.nom_categorie || '') : '';
    const hint = (contrat && typeName)
        ? '<div style="font-size:11px;color:#FF6B1A;margin-bottom:8px;display:flex;align-items:center;gap:5px;grid-column:1/-1;"><i class="bi bi-info-circle"></i>&nbsp;Types disponibles pour un contrat <strong>' + typeName + '</strong></div>'
        : '';

    grid.innerHTML = hint + types.map(function(t) {
        return '<button type="button" class="type-btn" data-val="' + t.val + '" onclick="selectType(this)">'
             + '<i class="bi ' + t.icon + '"></i><span>' + t.label + '</span></button>';
    }).join('');
}

function onContratChange() {
    const sel     = document.getElementById('fContrat');
    const preview = document.getElementById('contratPreview');
    const id      = parseInt(sel.value);
    clearErr('fContrat', 'errContrat');

    if (!id) { preview.style.display = 'none'; updateTypeButtons(null); return; }

    const c = contratsData.find(function(x) { return x.id_contrat == id; });
    if (!c) { preview.style.display = 'none'; return; }

    const today     = new Date().toISOString().split('T')[0];
    const expired   = c.date_fin_contrat && c.date_fin_contrat < today;
    const statutCls = (c.statut_contrat === 'actif' && !expired) ? '' : 'danger';
    const statutLbl = expired ? 'Expire' : (c.statut_contrat || '-');

    preview.style.display = 'block';
    let primeHtml = c.prime_contrat
        ? '<div class="contrat-preview-item"><span class="contrat-preview-label">Prime</span><span class="contrat-preview-val">' + parseFloat(c.prime_contrat).toLocaleString('fr-FR') + ' DT</span></div>'
        : '';
    let expiredHtml = expired
        ? '<div style="margin-top:8px;font-size:11px;color:var(--danger);"><i class="bi bi-exclamation-circle"></i> Ce contrat est expire.</div>'
        : '';

    preview.innerHTML =
        '<div class="contrat-preview-row">'
        + '<div class="contrat-preview-item"><span class="contrat-preview-label">Type</span><span class="contrat-preview-val">' + (c.type_contrat || c.nom_categorie || '-') + '</span></div>'
        + '<div class="contrat-preview-item"><span class="contrat-preview-label">Fin de contrat</span><span class="contrat-preview-val ' + (expired ? 'danger' : '') + '">' + formatDate(c.date_fin_contrat) + '</span></div>'
        + '<div class="contrat-preview-item"><span class="contrat-preview-label">Statut</span><span class="contrat-preview-val ' + statutCls + '">' + statutLbl + '</span></div>'
        + primeHtml
        + '</div>'
        + expiredHtml;

    // Update sinistre type buttons based on the contract type
    updateTypeButtons(c);
}

// -- Type selector -------------------------------------------------------------
function selectType(btn) {
    document.querySelectorAll('.type-btn').forEach(function(b) { b.classList.remove('selected'); });
    btn.classList.add('selected');
    document.getElementById('typeHidden').value = btn.dataset.val;
    clearErr('typeHidden', 'errType');
    scheduleFraudPreview();
}


// ── Char counter ──────────────────────────────────────────────────────────────
function initCharCounter() {
    const ta = document.getElementById('fDescription');
    ta.addEventListener('input', () => {
        document.getElementById('charCount').textContent = ta.value.length;
        if (ta.value.length >= 20) clearErr('fDescription', 'errDescription');
        scheduleFraudPreview();
    });
}

function initFraudPreview() {
    const ta = document.getElementById('fDescription');
    if (!ta) return;
    ta.addEventListener('keyup', scheduleFraudPreview);
}

function scheduleFraudPreview() {
    clearTimeout(fraudPreviewTimer);
    fraudPreviewTimer = setTimeout(runFraudPreview, 800);
}

async function runFraudPreview() {
    const description = document.getElementById('fDescription').value.trim();
    const type = document.getElementById('typeHidden').value || 'sinistre';
    const scoreEl   = document.getElementById('fraudScoreValue');
    const gaugePath = document.getElementById('fraudGaugePath');
    const label     = document.getElementById('fraudLabel');
    const indicator = document.getElementById('fraud-indicator');

    if (!description) {
        fraudLastScore = 0;
        updateGauge(0, '#2EC4B6', scoreEl, gaugePath, label, indicator);
        return;
    }

    try {
        const response = await fetch('api.php?action=fraud_preview', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type, description }),
        });
        const data = await response.json();
        const score = Math.max(0, Math.min(100, parseInt(data.score || 0, 10)));
        fraudLastScore = score;

        let color = '#2EC4B6';
        let text  = '✅ Faible risque';
        let cls   = '';
        let flags = [];

        if (score >= 81) {
            color = '#e63946';
            text  = '🔴 FRAUDE PROBABLE';
            cls   = 'probable';
            flags = data.flags || ['Montant suspect', 'Incohérence temporelle', 'Description générique'];
        } else if (score >= 61) {
            color = '#f4a261';
            text  = '🚨 Suspect';
            cls   = 'suspect';
            flags = data.flags || ['Détails insuffisants'];
        } else if (score >= 31) {
            color = '#EF9F27';
            text  = '⚠️ Risque modéré';
            cls   = 'modere';
            flags = data.flags || [];
        }

        updateGauge(score, color, scoreEl, gaugePath, label, indicator);
        label.textContent = text;
        label.className   = 'fraud-progress-label' + (cls ? ' ' + cls : '');

        // Pulse the gauge container on high risk
        const gaugeCont = indicator.querySelector('.gauge-container');
        if (score >= 81) {
            gaugeCont.style.animation = 'fraudPulse 1s ease-in-out infinite';
        } else {
            gaugeCont.style.animation = '';
        }

        // Show flags below label
        let flagsEl = document.getElementById('fraudFlags');
        if (!flagsEl) {
            flagsEl = document.createElement('div');
            flagsEl.id = 'fraudFlags';
            flagsEl.style.cssText = 'margin-top:6px;display:flex;flex-wrap:wrap;gap:4px;';
            label.parentNode.appendChild(flagsEl);
        }
        flagsEl.innerHTML = flags.map(f =>
            `<span style="font-size:10px;padding:2px 7px;border-radius:20px;background:rgba(230,57,70,0.12);color:#e63946;border:1px solid rgba(230,57,70,0.3);">⚑ ${f}</span>`
        ).join('');

    } catch (e) {
        if (label) {
            label.textContent = 'Analyse indisponible';
            label.className   = 'fraud-progress-label modere';
        }
    }
}

function updateGauge(score, color, scoreEl, gaugePath, label, indicator) {
    if (scoreEl)   scoreEl.textContent = score;
    if (scoreEl)   scoreEl.style.color = color;
    if (gaugePath) {
        gaugePath.style.stroke = color;
        gaugePath.style.strokeDasharray = score + ', 100';
    }
    if (!score && label) {
        label.textContent = '✅ Faible risque';
        label.className   = 'fraud-progress-label';
        const flagsEl = document.getElementById('fraudFlags');
        if (flagsEl) flagsEl.innerHTML = '';
        const gaugeCont = indicator && indicator.querySelector('.gauge-container');
        if (gaugeCont) gaugeCont.style.animation = '';
    }
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
    document.getElementById('previewImg').src = '';
}

// ── Lightbox ──────────────────────────────────────────────────────────────────
function openLightbox() {
    const src = document.getElementById('previewImg').src;
    if (!src) return;
    document.getElementById('lightboxImg').src = src;
    document.getElementById('photoLightbox').classList.add('show');
    document.body.style.overflow = 'hidden'; // Empêcher le scroll
}

function closeLightbox() {
    document.getElementById('photoLightbox').classList.remove('show');
    document.body.style.overflow = '';
}

// ── Submit ────────────────────────────────────────────────────────────────────
async function submitSinistre() {
    const contratVal = document.getElementById('fContrat').value;
    const type       = document.getElementById('typeHidden').value;
    const desc       = document.getElementById('fDescription').value.trim();

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
    
    currentStep = 1;
    updateStepperUI();
    
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
