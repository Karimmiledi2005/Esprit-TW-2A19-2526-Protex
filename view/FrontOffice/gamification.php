<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();

$base   = defined('BASE_URL') ? BASE_URL : '/assurance';
$userId = (int)($_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 0);
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Hub Fidélité — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Votre espace fidélité Protex : points, paliers, mini-jeux et récompenses.">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&family=Inter:wght@300;400;500;600&display=swap">
    <style>
        :root {
            --primary:  #1A3A7A;
            --accent:   #FF6B1A;
            --gold:     #EF9F27;
            --success:  #2EC4B6;
            --danger:   #e63946;
            --bg:       #F4F6FB;
            --card:     #FFFFFF;
            --border:   rgba(26,58,122,0.10);
            --text:     #15233C;
            --muted:    #6B7A90;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--bg);
            font-family: 'Inter', sans-serif;
            color: var(--text);
            min-height: 100vh;
        }

        /* ── Hero Banner ─────────────────────────────────── */
        .hero {
            background: linear-gradient(135deg, #0f2557 0%, #1A3A7A 50%, #2a4f9e 100%);
            padding: 48px 32px 80px;
            position: relative; overflow: hidden;
            text-align: center;
        }
        .hero::before {
            content: '';
            position: absolute; inset: 0;
            background:
                radial-gradient(circle at 20% 50%, rgba(255,107,26,0.18) 0%, transparent 50%),
                radial-gradient(circle at 80% 30%, rgba(46,196,182,0.15) 0%, transparent 50%);
        }
        .hero-logo {
            font-family: 'Outfit', sans-serif;
            font-size: 24px; font-weight: 900;
            color: #fff; letter-spacing: -0.5px;
            position: relative; z-index: 1;
            margin-bottom: 24px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .hero-logo span { color: var(--accent); }
        .back-btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 18px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50px; color: #fff;
            font-size: 13px; font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .back-btn:hover { background: rgba(255,255,255,0.22); color: #fff; }

        .hero-title {
            font-family: 'Outfit', sans-serif;
            font-size: 36px; font-weight: 900;
            color: #fff; position: relative; z-index: 1;
            margin-bottom: 10px;
        }
        .hero-sub {
            font-size: 16px; color: rgba(255,255,255,0.75);
            position: relative; z-index: 1;
        }

        /* ── Points Display ──────────────────────────────── */
        .points-globe {
            position: relative; z-index: 1;
            margin: 24px auto 0;
            display: inline-block;
        }
        .points-circle {
            width: 140px; height: 140px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(12px);
            border: 3px solid rgba(255,255,255,0.25);
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            margin: 0 auto;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3),
                        inset 0 1px 0 rgba(255,255,255,0.2);
            animation: globeFloat 3s ease-in-out infinite;
        }
        @keyframes globeFloat {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-8px); }
        }
        .points-number {
            font-family: 'Outfit', sans-serif;
            font-size: 42px; font-weight: 900;
            color: var(--gold); line-height: 1;
        }
        .points-label {
            font-size: 11px; font-weight: 700;
            color: rgba(255,255,255,0.7);
            text-transform: uppercase; letter-spacing: 0.1em;
            margin-top: 4px;
        }

        /* ── Main Content ────────────────────────────────── */
        .page { max-width: 960px; margin: -40px auto 60px; padding: 0 20px; }

        .section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 18px; font-weight: 800;
            color: var(--text); margin: 32px 0 16px;
            display: flex; align-items: center; gap: 8px;
        }

        /* ── Tier Cards ──────────────────────────────────── */
        .tier-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }
        @media(max-width: 700px) { .tier-grid { grid-template-columns: repeat(2,1fr); } }
        @media(max-width: 400px) { .tier-grid { grid-template-columns: 1fr; } }

        .tier-card {
            background: var(--card);
            border: 2px solid var(--border);
            border-radius: 20px;
            padding: 24px 16px;
            text-align: center;
            position: relative; overflow: hidden;
            transition: all 0.3s ease;
        }
        .tier-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 32px rgba(26,58,122,0.12);
        }
        .tier-card.active-tier {
            border-width: 2px;
            box-shadow: 0 0 0 4px rgba(26,58,122,0.08);
        }
        .tier-card.active-tier::before {
            content: '✓ Actuel';
            position: absolute; top: 0; right: 0;
            background: var(--primary); color: #fff;
            font-size: 10px; font-weight: 700;
            padding: 4px 10px;
            border-bottom-left-radius: 10px;
        }
        .tier-emoji { font-size: 36px; display: block; margin-bottom: 8px; }
        .tier-name {
            font-family: 'Outfit', sans-serif;
            font-size: 16px; font-weight: 800; color: var(--text);
        }
        .tier-pts {
            font-size: 11px; color: var(--muted);
            margin: 4px 0 12px;
        }
        .tier-perk {
            font-size: 12px; color: var(--text);
            background: rgba(26,58,122,0.05);
            border-radius: 8px; padding: 6px 10px;
            margin-bottom: 5px;
        }

        /* ── Progress Bar to next tier ───────────────────── */
        .progress-section {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px 28px;
            box-shadow: 0 4px 20px rgba(26,58,122,0.06);
        }
        .progress-bar-wrap {
            height: 12px; background: rgba(26,58,122,0.08);
            border-radius: 999px; overflow: hidden; margin: 12px 0 6px;
        }
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: inherit;
            transition: width 1.2s cubic-bezier(0.4,0,0.2,1);
        }
        .progress-meta {
            display: flex; justify-content: space-between;
            font-size: 12px; color: var(--muted);
        }

        /* ── Mini-games ──────────────────────────────────── */
        .games-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        @media(max-width: 600px) { .games-grid { grid-template-columns: 1fr; } }

        .game-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px 20px;
            text-align: center;
            text-decoration: none; color: inherit;
            transition: all 0.3s ease;
            display: block;
            position: relative; overflow: hidden;
        }
        .game-card::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, var(--accent), var(--primary));
            opacity: 0;
            transition: opacity 0.3s;
        }
        .game-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 48px rgba(26,58,122,0.18);
            color: inherit;
        }
        .game-card:hover::after { opacity: 0.05; }

        .game-icon {
            font-size: 48px; display: block;
            margin-bottom: 14px;
            transition: transform 0.3s ease;
        }
        .game-card:hover .game-icon { transform: scale(1.1) rotate(-5deg); }

        .game-name {
            font-family: 'Outfit', sans-serif;
            font-size: 16px; font-weight: 800; color: var(--text);
            margin-bottom: 6px;
        }
        .game-desc { font-size: 12px; color: var(--muted); line-height: 1.5; }
        .game-pts {
            display: inline-flex; align-items: center; gap: 4px;
            margin-top: 12px; padding: 4px 12px;
            background: rgba(239,159,39,0.12); color: #b97a00;
            border-radius: 20px; font-size: 11px; font-weight: 700;
        }

        /* ── History ─────────────────────────────────────── */
        .history-list { list-style: none; }
        .history-item {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 20px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px; margin-bottom: 10px;
            transition: transform 0.2s ease;
        }
        .history-item:hover { transform: translateX(5px); }
        .history-icon {
            width: 38px; height: 38px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; flex-shrink: 0;
        }
        .h-earn { background: rgba(46,196,182,0.12); color: var(--success); }
        .h-spend { background: rgba(255,107,26,0.12); color: var(--accent); }
        .history-desc { flex: 1; font-size: 13px; color: var(--text); }
        .history-date { font-size: 11px; color: var(--muted); }
        .history-pts {
            font-family: 'Outfit', sans-serif;
            font-size: 15px; font-weight: 800;
        }
        .pts-earn  { color: var(--success); }
        .pts-spend { color: var(--danger); }

        /* ── Confetti canvas ─────────────────────────────── */
        #confettiCanvas {
            position: fixed; inset: 0;
            pointer-events: none; z-index: 9999;
        }
    </style>
