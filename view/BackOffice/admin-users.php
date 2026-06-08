<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Gestion Utilisateurs — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/admin-users.css">
    <link rel="stylesheet" href="assets/css/validation.css">
    <link rel="stylesheet" href="assets/css/animations.css">
</head>
<body>

<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">

    <!-- ===== SIDEBAR ===== -->
    <?php require_once __DIR__.'/assets/includes/sidebar.php'; ?>

    <!-- ===== MAIN ===== -->
    <main class="main">

        <!-- TOPBAR -->
        <div class="topbar">
            <div>
                <div class="topbar-title">Gestion des utilisateurs</div>
                <div class="topbar-sub" id="topbarDate"></div>
            </div>
            <div class="topbar-actions">
                <a href="#" class="topbar-btn" title="Notifications">
                    <i class="bi bi-bell"></i>
                    <span class="notif-dot"></span>
                </a>
                <a href="#" class="topbar-btn" title="Aide">
                    <i class="bi bi-question-circle"></i>
                </a>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="content">

            <!-- Page header -->
            <div class="page-header-bar">
                <div>
                    <div class="page-title">Utilisateurs</div>
                    <div class="page-breadcrumb">
                        <i class="bi bi-house"></i>
                        <a href="admin.php">Accueil</a>
                        <i class="bi bi-chevron-right" style="font-size:10px"></i>
                        <span>Utilisateurs</span>
                    </div>
                </div>
                <button class="btn btn-primary" id="addUserBtn" onclick="resetForm(); openModal('modalAdd')" style="display:none;">
                    <i class="bi bi-person-plus"></i> Ajouter un utilisateur
                </button>
            </div>

            <!-- STATS -->
            <!-- Filtre période pour les stats -->
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px; flex-wrap:wrap;">
                <span style="font-size:12px; color:var(--text-secondary);"><i class="bi bi-calendar-range"></i> Période :</span>
                <button class="btn btn-outline btn-sm period-btn active" data-period="all" onclick="setPeriod(this,'all')">Tout</button>
                <button class="btn btn-outline btn-sm period-btn" data-period="7"   onclick="setPeriod(this,'7')">7 jours</button>
                <button class="btn btn-outline btn-sm period-btn" data-period="30"  onclick="setPeriod(this,'30')">30 jours</button>
                <button class="btn btn-outline btn-sm period-btn" data-period="90"  onclick="setPeriod(this,'90')">3 mois</button>
                <button class="btn btn-outline btn-sm period-btn" data-period="365" onclick="setPeriod(this,'365')">1 an</button>
                <span id="statsPeriodLabel" style="font-size:11px; color:var(--text-secondary); margin-left:4px;"></span>
            </div>
            <div class="stats-grid">

                <div class="stat-card blue">
                    <div class="stat-icon"><i class="bi bi-people"></i></div>
                    <div class="stat-value" id="totalUsers">0</div>
                    <div class="stat-label">Total utilisateurs</div>
                    <div class="stat-trend trend-up" id="trendNewMonth"><i class="bi bi-arrow-up"></i> <span id="newThisMonth">0</span> ce mois</div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon"><i class="bi bi-person-check"></i></div>
                    <div class="stat-value" id="actifs">0</div>
                    <div class="stat-label">Comptes actifs</div>
                    <div class="stat-trend trend-up"><i class="bi bi-check-circle"></i> <span id="tauxActifs">0</span>%</div>
                </div>

                <div class="stat-card gold">
                    <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
                    <div class="stat-value" id="agents">0</div>
                    <div class="stat-label">Agents</div>
                    <div class="stat-trend trend-up"><i class="bi bi-building"></i> 2 agences</div>
                </div>

                <div class="stat-card red">
                    <div class="stat-icon"><i class="bi bi-person-slash"></i></div>
                    <div class="stat-value" id="bloques">0</div>
                    <div class="stat-label">Comptes bloqués</div>
                    <div class="stat-trend trend-down"><i class="bi bi-exclamation-triangle"></i> À vérifier</div>
                </div>

            </div>

            <!-- TABLE CARD -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="bi bi-table"></i> Liste des utilisateurs
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <a href="import_users.php" class="btn btn-outline btn-sm" style="text-decoration: none;" title="Importer des utilisateurs depuis un fichier CSV">
                            <i class="bi bi-file-earmark-arrow-up"></i> Importer CSV
                        </a>
                        <button class="btn btn-outline btn-sm" onclick="exportExcel()" title="Exporter en CSV (compatible Excel)">
                            <i class="bi bi-file-earmark-excel"></i> Exporter
                        </button>
                    </div>
                </div>

                <!-- Toolbar -->
                <div style="padding: 16px 24px; border-bottom: 1px solid var(--glass-border);">
                    <div class="toolbar">
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="text" id="searchInput" placeholder="Rechercher par nom, email, CIN..." autocomplete="off">
                        </div>
                        <select class="filter-select" id="filterRole" autocomplete="off">
                            <option value="">Tous les rôles</option>
                            <option value="superadmin">Super Admin</option>
                            <option value="admin">Admin</option>
                            <option value="agent">Agent</option>
                            <option value="client">Client</option>
                        </select>
                        <select class="filter-select" id="filterAgence" autocomplete="off">
                            <option value="">Toutes les agences</option>
                        </select>
                        <select class="filter-select" id="filterStatut" autocomplete="off">
                            <option value="">Tous les statuts</option>
                            <option value="actif">Actif</option>
                            <option value="bloque">Bloqué</option>
                        </select>
                        <select class="filter-select" id="filterOrderBy" autocomplete="off">
                            <option value="date_desc">Date ↓</option>
                            <option value="date_asc">Date ↑</option>
                            <option value="nom_asc">Nom A→Z</option>
                            <option value="nom_desc">Nom Z→A</option>
                        </select>
                        <!-- Filtre période stats -->
                        <input type="date" class="filter-select" id="filterDateFrom" title="Inscrit depuis" autocomplete="off" style="cursor:pointer;">
                        <input type="date" class="filter-select" id="filterDateTo" title="Inscrit jusqu'à" autocomplete="off" style="cursor:pointer;">
                        <button class="btn btn-outline btn-sm" onclick="resetFilters()">
                            <i class="bi bi-x-circle"></i> Réinitialiser
                        </button>
                    </div>
                    <!-- Badges filtres actifs -->
                    <div id="activeBadges" style="display:none; margin-top:10px; display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
                        <span style="font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.6px;">Filtres actifs :</span>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-wrap">
                    <table id="usersTable">
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>CIN</th>
                                <th>Téléphone</th>
                                <th>Rôle</th>
                                <th>Info</th>
                                <th>Statut</th>
                                <th>Inscrit le</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersBody">
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination">
                    <div class="pagination-info" id="paginationInfo"></div>
                    <div class="pagination-btns" id="paginationBtns"></div>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- ===== MODAL AJOUTER / MODIFIER ===== -->
