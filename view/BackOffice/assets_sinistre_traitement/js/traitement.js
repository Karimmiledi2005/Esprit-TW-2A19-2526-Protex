
// ── ANTIFRAUD API ─────────────────────────────────────────────────────────────
var FRAUD_GET_API     = 'fraud_get.php';
var FRAUD_ANALYSE_API = 'fraud_analyse.php';

var FRAUD_NIVEAU_LABELS = { faible:'Faible', normal:'Normal', fraude:'FRAUDE' };
var FRAUD_SUGG_LABELS   = { accepter:'Accepter', investiguer:'Investiguer', refuser:'Refuser' };
var FRAUD_FLAG_DEFS = [
  { key:'description_vague',   label:'Description vague',   icon:'bi-chat-square-dots' },
  { key:'sinistres_multiples', label:'Sinistres multiples', icon:'bi-exclamation-triangle' },
  { key:'contrat_recent',      label:'Contrat récent',      icon:'bi-calendar-x' },
  { key:'montant_eleve',       label:'Montant élevé',       icon:'bi-cash-stack' },
  { key:'image_suspecte',      label:'Image suspecte',      icon:'bi-image' },
];

function renderFraudPanelData(data, idSinistre) {
  var panel = document.getElementById('fraudPanel');
  if (!panel) return;
  panel.style.display = '';
  document.getElementById('fraudScoreNum').textContent    = data.score_global;
  document.getElementById('fraudCircle').className        = 'fraud-score-circle ' + data.niveau_risque;
  document.getElementById('fraudNiveauLabel').className   = 'niveau-label ' + data.niveau_risque;
  document.getElementById('fraudNiveauLabel').textContent = FRAUD_NIVEAU_LABELS[data.niveau_risque] || data.niveau_risque;
  var pill = document.getElementById('fraudSuggestionPill');
  pill.innerHTML = '<span class="suggestion-pill ' + data.suggestion_ia + '"><i class="bi bi-lightning-charge"></i> ' + (FRAUD_SUGG_LABELS[data.suggestion_ia] || data.suggestion_ia) + '</span>';
  var sd = data.scores_detail || {};
  document.getElementById('barTexteVal').textContent   = (sd.texte !== undefined ? sd.texte : '—') + '/100';
  document.getElementById('barComportVal').textContent = (sd.comportement !== undefined ? sd.comportement : '—') + '/100';
  document.getElementById('barContratVal').textContent = (sd.contrat !== undefined ? sd.contrat : '—') + '/100';
  document.getElementById('barTexte').style.width   = (sd.texte  || 0) + '%';
  document.getElementById('barComport').style.width = (sd.comportement || 0) + '%';
  document.getElementById('barContrat').style.width = (sd.contrat || 0) + '%';
  var flags = data.flags || {};
  document.getElementById('fraudFlags').innerHTML = FRAUD_FLAG_DEFS.map(function(f) {
    var active = !!flags[f.key];
    return '<span class="fraud-flag ' + (active ? 'active' : 'inactive') + '"><i class="bi ' + f.icon + '"></i> ' + f.label + '</span>';
  }).join('');
  var rec = document.getElementById('fraudRecommandation');
  if (rec) rec.textContent = data.recommandation || '—';
  var btn = document.getElementById('fraudReanalyseBtn');
  if (btn) btn.onclick = function() { reanalyserFraudTraitement(idSinistre); };
  var dt = document.getElementById('fraudAnalyseDate');
  if (dt && data.date_analyse) dt.textContent = '— ' + data.date_analyse.substring(0, 10);
}

async function loadFraudPanelTraitement(idSinistre) {
  var panel = document.getElementById('fraudPanel');
  if (!panel) return;
  panel.style.display = '';
  try {
    var res  = await fetch(FRAUD_GET_API + '?id_sinistre=' + idSinistre);
    var json = await res.json();
    if (json.success && json.data) {
      renderFraudPanelData(json.data, idSinistre);
    } else {
      let analyzeBtnHtml = currentUserRole !== 'admin' ? ' <button class="fraud-reanalyse-btn" onclick="reanalyserFraudTraitement(' + idSinistre + ')"><i class="bi bi-shield-shaded"></i> Analyser</button>' : '';
      panel.querySelector('.fraud-flags').innerHTML = '<span style="color:var(--text-secondary);font-size:12px;">Aucune analyse.' + analyzeBtnHtml + '</span>';
    }
  } catch(e) { /* silencieux */ }
}

