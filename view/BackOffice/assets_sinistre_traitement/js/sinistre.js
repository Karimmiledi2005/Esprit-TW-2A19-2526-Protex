// Sinistre page logic

// API endpoints
const SINISTRE_LIST_API         = 'sinistre_list.php';
const SINISTRE_UPDATE_STATUT_API = '../FrontOffice/sinistre_update_statut.php';
const SINISTRE_CREATE_API       = 'sinistre_create.php';
const SINISTRE_DELETE_API       = 'sinistre_delete.php';
const FRAUD_GET_API             = 'fraud_get.php';
const FRAUD_ANALYSE_API         = 'fraud_analyse.php';

let sinistres = [];   // chargé depuis la BDD
let nextNum = 1000, currentPage = 1, deletingId = null;
const perPage = 8;

// Sort state
let sortColumn = null;   // 'id', 'contrat', 'type', 'client', 'date', 'statut'
let sortDirection = null; // 'asc' or 'desc'

const STATUT_LABELS = { en_attente:'En attente', en_analyse:'En analyse', assigne:'Assigné', en_cours:'En cours', rembourse:'Remboursé', refuse:'Refusé', cloture:'Clôturé' };
const STATUT_BADGE  = { en_attente:'badge-agent', en_analyse:'badge-en-attente', assigne:'badge-en-attente', en_cours:'badge-en-cours', rembourse:'badge-actif', refuse:'badge-admin', cloture:'badge-actif' };
let currentView = 'list'; // 'list' or 'kanban'
const TYPE_ICONS    = { 
    'Accident auto': 'bi-car-front',
    'Vol de véhicule': 'bi-car-front',
    'Bris de glace': 'bi-window',
    'Incendie véhicule': 'bi-fire',
    'Incendie': 'bi-fire',
    'Cambriolage / Vol': 'bi-lock',
    'Dégât des eaux': 'bi-droplet',
    'Catastrophe naturelle': 'bi-cloud-lightning-rain',
    'Décès': 'bi-heartbreak',
    'Invalidité': 'bi-person-wheelchair',
    'Hospitalisation': 'bi-hospital',
    'Accident corporel': 'bi-bandaid',
    'Maladie grave': 'bi-activity',
    'Vol / Vandalisme': 'bi-shield-slash'
};

// Load data from DB
async function loadSinistres() {
    try {
        const res  = await fetch(SINISTRE_LIST_API + '?_=' + Date.now());
        const json = await res.json();
        if (json.success) {
            sinistres = json.data.map(s => ({
                id:             s.id_sinistre,
                contrat:        s.numero_contrat,
                type:           s.type,
                date:           s.date_declaration,
                statut:         s.statut,
                description:    s.description,
                client:         s.client_nom || '—',
                fraudScore:     s.fraud_score  !== undefined ? s.fraud_score  : null,
                fraudNiveau:    s.fraud_niveau !== undefined ? s.fraud_niveau : null,
                fraudSuggestion:s.fraud_suggestion !== undefined ? s.fraud_suggestion : null,
            }));
            currentPage = 1;
            render();
            updateChart();
        } else {
            showToast('Erreur chargement: ' + json.message, 'danger');
        }
    } catch(e) {
        showToast('Impossible de contacter le serveur PHP.', 'danger');
    }
}

// Chart.js integration
let sinistresChart = null;

function updateChart() {
    const ctx = document.getElementById('sinistresChart');
    if (!ctx) return;

    // Group by month (YYYY-MM)
    const counts = {};
    sinistres.forEach(s => {
        if (!s.date) return;
        const month = s.date.substring(0, 7);
        counts[month] = (counts[month] || 0) + 1;
    });

    // Sort months chronologically
    const labels = Object.keys(counts).sort();
    const data = labels.map(label => counts[label]);

    // Format labels nicely (e.g. "Oct 2023")
    const niceLabels = labels.map(label => {
        const d = new Date(label + '-01');
        return d.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
    });

    if (sinistresChart) {
        sinistresChart.data.labels = niceLabels;
        sinistresChart.data.datasets[0].data = data;
        sinistresChart.update();
    } else {
        sinistresChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: niceLabels,
                datasets: [{
                    label: 'Sinistres déclarés',
                    data: data,
                    borderColor: '#00b4d8',
                    backgroundColor: 'rgba(0, 180, 216, 0.15)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#0077b6',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(10, 15, 30, 0.9)',
                        titleFont: { size: 13, family: "'Inter', sans-serif" },
                        bodyFont: { size: 14, family: "'Inter', sans-serif" },
                        padding: 12,
                        displayColors: false
                    }
                }
            }
        });
    }
}

async function changeStatut(id, newStatut) {
    const body = new URLSearchParams({ id, statut: newStatut });
    const res  = await fetch(SINISTRE_UPDATE_STATUT_API, { method:'POST', body });
    const json = await res.json();
    if (json.success) {
        const s = sinistres.find(x => x.id == id);
        if (s) { s.statut = newStatut; render(); }
        showToast(`Statut → ${STATUT_LABELS[newStatut]}`, newStatut==='refuse'?'danger':'success');
    } else {
        showToast(json.message, 'danger');
    }
}