<div class="modal-overlay" id="modalAdd">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modalAddTitle">
                <i class="bi bi-person-plus"></i> Ajouter un utilisateur
            </div>
            <button class="modal-close" onclick="closeModal('modalAdd')">
                <i class="bi bi-x"></i>
            </button>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Nom *</label>
                <input type="text" class="form-control" id="fNom" placeholder="Ben Ali" data-rule="nom" autocomplete="off">
            </div>
            <div class="form-group">
                <label>Prénom *</label>
                <input type="text" class="form-control" id="fPrenom" placeholder="Ahmed" data-rule="prenom" autocomplete="off">
            </div>
        </div>

        <div class="form-group">
            <label>Email *</label>
            <input type="email" class="form-control" id="fEmail" placeholder="ahmed@example.com" data-rule="email" autocomplete="off">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Téléphone</label>
                <input type="tel" class="form-control" id="fTel" placeholder="+216 XX XXX XXX" data-rule="telephone" autocomplete="off">
            </div>
            <div class="form-group">
                <label>CIN</label>
                <input type="text" class="form-control" id="fCin" placeholder="12345678" maxlength="8" data-rule="cin" autocomplete="off">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Rôle *</label>
                <select class="form-control" id="fRole" autocomplete="off">
                    <option value="client">Client</option>
                    <option value="agent">Agent</option>
                    <option value="admin">Admin</option>
                    <!-- FIX 12 : option superadmin injectée par JS si role === superadmin -->
                </select>
            </div>
            <div class="form-group">
                <label>Statut</label>
                <select class="form-control" id="fStatut" autocomplete="off">
                    <option value="actif">Actif</option>
                    <option value="bloque">Bloqué</option>
                </select>
            </div>
        </div>

        <div class="form-group" id="pwdGroup">
            <label>Mot de passe *</label>
            <input type="password" class="form-control" id="fPassword" placeholder="Min. 8 caractères" data-rule="password" autocomplete="new-password">
            <div id="pwdStrengthBar" style="margin-top:6px;height:4px;border-radius:4px;background:var(--glass-border);overflow:hidden;display:none;">
                <div id="pwdStrengthFill" style="height:100%;width:0%;transition:width .3s,background .3s;border-radius:4px;"></div>
            </div>
            <div id="pwdStrengthLabel" style="font-size:11px;margin-top:3px;color:var(--text-secondary);display:none;"></div>
        </div>

        <div id="roleFieldsAdmin" style="display:none">
            <div class="form-group">
                <label>Niveau d'accès</label>
                <input type="number" class="form-control" id="fNiveau" min="1" max="5" value="1" placeholder="1" autocomplete="off">
            </div>
        </div>

        <div id="roleFieldAgence" style="display:none">
            <div class="form-group">
                <label>Agence</label>
                <select class="form-control" id="fAgence" autocomplete="off">
                    <option value="">— Choisir une agence —</option>
                </select>
            </div>
        </div>

        <div id="roleFieldsAgent" style="display:none">
            <div class="form-group">
                <label>Salaire (TND)</label>
                <input type="number" class="form-control" id="fSalaire" min="0" step="0.01" placeholder="1500.00" autocomplete="off">
            </div>
        </div>

        <div id="roleFieldsClient" style="display:none">
            <div class="form-group">
                <label>Numéro client</label>
                <input type="text" class="form-control" id="fNumClient" placeholder="Généré automatiquement" disabled>
                <small style="color:var(--text-secondary);font-size:11px;">Généré automatiquement</small>
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('modalAdd')">Annuler</button>
            <button class="btn btn-primary" id="btnSaveUser" onclick="saveUser()">
                <i class="bi bi-save"></i> Enregistrer
            </button>
        </div>
    </div>
</div>

<!-- ===== MODAL VOIR DÉTAIL ===== -->
<div class="modal-overlay" id="modalView">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="bi bi-person-circle"></i> Fiche utilisateur</div>
            <button class="modal-close" onclick="closeModal('modalView')"><i class="bi bi-x"></i></button>
        </div>
        <div id="modalViewBody"></div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('modalView')">Fermer</button>
            <button class="btn btn-primary" id="btnEditFromView" onclick="editFromView()">
                <i class="bi bi-pencil"></i> Modifier
            </button>
        </div>
    </div>
</div>