async function reanalyserFraudTraitement(idSinistre) {
  var btn = document.getElementById('fraudReanalyseBtn');
  if (btn) { btn.classList.add('loading'); btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Analyse...'; }
  try {
    var fd = new FormData(); fd.append('id_sinistre', idSinistre);
    var res  = await fetch(FRAUD_ANALYSE_API, { method: 'POST', body: fd });
    var json = await res.json();
    if (json.success) { 
      renderFraudPanelData(json.data, idSinistre); 
      if (json.data.auto_refused) {
        showToast('Sinistre REFUSÉ automatiquement (Risque IA Critique).', 'danger');
        // Recharger la liste principale
        if (typeof loadTraitements === 'function') loadTraitements();
        // Mettre à jour le badge de statut dans le modal ouvert (si applicable)
        var badge = document.querySelector('#modalView .badge');
        if (badge) {
          badge.className = 'badge badge-admin';
          badge.textContent = 'Refusé';
        }
      } else {
        showToast('Analyse antifraud terminée.', 'success'); 
      }
    }
    else { showToast(json.message || 'Erreur analyse.', 'danger'); }
  } catch(e) { showToast('Erreur réseau.', 'danger'); }
  finally { if (btn) { btn.classList.remove('loading'); btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Relancer l\'analyse'; } }
}

// ===== TRAITEMENT PAGE LOGIC =====

const TRAIT_LIST_API = 'traitement_list.php';
const TRAIT_BY_SINISTRE_API = 'traitement_list_sinistre.php';
const TRAIT_CHECK_API = 'traitement_check_sinistre.php';
const TRAIT_CREATE_API = 'traitement_create.php';
const TRAIT_UPDATE_API = 'traitement_update.php';
const TRAIT_DELETE_API = 'traitement_delete.php';
const SINISTRE_DETAILS_API = 'sinistre_details.php';
let traitements = [];
let currentPage = 1, editingId = null, deletingId = null;
const perPage = 8;
let currentUserRole = 'client';
let currentUserId = null;

// ── Sort state ───────────────────────────────────────────────────────────────
var sortColumn = null;    // 'id', 'sinistre', 'date', 'agent', 'decision', 'montant'
var sortDirection = null; // 'asc' or 'desc'

const DECISION_LABELS = { en_attente: 'En attente', refuse: 'Refusé', rembourse: 'Remboursé' };
const DECISION_COLORS = { en_attente: 'badge-agent', refuse: 'badge-admin', rembourse: 'badge-actif' };

function decisionBadge(statut, decision) {
  var key = decision || statut;
  var color = DECISION_COLORS[key] || 'badge-agent';
  var label = DECISION_LABELS[key] || key;
  return '<span class="badge ' + color + '">' + label + '</span>';
}
function initials(name) {
  if (!name) return '??';
  // Handle "Agent #1" style fallback
  var match = name.match(/^Agent #(\d+)$/);
  if (match) return 'A' + match[1];
  return name.split(' ').map(function (w) { return w[0]; }).join('').substring(0, 2).toUpperCase();
}
function formatDate(d) {
  if (!d) return '—';
  var parts = d.split('-');
  return parts[2] + '/' + parts[1] + '/' + parts[0];
}

// ── Date topbar ──────────────────────────────────────────────────────────────
document.getElementById('topbarDate').textContent =
  new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

// ── Load from DB ─────────────────────────────────────────────────────────────
async function loadTraitements() {
  try {
    var res = await fetch(TRAIT_LIST_API + '?_=' + Date.now());
    var json = await res.json();
    if (json.success) {
      traitements = json.data.map(function (t) {
        return {
          id: t.id_traitement,
          sinistre: t.id_sinistre,
          sinType: t.sinistre_type || '—',
          date: t.date_traitement,
          agent: t.nom_agent,
          agentId: t.id_user, // On récupère l'ID pour le sélecteur
          decision: t.decision,
          message_agent: t.message_agent,
          montant: t.montant_indemnise !== null ? parseFloat(t.montant_indemnise) : null,
          statut: t.statut,
        };
      });
      currentPage = 1;
      render();
    } else {
      showToast('Erreur: ' + json.message, 'danger');
    }
  } catch (e) {
    showToast('Impossible de contacter le serveur PHP.', 'danger');
  }
}

// serach et filter
function getFiltered() {
  var q = document.getElementById('searchInput').value.toLowerCase();
  var dec = document.getElementById('filterDecision').value;
  var mon = document.getElementById('filterMontant').value;
  var filtered = traitements.filter(function (t) {
    var mQ = !q || String(t.sinistre).includes(q) || String(t.agent || '').toLowerCase().includes(q) || String(t.decision || '').toLowerCase().includes(q);
    var mD = !dec || t.decision === dec;
    var mM = !mon || (mon === 'avec' ? (t.montant !== null && t.montant > 0) : (t.montant === null || t.montant === 0));
    return mQ && mD && mM;
  });

  // sort
  if (sortColumn && sortDirection) {
    filtered.sort(function (a, b) {
      var valA, valB;
      switch (sortColumn) {
        case 'id': valA = a.id; valB = b.id; break;
        case 'sinistre': valA = a.sinistre; valB = b.sinistre; break;
        case 'date': valA = a.date || ''; valB = b.date || ''; break;
        case 'agent': valA = (a.agent || '').toLowerCase(); valB = (b.agent || '').toLowerCase(); break;
        case 'decision': valA = (a.decision || '').toLowerCase(); valB = (b.decision || '').toLowerCase(); break;
        case 'montant': valA = a.montant !== null ? a.montant : -1; valB = b.montant !== null ? b.montant : -1; break;
        default: return 0;
      }
      var cmp = 0;
      if (typeof valA === 'number' && typeof valB === 'number') {
        cmp = valA - valB;
      } else {
        cmp = String(valA).localeCompare(String(valB), 'fr');
      }
      return sortDirection === 'desc' ? -cmp : cmp;
    });
  }

  return filtered;
}

function resetFilters() {
  ['searchInput', 'filterDecision', 'filterMontant'].forEach(function (id) {
    document.getElementById(id).value = '';
  });
  sortColumn = null; sortDirection = null;
  currentPage = 1;
  render();
}

// toggle sort
function toggleSort(col) {
  if (sortColumn === col) {
    if (sortDirection === 'asc') sortDirection = 'desc';
    else if (sortDirection === 'desc') { sortColumn = null; sortDirection = null; }
  } else {
    sortColumn = col;
    sortDirection = 'asc';
  }
  currentPage = 1;
  render();
}

function updateSortHeaders() {
  document.querySelectorAll('thead th.sortable').forEach(function (th) {
    th.classList.remove('sort-asc', 'sort-desc');
    if (th.dataset.sort === sortColumn) {
      th.classList.add(sortDirection === 'asc' ? 'sort-asc' : 'sort-desc');
    }
  });
}

// rendri table
function render() {
  var filtered = getFiltered();
  var total = filtered.length;
  var pages = Math.ceil(total / perPage) || 1;
  if (currentPage > pages) currentPage = pages;
  var slice = filtered.slice((currentPage - 1) * perPage, currentPage * perPage);

  var tbody = document.getElementById('traitBody');
  var empty = document.getElementById('emptyState');

  if (!slice.length) {
    tbody.innerHTML = '';
    empty.style.display = 'block';
  } else {
    empty.style.display = 'none';
    tbody.innerHTML = slice.map(function (t) {
      var montantCell = t.montant !== null
        ? '<span class="amount-value">' + t.montant.toLocaleString('fr-FR') + ' DT</span>'
        : '<span class="amount-empty">—</span>';
      return '<tr>' +
        '<td><span style="font-family:monospace;font-size:12px;color:var(--accent);">TR-' + String(t.id).padStart(3, '0') + '</span></td>' +
        '<td><span style="color:var(--gold);font-weight:600;">#' + t.sinistre + '</span> <span style="font-size:11px;color:var(--text-secondary);">' + t.sinType + '</span></td>' +
        '<td style="color:var(--text-secondary);">' + formatDate(t.date) + '</td>' +
        '<td><div class="agent-cell"><div class="agent-avatar">' + initials(t.agent) + '</div><span style="font-size:13px;">' + t.agent + '</span></div></td>' +
        '<td>' + decisionBadge(t.statut, t.decision) + '</td>' +
        '<td>' + montantCell + '</td>' +
        '<td><div class="actions">' +
        '<button class="btn btn-outline btn-sm" onclick="openViewModal(' + t.sinistre + ')" title="Voir le sinistre"><i class="bi bi-eye"></i></button>' +
        '<button class="btn btn-outline btn-sm btn-edit-traitement" data-statut="' + t.statut + '" onclick="openEditModal(' + t.id + ')" title="Modifier"><i class="bi bi-pencil"></i></button>' +
        '<button class="btn btn-danger btn-sm btn-delete-traitement" onclick="openDeleteModal(' + t.id + ')" title="Supprimer"><i class="bi bi-trash3"></i></button>' +
        '</div></td>' +
        '</tr>';
    }).join('');
  }

  var start = total === 0 ? 0 : (currentPage - 1) * perPage + 1;
  var end = Math.min(currentPage * perPage, total);
  document.getElementById('paginationInfo').textContent =
    'Affichage ' + start + '–' + end + ' sur ' + total + ' traitement' + (total > 1 ? 's' : '');

  var btnHtml = '<button class="page-btn" onclick="goPage(' + (currentPage - 1) + ')" ' + (currentPage <= 1 ? 'disabled' : '') + '><i class="bi bi-chevron-left"></i></button>';
  for (var i = 1; i <= pages; i++) {
    btnHtml += '<button class="page-btn ' + (i === currentPage ? 'active' : '') + '" onclick="goPage(' + i + ')">' + i + '</button>';
  }
  btnHtml += '<button class="page-btn" onclick="goPage(' + (currentPage + 1) + ')" ' + (currentPage >= pages ? 'disabled' : '') + '><i class="bi bi-chevron-right"></i></button>';
  document.getElementById('paginationBtns').innerHTML = btnHtml;

  updateStats();
  updateSortHeaders();
  loadPermissions();
}

function goPage(p) { currentPage = p; render(); }

function updateStats() {
  document.getElementById('statTotal').textContent = traitements.length;
  var total = traitements.filter(function (t) { return t.montant !== null; })
    .reduce(function (s, t) { return s + t.montant; }, 0);
  document.getElementById('statMontant').textContent = total.toLocaleString('fr-FR');

  var rembourses = traitements.filter(function (t) { return t.decision === 'rembourse'; }).length;
  document.getElementById('statRembourses').textContent = rembourses;

  var refuses = traitements.filter(function (t) { return t.decision === 'refuse'; }).length;
  document.getElementById('statRefuses').textContent = refuses;
}

// ── Form helpers ─────────────────────────────────────────────────────────────
async function loadAgents(selectedId = null) {
  const sel = document.getElementById('fAgent');
  if (!sel) return;
  try {
    const res = await fetch('get_agents_by_agence.php');
    const json = await res.json();
    if (json.success) {
      sel.innerHTML = '<option value="">— Sélectionner un agent —</option>';
      json.data.forEach(ag => {
        const opt = document.createElement('option');
        opt.value = ag.id_user; // On stocke l'ID maintenant !
        opt.dataset.fullname = ag.prenom + ' ' + ag.nom;
        opt.textContent = ag.prenom + ' ' + ag.nom;
        sel.appendChild(opt);
      });
      if (selectedId) {
        // On force la sélection en s'assurant que la comparaison fonctionne (string/int)
        sel.value = selectedId;
        if (sel.value === "" && json.data.length > 0) {
            // Fallback au cas où le premier essai échouerait
            for (let opt of sel.options) {
                if (opt.value == selectedId) {
                    sel.value = opt.value;
                    break;
                }
            }
        }
      }
    }
  } catch (e) { console.error('Erreur chargement agents:', e); }
}

// ── Modal open/close ─────────────────────────────────────────────────────────
function openCreateModal() {
  editingId = null;
  document.getElementById('modalFormTitle').innerHTML = '<i class="bi bi-plus-circle"></i> Ajouter un traitement';
  document.getElementById('btnSave').innerHTML = '<i class="bi bi-save"></i> Enregistrer';
  ['fSinistre', 'fDate', 'fMontant', 'fMessage'].forEach(function (id) { document.getElementById(id).value = ''; });
  document.getElementById('fAgent').value = '';
  loadAgents();
  document.getElementById('fDecision').value = '';
  document.getElementById('fStatut').value = 'en_cours';
  document.getElementById('fDate').value = new Date().toISOString().split('T')[0];
  document.getElementById('sinistrePreview').style.display = 'none';
  clearAllErrors();
  
  if (currentUserRole === 'agent') {
      setTimeout(() => {
          const agentSel = document.getElementById('fAgent');
          if (agentSel) agentSel.value = currentUserId;
      }, 500); // Wait for agents list to load
  }
  
  if (currentUserRole === 'admin') {
      document.getElementById('fDecision').value = 'en_attente';
      document.getElementById('fDecision').disabled = true;
      document.getElementById('fMontant').value = '0';
      document.getElementById('fMontant').disabled = true;
      document.getElementById('fStatut').value = 'en_cours';
      document.getElementById('fStatut').disabled = true;
  } else {
      document.getElementById('fDecision').disabled = false;
      document.getElementById('fMontant').disabled = false;
      document.getElementById('fStatut').disabled = false;
  }

  openModal('modalForm');
}

async function openEditModal(id) {
  var t = traitements.find(function (x) { return x.id == id; });
  if (!t) return;
  editingId = id;
  document.getElementById('modalFormTitle').innerHTML = '<i class="bi bi-pencil"></i> Modifier le traitement';
  document.getElementById('btnSave').innerHTML = '<i class="bi bi-save"></i> Mettre à jour';
  document.getElementById('fSinistre').value = t.sinistre;
  document.getElementById('fDate').value = t.date;
  await loadAgents(t.agentId); // On attend que la liste soit là !
  document.getElementById('fDecision').value = t.decision;
  document.getElementById('fMessage').value = t.message_agent || '';
  document.getElementById('fMontant').value = t.montant !== null ? t.montant : '';
  document.getElementById('fStatut').value = t.statut || 'en_cours';
  document.getElementById('sinistrePreview').style.display = 'none';
  document.getElementById('sinistrePreview').style.display = 'none';
  clearAllErrors();

  if (currentUserRole === 'admin') {
      document.getElementById('fDecision').disabled = true;
      document.getElementById('fMontant').disabled = true;
      document.getElementById('fStatut').disabled = true;
  } else {
      document.getElementById('fDecision').disabled = false;
      document.getElementById('fMontant').disabled = false;
      document.getElementById('fStatut').disabled = false;
  }

  openModal('modalForm');
  loadPermissions(); // Pour griser le champ agent si nécessaire
}

// save
async function saveTraitement() {
  var ok = true;

  // 1. Sinistre ID — basic numeric check only (DB check is advisory, not blocking)
  var sinVal = document.getElementById('fSinistre').value.trim();
  var sinId = parseInt(sinVal);
  if (!sinVal || isNaN(sinId) || sinId <= 0) {
    showErr('fSinistre', 'errSinistre', "L'ID du sinistre est requis (nombre entier positif).");
    ok = false;
  } else {
    clearErr('fSinistre', 'errSinistre');
    var sinValid = await validateSinistreId(); // blocks if duplicate or not found
    if (!sinValid) ok = false;
  }

  // 2. Date
  var date = document.getElementById('fDate').value;
  if (!date) { showErr('fDate', 'errDate', 'La date est requise.'); ok = false; }
  else clearErr('fDate', 'errDate');

  // 3. Agent 
  var agentVal = document.getElementById('fAgent').value.trim();
  if (!agentVal) {
    showErr('fAgent', 'errAgent', "Le nom de l'agent est requis.");
    ok = false;
  } else {
    clearErr('fAgent', 'errAgent');
  }

  // 4. Décision
  var decVal = document.getElementById('fDecision').value;
  if (!decVal) { showErr('fDecision', 'errDecision', 'Choisissez une décision.'); ok = false; }
  else clearErr('fDecision', 'errDecision');

  // 5. Montant (mandatory for all decisions, can be 0)
  var montantV = document.getElementById('fMontant').value.trim();
  var montant = montantV !== '' ? parseFloat(montantV) : null;
  if (montantV === '') {
    showErr('fMontant', 'errMontant', 'Le montant est obligatoire (entrez 0 si non applicable).');
    ok = false;
  } else if (isNaN(montant) || montant < 0) {
    showErr('fMontant', 'errMontant', 'Montant invalide.');
    ok = false;
  } else {
    clearErr('fMontant', 'errMontant');
  }

  // 6. Statut
  var statutVal = document.getElementById('fStatut').value;
  if (!statutVal) {
    showErr('fStatut', 'errStatut', 'Choisissez un statut.');
    ok = false;
  } else {
    clearErr('fStatut', 'errStatut');
  }

  if (!ok) return;

  var btn = document.getElementById('btnSave');
  var orig = btn.innerHTML;
  btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Enregistrement...';
  btn.disabled = true;

  var sinistre = document.getElementById('fSinistre').value.trim();
  var agentId  = document.getElementById('fAgent').value;
  var agentOpt = document.getElementById('fAgent').options[document.getElementById('fAgent').selectedIndex];
  var agentNom = agentOpt ? (agentOpt.dataset.fullname || '') : '';
  var message  = document.getElementById('fMessage').value.trim();
  var statut   = document.getElementById('fStatut').value;

  var params = 'id_sinistre=' + encodeURIComponent(sinistre) +
    '&assigned_agent_id=' + encodeURIComponent(agentId) +
    '&nom_agent=' + encodeURIComponent(agentNom) +
    '&decision=' + encodeURIComponent(decVal) +
    '&message_agent=' + encodeURIComponent(message) +
    '&montant=' + encodeURIComponent(montantV) +
    '&statut=' + encodeURIComponent(statut);
  if (editingId) params += '&id_traitement=' + editingId;

  var url = editingId ? TRAIT_UPDATE_API : TRAIT_CREATE_API;

  try {
    var res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: params
    });
    var json = await res.json();
    if (json.success) {
      closeModal('modalForm');
      await loadTraitements();
      showToast(editingId ? 'Traitement modifié.' : 'Traitement ajouté avec succès.', 'success');
    } else {
      showToast(json.message, 'danger');
    }
  } catch (e) {
    showToast('Erreur réseau.', 'danger');
  } finally {
    btn.innerHTML = orig;
    btn.disabled = false;
  }
}