async function saveSinistre() {
    const contrat = document.getElementById('fContrat').value.trim();
    const date    = document.getElementById('fDate').value;
    const desc    = document.getElementById('fDescription').value.trim();
    let ok = true;
    if(!contrat){showErr('fContrat','errContrat',true);ok=false;}else showErr('fContrat','errContrat',false);
    if(!date)   {showErr('fDate','errDate',true);ok=false;}      else showErr('fDate','errDate',false);
    if(!desc)   {showErr('fDescription','errDescription',true);ok=false;} else showErr('fDescription','errDescription',false);
    if(!ok) return;

    const btn = document.getElementById('btnCreate');
    btn.innerHTML='<i class="bi bi-arrow-repeat spin"></i> Enregistrement...'; btn.disabled=true;

    // id_contrat et id_user: pour la démo on envoie 1 — en production, utilisez la session
    const formData = new FormData();
    formData.append('id_contrat',  contrat);
    formData.append('type',        document.getElementById('fType').value);
    formData.append('description', desc);
    formData.append('id_user',     1); // remplacer par $_SESSION['user_id'] côté PHP

    try {
        const res  = await fetch(SINISTRE_CREATE_API, { method:'POST', body: formData });
        const json = await res.json();
        if (json.success) {
            closeModal('modalCreate');
            await loadSinistres();
            showToast('Sinistre déclaré avec succès.', 'success');
        } else {
            showToast(json.message, 'danger');
        }
    } catch(e) {
        showToast('Erreur réseau.', 'danger');
    } finally {
        btn.innerHTML='<i class="bi bi-save"></i> Enregistrer'; btn.disabled=false;
    }
}

// Topbar date
const now = new Date();
document.getElementById('topbarDate').textContent =
  now.toLocaleDateString('fr-FR',{weekday:'long',day:'numeric',month:'long',year:'numeric'});