<!-- ===== MODAL SUPPRIMER ===== -->
<div class="modal-overlay delete-modal" id="modalDelete">
    <div class="modal" style="text-align:center">
        <div class="delete-icon"><i class="bi bi-trash3"></i></div>
        <div class="delete-title">Supprimer l'utilisateur</div>
        <div class="delete-msg" id="deleteMsg"></div>
        <div class="modal-footer" style="justify-content:center; margin-top:28px">
            <button class="btn btn-outline" onclick="closeModal('modalDelete')">Annuler</button>
            <button class="btn btn-danger" id="btnConfirmDelete" onclick="confirmDelete()">
                <i class="bi bi-trash3"></i> Supprimer définitivement
            </button>
        </div>
    </div>
</div>

<script>
let users = [];
let currentPage = 1;
const perPage = 10;
let editingId = null;
let deletingId = null;
let viewingId = null;
let totalUsers = 0;
let totalPages = 1;
let orderTimeout = null;
let searchTimeout = null;
let currentPeriodDays = null; // null = tout

// ===== DATE TOPBAR =====
const now = new Date();
document.getElementById('topbarDate').textContent =
    now.toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long', year:'numeric' });

// ============================================================
// FILTRE PÉRIODE STATS
// ============================================================
function setPeriod(btn, period) {
    document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentPeriodDays = period === 'all' ? null : parseInt(period);
    const label = document.getElementById('statsPeriodLabel');
    label.textContent = period === 'all' ? '' : `(${btn.textContent.trim()})`;
    loadStats();
}

// ============================================================
// BADGES FILTRES ACTIFS
// ============================================================
function updateBadges() {
    const container = document.getElementById('activeBadges');
    const search    = document.getElementById('searchInput').value.trim();
    const role      = document.getElementById('filterRole').value;
    const agence    = document.getElementById('filterAgence').value;
    const agenceText = document.getElementById('filterAgence').options[document.getElementById('filterAgence').selectedIndex]?.text || '';
    const statut    = document.getElementById('filterStatut').value;
    const dateFrom  = document.getElementById('filterDateFrom').value;
    const dateTo    = document.getElementById('filterDateTo').value;

    const badges = [];
    if (search)   badges.push({ label: `Recherche : "${search}"`,  field: 'searchInput',    clear: () => { document.getElementById('searchInput').value = ''; } });
    if (role)     badges.push({ label: `Rôle : ${role}`,           field: 'filterRole',     clear: () => { document.getElementById('filterRole').value = ''; } });
    if (agence)   badges.push({ label: `Agence : ${agenceText}`,   field: 'filterAgence',   clear: () => { document.getElementById('filterAgence').value = ''; } });
    if (statut)   badges.push({ label: `Statut : ${statut}`,       field: 'filterStatut',   clear: () => { document.getElementById('filterStatut').value = ''; } });
    if (dateFrom) badges.push({ label: `Depuis : ${dateFrom}`,     field: 'filterDateFrom', clear: () => { document.getElementById('filterDateFrom').value = ''; } });
    if (dateTo)   badges.push({ label: `Jusqu'à : ${dateTo}`,    field: 'filterDateTo',   clear: () => { document.getElementById('filterDateTo').value = ''; } });

    if (badges.length === 0) {
        container.style.display = 'none';
        return;
    }
    container.style.display = 'flex';
    // Keep the label span, rebuild the rest
    const labelSpan = container.querySelector('span');
    container.innerHTML = '';
    container.appendChild(labelSpan);

    badges.forEach(b => {
        const badge = document.createElement('span');
        badge.className = 'active-filter-badge';
        badge.innerHTML = `${b.label} <button onclick="removeBadge('${b.field}')" title="Supprimer ce filtre">&times;</button>`;
        container.appendChild(badge);
    });
}

function removeBadge(fieldId) {
    document.getElementById(fieldId).value = '';
    currentPage = 1;
    updateBadges();
    loadUsers();
}

// ============================================================
// CHARGEMENT SERVEUR (recherche avancée)
// ============================================================
function loadUsers() {
    const search   = document.getElementById('searchInput').value.trim();
    const role     = document.getElementById('filterRole').value;
    const agence   = document.getElementById('filterAgence').value;
    const statut   = document.getElementById('filterStatut').value;
    const orderBy  = document.getElementById('filterOrderBy').value;
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo   = document.getElementById('filterDateTo').value;

    updateBadges();

    const params = new URLSearchParams({
        page: currentPage,
        per_page: perPage,
        order_by: orderBy,
    });
    if (search)   params.set('keyword',   search);
    if (role)     params.set('role',      role);
    if (agence)   params.set('agence',    agence);
    if (statut)   params.set('statut',    statut);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo)   params.set('date_to',   dateTo);

    const tbody = document.getElementById('usersBody');
    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:40px;"><div style="margin-top:12px;color:var(--text-secondary);font-size:13px;">Chargement...</div></td></tr>`;

    fetch(`../../api.php?action=search_users&${params.toString()}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                users = data.data.map(u => ({
                    id:            u.id_user,
                    nom:           u.nom,
                    prenom:        u.prenom,
                    email:         u.email,
                    tel:           u.telephone    || '—',
                    cin:           u.cin          || '—',
                    role:          (u.role || 'client').toLowerCase(),
                    statut:        (u.statut || 'actif').toLowerCase(),
                    created:       u.date_creation || '—',
                    avatar:        u.avatar       || null,
                    niveau_acces:  u.niveau_acces  ?? null,
                    agence:        u.agence        || null,
                    salaire:       u.salaire       || null,
                    numero_client: u.numero_client || null
                }));
                totalUsers = data.total;
                totalPages = data.total_pages || 1;
                if (currentPage > totalPages) currentPage = totalPages;
                render();
                loadStats();
            } else {
                tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-secondary)"><i class="bi bi-exclamation-triangle" style="font-size:24px;display:block;margin-bottom:8px;color:var(--danger)"></i>${data.message || 'Erreur'}</td></tr>`;
            }
        })
        .catch(err => {
            console.error('Erreur réseau:', err);
            tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-secondary)"><i class="bi bi-wifi-off" style="font-size:24px;display:block;margin-bottom:8px"></i>Erreur de connexion au serveur PHP</td></tr>`;
        });
}

