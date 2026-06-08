<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();
$base = defined('BASE_URL') ? BASE_URL : '/assurance';
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Simulateur de devis — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Obtenez une estimation instantanée de votre prime d'assurance avec le simulateur intelligent Protex.">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&family=Inter:wght@300;400;500;600&display=swap">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --primary: #1A3A7A; --accent: #FF6B1A; --success: #2EC4B6;
            --danger: #e63946; --warning: #EF9F27;
            --bg: #F4F6FB; --card: #fff; --border: rgba(26,58,122,0.10);
            --text: #15233C; --muted: #6B7A90;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg); font-family: 'Inter', sans-serif; color: var(--text); min-height: 100vh; }

        /* ── Top Bar ────────────────────────────────────── */
        .top-bar {
            background: linear-gradient(135deg, #0f2557, #1A3A7A);
            padding: 16px 32px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 4px 20px rgba(15,37,87,0.3);
        }
        .logo { font-family:'Outfit',sans-serif; font-size:22px; font-weight:900; color:#fff; letter-spacing:-0.5px; }
        .logo span { color: var(--accent); }
        .back-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 9px 18px; background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2); border-radius: 50px;
            color: #fff; font-size: 13px; font-weight: 600; text-decoration: none;
            transition: all 0.2s;
        }
        .back-btn:hover { background: rgba(255,255,255,0.22); color: #fff; }

        /* ── Wizard Shell ────────────────────────────────── */
        .wizard-wrap {
            max-width: 760px; margin: 48px auto 80px; padding: 0 20px;
        }
        .wizard-title {
            font-family: 'Outfit', sans-serif;
            font-size: 30px; font-weight: 900; color: var(--text);
            margin-bottom: 6px;
        }
        .wizard-sub { font-size: 14px; color: var(--muted); margin-bottom: 32px; }

        /* ── Progress Steps ──────────────────────────────── */
        .wizard-steps {
            display: flex; gap: 0; margin-bottom: 36px;
            position: relative;
        }
        .wizard-steps::before {
            content: '';
            position: absolute; top: 20px; left: 24px; right: 24px;
            height: 3px; background: rgba(26,58,122,0.10); z-index: 0;
        }
        .wz-step {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; gap: 8px; position: relative; z-index: 1;
        }
        .wz-circle {
            width: 42px; height: 42px; border-radius: 50%;
            background: rgba(26,58,122,0.07); border: 3px solid rgba(26,58,122,0.12);
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; color: var(--muted); font-weight: 700;
            transition: all 0.35s cubic-bezier(0.34,1.56,0.64,1);
        }
        .wz-step.active .wz-circle {
            background: var(--accent); border-color: var(--accent); color: #fff;
            box-shadow: 0 0 0 6px rgba(255,107,26,0.18);
        }
        .wz-step.done .wz-circle {
            background: var(--success); border-color: var(--success); color: #fff;
        }
        .wz-label {
            font-size: 10px; font-weight: 700; color: var(--muted);
            text-transform: uppercase; letter-spacing: 0.06em; text-align: center;
        }
        .wz-step.active .wz-label { color: var(--accent); }
        .wz-step.done .wz-label   { color: var(--success); }

        /* ── Card ────────────────────────────────────────── */
        .wz-card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 24px; padding: 36px 32px;
            box-shadow: 0 8px 32px rgba(26,58,122,0.08);
        }
        .wz-card-title {
            font-family: 'Outfit', sans-serif;
            font-size: 20px; font-weight: 800; color: var(--text);
            margin-bottom: 6px;
        }
        .wz-card-sub { font-size: 13px; color: var(--muted); margin-bottom: 28px; }

        /* Step panel */
        .wz-panel { display: none; animation: fadeUp 0.35s ease; }
        .wz-panel.active { display: block; }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Step 1: Insurance Type Cards ────────────────── */
        .type-grid {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px;
        }
        @media(max-width:500px) { .type-grid { grid-template-columns: 1fr; } }

        .type-card {
            border: 2px solid var(--border); border-radius: 16px;
            padding: 22px 18px; cursor: pointer;
            transition: all 0.25s ease; background: #f8f9ff;
            display: flex; flex-direction: column; align-items: center; gap: 10px;
            text-align: center;
        }
        .type-card:hover { border-color: rgba(255,107,26,0.4); background: rgba(255,107,26,0.03); }
        .type-card.selected {
            border-color: var(--accent); background: rgba(255,107,26,0.06);
            box-shadow: 0 0 0 4px rgba(255,107,26,0.10);
        }
        .type-card-icon {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
        }
        .type-card-name { font-weight: 700; font-size: 14px; color: var(--text); }
        .type-card-desc { font-size: 11px; color: var(--muted); line-height: 1.5; }

        /* ── Step 2: Sliders ─────────────────────────────── */
        .slider-group { margin-bottom: 24px; }
        .slider-label {
            display: flex; justify-content: space-between; align-items: center;
            font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 10px;
        }
        .slider-val {
            font-family: 'Outfit', sans-serif; font-weight: 800;
            color: var(--accent); font-size: 15px;
        }
        input[type=range] {
            -webkit-appearance: none; width: 100%; height: 6px;
            border-radius: 3px; background: rgba(26,58,122,0.12); outline: none;
        }
        input[type=range]::-webkit-slider-thumb {
            -webkit-appearance: none; width: 20px; height: 20px;
            border-radius: 50%; background: var(--accent); cursor: pointer;
            box-shadow: 0 2px 8px rgba(255,107,26,0.4);
            transition: transform 0.2s;
        }
        input[type=range]::-webkit-slider-thumb:hover { transform: scale(1.2); }

        /* ── Step 3: Coverage ────────────────────────────── */
        .coverage-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px;
            margin-bottom: 28px;
        }
        @media(max-width:560px) { .coverage-grid { grid-template-columns: 1fr; } }

        .coverage-card {
            border: 2px solid var(--border); border-radius: 16px;
            padding: 20px 16px; cursor: pointer; text-align: center;
            transition: all 0.25s ease; background: #f8f9ff;
        }
        .coverage-card:hover { border-color: rgba(26,58,122,0.4); }
        .coverage-card.selected {
            border-color: var(--primary); background: rgba(26,58,122,0.05);
            box-shadow: 0 0 0 4px rgba(26,58,122,0.08);
        }
        .coverage-name { font-weight: 800; font-size: 15px; color: var(--text); margin-bottom: 6px; }
        .coverage-mult { font-size: 12px; color: var(--muted); margin-bottom: 10px; }
        .coverage-features { font-size: 11px; color: var(--muted); line-height: 1.8; }

        .price-display {
            background: linear-gradient(135deg, #0f2557, #1A3A7A);
            color: #fff; border-radius: 16px; padding: 24px;
            text-align: center; margin-bottom: 24px;
        }
        .price-label { font-size: 12px; opacity: 0.7; text-transform: uppercase; letter-spacing: 0.08em; }
        .price-amount {
            font-family: 'Outfit', sans-serif;
            font-size: 44px; font-weight: 900; line-height: 1;
            color: #fff; margin: 8px 0 4px;
        }
        .price-amount span { font-size: 20px; font-weight: 400; color: rgba(255,255,255,0.7); }
        .price-sub { font-size: 12px; opacity: 0.6; }
        .price-chart-wrap { max-width: 200px; margin: 0 auto 0; }

        /* ── Step 4: Summary ─────────────────────────────── */
        .summary-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 14px 0; border-bottom: 1px solid rgba(26,58,122,0.07);
            font-size: 14px;
        }
        .summary-row:last-of-type { border-bottom: none; }
        .summary-key { color: var(--muted); font-weight: 500; }
        .summary-val { color: var(--text); font-weight: 700; }
        .summary-total {
            background: linear-gradient(135deg, #0f2557, #1A3A7A);
            color: #fff; border-radius: 16px; padding: 20px 24px;
            display: flex; justify-content: space-between; align-items: center;
            margin: 20px 0;
        }
        .summary-total-label { font-size: 14px; opacity: 0.8; }
        .summary-total-val {
            font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 900;
        }

        /* ── Nav Buttons ─────────────────────────────────── */
        .wz-nav {
            display: flex; gap: 12px; margin-top: 28px;
        }
        .btn-wz {
            flex: 1; padding: 14px 20px; border-radius: 12px;
            font-size: 14px; font-weight: 700; cursor: pointer;
            border: none; transition: all 0.2s; display: flex;
            align-items: center; justify-content: center; gap: 8px;
        }
        .btn-wz-next {
            background: linear-gradient(135deg, var(--accent), #e05a0f);
            color: #fff; box-shadow: 0 4px 16px rgba(255,107,26,0.3);
        }
        .btn-wz-next:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(255,107,26,0.4); }
        .btn-wz-prev {
            background: rgba(26,58,122,0.07); color: var(--primary);
            border: 1px solid rgba(26,58,122,0.15);
        }
        .btn-wz-prev:hover { background: rgba(26,58,122,0.12); }
        .btn-wz-submit {
            background: linear-gradient(135deg, var(--success), #1a9e94);
            color: #fff; box-shadow: 0 4px 16px rgba(46,196,182,0.3);
        }
        .btn-wz-submit:hover { transform: translateY(-2px); }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="logo">Prot<span>ex</span></div>
    <a href="client.php" class="back-btn"><i class="bi bi-arrow-left"></i> Tableau de bord</a>
</div>

<div class="wizard-wrap">
    <h1 class="wizard-title"><i class="bi bi-calculator" style="color:var(--accent)"></i> Simulateur de devis</h1>
    <p class="wizard-sub">Obtenez une estimation instantanée de votre prime en 4 étapes simples.</p>

    <!-- Step Indicators -->
    <div class="wizard-steps" id="wizardSteps">
        <div class="wz-step active" id="stp-1">
            <div class="wz-circle"><i class="bi bi-shield-check"></i></div>
            <span class="wz-label">Assurance</span>
        </div>
        <div class="wz-step" id="stp-2">
            <div class="wz-circle"><i class="bi bi-sliders"></i></div>
            <span class="wz-label">Paramètres</span>
        </div>
        <div class="wz-step" id="stp-3">
            <div class="wz-circle"><i class="bi bi-layers"></i></div>
            <span class="wz-label">Couverture</span>
        </div>
        <div class="wz-step" id="stp-4">
            <div class="wz-circle"><i class="bi bi-check2-circle"></i></div>
            <span class="wz-label">Récapitulatif</span>
        </div>
    </div>

    <div class="wz-card">

        <!-- ── STEP 1: Type ─────────────────────────────── -->
        <div class="wz-panel active" id="panel-1">
            <div class="wz-card-title">Quel type d'assurance vous intéresse ?</div>
            <div class="wz-card-sub">Sélectionnez la couverture adaptée à vos besoins.</div>
            <div class="type-grid" id="typeGrid">
                <!-- injected by JS -->
            </div>
            <div class="wz-nav">
                <button class="btn-wz btn-wz-next" onclick="nextStep(2)">
                    Continuer <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- ── STEP 2: Parameters ─────────────────────── -->
        <div class="wz-panel" id="panel-2">
            <div class="wz-card-title">Ajustez vos paramètres</div>
            <div class="wz-card-sub">Personnalisez les critères de votre devis.</div>
            <div id="slidersContainer">
                <!-- injected dynamically per type -->
            </div>
            <div class="wz-nav">
                <button class="btn-wz btn-wz-prev" onclick="prevStep(1)"><i class="bi bi-arrow-left"></i> Retour</button>
                <button class="btn-wz btn-wz-next" onclick="nextStep(3)">Continuer <i class="bi bi-arrow-right"></i></button>
            </div>
        </div>

        <!-- ── STEP 3: Coverage ────────────────────────── -->
        <div class="wz-panel" id="panel-3">
            <div class="wz-card-title">Choisissez votre niveau de couverture</div>
            <div class="wz-card-sub">Comparez et sélectionnez l'offre qui vous convient.</div>
            <div class="coverage-grid">
                <div class="coverage-card" onclick="selectCoverage('essential', this, 1.0)">
                    <div class="coverage-name">🛡️ Essentiel</div>
                    <div class="coverage-mult">Couverture de base</div>
                    <div class="coverage-features">✓ Responsabilité civile<br>✓ Assistance 24/7<br>✗ Tous risques<br>✗ Capital décès</div>
                </div>
                <div class="coverage-card selected" onclick="selectCoverage('premium', this, 1.5)">
                    <div class="coverage-name">⭐ Premium</div>
                    <div class="coverage-mult">Recommandé</div>
                    <div class="coverage-features">✓ Responsabilité civile<br>✓ Assistance 24/7<br>✓ Tous risques<br>✗ Capital décès</div>
                </div>
                <div class="coverage-card" onclick="selectCoverage('platinum', this, 2.0)">
                    <div class="coverage-name">💎 Platine</div>
                    <div class="coverage-mult">Couverture totale</div>
                    <div class="coverage-features">✓ Responsabilité civile<br>✓ Assistance 24/7<br>✓ Tous risques<br>✓ Capital décès</div>
                </div>
            </div>
            <div class="price-display">
                <div class="price-label">Estimation de votre prime</div>
                <div class="price-amount" id="priceDisplay">—<span> DT/an</span></div>
                <div class="price-sub">Estimation indicative hors taxes</div>
            </div>
            <div class="price-chart-wrap">
                <canvas id="coverageChart" height="180"></canvas>
            </div>
            <div class="wz-nav" style="margin-top:20px;">
                <button class="btn-wz btn-wz-prev" onclick="prevStep(2)"><i class="bi bi-arrow-left"></i> Retour</button>
                <button class="btn-wz btn-wz-next" onclick="nextStep(4)">Continuer <i class="bi bi-arrow-right"></i></button>
            </div>
        </div>

        <!-- ── STEP 4: Summary ────────────────────────── -->
        <div class="wz-panel" id="panel-4">
            <div class="wz-card-title">✅ Récapitulatif de votre devis</div>
            <div class="wz-card-sub">Vérifiez les informations avant de soumettre.</div>
            <div id="summaryContent"><!-- injected by JS --></div>
            <div class="summary-total">
                <span class="summary-total-label">Prime annuelle estimée</span>
                <span class="summary-total-val" id="summaryPrice">—</span>
            </div>
            <div class="wz-nav">
                <button class="btn-wz btn-wz-prev" onclick="prevStep(3)"><i class="bi bi-arrow-left"></i> Modifier</button>
                <button class="btn-wz btn-wz-submit" onclick="submitDevis()">
                    <i class="bi bi-send-check"></i> Soumettre le devis
                </button>
            </div>
        </div>
    </div>
</div>

<script>
/* ── Insurance Types ─────────────────────────────────────── */
const TYPES = [
    { id:'auto',       name:'Auto',       icon:'bi-car-front',       bg:'rgba(26,58,122,0.10)',  color:'#1A3A7A', desc:'Véhicule personnel ou professionnel', base:450 },
    { id:'habitation', name:'Habitation', icon:'bi-house-heart',     bg:'rgba(255,107,26,0.10)', color:'#FF6B1A', desc:'Appartement, maison, local commercial', base:360 },
    { id:'sante',      name:'Santé',      icon:'bi-heart-pulse',     bg:'rgba(46,196,182,0.10)', color:'#2EC4B6', desc:'Frais médicaux et hospitalisation', base:520 },
    { id:'vie',        name:'Vie',        icon:'bi-person-heart',    bg:'rgba(239,159,39,0.10)', color:'#EF9F27', desc:'Capital décès et invalidité', base:380 },
];

const SLIDERS = {
    auto: [
        { id:'age',    label:'Âge du véhicule (ans)', min:1, max:20, val:5, unit:' ans', factor: -5 },
        { id:'km',     label:'Kilométrage annuel (km)', min:5000, max:60000, val:15000, step:1000, unit:' km', factor: 0.003 },
        { id:'driver', label:'Âge du conducteur (ans)', min:18, max:80, val:35, unit:' ans', factor:-1 },
    ],
    habitation: [
        { id:'surface', label:'Surface (m²)', min:20, max:400, val:80, unit:' m²', factor:2 },
        { id:'valeur',  label:'Valeur mobilier (DT)', min:5000, max:100000, val:20000, step:1000, unit:' DT', factor:0.002 },
        { id:'etage',   label:'Étage', min:0, max:20, val:2, unit:'', factor:1 },
    ],
    sante: [
        { id:'age',     label:'Âge assuré (ans)', min:18, max:80, val:35, unit:' ans', factor:3 },
        { id:'persons', label:'Nombre de personnes', min:1, max:8, val:2, unit:' pers.', factor:80 },
    ],
    vie: [
        { id:'age',     label:'Âge assuré (ans)', min:18, max:70, val:35, unit:' ans', factor:5 },
        { id:'capital', label:'Capital décès (DT)', min:10000, max:500000, val:100000, step:5000, unit:' DT', factor:0.001 },
    ],
};

let state = {
    step: 1,
    typeId: 'auto',
    sliderVals: {},
    coverageId: 'premium',
    coverageMult: 1.5,
    basePrice: 450,
    finalPrice: 675,
};
let coverageChart = null;

/* ── Render types ────────────────────────────────────────── */
function renderTypes() {
    const grid = document.getElementById('typeGrid');
    grid.innerHTML = TYPES.map(t => `
        <div class="type-card ${t.id === state.typeId ? 'selected' : ''}"
             onclick="selectType('${t.id}', this)">
            <div class="type-card-icon" style="background:${t.bg};color:${t.color};">
                <i class="bi ${t.icon}"></i>
            </div>
            <div class="type-card-name">${t.name}</div>
            <div class="type-card-desc">${t.desc}</div>
        </div>`).join('');
}

function selectType(id, el) {
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    state.typeId = id;
    const t = TYPES.find(x => x.id === id);
    state.basePrice = t ? t.base : 400;
}

/* ── Render sliders ──────────────────────────────────────── */
function renderSliders() {
    const sliders = SLIDERS[state.typeId] || [];
    state.sliderVals = {};
    const cont = document.getElementById('slidersContainer');
    cont.innerHTML = sliders.map(s => {
        state.sliderVals[s.id] = s.val;
        return `
        <div class="slider-group">
            <div class="slider-label">
                <span>${s.label}</span>
                <span class="slider-val" id="val-${s.id}">${s.val.toLocaleString('fr-FR')}${s.unit}</span>
            </div>
            <input type="range" min="${s.min}" max="${s.max}" value="${s.val}"
                   step="${s.step || 1}" id="slider-${s.id}"
                   oninput="onSlider('${s.id}', this.value, '${s.unit}')">
        </div>`;
    }).join('');
}

function onSlider(id, val, unit) {
    state.sliderVals[id] = parseFloat(val);
    const disp = parseFloat(val).toLocaleString('fr-FR');
    document.getElementById('val-' + id).textContent = disp + unit;
    recalcPrice();
}

/* ── Price calculation ───────────────────────────────────── */
function recalcPrice() {
    const sliders = SLIDERS[state.typeId] || [];
    let adjustedBase = state.basePrice;
    sliders.forEach(s => {
        const v = state.sliderVals[s.id] ?? s.val;
        adjustedBase += v * s.factor;
    });
    adjustedBase = Math.max(100, adjustedBase);
    state.finalPrice = Math.round(adjustedBase * state.coverageMult);
    updatePriceDisplay();
}

function updatePriceDisplay() {
    const el = document.getElementById('priceDisplay');
    if (el) el.innerHTML = state.finalPrice.toLocaleString('fr-FR') + '<span> DT/an</span>';
    const sp = document.getElementById('summaryPrice');
    if (sp) sp.textContent = state.finalPrice.toLocaleString('fr-FR') + ' DT';
    updateChart();
}

/* ── Coverage ────────────────────────────────────────────── */
function selectCoverage(id, el, mult) {
    document.querySelectorAll('.coverage-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    state.coverageId   = id;
    state.coverageMult = mult;
    recalcPrice();
}

/* ── Chart ───────────────────────────────────────────────── */
function buildChart() {
    const ctx = document.getElementById('coverageChart');
    if (!ctx) return;
    const typeInfo = TYPES.find(t => t.id === state.typeId) || TYPES[0];
    coverageChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Couverture de base', 'Garanties optionnelles', 'Assistance'],
            datasets: [{
                data: [55, 30, 15],
                backgroundColor: [typeInfo.color, '#FF6B1A', '#2EC4B6'],
                borderWidth: 0,
                hoverOffset: 6,
            }]
        },
        options: {
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { size: 11, family: 'Inter' }, padding: 12 }
                },
                tooltip: { enabled: true }
            },
            animation: { animateRotate: true, duration: 700 }
        }
    });
}

