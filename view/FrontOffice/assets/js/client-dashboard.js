// ══════════════════════════════════════════════════════════════════
//  CLIENT DASHBOARD — DYNAMIC DATA LOADING
// ══════════════════════════════════════════════════════════════════

// ═══════════════════════════════════════════
//  CHARGEMENT PROFIL UTILISATEUR
// ═══════════════════════════════════════════
async function loadUserProfile() {
    try {
        const res  = await fetch('get_user.php');
        const json = await res.json();
        if (!json.success) {
            window.location.href = 'login.html';
            return;
        }

        const u = json.user;
        const initials = ((u.nom?.[0] ?? '') + (u.prenom?.[0] ?? '')).toUpperCase() || 'U';
        const fullName = (u.prenom ?? '') + ' ' + (u.nom ?? '');
        const hour     = new Date().getHours();
        const greeting = hour < 12 ? 'Bonjour' : hour < 18 ? 'Bon après-midi' : 'Bonsoir';

        // Update avatar and dropdowns
        document.getElementById('avatarInitials').textContent  = initials;
        document.getElementById('dropdownAvatar').textContent  = initials;
        document.getElementById('dropdownName').textContent    = fullName;
        document.getElementById('dropdownEmail').textContent   = u.email ?? '';
        document.getElementById('dropdownRole').textContent    = u.role  ?? 'Client';
        document.getElementById('welcome').textContent = `${greeting}, ${u.prenom ?? 'Client'} 👋`;

    } catch(e) {
        console.error('Erreur profil:', e);
        document.getElementById('welcome').textContent = 'Tableau de bord';
    }
}

// ═══════════════════════════════════════════
//  CHARGEMENT STATS DASHBOARD
// ═══════════════════════════════════════════

function addIdToElement(selector, id) {
    const el = document.querySelector(selector);
    if (el) el.id = id;
}

async function loadDashboardStats() {
    // Add IDs to stat elements if not present
    addIdToElement('.stat-card.blue .stat-value', 'statContrats');
    addIdToElement('.stat-card.blue .stat-trend', 'trendContrats');
    addIdToElement('.stat-card.gold .stat-value', 'statSinistres');
    addIdToElement('.stat-card.gold .stat-trend', 'trendSinistres');
    addIdToElement('.stat-card.green .stat-value', 'statPrimes');
    addIdToElement('.stat-card.red .stat-value', 'statReclamations');
    addIdToElement('.stat-card.red .stat-trend', 'trendReclamations');

    // ── Contrats ──
    try {
        const res  = await fetch('contrat_list_client.php');
        const json = await res.json();
        if (json.success) {
            const contrats = json.data ?? [];
            const actifs   = contrats.filter(c => c.statut_contrat === 'actif' || c.statut_contrat === 'Actif');
            const el = document.getElementById('statContrats') || document.querySelector('.stat-card.blue .stat-value');
            if (el) el.textContent = actifs.length;

            const trendEl = document.getElementById('trendContrats') || document.querySelector('.stat-card.blue .stat-trend');
            if (trendEl) trendEl.innerHTML = `<i class="bi bi-arrow-up"></i> ${actifs.length} valide${actifs.length !== 1 ? 's' : ''}`;

            // Update section subtitle
            const subEl = document.querySelector('.section-sub');
            if (subEl && subEl.textContent.includes('contrats')) {
                subEl.textContent = `${contrats.length} contrat${contrats.length > 1 ? 's' : ''} actif${contrats.length > 1 ? 's' : ''}`;
            }

            // Calculate total premiums
            const totalPrimes = contrats.reduce((sum, c) => sum + parseFloat(c.prime_contrat ?? 0), 0) * 12;
            const premEl = document.getElementById('statPrimes') || document.querySelector('.stat-card.green .stat-value');
            if (premEl) premEl.innerHTML = `${Math.round(totalPrimes)} <span>TND</span>`;

            // Update badge
            const badge = document.querySelector('.nav-link [href="contrat.php"] .nav-badge.accent');
            if (badge) badge.textContent = actifs.length;
        }
    } catch(e) { console.error('Erreur contrats:', e); }

    // ── Sinistres ──
    try {
        const res  = await fetch('sinistre_list_user.php');
        const json = await res.json();
        if (json.success) {
            const sinistres  = json.data ?? [];
            const enCours    = sinistres.filter(s => s.statut === 'en_attente' || s.statut === 'en_cours');
            const el = document.getElementById('statSinistres') || document.querySelector('.stat-card.gold .stat-value');
            if (el) el.textContent = enCours.length;

            const trendEl = document.getElementById('trendSinistres') || document.querySelector('.stat-card.gold .stat-trend');
            if (trendEl) {
                if (enCours.length > 0) {
                    trendEl.innerHTML = `<i class="bi bi-clock"></i> En traitement`;
                } else {
                    trendEl.innerHTML = `<i class="bi bi-check-circle"></i> Aucun en cours`;
                    trendEl.classList.add('trend-success');
                }
            }

            // Update badge
            const badge = document.querySelector('.nav-link [href="mes-sinistres.html"] .nav-badge');
            if (badge) {
                badge.textContent = enCours.length;
                if (enCours.length === 0) badge.style.display = 'none';
            }
        }
    } catch(e) { console.error('Erreur sinistres:', e); }

    // ── Réclamations ──
    try {
        const res  = await fetch('reclamationList.php?json=1');
        const json = await res.json();
        if (json.success) {
            const ouvertes = (json.data ?? []).filter(r => r.statut === 'open');
            const el = document.getElementById('statReclamations') || document.querySelector('.stat-card.red .stat-value');
            if (el) el.textContent = ouvertes.length;

            const trendEl = document.getElementById('trendReclamations') || document.querySelector('.stat-card.red .stat-trend');
            if (trendEl) {
                if (ouvertes.length > 0) {
                    trendEl.innerHTML = `<i class="bi bi-clock"></i> Réponse en attente`;
                } else {
                    trendEl.innerHTML = `<i class="bi bi-check-circle"></i> Aucune ouverte`;
                    trendEl.classList.add('trend-success');
                }
            }
        }
    } catch(e) { console.error('Erreur réclamations:', e); }
}