// ===== FILTRAGE SERVEUR =====
function getFiltered() { return users; }

function resetFilters() {
    document.getElementById('searchInput').value   = '';
    document.getElementById('filterRole').value    = '';
    document.getElementById('filterAgence').value  = '';
    document.getElementById('filterStatut').value  = '';
    document.getElementById('filterOrderBy').value = 'date_desc';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value   = '';
    currentPage = 1;
    updateBadges();
    loadUsers();
}

// ============================================================
// EXPORT EXCEL (CSV)
// ============================================================
function exportExcel() {
    const search   = document.getElementById('searchInput').value.trim();
    const role     = document.getElementById('filterRole').value;
    const statut   = document.getElementById('filterStatut').value;
    const orderBy  = document.getElementById('filterOrderBy').value;
    
    const params = new URLSearchParams();
    if (search)  params.set('search', search);
    if (role)    params.set('role', role);
    if (statut)  params.set('statut', statut);
    if (orderBy) params.set('order_by', orderBy);

    // Ouvre le fichier dans un nouvel onglet pour lancer le téléchargement
    window.location.href = `export_users.php?${params.toString()}`;
}

// ============================================================
// RENDER TABLE
// ============================================================
function initials(u) { return (u.prenom[0] + u.nom[0]).toUpperCase(); }

function getAvatarCell(u) {
    const av = u.avatar;
    const skip = !av || av === 'default.png' || av === 'default' || av.trim() === '';
    if (!skip) {
        const avatarPath = av.includes('/') ? av : '../../uploads/avatars/' + av;
        return `<img src="${avatarPath}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"><div class="user-avatar-sm" style="display:none;">${initials(u)}</div>`;
    }
    return `<div class="user-avatar-sm">${initials(u)}</div>`;
}

function roleInfo(u) {
    if (u.role === 'admin') {
        let res = u.niveau_acces  !== null ? `<i class="bi bi-shield-check"></i> Niveau ${u.niveau_acces}` : '—';
        if (currentUserRole === 'superadmin' && u.agence) {
            res += ` <br><small style="color:var(--accent)"><i class="bi bi-building"></i> ${u.agence}</small>`;
        }
        return res;
    }
    if (u.role === 'agent') {
        let res = (currentUserRole === 'superadmin' && u.agence) ? `<i class="bi bi-building"></i> ${u.agence}` : '';
        if (u.salaire) {
            if (res) res += ' · ';
            res += Number(u.salaire).toLocaleString('fr-FR')+' TND';
        }
        return res || '—';
    }
    if (u.role === 'client') {
        let res = u.numero_client ? `<i class="bi bi-person-badge"></i> ${u.numero_client}` : '—';
        if (currentUserRole === 'superadmin' && u.agence) {
            res += ` <br><small style="color:var(--accent)"><i class="bi bi-building"></i> ${u.agence}</small>`;
        }
        return res;
    }
    return '—';
}

function render() {
    const tbody = document.getElementById('usersBody');
    tbody.innerHTML = users.length === 0
        ? `<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-secondary)"><i class="bi bi-inbox" style="font-size:24px;display:block;margin-bottom:8px"></i>Aucun utilisateur trouvé</td></tr>`
        : users.map(u => `
        <tr>
            <td>
                <div class="user-cell">
                    ${getAvatarCell(u)}
                    <div>
                        <div class="user-name-cell">${u.prenom} ${u.nom}</div>
                        <div class="user-email-cell">${u.email}</div>
                    </div>
                </div>
            </td>
            <td style="color:var(--text-secondary)">${u.cin}</td>
            <td style="color:var(--text-secondary)">${u.tel}</td>
            <td><span class="badge badge-${u.role.toLowerCase()}">${u.role.toLowerCase() === 'admin' ? 'Administrateur' : u.role.charAt(0).toUpperCase() + u.role.slice(1)}</span></td>
            <td style="color:var(--text-secondary);font-size:0.85rem">${roleInfo(u)}</td>
            <td><span class="badge badge-${u.statut}">
                <i class="bi bi-${u.statut === 'actif' ? 'check-circle' : 'slash-circle'}"></i>
                ${u.statut.charAt(0).toUpperCase() + u.statut.slice(1)}
            </span></td>
            <td style="color:var(--text-secondary)">${formatDate(u.created)}</td>
            <td>
                <div class="actions">
                    <button class="btn btn-outline btn-sm" title="Voir" onclick='viewUser(${JSON.stringify(String(u.id))})'>
                        <i class="bi bi-eye"></i>
                    </button>
                    ${(currentUserRole === 'superadmin' || currentUserRole === 'admin' || (currentUserRole === 'agent' && u.role === 'client')) ? `
                    <button class="btn btn-outline btn-sm" title="Modifier" onclick='editUser(${JSON.stringify(String(u.id))})'>
                        <i class="bi bi-pencil"></i>
                    </button>
                    ` : ''}
                    ${(currentUserRole === 'superadmin' || (currentUserRole === 'admin' && u.role !== 'admin' && u.role !== 'superadmin')) ? `
                    <div class="toggle-switch-wrapper" title="${u.statut === 'actif' ? 'Bloquer' : 'Débloquer'}">
                        <input type="checkbox" id="toggle-${u.id}" class="toggle-switch-input" 
                            ${u.statut === 'actif' ? 'checked' : ''} 
                            onchange='toggleStatut(${JSON.stringify(String(u.id))})'>
                        <label for="toggle-${u.id}" class="toggle-switch-label"></label>
                    </div>
                    ` : ''}
                    ${(currentUserRole === 'superadmin' || (currentUserRole === 'admin' && u.role !== 'admin')) ? `
                    <button class="btn btn-danger btn-sm" title="Supprimer" onclick='deleteUser(${JSON.stringify(String(u.id))})'>
                        <i class="bi bi-trash3"></i>
                    </button>
                    ` : ''}
                </div>
            </td>
        </tr>`).join('');

    // Pagination info
    const total = totalUsers;
    const start = total === 0 ? 0 : (currentPage-1)*perPage + 1;
    const end = Math.min(currentPage*perPage, total);
    document.getElementById('paginationInfo').textContent =
        `Affichage ${start}–${end} sur ${total} utilisateur${total > 1 ? 's' : ''}`;

    // Pagination boutons
    const pages = Math.min(totalPages, 7);
    let startPage = Math.max(1, currentPage - 3);
    let endPage = Math.min(totalPages, startPage + pages - 1);
    if (endPage - startPage < pages - 1) startPage = Math.max(1, endPage - pages + 1);

    const btns = document.getElementById('paginationBtns');
    btns.innerHTML = `
        <button class="page-btn" onclick="goPage(${currentPage-1})" ${currentPage<=1?'disabled':''}>
            <i class="bi bi-chevron-left"></i>
        </button>
        ${Array.from({length: endPage - startPage + 1}, (_, i) => {
            const p = startPage + i;
            return `<button class="page-btn ${p===currentPage?'active':''}" onclick="goPage(${p})">${p}</button>`;
        }).join('')}
        <button class="page-btn" onclick="goPage(${currentPage+1})" ${currentPage>=totalPages?'disabled':''}>
            <i class="bi bi-chevron-right"></i>
        </button>`;
}