function getFiltered() {
  const q    = document.getElementById('searchInput').value.toLowerCase();
  const stat = document.getElementById('filterStatut').value;
  const type = document.getElementById('filterType').value;
  const date = document.getElementById('filterDate').value;
  var filtered = sinistres.filter(s =>
    (!q    || String(s.id).includes(q) || String(s.contrat||'').toLowerCase().includes(q) || String(s.type||'').toLowerCase().includes(q) || String(s.client||'').toLowerCase().includes(q)) &&
    (!stat || s.statut === stat) &&
    (!type || s.type === type) &&
    (!date || s.date === date)
  );

  // ── Apply sort ──
  if (sortColumn && sortDirection) {
    filtered.sort(function(a, b) {
      var valA, valB;
      switch(sortColumn) {
        case 'id':      valA = a.id;      valB = b.id;      break;
        case 'contrat': valA = (a.contrat||'').toLowerCase(); valB = (b.contrat||'').toLowerCase(); break;
        case 'type':    valA = (a.type||'').toLowerCase();    valB = (b.type||'').toLowerCase();    break;
        case 'client':  valA = (a.client||'').toLowerCase();  valB = (b.client||'').toLowerCase();  break;
        case 'date':    valA = a.date||'';  valB = b.date||'';  break;
        case 'statut':  valA = (a.statut||'').toLowerCase();  valB = (b.statut||'').toLowerCase();  break;
        default:        return 0;
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

function resetFilters(){
  ['searchInput','filterStatut','filterType','filterDate'].forEach(id=>document.getElementById(id).value='');
  sortColumn = null; sortDirection = null;
  currentPage=1; render();
}

// Sort toggle
function toggleSort(col) {
  if (sortColumn === col) {
    if (sortDirection === 'asc')       sortDirection = 'desc';
    else if (sortDirection === 'desc') { sortColumn = null; sortDirection = null; }
  } else {
    sortColumn = col;
    sortDirection = 'asc';
  }
  currentPage = 1;
  render();
}

function updateSortHeaders() {
  document.querySelectorAll('thead th.sortable').forEach(function(th) {
    th.classList.remove('sort-asc', 'sort-desc');
    if (th.dataset.sort === sortColumn) {
      th.classList.add(sortDirection === 'asc' ? 'sort-asc' : 'sort-desc');
    }
  });
}


// Antifraud helpers
const FRAUD_NIVEAU_LABELS = { faible:'Faible', normal:'Normal', fraude:'FRAUDE' };

function renderFraudBadge(score, niveau) {
  if (score === null || niveau === null) {
    return '<span class="fraud-badge none"><i class="bi bi-shield"></i> —</span>';
  }
  const icons = { faible:'bi-shield-check', normal:'bi-shield-exclamation', fraude:'bi-shield-fill' };
  const icon  = icons[niveau] || 'bi-shield';
  const label = FRAUD_NIVEAU_LABELS[niveau] || niveau;
  return `<span class="fraud-badge ${niveau}"><i class="bi ${icon}"></i> ${label} (${score})</span>`;
}

async function reanalyserFraud(idSinistre) {
  const btn = document.getElementById('fraudReanalyseBtn');
  if (btn) { btn.classList.add('loading'); btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Analyse...'; }
  try {
    const fd = new FormData(); fd.append('id_sinistre', idSinistre);
    const res  = await fetch(FRAUD_ANALYSE_API, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
      renderFraudPanel(json.data, idSinistre);
      // Mettre à jour le badge dans le tableau
      const s = sinistres.find(x => x.id == idSinistre);
      if (s) { 
        s.fraudScore = json.data.score_global; 
        s.fraudNiveau = json.data.niveau_risque; 
        s.fraudSuggestion = json.data.suggestion_ia; 
        if (json.data.auto_refused) {
            s.statut = 'refuse';
            showToast('Sinistre REFUSÉ automatiquement (Risque IA Critique).', 'danger');
            // Mettre à jour le badge de statut dans le modal ouvert
            var badge = document.querySelector('#modalDetail .badge');
            if (badge) {
              badge.className = 'badge badge-admin';
              badge.textContent = 'Refusé';
            }
        } else {
            showToast('Analyse antifraud terminée.', 'success');
        }
        render(); 
      }
    } else {
      showToast(json.message || 'Erreur analyse.', 'danger');
    }
  } catch(e) { showToast('Erreur réseau lors de l\'analyse.', 'danger'); }
  finally { if (btn) { btn.classList.remove('loading'); btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Relancer l\'analyse'; } }
}

function renderFraudPanel(data, idSinistre) {
  const panel = document.getElementById('fraudPanel');
  if (!panel) return;
  panel.style.display = '';
  const niveauMap = { faible:'Faible', normal:'Normal', fraude:'FRAUDE' };
  const suggMap   = { accepter:'Accepter', investiguer:'Investiguer', refuser:'Refuser' };
  document.getElementById('fraudScoreNum').textContent  = data.score_global;
  document.getElementById('fraudCircle').className      = 'fraud-score-circle ' + data.niveau_risque;
  document.getElementById('fraudNiveauLabel').className = 'niveau-label ' + data.niveau_risque;
  document.getElementById('fraudNiveauLabel').textContent = niveauMap[data.niveau_risque] || data.niveau_risque;
  const pill = document.getElementById('fraudSuggestionPill');
  pill.innerHTML = `<span class="suggestion-pill ${data.suggestion_ia}"><i class="bi bi-lightning-charge"></i> ${suggMap[data.suggestion_ia] || data.suggestion_ia}</span>`;

  // Barres sous-scores
  const sd = data.scores_detail || {};
  document.getElementById('barTexteVal').textContent   = (sd.texte ?? '—') + '/100';
  document.getElementById('barComportVal').textContent = (sd.comportement ?? '—') + '/100';
  document.getElementById('barContratVal').textContent = (sd.contrat ?? '—') + '/100';
  document.getElementById('barTexte').style.width   = (sd.texte  || 0) + '%';
  document.getElementById('barComport').style.width = (sd.comportement || 0) + '%';
  document.getElementById('barContrat').style.width = (sd.contrat || 0) + '%';

  // Flags
  const flagDefs = [
    { key:'description_vague',   label:'Description vague',     icon:'bi-chat-square-dots' },
    { key:'sinistres_multiples', label:'Sinistres multiples',   icon:'bi-exclamation-triangle' },
    { key:'contrat_recent',      label:'Contrat récent',        icon:'bi-calendar-x' },
    { key:'montant_eleve',       label:'Montant élevé',         icon:'bi-cash-stack' },
    { key:'image_suspecte',      label:'Image suspecte',        icon:'bi-image' },
  ];
  const flags = data.flags || {};
  document.getElementById('fraudFlags').innerHTML = flagDefs.map(f => {
    const active = !!flags[f.key];
    return `<span class="fraud-flag ${active ? 'active' : 'inactive'}"><i class="bi ${f.icon}"></i> ${f.label}</span>`;
  }).join('');

  // Recommandation
  const rec = document.getElementById('fraudRecommandation');
  if (rec) rec.textContent = data.recommandation || '—';

  // Bouton relancer
  const btn = document.getElementById('fraudReanalyseBtn');
  if (btn) btn.setAttribute('onclick', `reanalyserFraud(${idSinistre})`);

  // Date
  const dt = document.getElementById('fraudAnalyseDate');
  if (dt && data.date_analyse) dt.textContent = '— ' + data.date_analyse.substring(0,10);
}

async function loadFraudPanel(idSinistre) {
  const panel = document.getElementById('fraudPanel');
  if (!panel) return;
  panel.style.display = '';
  try {
    const res  = await fetch(FRAUD_GET_API + '?id_sinistre=' + idSinistre);
    const json = await res.json();
    if (json.success && json.data) {
      renderFraudPanel(json.data, idSinistre);
    } else {
      // Pas encore analysé : simple message sans bouton
      panel.innerHTML += '<div style="padding:14px 18px;font-size:13px;color:var(--text-secondary);"><i class="bi bi-info-circle"></i> Aucune analyse disponible. Veuillez passer par l\'onglet "Traitements" pour analyser ce sinistre.</div>';
    }
  } catch(e) { /* silencieux */ }
}

function render() {
  if (currentView === 'kanban') {
    renderKanban();
    return;
  }
  const filtered=getFiltered(), total=filtered.length;
  const pages=Math.ceil(total/perPage)||1;
  if(currentPage>pages) currentPage=pages;
  const slice=filtered.slice((currentPage-1)*perPage,currentPage*perPage);

  const tbody=document.getElementById('sinistreBody');
  const empty=document.getElementById('emptyState');

  if(!slice.length){ tbody.innerHTML=''; empty.style.display='block'; }
  else {
    empty.style.display='none';
    tbody.innerHTML=slice.map(s=>{
      const icon=TYPE_ICONS[s.type]||'bi-shield';
      return `<tr>
        <td><span style="font-family:monospace;font-size:12px;color:var(--accent);">#${s.id}</span></td>
        <td><span style="color:var(--gold);font-weight:600;">${s.contrat}</span></td>
        <td><div class="type-cell"><div class="type-icon"><i class="bi ${icon}"></i></div>${s.type}</div></td>
        <td style="color:var(--text-secondary);">${s.client||'—'}</td>
        <td style="color:var(--text-secondary);">${formatDate(s.date)}</td>
        <td>
          <select class="status-select ${s.statut}" onchange="changeStatut(${s.id},this.value)">
            <option value="en_attente" ${s.statut==='en_attente'?'selected':''}>En attente</option>
            <option value="en_analyse" ${s.statut==='en_analyse' ?'selected':''}>En analyse</option>
            <option value="assigne"    ${s.statut==='assigne'    ?'selected':''}>Assigné</option>
            <option value="en_cours"   ${s.statut==='en_cours'   ?'selected':''}>En cours</option>
            <option value="rembourse"  ${s.statut==='rembourse'  ?'selected':''}>Remboursé</option>
            <option value="refuse"     ${s.statut==='refuse'     ?'selected':''}>Refusé</option>
            <option value="cloture"    ${s.statut==='cloture'    ?'selected':''}>Clôturé</option>
          </select>
        </td>
        <td>${renderFraudBadge(s.fraudScore, s.fraudNiveau)}</td>
        <td>
          <div class="actions">
            <button class="btn btn-outline btn-sm" onclick="viewSinistre(${s.id})" title="Voir"><i class="bi bi-eye"></i></button>
            <button class="btn btn-danger  btn-sm btn-delete-sinistre" onclick="openDeleteModal(${s.id})" title="Supprimer"><i class="bi bi-trash3"></i></button>
          </div>
        </td>
      </tr>`;
    }).join('');
  }

  const start=total===0?0:(currentPage-1)*perPage+1;
  const end=Math.min(currentPage*perPage,total);
  document.getElementById('paginationInfo').textContent=`Affichage ${start}–${end} sur ${total} sinistre${total>1?'s':''}`;
  document.getElementById('paginationBtns').innerHTML=`
    <button class="page-btn" onclick="goPage(${currentPage-1})" ${currentPage<=1?'disabled':''}><i class="bi bi-chevron-left"></i></button>
    ${Array.from({length:pages},(_,i)=>`<button class="page-btn ${i+1===currentPage?'active':''}" onclick="goPage(${i+1})">${i+1}</button>`).join('')}
    <button class="page-btn" onclick="goPage(${currentPage+1})" ${currentPage>=pages?'disabled':''}><i class="bi bi-chevron-right"></i></button>`;

  updateStats();
  updateSortHeaders();
}

function goPage(p){currentPage=p;render();}
function formatDate(d){if(!d)return'—';const[y,m,day]=d.split('-');return`${day}/${m}/${y}`;}

let statutChart = null;
async function fetchDashboardStats() {
    try {
        const res = await fetch('../../api.php?action=sinistre_dashboard_stats');
        const data = await res.json();
        
        document.getElementById('statTotal').textContent = data.total || 0;
        document.getElementById('statAttente').textContent = data.en_attente || 0;
        document.getElementById('statValides').textContent = data.rembourse || 0;
        document.getElementById('statRefuses').textContent = data.refuse || 0;
        
        const lineCtx = document.getElementById('sinistresChart');
        if (lineCtx && data.historique_30j) {
            const labels = data.historique_30j.map(d => {
                const parts = d.jour.split('-');
                return `${parts[2]}/${parts[1]}`;
            });
            const vals = data.historique_30j.map(d => d.total);
            if (sinistresChart) {
                sinistresChart.data.labels = labels;
                sinistresChart.data.datasets[0].data = vals;
                sinistresChart.update();
            } else {
                sinistresChart = new Chart(lineCtx, {
                    type: 'line',
                    data: { labels: labels, datasets: [{ label: 'Sinistres', data: vals, borderColor: '#00b4d8', backgroundColor: 'rgba(0, 180, 216, 0.15)', fill: true }] },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            }
        }
        
        const donutCtx = document.getElementById('statutChart');
        if (donutCtx && data.statuts) {
            const labels = data.statuts.map(d => STATUT_LABELS[d.statut] || d.statut);
            const vals = data.statuts.map(d => d.total);
            const colors = data.statuts.map(d => {
                if (d.statut === 'en_attente' || d.statut === 'en_analyse' || d.statut === 'assigne' || d.statut === 'en_cours') return '#f4a261';
                if (d.statut === 'rembourse' || d.statut === 'valide') return '#2ec4b6';
                if (d.statut === 'refuse') return '#e63946';
                return '#457b9d';
            });
            if (statutChart) {
                statutChart.data.labels = labels;
                statutChart.data.datasets[0].data = vals;
                statutChart.data.datasets[0].backgroundColor = colors;
                statutChart.update();
            } else {
                statutChart = new Chart(donutCtx, {
                    type: 'doughnut',
                    data: { labels: labels, datasets: [{ data: vals, backgroundColor: colors }] },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            }
        }
    } catch(e) {
        console.error('Erreur stats', e);
    }
}

// Override updateStats to use real API instead of local array
function updateStats(){
  fetchDashboardStats();
}


function viewSinistre(id){
  const s=sinistres.find(x=>x.id==id); if(!s)return;
  const icon=TYPE_ICONS[s.type]||'bi-shield';
  const bc=STATUT_BADGE[s.statut]||'badge-agent';
  
  document.getElementById('modalDetailBody').innerHTML=`
    <div class="sinistre-modal-header">
      <div class="sinistre-modal-icon"><i class="bi ${icon}"></i></div>
      <div style="flex:1;">
        <div class="sinistre-modal-type">${s.type}</div>
        <div class="sinistre-modal-id">Dossier #${s.id} · Contrat ${s.contrat}</div>
      </div>
      <span class="badge ${bc}">${STATUT_LABELS[s.statut]}</span>
      <button class="btn btn-sm btn-outline-primary ms-2" onclick="window.open('sinistre_message.php?id_sinistre=${s.id}', 'chatWindow', 'width=450,height=600')">
        <i class="bi bi-chat-dots"></i> Discuter
      </button>
    </div>
    <div class="detail-grid">
      <div class="detail-field"><div class="detail-field-label"><i class="bi bi-hash"></i> ID</div><div class="detail-field-value" style="font-family:monospace;color:var(--accent);">#${s.id}</div></div>
      <div class="detail-field"><div class="detail-field-label"><i class="bi bi-file-earmark-text"></i> Contrat</div><div class="detail-field-value" style="color:var(--gold);">${s.contrat}</div></div>
      <div class="detail-field"><div class="detail-field-label"><i class="bi bi-person"></i> Client</div><div class="detail-field-value">${s.client||'—'}</div></div>
      <div class="detail-field"><div class="detail-field-label"><i class="bi bi-calendar3"></i> Date</div><div class="detail-field-value">${formatDate(s.date)}</div></div>
      <div class="detail-field full"><div class="detail-field-label"><i class="bi bi-chat-left-text"></i> Description</div><div class="detail-field-value" style="color:var(--text-secondary);">${s.description}</div></div>
    </div>

    <!-- TIMELINE SECTION -->
    <div style="margin-top:24px; padding-top:20px; border-top:1px solid var(--glass-border);">
      <div style="font-size:12px; font-weight:700; text-transform:uppercase; color:var(--text-secondary); margin-bottom:15px; letter-spacing:1px;">
        <i class="bi bi-clock-history"></i> Historique du dossier
      </div>
      <div class="timeline" id="sinistreTimeline">
        <div class="skeleton" style="height:50px; margin-bottom:10px;"></div>
        <div class="skeleton" style="height:50px;"></div>
      </div>
    </div>

    <!-- ANTIFRAUD PANEL -->
    <div class="fraud-panel" id="fraudPanel" style="display:none;">
      <div class="fraud-panel-header">
        <div class="fraud-panel-title"><i class="bi bi-shield-shaded"></i> Analyse Antifraud IA <span id="fraudAnalyseDate" style="font-weight:400;font-size:11px;color:var(--text-secondary);margin-left:4px;"></span></div>
      </div>
      <div class="fraud-score-row">
        <div class="fraud-score-circle" id="fraudCircle"><span class="fraud-score-num" id="fraudScoreNum">—</span><span class="fraud-score-denom">/100</span></div>
        <div class="fraud-score-meta">
          <div class="niveau-label" id="fraudNiveauLabel">—</div>
          <div id="fraudSuggestionPill"></div>
          <div style="font-size:12px;color:var(--text-secondary);">Score de risque global calculé par l'IA</div>
        </div>
      </div>
      <div class="fraud-bars">
        <div class="fraud-bar-row"><div class="fraud-bar-label"><span><i class="bi bi-chat-text"></i> Analyse textuelle</span><span id="barTexteVal">—</span></div><div class="fraud-bar-track"><div class="fraud-bar-fill bar-texte" id="barTexte" style="width:0%"></div></div></div>
        <div class="fraud-bar-row"><div class="fraud-bar-label"><span><i class="bi bi-person-lines-fill"></i> Comportement client</span><span id="barComportVal">—</span></div><div class="fraud-bar-track"><div class="fraud-bar-fill bar-comportement" id="barComport" style="width:0%"></div></div></div>
        <div class="fraud-bar-row" style="margin-bottom:0;"><div class="fraud-bar-label"><span><i class="bi bi-file-earmark-text"></i> Profil contrat</span><span id="barContratVal">—</span></div><div class="fraud-bar-track"><div class="fraud-bar-fill bar-contrat" id="barContrat" style="width:0%"></div></div></div>
      </div>
      <div class="fraud-flags" id="fraudFlags"></div>
      <div class="fraud-recommandation" id="fraudRecommandation"></div>
    </div>
    
    <!-- S4 & S7: Comments and Files Area -->
    <div style="margin-top:24px; padding-top:20px; border-top:1px solid var(--glass-border); display:flex; gap:20px;">
        <!-- Files (Drag & Drop) -->
        <div style="flex:1;">
            <div style="font-size:12px; font-weight:700; text-transform:uppercase; color:var(--text-secondary); margin-bottom:15px;"><i class="bi bi-folder"></i> Fichiers & Photos</div>
            <div id="dropZone-${s.id}" style="border: 2px dashed var(--glass-border); border-radius: 8px; padding: 20px; text-align: center; color: var(--text-secondary); cursor: pointer; transition: all 0.3s;"
                 ondragover="event.preventDefault(); this.style.borderColor='var(--accent)';" 
                 ondragleave="this.style.borderColor='var(--glass-border)';" 
                 ondrop="event.preventDefault(); this.style.borderColor='var(--glass-border)'; handleFileUpload(${s.id}, event.dataTransfer.files);">
                <i class="bi bi-cloud-arrow-up" style="font-size:24px;"></i><br>
                Glissez vos fichiers ici ou 
                <label style="color:var(--accent); cursor:pointer;">cliquez<input type="file" multiple style="display:none;" onchange="handleFileUpload(${s.id}, this.files)"></label>
            </div>
            <div id="fileList-${s.id}" style="margin-top: 10px; display: flex; flex-direction: column; gap: 8px;"></div>
        </div>
        
        <!-- Comments -->
        <div style="flex:1;">
            <div style="font-size:12px; font-weight:700; text-transform:uppercase; color:var(--text-secondary); margin-bottom:15px;"><i class="bi bi-chat-dots"></i> Commentaires internes</div>
            <div style="display:flex; gap:8px; margin-bottom: 10px;">
                <textarea id="commentInput-${s.id}" class="form-control" placeholder="Taper un commentaire... (utilisez @prenom_nom pour mentionner)" style="height:60px; font-size:13px;"></textarea>
                <button class="btn btn-primary" onclick="addComment(${s.id})" style="padding: 0 16px;"><i class="bi bi-send"></i></button>
            </div>
            <div id="commentList-${s.id}" style="max-height: 200px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; padding-right: 5px;"></div>
        </div>
    </div>`;

  // Fetch full details (History, Comments, Files)
  fetch('sinistre_details.php?id=' + id)
    .then(r => r.json())
    .then(res => {
      if (res.success && res.data.history) {
        const timeline = document.getElementById('sinistreTimeline');
        timeline.innerHTML = res.data.history.map((h, index) => `
          <div class="timeline-item ${index === res.data.history.length - 1 ? 'active' : ''}">
            <div class="timeline-icon"><i class="bi ${h.icon}"></i></div>
            <div class="timeline-content">
              <div class="timeline-title">${h.event}</div>
              <div class="timeline-meta">
                <span><i class="bi bi-person"></i> ${h.author}</span>
                ${h.date ? `<span><i class="bi bi-calendar-event"></i> ${formatDate(h.date)}</span>` : ''}
              </div>
            </div>
          </div>
        `).join('');
      }
      if (res.success && res.data.comments) {
        renderComments(id, res.data.comments);
      }
      if (res.success && res.data.files) {
        renderFiles(id, res.data.files);
      }
    });

  loadFraudPanel(s.id);
  openModal('modalDetail');
}

function renderComments(id, comments) {
    const list = document.getElementById(`commentList-${id}`);
    if (!list) return;
    list.innerHTML = comments.map(c => `
        <div style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 8px; font-size: 13px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                <span style="font-weight:600; color:var(--accent);">${c.prenom} ${c.nom}</span>
                <span style="color:var(--text-secondary); font-size:11px;">${c.created_at}</span>
            </div>
            <div>${c.commentaire.replace(/@([a-zA-Z0-9_]+)/g, '<span style="color:var(--gold); font-weight:bold;">@$1</span>')}</div>
        </div>
    `).join('');
}

function renderFiles(id, files) {
    const list = document.getElementById(`fileList-${id}`);
    if (!list) return;
    list.innerHTML = files.map(f => `
        <div style="display:flex; align-items:center; gap:10px; background: rgba(255,255,255,0.03); border:1px solid var(--glass-border); padding: 8px 12px; border-radius: 6px; font-size: 13px;">
            <i class="bi bi-file-earmark" style="color:var(--text-secondary);"></i>
            <a href="../../${f.chemin}" target="_blank" style="color:var(--text-primary); text-decoration:none; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${f.nom_fichier}</a>
            <span style="color:var(--text-secondary); font-size:11px;">${Math.round(f.taille/1024)} KB</span>
        </div>
    `).join('');
}

async function addComment(id) {
    const input = document.getElementById(`commentInput-${id}`);
    const text = input.value.trim();
    if (!text) return;
    
    try {
        const res = await fetch('../../api.php?action=sinistre_add_comment', {
            method: 'POST',
            body: JSON.stringify({ id_sinistre: id, commentaire: text })
        });
        const json = await res.json();
        if (json.success) {
            input.value = '';
            // Refresh comments
            const refreshRes = await fetch('sinistre_details.php?id=' + id);
            const refreshJson = await refreshRes.json();
            if (refreshJson.success) renderComments(id, refreshJson.data.comments);
        } else {
            showToast(json.message, 'danger');
        }
    } catch(e) { showToast('Erreur', 'danger'); }
}

async function handleFileUpload(id, files) {
    if (!files.length) return;
    const fd = new FormData();
    fd.append('id_sinistre', id);
    for(let i=0; i<files.length; i++) {
        fd.append('files[]', files[i]);
    }
    
    try {
        const res = await fetch('../../api.php?action=sinistre_upload_files', {
            method: 'POST',
            body: fd
        });
        const json = await res.json();
        if (json.success) {
            showToast('Fichier(s) envoyé(s)', 'success');
            // Refresh files
            const refreshRes = await fetch('sinistre_details.php?id=' + id);
            const refreshJson = await refreshRes.json();
            if (refreshJson.success) renderFiles(id, refreshJson.data.files);
        } else {
            showToast(json.message, 'danger');
        }
    } catch(e) { showToast('Erreur d\'envoi', 'danger'); }
}

function openDeleteModal(id){
  deletingId=id;
  document.getElementById('deleteMsg').textContent=`Vous êtes sur le point de supprimer le dossier #${id}. Cette action est irréversible.`;
  openModal('modalDelete');
}
async function confirmDelete(){
  const res  = await fetch(SINISTRE_DELETE_API + '?id=' + deletingId, { method:'GET' });
  const json = await res.json();
  closeModal('modalDelete');
  if (json.success) {
    sinistres = sinistres.filter(x=>x.id!=deletingId);
    render(); showToast('Sinistre supprimé.','danger');
  } else {
    showToast(json.message,'danger');
  }
}

function openCreateModal(){
  document.getElementById('fContrat').value='';
  document.getElementById('fDescription').value='';
  document.getElementById('fDate').value=new Date().toISOString().split('T')[0];
  document.getElementById('fType').value='Accident auto';
  document.getElementById('fStatut').value='en_attente';
  document.querySelectorAll('.form-error').forEach(e=>e.classList.remove('show'));
  document.querySelectorAll('.form-control').forEach(e=>e.classList.remove('error'));
  openModal('modalCreate');
}


function exportPDF() {
  const month = new Date().toISOString().slice(0, 7);
  window.open('sinistre_export.php?format=pdf&month=' + month, '_blank');
}

function exportExcel() {
  const month = new Date().toISOString().slice(0, 7);
  window.open('sinistre_export.php?format=excel&month=' + month, '_blank');
}

function openModal(id){document.getElementById(id).classList.add('open');document.body.style.overflow='hidden';}
function closeModal(id){document.getElementById(id).classList.remove('open');document.body.style.overflow='';}

document.addEventListener('keydown',e=>{
  if(e.key==='Escape') document.querySelectorAll('.modal-overlay.open').forEach(m=>{m.classList.remove('open');document.body.style.overflow='';});
});
document.querySelectorAll('.modal-overlay').forEach(o=>{
  o.addEventListener('click',e=>{if(e.target===o){o.classList.remove('open');document.body.style.overflow='';}});
});


document.getElementById('searchInput').addEventListener('input',()=>{currentPage=1;render();});
document.getElementById('filterStatut').addEventListener('change',()=>{currentPage=1;render();});
document.getElementById('filterType').addEventListener('change',()=>{currentPage=1;render();});
document.getElementById('filterDate').addEventListener('change',()=>{currentPage=1;render();});

function toggleView(view) {
  currentView = view;
  document.getElementById('listViewBtn').classList.toggle('active', view === 'list');
  document.getElementById('kanbanViewBtn').classList.toggle('active', view === 'kanban');
  
  const tableWrap = document.querySelector('.table-wrap');
  const pagination = document.getElementById('listPagination');
  const kanban = document.getElementById('kanbanContainer');

  if (view === 'list') {
    tableWrap.style.display = 'block';
    pagination.style.display = 'flex';
    kanban.style.display = 'none';
  } else {
    tableWrap.style.display = 'none';
    pagination.style.display = 'none';
    kanban.style.display = 'flex';
  }
  render();
}

function renderKanban() {
  const filtered = getFiltered();
  const columns = {
    en_attente: document.getElementById('kanbanAttente'),
    rembourse:  document.getElementById('kanbanRembourse'),
    refuse:     document.getElementById('kanbanRefuse')
  };

  // Clear columns
  Object.values(columns).forEach(col => col.innerHTML = '');

  filtered.forEach(s => {
    const col = columns[s.statut];
    if (!col) return;

    const card = document.createElement('div');
    card.className = 'kanban-card';
    card.draggable = true;
    card.dataset.id = s.id;
    card.innerHTML = `
      <div class="kanban-card-id">#${s.id}</div>
      <div class="kanban-card-type">${s.type}</div>
      <div class="kanban-card-client">${s.client}</div>
      <div class="kanban-card-footer">
        ${renderFraudBadge(s.fraudScore, s.fraudNiveau)}
        <div class="kanban-card-date">${formatDate(s.date)}</div>
      </div>
    `;
    
    // Drag events
    card.addEventListener('dragstart', () => card.classList.add('dragging'));
    card.addEventListener('dragend', () => card.classList.remove('dragging'));
    card.addEventListener('click', () => viewSinistre(s.id));

    col.appendChild(card);
  });

  // Update counts
  document.getElementById('countAttente').textContent = filtered.filter(s => s.statut === 'en_attente').length;
  document.getElementById('countRembourse').textContent = filtered.filter(s => s.statut === 'rembourse').length;
  document.getElementById('countRefuse').textContent = filtered.filter(s => s.statut === 'refuse').length;

  // Setup drop zones
  document.querySelectorAll('.kanban-column').forEach(column => {
    column.addEventListener('dragover', e => e.preventDefault());
    column.addEventListener('drop', e => {
      e.preventDefault();
      const draggingCard = document.querySelector('.dragging');
      if (!draggingCard) return;
      
      const id = draggingCard.dataset.id;
      const newStatut = column.dataset.statut;
      
      if (id && newStatut) {
        changeStatut(id, newStatut);
      }
    });
  });
  
  updateStats();
}

document.addEventListener('DOMContentLoaded', () => loadSinistres());
async function loadPermissions() {
    try {
        const res = await fetch('get_permissions.php');
        const perms = await res.json();

        if (!perms.canDeleteSinistre) {
            document.querySelectorAll('.btn-delete-sinistre').forEach(b => b.remove());
        }
        if (!perms.canModifySinistre) {
            document.querySelectorAll('.btn-edit-sinistre').forEach(b => b.remove());
        }
        if (!perms.canAssignSinistre) {
            document.querySelectorAll('.btn-assign-sinistre').forEach(b => b.remove());
        }
        if (!perms.canSeeFraudScore) {
            document.querySelectorAll('.fraud-score-col').forEach(el => el.remove());
        }
        if (!perms.canExportSinistres) {
            document.querySelectorAll('.btn-export').forEach(b => b.remove());
        }
    } catch (e) { console.error('Erreur chargement permissions:', e); }
}

// ── S4: @mention autocomplete ──
let mentionUsers = [];

async function loadMentionUsers() {
    try {
        const res = await fetch('../../api.php?action=search_users&role=agent&per_page=50');
        const json = await res.json();
        if (json.success && Array.isArray(json.data)) {
            mentionUsers = json.data.map(u => ({
                id: u.id_user,
                label: (u.prenom || '') + ' ' + (u.nom || ''),
                searchKey: ((u.prenom || '') + (u.nom || '')).toLowerCase()
            }));
        }
    } catch (e) { /* silent */ }
}

document.addEventListener('DOMContentLoaded', () => {
    loadMentionUsers();
});

document.addEventListener('input', function (e) {
    if (e.target.id && e.target.id.startsWith('commentInput-')) {
        const textarea = e.target;
        const pos = textarea.selectionStart;
        const text = textarea.value.substring(0, pos);
        const atIdx = text.lastIndexOf('@');
        if (atIdx >= 0 && (atIdx === 0 || text[atIdx - 1] === ' ')) {
            const query = text.substring(atIdx + 1).toLowerCase();
            const existingDropdown = textarea.parentElement.querySelector('.mention-dropdown');
            if (existingDropdown) existingDropdown.remove();

            if (query.length >= 1) {
                const matches = mentionUsers.filter(u => u.searchKey.includes(query)).slice(0, 6);
                if (matches.length) {
                    const dropdown = document.createElement('div');
                    dropdown.className = 'mention-dropdown';
                    dropdown.style.cssText = 'position:absolute;bottom:100%;left:0;background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:8px;z-index:100;max-height:200px;overflow-y:auto;min-width:200px;box-shadow:0 8px 32px rgba(0,0,0,0.3);';
                    matches.forEach(m => {
                        const item = document.createElement('div');
                        item.style.cssText = 'padding:8px 12px;cursor:pointer;font-size:13px;color:var(--text-primary);display:flex;align-items:center;gap:8px;';
                        item.innerHTML = '<span style="width:24px;height:24px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0;">' + m.label.charAt(0).toUpperCase() + '</span>' + m.label;
                        item.onmouseenter = () => item.style.background = 'rgba(255,255,255,0.08)';
                        item.onmouseleave = () => item.style.background = 'transparent';
                        item.onclick = () => {
                            const before = textarea.value.substring(0, atIdx);
                            const after = textarea.value.substring(pos);
                            textarea.value = before + '@' + m.label.replace(' ', '_') + ' ' + after;
                            dropdown.remove();
                            textarea.focus();
                            const newPos = before.length + m.label.replace(' ', '_').length + 2;
                            textarea.setSelectionRange(newPos, newPos);
                        };
                        dropdown.appendChild(item);
                    });
                    textarea.parentElement.style.position = 'relative';
                    textarea.parentElement.appendChild(dropdown);
                }
            }
        } else {
            const existingDropdown = textarea.parentElement.querySelector('.mention-dropdown');
            if (existingDropdown) existingDropdown.remove();
        }
    }
});

document.addEventListener('blur', function (e) {
    if (e.target.id && e.target.id.startsWith('commentInput-')) {
        setTimeout(() => {
            const dropdown = e.target.parentElement.querySelector('.mention-dropdown');
            if (dropdown) dropdown.remove();
        }, 200);
    }
}, true);

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    loadPermissions();
    loadSinistres();
    setInterval(fetchDashboardStats, 60000); // S1: Auto refresh every 60s
});