// ═══════════════════════════════════════════
//  CONTRATS DYNAMIQUES
// ═══════════════════════════════════════════

const TYPE_ICON = {
    'auto':       { icon: 'bi-car-front',    css: 'auto',   label: 'Auto' },
    'habitation': { icon: 'bi-house-heart',  css: 'maison', label: 'Habitation' },
    'sante':      { icon: 'bi-heart-pulse',  css: 'sante',  label: 'Santé' },
    'santé':      { icon: 'bi-heart-pulse',  css: 'sante',  label: 'Santé' },
    'protection': { icon: 'bi-shield-check', css: 'auto',   label: 'Protection' },
    'default':    { icon: 'bi-file-earmark-text', css: 'sante', label: 'Contrat' },
};

function getTypeInfo(type) {
    const key = (type ?? '').toLowerCase();
    for (const k of Object.keys(TYPE_ICON)) {
        if (key.includes(k)) return TYPE_ICON[k];
    }
    return TYPE_ICON['default'];
}

function formatDate(d) {
    if (!d) return '—';
    const [y,m,day] = d.split('-');
    const months = ['','Jan','Fév','Mar','Avr','Mai','Juin','Juil','Août','Sep','Oct','Nov','Déc'];
    return `${parseInt(day)} ${months[parseInt(m)]} ${y}`;
}