function goPage(p) { if (p >= 1 && p <= totalPages) { currentPage = p; loadUsers(); } }

function formatDate(d) {
    if (!d || d === '—') return '—';
    return new Date(d).toLocaleDateString('fr-FR', { day:'2-digit', month:'short', year:'numeric' });
}

// ============================================================
// MODAL HELPERS
// ============================================================
function openModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('active', 'open');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('active', 'open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') { ['modalAdd','modalView','modalDelete'].forEach(closeModal); } });
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) closeModal(overlay.id);
    });
});

// ============================================================
// FORM RESET
// ============================================================
function resetForm() {
    ['fNom','fPrenom','fEmail','fTel','fCin','fPassword','fNiveau','fAgence','fSalaire','fNumClient'].forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.value = ''; el.classList.remove('input-error','input-valid'); }
        const err = el && el.parentNode.querySelector('.field-error');
        if (err) err.remove();
    });
    document.getElementById('fRole').value   = 'client';
    document.getElementById('fStatut').value = 'actif';
    document.getElementById('pwdGroup').style.display = '';
    document.getElementById('modalAddTitle').innerHTML = '<i class="bi bi-person-plus"></i> Ajouter un utilisateur';
    document.getElementById('btnSaveUser').innerHTML = '<i class="bi bi-save"></i> Enregistrer';
    editingId = null;
    toggleRoleFields('client');
}
// ============================================================
const adminRules = {
    nom: {
        validate(v) {
            if (!v) return 'Le nom est obligatoire';
            if (v.length < 2) return 'Le nom doit contenir au moins 2 lettres';
            if (v.length > 50) return 'Le nom ne doit pas dépasser 50 caractères';
            if (/[0-9]/.test(v)) return 'Le nom ne doit pas contenir de chiffres';
            if (!/^[a-zA-ZÀ-ÿ\s'\-]+$/.test(v)) return 'Le nom ne doit contenir que des lettres';
            return null;
        }
    },
    prenom: {
        validate(v) {
            if (!v) return 'Le prénom est obligatoire';
            if (v.length < 2) return 'Le prénom doit contenir au moins 2 lettres';
            if (v.length > 50) return 'Le prénom ne doit pas dépasser 50 caractères';
            if (/[0-9]/.test(v)) return 'Le prénom ne doit pas contenir de chiffres';
            if (!/^[a-zA-ZÀ-ÿ\s'\-]+$/.test(v)) return 'Le prénom ne doit contenir que des lettres';
            return null;
        }
    },
    email: {
        validate(v) {
            if (!v) return "L'email est obligatoire";
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) return 'Email invalide';
            return null;
        }
    },
    telephone: {
        validate(v) {
            if (!v) return null;
            const clean = v.replace(/\s/g, '');
            if (!/^(\+216)?[2-9]\d{7}$/.test(clean)) return 'Numéro tunisien invalide';
            return null;
        }
    },
    cin: {
        validate(v) {
            if (!v) return null;
            if (!/^\d{8}$/.test(v)) return 'Le CIN doit contenir 8 chiffres';
            return null;
        }
    },
    password: {
        validate(v) {
            if (!v) return 'Le mot de passe est obligatoire';
            if (v.length < 8) return 'Minimum 8 caractères';
            if (!/[A-Z]/.test(v)) return 'Au moins 1 majuscule';
            if (!/[a-z]/.test(v)) return 'Au moins 1 minuscule';
            if (!/[0-9]/.test(v)) return 'Au moins 1 chiffre';
            if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(v)) return 'Au moins 1 caractère spécial';
            return null;
        }
    }
};

function adminShowError(input, message) {
    adminClearError(input);
    if (!input) return;
    input.classList.add('input-error');
    const err = document.createElement('span');
    err.className = 'field-error';
    err.textContent = message;
    if (input.nextSibling) {
        input.parentNode.insertBefore(err, input.nextSibling);
    } else {
        input.parentNode.appendChild(err);
    }
}

function adminClearError(input) {
    if (!input) return;
    input.classList.remove('input-error', 'input-valid');
    const existing = input.parentNode.querySelector('.field-error');
    if (existing) existing.remove();
}

function adminMarkValid(input) {
    if (!input) return;
    adminClearError(input);
    input.classList.add('input-valid');
}

function adminValidateField(input, ruleName) {
    const rule = adminRules[ruleName];
    if (!rule) return true;
    const err = rule.validate(input.value.trim());
    if (err) { adminShowError(input, err); return false; }
    adminMarkValid(input);
    return true;
}

function validate() {
    let ok = true;
    const nom = document.getElementById('fNom');
    const prenom = document.getElementById('fPrenom');
    const email = document.getElementById('fEmail');
    const tel = document.getElementById('fTel');
    const cin = document.getElementById('fCin');
    const pwd = document.getElementById('fPassword');
    const role = document.getElementById('fRole').value;
    const agence = document.getElementById('fAgence');
    const salaire = document.getElementById('fSalaire');

    if (!adminValidateField(nom, 'nom')) ok = false;
    if (!adminValidateField(prenom, 'prenom')) ok = false;
    if (!adminValidateField(email, 'email')) ok = false;
    if (tel && tel.value && !adminValidateField(tel, 'telephone')) ok = false;
    if (cin && cin.value && !adminValidateField(cin, 'cin')) ok = false;

    if (!editingId && pwd) {
        if (!adminValidateField(pwd, 'password')) ok = false;
    }

    // Role specific
    if (['admin', 'agent', 'client'].includes(role)) {
        if (!agence.value) {
            adminShowError(agence, 'Veuillez choisir une agence');
            ok = false;
        } else {
            adminMarkValid(agence);
        }
    }

    if (role === 'agent') {
        if (!salaire.value || parseFloat(salaire.value) <= 0) {
            adminShowError(salaire, 'Le salaire doit être un nombre positif');
            ok = false;
        } else {
            adminMarkValid(salaire);
        }
    }

    return ok;
}

// ===== RIPPLE EFFECT =====
document.addEventListener('click', function(e) {
    const target = e.target.closest('.btn');
    if (!target) return;
    const rect = target.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    const ripple = document.createElement('span');
    ripple.style.cssText = `
        position: absolute;
        background: rgba(255,255,255,0.3);
        border-radius: 50%;
        width: 100px;
        height: 100px;
        left: ${x - 50}px;
        top: ${y - 50}px;
        transform: scale(0);
        animation: ripple 0.6s ease-out;
        pointer-events: none;
    `;
    target.appendChild(ripple);
    setTimeout(() => ripple.remove(), 600);
});

// ===== EDIT =====
function editUser(id) {
    const u = users.find(u => String(u.id) === String(id));
    if (!u) return;
    editingId = id;
    document.getElementById('fNom').value    = u.nom;
    document.getElementById('fPrenom').value = u.prenom;
    document.getElementById('fEmail').value  = u.email;
    document.getElementById('fTel').value    = u.tel !== '—' ? u.tel : '';
    document.getElementById('fCin').value    = u.cin !== '—' ? u.cin : '';
    document.getElementById('fRole').value   = u.role;
    document.getElementById('fStatut').value = u.statut;
    toggleRoleFields(u.role);
    if (u.role === 'admin') {
        document.getElementById('fNiveau').value = u.niveau_acces ?? 1;
        document.getElementById('fNiveau').disabled = false;
    }
    if (u.role === 'admin' || u.role === 'agent' || u.role === 'client') {
        loadAgences();
        // Wait for options to load then set value
        setTimeout(() => { 
            const agId = u.admin_id_agence || u.agent_id_agence || u.client_id_agence || u.id_agence || '';
            document.getElementById('fAgence').value = agId; 
        }, 300);
        
        if (u.role === 'agent') {
            document.getElementById('fSalaire').value = u.salaire || '';
            document.getElementById('fSalaire').disabled = false;
        }
        document.getElementById('fAgence').disabled = false;
    }
    if (u.role === 'client') {
        document.getElementById('fNumClient').value = u.numero_client || '';
        document.getElementById('fNumClient').disabled = true;
    }
    document.getElementById('pwdGroup').style.display = 'none';
    document.getElementById('modalAddTitle').innerHTML = '<i class="bi bi-pencil"></i> Modifier lutilisateur';
    document.getElementById('btnSaveUser').innerHTML = '<i class="bi bi-save"></i> Mettre à jour';
    openModal('modalAdd');
}

function editFromView() {
    closeModal('modalView');
    editUser(viewingId);
}

// ===== VIEW =====
function viewUser(id) {
    viewingId = id;
    const modalBody = document.getElementById('modalViewBody');
    modalBody.innerHTML = '<div style="text-align:center; padding:40px;"><div class="spinner"></div></div>';
    openModal('modalView');

    fetch(`get_user_detail.php?id=${id}`)
    .then(r => r.json())
    .then(data => {
        if (!data.success) { modalBody.innerHTML = 'Erreur lors du chargement'; return; }
        const u = data.user;
        const initiales = ((u.nom||'').charAt(0) + (u.prenom||'').charAt(0)).toUpperCase();
        const av = u.avatar;
        const avatarPath = av && av !== 'default.png' ? (av.includes('/') ? av : '../../uploads/avatars/' + av) : '';
        
        let kpisHtml = '';
        if (u.role.toLowerCase() === 'client' && data.kpis) {
            const k = data.kpis;
            const lastLoginStr = k.last_login ? new Date(k.last_login).toLocaleString('fr-FR') : 'Jamais';
            
            kpisHtml = `
                <div style="margin-top:20px; border-top:1px solid var(--glass-border); padding-top:20px;">
                    <div style="font-size:11px; text-transform:uppercase; color:var(--text-secondary); margin-bottom:12px; letter-spacing:.8px;">
                        <i class="bi bi-shield-check" style="margin-right:5px;"></i>Indicateurs Client
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:15px;">
                        ${field('bi-file-earmark-check', 'Contrats Actifs', k.nb_contrats_actifs)}
                        ${field('bi-exclamation-octagon', 'Sinistres Déclarés', k.nb_sinistres_declares)}
                        ${field('bi-cash-coin', 'Montant Payé', Number(k.montant_total_paye).toLocaleString('fr-FR') + ' TND')}
                        ${field('bi-star-fill', 'Score Fidélité', k.score_fidelite + ' pts')}
                        ${field('bi-shield-slash', 'Score Fraude Moyen', k.score_fraude_moyen + '%')}
                        ${field('bi-calendar-check', 'Dernière Connexion', lastLoginStr)}
                    </div>
                    <div style="display:flex; gap:10px; margin-top:15px;">
                        <a href="contrats_back.php?search=${encodeURIComponent(u.email)}" class="btn btn-outline btn-sm" style="flex:1; justify-content:center; text-decoration:none;">
                            <i class="bi bi-file-earmark-check"></i> Voir les contrats
                        </a>
                        <a href="sinsiter.php?search=${encodeURIComponent(u.email)}" class="btn btn-outline btn-sm" style="flex:1; justify-content:center; text-decoration:none;">
                            <i class="bi bi-shield-exclamation"></i> Voir les sinistres
                        </a>
                    </div>
                </div>
            `;
        }

        modalBody.innerHTML = `
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--glass-border);">
                ${avatarPath ? `<img src="${avatarPath}" style="width:64px;height:64px;border-radius:50%;object-fit:cover;">` : `<div class="user-avatar-sm" style="width:64px;height:64px;font-size:20px">${initiales}</div>`}
                <div>
                    <div style="font-family:var(--font-display);font-size:18px;font-weight:700;color:#fff">${u.prenom} ${u.nom}</div>
                    <div style="font-size:12px;color:var(--text-secondary);">${u.email}</div>
                    <div style="margin-top:8px;display:flex;gap:8px">
                        <span class="badge badge-${u.role.toLowerCase()}">${u.role === 'admin' ? 'Administrateur' : u.role.charAt(0).toUpperCase() + u.role.slice(1)}</span>
                        <span class="badge badge-${u.statut}">${u.statut.charAt(0).toUpperCase() + u.statut.slice(1)}</span>
                    </div>
                </div>
            </div>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
                ${field('bi-people','Réseau', data.nb_friends + ' contacts')}
                ${field('bi-exclamation-triangle','Alertes SOS', data.nb_sos + ' alertes')}
                ${field('bi-star','Points Parrainage', (u.points_parrainage || 0) + ' pts')}
                ${field('bi-calendar-check','Inscrit le', formatDate(u.date_creation))}
            </div>

            ${kpisHtml}

            <div style="margin-top:20px;">
                <div style="font-size:11px; text-transform:uppercase; color:var(--text-secondary); margin-bottom:10px;">Dernières connexions</div>
                ${data.logins.length ? data.logins.map(l => `
                    <div style="display:flex; justify-content:space-between; font-size:12px; padding:6px 0; border-bottom:1px solid rgba(255,255,255,0.03);">
                        <span style="color:rgba(255,255,255,0.6)">${l.ip}</span>
                        <span style="color:${l.statut==='success'?'#2ed573':'#ff4757'}">${l.statut.charAt(0).toUpperCase() + l.statut.slice(1)}</span>
                        <span style="color:rgba(255,255,255,0.3)">${new Date(l.created_at).toLocaleString('fr-FR')}</span>
                    </div>
                `).join('') : '<div style="font-size:12px; color:rgba(255,255,255,0.2);">Aucun historique</div>'}
            </div>
        `;
    });
}
function field(icon, label, val) {
    return `<div style="background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:var(--radius-md);padding:14px">
        <div style="font-size:10px;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px"><i class="bi ${icon}" style="margin-right:5px"></i>${label}</div>
        <div style="font-size:14px;color:#fff;font-weight:500">${val}</div>
    </div>`;
}

// ===== TOAST =====
function showToast(message, type = 'success') {
    const icons = { success:'check-circle', warning:'exclamation-triangle', danger:'x-circle' };
    const t = document.createElement('div');
    t.className = `toast-notif toast-${type}`;
    t.innerHTML = `<i class="bi bi-${icons[type]}"></i><span>${message}</span>`;
    document.body.appendChild(t);
    setTimeout(() => t.classList.add('show'), 50);
    setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 3000);
}

// ============================================================
// SEARCH & FILTER — avec délai automatique 350ms
// ============================================================
document.getElementById('searchInput').addEventListener('input', () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => { currentPage = 1; loadUsers(); }, 350);
});
document.getElementById('filterRole').addEventListener('change',      () => { currentPage = 1; loadUsers(); });
document.getElementById('filterAgence').addEventListener('change',    () => { currentPage = 1; loadUsers(); });
document.getElementById('filterStatut').addEventListener('change',    () => { currentPage = 1; loadUsers(); });
document.getElementById('filterOrderBy').addEventListener('change',   () => { currentPage = 1; loadUsers(); });
document.getElementById('filterDateFrom').addEventListener('change',  () => { currentPage = 1; loadUsers(); });
document.getElementById('filterDateTo').addEventListener('change',    () => { currentPage = 1; loadUsers(); });