function updateChart() {
    if (!coverageChart) return;
    const ess = Math.round(state.finalPrice * 0.55);
    const opt = Math.round(state.finalPrice * 0.30);
    const ast = Math.round(state.finalPrice * 0.15);
    coverageChart.data.datasets[0].data = [ess, opt, ast];
    coverageChart.update();
}

/* ── Summary ─────────────────────────────────────────────── */
function renderSummary() {
    const typeInfo     = TYPES.find(t => t.id === state.typeId);
    const sliders      = SLIDERS[state.typeId] || [];
    const coverageNames = { essential:'Essentiel 🛡️', premium:'Premium ⭐', platinum:'Platine 💎' };
    let rows = [
        { key: "Type d'assurance", val: typeInfo ? typeInfo.name : '—' },
        { key: "Niveau de couverture", val: coverageNames[state.coverageId] || '—' },
    ];
    sliders.forEach(s => {
        const v = state.sliderVals[s.id] ?? s.val;
        rows.push({ key: s.label, val: v.toLocaleString('fr-FR') + s.unit });
    });
    document.getElementById('summaryContent').innerHTML =
        rows.map(r => `<div class="summary-row"><span class="summary-key">${r.key}</span><span class="summary-val">${r.val}</span></div>`).join('');
    document.getElementById('summaryPrice').textContent = state.finalPrice.toLocaleString('fr-FR') + ' DT';
}