async function loadContrats() {
    try {
        const res  = await fetch('contrat_list_client.php');
        const json = await res.json();
        const grid = document.querySelector('.grid-3') || document.getElementById('contratsGrid');

        if (!grid) return; // Grid not found

        if (!json.success || !json.data?.length) {
            grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:40px;color:rgba(255,255,255,0.3);">
                <i class="bi bi-file-earmark-x" style="font-size:28px;display:block;margin-bottom:8px;"></i>
                Aucun contrat actif
            </div>`;
            return;
        }

        const contrats = json.data.slice(0, 3); // Max 3 on dashboard

        grid.innerHTML = contrats.map(c => {
            const t = getTypeInfo(c.type_contrat);
            return `
            <div class="contrat-card ${t.css}">
                <div class="contrat-type">
                    <div class="contrat-icon"><i class="bi ${t.icon}"></i></div>
                    <div>
                        <div class="contrat-name">${c.type_contrat ?? t.label}</div>
                        <div class="contrat-ref">${c.numero_contrat ?? '—'}</div>
                    </div>
                </div>
                <div class="contrat-info">
                    <div class="info-item">
                        <label>Prime</label>
                        <span>${c.prime_contrat ?? '—'} TND/mois</span>
                    </div>
                    <div class="info-item">
                        <label>Échéance</label>
                        <span>${formatDate(c.date_fin_contrat)}</span>
                    </div>
                </div>
                <div class="contrat-actions">
                    <a href="contratshow.php?id=${c.id_contrat}" class="btn btn-outline btn-sm">
                        <i class="bi bi-eye"></i> Détails
                    </a>
                    <a href="qrcode_contrat.php?id=${c.id_contrat}" class="btn btn-outline btn-sm">
                        <i class="bi bi-download"></i> PDF
                    </a>
                </div>
            </div>`;
        }).join('');

    } catch(e) {
        console.error('Erreur chargement contrats:', e);
    }
}

// ═══════════════════════════════════════════
//  SINISTRE EN COURS (card dashboard)
// ═══════════════════════════════════════════

const STATUT_SINISTRE = {
    'en_attente': { label: 'En traitement', css: 'badge-warning' },
    'en_cours':   { label: 'En traitement', css: 'badge-warning' },
    'valide':     { label: 'Validé',        css: 'badge-info' },
    'rembourse':  { label: 'Remboursé',     css: 'badge-success' },
    'refuse':     { label: 'Refusé',        css: 'badge-danger' },
};

async function loadSinistreCard() {
    const body = document.querySelector('.grid-2 > .card:first-child .card-body') || document.getElementById('sinistreCardBody');
    if (!body) return;

    try {
        const res  = await fetch('sinistre_list_user.php');
        const json = await res.json();

        if (!json.success || !json.data?.length) {
            body.innerHTML = `<div style="text-align:center;padding:30px;color:rgba(255,255,255,0.3);">
                <i class="bi bi-shield-check" style="font-size:28px;display:block;margin-bottom:8px;"></i>
                Aucun sinistre en cours
            </div>`;
            return;
        }

        // Get the most recent claim in progress
        const enCours = json.data.filter(s => ['en_attente','en_cours'].includes(s.statut));
        const s = enCours[0] ?? json.data[0];
        const st = STATUT_SINISTRE[s.statut] ?? { label: s.statut, css: 'badge-warning' };

        body.innerHTML = `
        <div class="sinistre-box">
            <div class="sinistre-header">
                <div class="sinistre-title">${s.type ?? '—'}</div>
                <span class="badge ${st.css}">${st.label}</span>
            </div>
            <div class="sinistre-meta">
                Déclaré le ${formatDate(s.date_declaration)} · ${s.id_sinistre ? 'SIN-'+String(s.id_sinistre).padStart(4,'0') : '—'}
            </div>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-dot green"></div>
                    <div class="timeline-content">
                        <div class="timeline-title">Déclaration reçue</div>
                        <div class="timeline-date">${formatDate(s.date_declaration)}</div>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot ${['en_cours','valide','rembourse'].includes(s.statut)?'blue':'gray'}"></div>
                    <div class="timeline-content">
                        <div class="timeline-title">En cours de traitement</div>
                        <div class="timeline-date">${['en_cours','valide','rembourse'].includes(s.statut)?'En cours':'En attente'}</div>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot ${s.statut==='rembourse'?'green':'gray'}"></div>
                    <div class="timeline-content">
                        <div class="timeline-title">Remboursement</div>
                        <div class="timeline-date">${s.statut==='rembourse'?(s.montant_indemnise??'')+'TND':'—'}</div>
                    </div>
                </div>
            </div>
        </div>`;
    } catch(e) {
        console.error('Erreur sinistre card:', e);
        body.innerHTML = `<div style="color:rgba(255,255,255,0.3);text-align:center;padding:20px;">Erreur de chargement</div>`;
    }
}

// ═══════════════════════════════════════════
//  PAIEMENTS DYNAMIQUES
// ═══════════════════════════════════════════

async function loadPaiements() {
    const body = document.querySelector('.grid-2 > .card:last-child .card-body') || document.getElementById('paiementsCardBody');
    if (!body) return;

    try {
        const res  = await fetch('contrat_list_client.php');
        const json = await res.json();

        if (!json.success || !json.data?.length) {
            body.innerHTML = `<div style="text-align:center;padding:20px;color:rgba(255,255,255,0.3);">Aucun paiement</div>`;
            return;
        }

        const today   = new Date();
        const month1  = today.toLocaleDateString('fr-FR', { day:'numeric', month:'long', year:'numeric' });

        body.innerHTML = json.data.slice(0, 4).map(c => {
            const t = getTypeInfo(c.type_contrat);
            const isPast = c.statut_contrat === 'actif';
            return `
            <div class="payment-item">
                <div class="payment-left">
                    <div class="payment-icon"><i class="bi ${t.icon}"></i></div>
                    <div>
                        <div class="payment-name">${c.type_contrat ?? 'Contrat'}</div>
                        <div class="payment-date">${month1}</div>
                    </div>
                </div>
                <div class="payment-right">
                    <div class="payment-amount">${parseFloat(c.prime_contrat ?? 0).toFixed(0)} TND</div>
                    <span class="badge ${isPast ? 'badge-success' : 'badge-warning'}" style="font-size:10px">
                        ${isPast ? 'Payé' : 'À venir'}
                    </span>
                </div>
            </div>`;
        }).join('');

    } catch(e) {
        console.error('Erreur paiements:', e);
        body.innerHTML = `<div style="color:rgba(255,255,255,0.3);padding:20px;">Erreur</div>`;
    }
}

// ═══════════════════════════════════════════
//  PANEL SINISTRES — CHARGER DONNÉES
// ═══════════════════════════════════════════

// Intercepter l'ouverture du panel sinistres pour charger les données
const originalOpenSinistresPanel = window.openSinistresPanel;
window.openSinistresPanel = async function(e) {
    if (e) e.preventDefault();
    document.getElementById('sinistresOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';

    // Loader state
    document.getElementById('spList').innerHTML =
        `<div style="text-align:center;padding:30px;color:rgba(255,255,255,0.3);">
            <i class="bi bi-hourglass-split" style="font-size:24px;display:block;margin-bottom:8px;"></i>
            Chargement...
        </div>`;

    try {
        const res  = await fetch('sinistre_list_user.php');
        const json = await res.json();

        if (json.success && json.data) {
            spSinistres = json.data.map(s => ({
                id:           s.id_sinistre,
                id_contrat:   s.id_contrat,
                contrat:      '#' + s.id_contrat,
                type:         s.type,
                description:  s.description,
                date:         s.date_declaration,
                statut:       s.statut,
                montant:      s.montant_indemnise ?? null,
                photo:        s.photo_url ?? '',
                commentaires: [],
                traitements:  (s.traitements ?? []).map((t, i) => ({
                    num:      'T' + (i + 1),
                    decision: t.message_agent ?? t.decision ?? 'Traitement',
                    date:     t.date_traitement ?? '',
                    montant:  t.montant_indemnise ?? null,
                })),
            }));
        }
    } catch(e) {
        console.error('Erreur panel sinistres:', e);
    }

    spRenderList();
};

// ═══════════════════════════════════════════
//  MISE À JOUR LOGOUT
// ═══════════════════════════════════════════

function fixLogoutLink() {
    const logoutLink = document.querySelector('.dropdown-item.logout');
    if (logoutLink) {
        logoutLink.href = 'logout.php';
    }
}

// ═══════════════════════════════════════════
//  MISE À JOUR LIENS client.html → client.php
// ═══════════════════════════════════════════

function fixNavigationLinks() {
    // Update navbar brand link
    const brandLink = document.querySelector('.navbar-brand');
    if (brandLink && brandLink.href.includes('client.html')) {
        brandLink.href = 'client.php';
    }

    // Update "Tableau de bord" nav link
    const dashboardLink = document.querySelector('.nav-link i.bi-grid-1x2')?.closest('.nav-link');
    if (dashboardLink && dashboardLink.href.includes('client.html')) {
        dashboardLink.href = 'client.php';
    }

    // Update breadcrumb link
    const breadcrumbLink = document.querySelector('.page-breadcrumb a[href*="client.html"]');
    if (breadcrumbLink) {
        breadcrumbLink.href = 'client.php';
    }

    // Update "Voir tout" buttons for contracts
    const allContractsBtn = document.querySelector('.section-header a[href*="mes-contrats"]');
    if (allContractsBtn && allContractsBtn.href.includes('.html')) {
        allContractsBtn.href = 'contrat.php'; // or contrats.php if that's the correct file
    }
}

// ═══════════════════════════════════════════
//  INIT — CHARGER TOUT AU DÉMARRAGE
// ═══════════════════════════════════════════

document.addEventListener('DOMContentLoaded', async () => {
    console.log('Dashboard initializing...');

    // Load profile
    await loadUserProfile();

    // Load stats
    await loadDashboardStats();

    // Load contracts
    await loadContrats();

    // Load current sinistre card
    await loadSinistreCard();

    // Load payments
    await loadPaiements();

    // Fix logout link
    fixLogoutLink();

    // Fix navigation links (client.html → client.php)
    fixNavigationLinks();

    console.log('Dashboard loaded successfully');
});