// ── Delete ────────────────────────────────────────────────────────────────────
function openDeleteModal(id) {
  deletingId = id;
  document.getElementById('deleteMsg').innerHTML =
    'Vous êtes sur le point de supprimer le traitement <strong>TR-' + String(id).padStart(3, '0') + '</strong>. Cette action est irréversible.';
  openModal('modalDelete');
}
async function confirmDelete() {
  try {
    var res = await fetch(TRAIT_DELETE_API + '?id=' + deletingId, { method: 'GET' });
    var json = await res.json();
    closeModal('modalDelete');
    if (json.success) {
      await loadTraitements();
      showToast('Traitement supprimé.', 'danger');
    } else {
      showToast(json.message, 'danger');
    }
  } catch (e) {
    showToast('Erreur réseau.', 'danger');
  }
}

// ── View Sinistre ─────────────────────────────────────────────────────────────
async function openViewModal(sinistreId) {
  openModal('modalView');
  document.getElementById('viewContent').innerHTML =
    '<div style="text-align:center;padding:40px;">' +
    '<i class="bi bi-hourglass" style="font-size:32px;opacity:0.5;display:block;margin-bottom:10px;"></i>' +
    '<p>Chargement...</p>' +
    '</div>';

  try {
    var res = await fetch(SINISTRE_DETAILS_API + '?id=' + sinistreId);
    var json = await res.json();

    if (json.success) {
      var s = json.data;
      var photoHtml = '';
      if (s.photo_url) {
        photoHtml = '<img src="../../' + s.photo_url + '" style="width:100%;max-height:400px;border-radius:8px;object-fit:cover;margin-bottom:20px;" alt="Photo du sinistre">';
      } else {
        photoHtml = '<div style="width:100%;height:300px;background:var(--glass-bg);border-radius:8px;display:flex;align-items:center;justify-content:center;margin-bottom:20px;border:1px solid var(--glass-border);"><i class="bi bi-image" style="font-size:48px;opacity:0.3;"></i></div>';
      }

      var statutColors = { en_attente: '#f4a261', rembourse: '#2ec4b6', refuse: '#e63946' };
      var statutLabels = { en_attente: 'En attente', rembourse: 'Remboursé', refuse: 'Refusé' };

      document.getElementById('modalViewTitle').innerHTML = '<i class="bi bi-shield-check"></i> Sinistre #' + s.id_sinistre;
      document.getElementById('viewContent').innerHTML =
        '<div style="padding:0 20px 20px 20px;">' +
        photoHtml +
        '<div style="background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:8px;padding:16px;margin-bottom:16px;">' +
        '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;font-size:13px;">' +
        '<div><span style="color:var(--text-secondary);">Type:</span> <strong style="color:#fff;display:block;margin-top:4px;">' + s.type + '</strong></div>' +
        '<div><span style="color:var(--text-secondary);">Statut:</span> <strong style="color:' + (statutColors[s.statut] || '#fff') + ';display:block;margin-top:4px;">' + (statutLabels[s.statut] || s.statut) + '</strong></div>' +
        '<div><span style="color:var(--text-secondary);">Date:</span> <strong style="color:#fff;display:block;margin-top:4px;">' + formatDate(s.date_declaration) + '</strong></div>' +
        '</div>' +
        '</div>' +
        '<div>' +
        '<div style="margin-bottom:8px;font-size:12px;color:var(--text-secondary);">Description:</div>' +
        '<div style="background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:8px;padding:12px;font-size:13px;line-height:1.6;color:#fff;word-wrap:break-word;">' + (s.description || '—') + '</div>' +
        '</div>' +
        /* ANTIFRAUD PANEL */
        '<div class="fraud-panel" id="fraudPanel" style="display:none;">' +
          '<div class="fraud-panel-header">' +
            '<div class="fraud-panel-title"><i class="bi bi-shield-shaded"></i> Analyse Antifraud IA <span id="fraudAnalyseDate" style="font-weight:400;font-size:11px;color:var(--text-secondary);margin-left:4px;"></span></div>' +
            (currentUserRole !== 'admin' ? '<button class="fraud-reanalyse-btn" id="fraudReanalyseBtn"><i class="bi bi-arrow-repeat"></i> Relancer l\'analyse</button>' : '') +
          '</div>' +
          '<div class="fraud-score-row">' +
            '<div class="fraud-score-circle" id="fraudCircle"><span class="fraud-score-num" id="fraudScoreNum">—</span><span class="fraud-score-denom">/100</span></div>' +
            '<div class="fraud-score-meta"><div class="niveau-label" id="fraudNiveauLabel">—</div><div id="fraudSuggestionPill"></div><div style="font-size:12px;color:var(--text-secondary);">Score de risque global calculé par l\'IA</div></div>' +
          '</div>' +
          '<div class="fraud-bars">' +
            '<div class="fraud-bar-row"><div class="fraud-bar-label"><span><i class="bi bi-chat-text"></i> Analyse textuelle</span><span id="barTexteVal">—</span></div><div class="fraud-bar-track"><div class="fraud-bar-fill bar-texte" id="barTexte" style="width:0%"></div></div></div>' +
            '<div class="fraud-bar-row"><div class="fraud-bar-label"><span><i class="bi bi-person-lines-fill"></i> Comportement client</span><span id="barComportVal">—</span></div><div class="fraud-bar-track"><div class="fraud-bar-fill bar-comportement" id="barComport" style="width:0%"></div></div></div>' +
            '<div class="fraud-bar-row" style="margin-bottom:0;"><div class="fraud-bar-label"><span><i class="bi bi-file-earmark-text"></i> Profil contrat</span><span id="barContratVal">—</span></div><div class="fraud-bar-track"><div class="fraud-bar-fill bar-contrat" id="barContrat" style="width:0%"></div></div></div>' +
          '</div>' +
          '<div class="fraud-flags" id="fraudFlags"></div>' +
          '<div class="fraud-recommandation" id="fraudRecommandation"></div>' +
        '</div>' +
        '</div>';
      loadFraudPanelTraitement(s.id_sinistre);
    } else {
      document.getElementById('viewContent').innerHTML =
        '<div style="text-align:center;padding:40px;color:var(--text-secondary);">' +
        '<i class="bi bi-exclamation-triangle" style="font-size:32px;display:block;margin-bottom:10px;opacity:0.5;"></i>' +
        '<p>' + json.message + '</p>' +
        '</div>';
    }
  } catch (e) {
    document.getElementById('viewContent').innerHTML =
      '<div style="text-align:center;padding:40px;color:var(--text-secondary);">' +
      '<i class="bi bi-exclamation-circle" style="font-size:32px;display:block;margin-bottom:10px;opacity:0.5;"></i>' +
      '<p>Erreur lors du chargement des détails.</p>' +
      '</div>';
  }
}