</head>
<body>

<canvas id="confettiCanvas"></canvas>

<!-- ── Hero ─────────────────────────────────────────────────── -->
<div class="hero">
    <div class="hero-logo">
        <span>Prot<span>ex</span></span>
        <a href="client.php" class="back-btn"><i class="bi bi-arrow-left"></i> Tableau de bord</a>
    </div>
    <h1 class="hero-title">🏆 Hub Fidélité</h1>
    <p class="hero-sub">Accumulez des points, montez en palier et déverrouillez des récompenses exclusives.</p>
    <div class="points-globe">
        <div class="points-circle">
            <div class="points-number" id="userPoints">—</div>
            <div class="points-label">Points</div>
        </div>
    </div>
</div>

<!-- ── Page ─────────────────────────────────────────────────── -->
<div class="page">

    <!-- Progress to next tier -->
    <h2 class="section-title"><i class="bi bi-bar-chart-steps" style="color:var(--accent)"></i> Progression vers le palier suivant</h2>
    <div class="progress-section">
        <div id="tierLabel" style="font-family:'Outfit',sans-serif; font-size:16px; font-weight:800; color:var(--text); margin-bottom:2px;">—</div>
        <div id="tierSub"   style="font-size:12px; color:var(--muted);">Chargement…</div>
        <div class="progress-bar-wrap">
            <div class="progress-bar-fill" id="tierProgressBar" style="width:0%"></div>
        </div>
        <div class="progress-meta">
            <span id="tierFrom">0 pts</span>
            <span id="tierTo">—</span>
        </div>
    </div>

    <!-- Tiers -->
    <h2 class="section-title"><i class="bi bi-trophy" style="color:var(--gold)"></i> Paliers de fidélité</h2>
    <div class="tier-grid" id="tierGrid">
        <!-- injected by JS -->
    </div>

    <!-- Mini-games -->
    <h2 class="section-title"><i class="bi bi-controller" style="color:var(--primary)"></i> Mini-jeux</h2>
    <div class="games-grid">
        <a href="roulette.php" class="game-card">
            <span class="game-icon">🎰</span>
            <div class="game-name">Roulette</div>
            <div class="game-desc">Tentez votre chance pour gagner des points et des réductions.</div>
            <div class="game-pts"><i class="bi bi-star-fill"></i> +50 pts max</div>
        </a>
        <a href="snake.php" class="game-card">
            <span class="game-icon">🐍</span>
            <div class="game-name">Snake</div>
            <div class="game-desc">Guidez le serpent pour marquer un max de points.</div>
            <div class="game-pts"><i class="bi bi-star-fill"></i> +30 pts max</div>
        </a>
        <a href="memory.php" class="game-card">
            <span class="game-icon">🧠</span>
            <div class="game-name">Memory</div>
            <div class="game-desc">Trouvez toutes les paires pour débloquer vos récompenses.</div>
            <div class="game-pts"><i class="bi bi-star-fill"></i> +40 pts max</div>
        </a>
    </div>

    <!-- History -->
    <h2 class="section-title"><i class="bi bi-clock-history" style="color:var(--muted)"></i> Historique des points</h2>
    <ul class="history-list" id="historyList">
        <!-- injected by JS -->
    </ul>