// ===== CHAMPS DYNAMIQUES PAR RÔLE =====
function toggleRoleFields(role) {
    const isSuper = currentUserRole === 'superadmin';
    const isAgent = currentUserRole === 'agent';

    document.getElementById('roleFieldsAdmin').style.display  = role === 'admin'  ? '' : 'none';
    document.getElementById('roleFieldAgence').style.display  = (role === 'admin' || role === 'agent' || role === 'client') ? '' : 'none';
    document.getElementById('roleFieldsAgent').style.display  = role === 'agent'  ? '' : 'none';
    document.getElementById('roleFieldsClient').style.display = role === 'client' ? '' : 'none';
    
    document.getElementById('fNiveau').disabled = role !== 'admin';
    document.getElementById('fAgence').disabled = !(role === 'admin' || role === 'agent' || role === 'client');
    document.getElementById('fSalaire').disabled = role !== 'agent';
    document.getElementById('fNumClient').disabled = true;
    
    const fStatut = document.getElementById('fStatut');
    if (fStatut) {
        // Un agent ne peut pas modifier le statut (bloqué/actif)
        fStatut.closest('.form-group').style.display = isAgent ? 'none' : '';
    }

    if (role === 'admin' || role === 'agent' || role === 'client') {
        loadAgences();
        // Si l'utilisateur courant est restreint à une seule agence, on la force
        if (!isSuper && currentUserAgenceId) {
            setTimeout(() => {
                const sel = document.getElementById('fAgence');
                if (sel) {
                    sel.value = currentUserAgenceId;
                    sel.disabled = true;
                }
            }, 500);
        } else {
            document.getElementById('fAgence').disabled = false;
        }
    }
}