// ── Export CSV ────────────────────────────────────────────────────────────────
function exportCSV() {
  var rows = [['ID', 'Sinistre', 'Date', 'Agent', 'Decision', 'Montant (DT)']];
  traitements.forEach(function (t) {
    rows.push(['TR-' + String(t.id).padStart(3, '0'), '#' + t.sinistre, t.date, t.agent, t.decision, t.montant !== null ? t.montant : '']);
  });
  var csv = rows.map(function (r) { return r.map(function (v) { return '"' + v + '"'; }).join(','); }).join('\n');
  var a = document.createElement('a');
  a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
  a.download = 'traitements.csv';
  a.click();
  showToast('Export CSV téléchargé.', 'success');
}

// ── Modal helpers ─────────────────────────────────────────────────────────────
function openModal(id) { document.getElementById(id).classList.add('open'); document.body.style.overflow = 'hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow = ''; }

document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(function (m) {
      m.classList.remove('open'); document.body.style.overflow = '';
    });
  }
});
document.querySelectorAll('.modal-overlay').forEach(function (o) {
  o.addEventListener('click', function (e) {
    if (e.target === o) { o.classList.remove('open'); document.body.style.overflow = ''; }
  });
});


document.getElementById('searchInput').addEventListener('input', function () { currentPage = 1; render(); });
document.getElementById('filterDecision').addEventListener('change', function () { currentPage = 1; render(); });
document.getElementById('filterMontant').addEventListener('change', function () { currentPage = 1; render(); });