/* ── Navigation ──────────────────────────────────────────── */
function nextStep(to) {
    if (to === 2) { if (!state.typeId) { alert('Choisissez un type d\'assurance.'); return; } renderSliders(); recalcPrice(); }
    if (to === 3) { if (!coverageChart) buildChart(); recalcPrice(); }
    if (to === 4) renderSummary();
    goTo(to);
}

function prevStep(to) { goTo(to); }

function goTo(n) {
    document.querySelectorAll('.wz-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + n).classList.add('active');
    for (let i = 1; i <= 4; i++) {
        const s = document.getElementById('stp-' + i);
        s.classList.remove('active', 'done');
        if (i < n)  s.classList.add('done');
        if (i === n) s.classList.add('active');
    }
    state.step = n;
}

async function submitDevis() {
    const typeInfo = TYPES.find(t => t.id === state.typeId);
    const form = new FormData();
    form.append('type_contrat', typeInfo ? typeInfo.name : state.typeId);
    form.append('couverture',   state.coverageId);
    form.append('prime_estimee', state.finalPrice);

    try {
        const res  = await fetch('<?= $base ?>/view/FrontOffice/generer_contrat_besoin.php', { method:'POST', body:form });
        const json = await res.json();
        if (json.success || json.id) {
            window.location.href = 'devis.php?success=1';
        } else {
            window.location.href = 'devis.php';
        }
    } catch(_) {
        window.location.href = 'ajoutdevis.php';
    }
}

/* ── Init ────────────────────────────────────────────────── */
renderTypes();
renderSliders();
recalcPrice();
</script>
</body>
</html>