</div>

<script>
/* ── Tiers Definition ─────────────────────────────────────── */
const TIERS = [
    { name: 'Bronze',   emoji: '🥉', min: 0,    max: 299,  color: '#CD7F32',
      perks: ['Accès mini-jeux', 'Newsletter mensuelle'] },
    { name: 'Argent',   emoji: '🥈', min: 300,  max: 699,  color: '#A8A9AD',
      perks: ['5% sur la prochaine prime', 'Support prioritaire'] },
    { name: 'Or',       emoji: '🥇', min: 700,  max: 1499, color: '#EF9F27',
      perks: ['10% sur les contrats', 'Conseiller dédié'] },
    { name: 'Platine',  emoji: '💎', min: 1500, max: Infinity, color: '#6a5acd',
      perks: ['15% fidélité', 'Accès VIP + cadeaux'] },
];

/* Simulated history — replace with API call if available */
const DEMO_HISTORY = [
    { type: 'earn',  label: 'Contrat renouvelé — Auto',     pts: +120, date: '2025-04-12' },
    { type: 'earn',  label: 'Mini-jeu Roulette',            pts:  +50, date: '2025-05-02' },
    { type: 'earn',  label: 'Déclaration de sinistre',      pts:  +30, date: '2025-05-18' },
    { type: 'spend', label: 'Réduction prime Habitation',   pts:  -80, date: '2025-05-22' },
    { type: 'earn',  label: 'Mini-jeu Memory',              pts:  +40, date: '2025-05-30' },
];

function getCurrentTier(pts) {
    for (let i = TIERS.length - 1; i >= 0; i--) {
        if (pts >= TIERS[i].min) return i;
    }
    return 0;
}

function renderTiers(pts) {
    const tierIdx = getCurrentTier(pts);
    const grid = document.getElementById('tierGrid');
    grid.innerHTML = TIERS.map((t, i) => `
        <div class="tier-card ${i === tierIdx ? 'active-tier' : ''}"
             style="${i === tierIdx ? 'border-color:' + t.color + ';' : ''}">
            <span class="tier-emoji">${t.emoji}</span>
            <div class="tier-name" style="${i === tierIdx ? 'color:' + t.color : ''}">${t.name}</div>
            <div class="tier-pts">${t.min === 0 ? '0' : t.min.toLocaleString('fr-FR')} – ${t.max === Infinity ? '∞' : t.max.toLocaleString('fr-FR')} pts</div>
            ${t.perks.map(p => `<div class="tier-perk">✓ ${p}</div>`).join('')}
        </div>`).join('');
}