let currentUserRole = null;
let currentUserAgenceId = null;

document.getElementById('fRole').addEventListener('change', function() {
    toggleRoleFields(this.value);
});

// ===== INDICATEUR DE FORCE DU MOT DE PASSE =====
document.getElementById('fPassword').addEventListener('input', function() {
    const val = this.value;
    const bar = document.getElementById('pwdStrengthBar');
    const fill = document.getElementById('pwdStrengthFill');
    const label = document.getElementById('pwdStrengthLabel');
    if (!val) { bar.style.display = 'none'; label.style.display = 'none'; return; }
    bar.style.display = ''; label.style.display = '';
    let score = 0;
    if (val.length >= 8)           score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[\W_]/.test(val))         score++;
    if (val.length >= 12)          score++;
    const levels = [
        { pct: '20%', color: '#e74c3c', txt: 'Très faible' },
        { pct: '40%', color: '#e67e22', txt: 'Faible' },
        { pct: '60%', color: '#f1c40f', txt: 'Moyen' },
        { pct: '80%', color: '#2ecc71', txt: 'Fort' },
        { pct: '100%',color: '#27ae60', txt: 'Très fort' },
    ];
    const lvl = levels[Math.min(score, 4)];
    fill.style.width = lvl.pct;
    fill.style.background = lvl.color;
    label.style.color = lvl.color;
    label.textContent = lvl.txt;
});