async function loadPermissions() {
  try {
    const res = await fetch('get_permissions.php');
    const perms = await res.json();
    currentUserRole = perms.role;
    currentUserId = perms.userId;

    // Masquer le bouton Ajouter si pas autorisé
    const addBtn = document.querySelector('button[onclick="openCreateModal()"]');
    if (addBtn && !perms.canCreateTraitement) {
      addBtn.remove();
    }

    // Désactiver le choix de l'agent si ce n'est pas un admin (l'agent ne s'auto-assigne pas à un autre)
    const agentSel = document.getElementById('fAgent');
    if (agentSel) {
      agentSel.disabled = !perms.canValiderTraitement;
    }

    if (!perms.canDeleteTraitement) {
      // Suppression réservée au superadmin
      document.querySelectorAll('.btn-delete-traitement').forEach(function(b) { b.remove(); });
    }
    
    // On laisse le bouton de modification (openEditModal) visible pour l'agent
    // car le tableau indique qu'il peut modifier SI NON VALIDÉ.
    if (perms.role === 'agent') {
      document.querySelectorAll('.btn-edit-traitement').forEach(function(btn) {
        const status = btn.dataset.statut;
        if (status === 'accepte' || status === 'refuse') {
          btn.remove(); // L'agent ne peut plus modifier si c'est validé
        }
      });
    }

    // L'admin ne fait que "voir" le traitement, il ne peut pas le modifier
    if (perms.role === 'admin') {
      document.querySelectorAll('.btn-edit-traitement').forEach(function(btn) {
        btn.remove();
      });
    }

    if (!perms.canSeeStatsGlobales) {
      document.querySelectorAll('.stats-globales').forEach(function(el) { el.remove(); });
    }
  } catch (e) { console.error('Erreur chargement permissions:', e); }
}

// Appel au chargement
document.addEventListener('DOMContentLoaded', function() {
  loadPermissions();
  loadTraitements();
});