function renderProgress(pts) {
    const idx  = getCurrentTier(pts);
    const tier = TIERS[idx];
    const next = TIERS[idx + 1];

    if (!next) {
        document.getElementById('tierLabel').textContent = '💎 Palier Platine atteint — Félicitations !';
        document.getElementById('tierSub').textContent   = 'Vous êtes au sommet de la fidélité Protex.';
        document.getElementById('tierProgressBar').style.width = '100%';
        document.getElementById('tierFrom').textContent = tier.min.toLocaleString('fr-FR') + ' pts';
        document.getElementById('tierTo').textContent   = '✓ Max';
        return;
    }

    const range   = next.min - tier.min;
    const elapsed = pts - tier.min;
    const pct     = Math.min(100, Math.round((elapsed / range) * 100));
    const needed  = next.min - pts;

    document.getElementById('tierLabel').textContent = `${tier.emoji} ${tier.name} → ${next.emoji} ${next.name}`;
    document.getElementById('tierSub').textContent   = `Plus que ${needed.toLocaleString('fr-FR')} points pour passer ${next.name} !`;
    document.getElementById('tierFrom').textContent  = tier.min.toLocaleString('fr-FR') + ' pts';
    document.getElementById('tierTo').textContent    = next.min.toLocaleString('fr-FR') + ' pts';
    setTimeout(() => {
        document.getElementById('tierProgressBar').style.width = pct + '%';
    }, 300);
}

function renderHistory(history) {
    const list = document.getElementById('historyList');
    list.innerHTML = history.map(h => {
        const isEarn = h.pts > 0;
        const d = new Date(h.date).toLocaleDateString('fr-FR', { day:'2-digit', month:'short', year:'numeric' });
        return `<li class="history-item">
            <div class="history-icon ${isEarn ? 'h-earn' : 'h-spend'}">
                <i class="bi ${isEarn ? 'bi-plus-circle-fill' : 'bi-dash-circle-fill'}"></i>
            </div>
            <div>
                <div class="history-desc">${h.label}</div>
                <div class="history-date">${d}</div>
            </div>
            <div class="history-pts ${isEarn ? 'pts-earn' : 'pts-spend'}">
                ${isEarn ? '+' : ''}${h.pts} pts
            </div>
        </li>`;
    }).join('');
}

/* ── Confetti ─────────────────────────────────────────────── */
function launchConfetti(duration = 2500) {
    const canvas  = document.getElementById('confettiCanvas');
    const ctx     = canvas.getContext('2d');
    canvas.width  = window.innerWidth;
    canvas.height = window.innerHeight;

    const colors  = ['#FF6B1A','#EF9F27','#2EC4B6','#1A3A7A','#e63946','#fff'];
    const pieces  = Array.from({ length: 120 }, () => ({
        x: Math.random() * canvas.width,
        y: -20,
        r: 4 + Math.random() * 6,
        color: colors[Math.floor(Math.random() * colors.length)],
        vx: (Math.random() - 0.5) * 4,
        vy: 2 + Math.random() * 4,
        rot: Math.random() * 360,
        vrot: (Math.random() - 0.5) * 6,
    }));

    const end = Date.now() + duration;

    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        pieces.forEach(p => {
            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate(p.rot * Math.PI / 180);
            ctx.fillStyle = p.color;
            ctx.fillRect(-p.r / 2, -p.r / 2, p.r, p.r * 0.5);
            ctx.restore();
            p.x   += p.vx;
            p.y   += p.vy;
            p.rot += p.vrot;
            p.vy  += 0.06; // gravity
        });
        if (Date.now() < end) requestAnimationFrame(draw);
        else ctx.clearRect(0, 0, canvas.width, canvas.height);
    }
    draw();
}

/* ── Boot ─────────────────────────────────────────────────── */
async function init() {
    // Try to load real points from profile API
    let pts = 0;
    try {
        const res  = await fetch('get_user.php');
        const data = await res.json();
        const user = data.user || data;
        pts = parseInt(user.points_fidelite || user.points || 0, 10);
    } catch (_) {
        // Use demo value
        pts = DEMO_HISTORY.reduce((sum, h) => sum + h.pts, 0);
    }

    // Animate points counter
    const el  = document.getElementById('userPoints');
    const dur = 1200;
    const start = performance.now();
    function tick(now) {
        const p = Math.min((now - start) / dur, 1);
        const e = 1 - Math.pow(1 - p, 3);
        el.textContent = Math.round(pts * e).toLocaleString('fr-FR');
        if (p < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);

    renderTiers(pts);
    renderProgress(pts);
    renderHistory(DEMO_HISTORY);

    // Celebrate if tier just upgraded (simulated)
    const tierIdx = getCurrentTier(pts);
    if (tierIdx >= 1) {
        setTimeout(() => launchConfetti(3000), 800);
    }
}

init();
</script>
</body>
</html>