function loadFilterAgences() {
    const sel = document.getElementById('filterAgence');
    if (!sel) return;
    fetch('get_agences.php')
        .then(res => res.json())
        .then(data => {
            if (!data.success || !Array.isArray(data.data)) return;
            sel.innerHTML = '<option value="">Toutes les agences</option>';
            data.data.forEach(ag => {
                const opt = document.createElement('option');
                opt.value = ag.id_agence;
                opt.textContent = ag.nom_agence;
                sel.appendChild(opt);
            });
        })
        .catch(() => {});
}

// ===== INIT =====
loadFilterAgences();
loadUsers();

// Charger le rôle de l'utilisateur connecté, restreindre les options et afficher/masquer le bouton
let csrfToken = '';
fetch("get_admin.php")
.then(res => res.json())
.then(profileData => {
    if (!profileData || profileData.error) return;
    
    // Stocker le token CSRF pour toutes les requêtes POST
    if (profileData.csrf_token) csrfToken = profileData.csrf_token;
    
    // Stocker le rôle de l'utilisateur connecté
    currentUserRole = profileData.role || null;
    currentUserAgenceId = profileData.admin_id_agence || profileData.agent_id_agence || null;
    
    // Afficher le bouton "Ajouter" pour tout le staff (superadmin, admin, agent)
    const addBtn = document.getElementById('addUserBtn');
    if (['superadmin', 'admin', 'agent'].includes(currentUserRole)) {
        addBtn.style.display = '';
    }
    
    // Restreindre les options de rôle en fonction du rôle connecté
    const fRole = document.getElementById('fRole');
    let allowedRoles = [];
    if (currentUserRole === 'superadmin') {
        // FIX 12 : ajouter l'option superadmin si pas encore présente
        if (!Array.from(fRole.options).some(o => o.value === 'superadmin')) {
            const optSA = document.createElement('option');
            optSA.value = 'superadmin'; optSA.textContent = 'Super Admin';
            fRole.appendChild(optSA);
        }
        allowedRoles = ['client', 'agent', 'admin', 'superadmin'];
    } else if (currentUserRole === 'admin') {
        allowedRoles = ['client', 'agent'];
    } else if (currentUserRole === 'agent') {
        allowedRoles = ['client'];
    }
    
    // Un agent ne peut pas changer le rôle (fixé à client)
    if (currentUserRole === 'agent') {
        fRole.disabled = true;
    }
    
    // Retirer les options non autorisées
    Array.from(fRole.options).forEach(opt => {
        if (!allowedRoles.includes(opt.value) && opt.value !== '') {
            opt.remove();
        }
    });
    
    if (window.ProtexSidebar) window.ProtexSidebar.applyUser(profileData);
});

// Activer le lien du sidebar selon la page actuelle
document.querySelectorAll('.nav-item').forEach(link => {
    link.classList.remove('active');
    if (link.getAttribute('href') === 'admin-users.html') {
        link.classList.add('active');
    }
});
</script>

<script src="assets/js/main.js"></script>
<script src="assets/js/admin.js"></script>

</body>
</html>


