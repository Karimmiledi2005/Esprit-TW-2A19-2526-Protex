<?php
/**
 * view/FrontOffice/memory.php — Jeu Memory Protex
 */
include_once __DIR__ . '/../../config.php';
include_once __DIR__ . '/../../model/JeuMemory.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$frontBase = BASE_URL . '/view/FrontOffice';
$ctrlBase  = BASE_URL . '/controller';

$userId = (int)($_SESSION['user_id'] ?? 0);
$bests = [];
$stats = [];
if ($userId > 0) {
    try {
        $db = config::getConnexion();
        $bests = JeuMemory::getBestScore($db, $userId);
        $stats = JeuMemory::getUserStats($db, $userId);
    } catch (Exception $e) {}
}
$bestTime = 9999;
foreach ($bests as $b) { if ((int)$b['best_time'] < $bestTime) $bestTime = (int)$b['best_time']; }
if ($bestTime === 9999) $bestTime = 0;
$totalParties = (int)($stats['total_parties'] ?? 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>🧠 Memory — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="<?= $frontBase ?>/assets/css/variables.css">
    <link rel="stylesheet" href="<?= $frontBase ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= $frontBase ?>/assets/css/layout.css">
    <link rel="stylesheet" href="<?= $frontBase ?>/assets/css/client.css">
    <style>
        /* ═══ HERO ═══ */
        .mem-hero {
            background: linear-gradient(135deg, #1A3A7A 0%, #0f2456 100%);
            border-radius: 24px; padding: 28px 32px;
            margin-bottom: 28px; position: relative; overflow: hidden;
            display: flex; align-items: center; gap: 20px;
        }
        .mem-hero::before {
            content: ''; position: absolute; top: -60px; right: -40px;
            width: 220px; height: 220px; background: rgba(139,92,246,0.10); border-radius: 50%;
        }
        .mem-hero-icon {
            width: 64px; height: 64px; border-radius: 18px;
            display: grid; place-items: center; font-size: 32px;
            background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.2);
            flex-shrink: 0; position: relative; z-index: 1;
        }
        .mem-hero h1 {
            font-family: 'Sora', sans-serif; font-size: 24px; font-weight: 800;
            color: #fff; margin-bottom: 4px; position: relative; z-index: 1;
        }
        .mem-hero p {
            color: rgba(255,255,255,0.60); font-size: 14px; position: relative; z-index: 1;
        }

        /* ═══ SCORE STRIP ═══ */
        .score-strip {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px;
        }
        @media (max-width: 700px) { .score-strip { grid-template-columns: repeat(2, 1fr); } }
        .score-box {
            background: #fff; border: 1px solid rgba(26,58,122,0.06);
            border-radius: 18px; padding: 18px 14px; text-align: center;
            box-shadow: 0 2px 12px rgba(26,58,122,0.04);
            transition: transform .2s, box-shadow .2s;
        }
        .score-box:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(26,58,122,0.08); }
        .score-box .s-icon { font-size: 20px; margin-bottom: 4px; }
        .score-box .lbl { font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: .08em; font-weight: 700; }
        .score-box .val { font-size: 26px; font-weight: 900; font-family: 'Sora', sans-serif; margin-top: 2px; color: #1A3A7A; }
        .score-box .val.time { color: #8b5cf6; }
        .score-box .val.best { color: #f59e0b; }
        .score-box .val.pairs { color: #10b981; }

        /* ═══ GRID 2 COLUMNS ═══ */
        .mem-grid {
            display: grid; grid-template-columns: 1fr 300px; gap: 20px; margin-bottom: 24px;
        }
        @media (max-width: 900px) { .mem-grid { grid-template-columns: 1fr; } }

        /* ═══ GAME CARD ═══ */
        .game-card {
            background: #fff; border: 1px solid rgba(26,58,122,0.06);
            border-radius: 24px; padding: 24px;
            box-shadow: 0 2px 12px rgba(26,58,122,0.04);
        }
        .game-card-head {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 18px; padding-bottom: 14px;
            border-bottom: 1px solid rgba(26,58,122,0.06);
        }
        .game-card-title {
            font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 700;
            color: #1A3A7A; display: flex; align-items: center; gap: 8px;
        }
        .game-card-title i { color: #FF6B1A; }
        .game-hint { font-size: 12px; color: #94a3b8; display: flex; align-items: center; gap: 6px; }
        .game-hint .timer { font-family: 'Sora', sans-serif; font-weight: 700; color: #1A3A7A; font-size: 14px; }
        .game-hint .pairs-info { font-size: 11px; color: #64748b; }

        /* ═══ DIFFICULTY ═══ */
        .diff-group { display: flex; gap: 8px; margin-bottom: 18px; justify-content: center; }
        .diff-chip {
            padding: 7px 14px; border-radius: 10px; cursor: pointer;
            border: 1px solid rgba(26,58,122,0.10); background: #f8fafc;
            text-align: center; transition: all .15s;
        }
        .diff-chip:hover { border-color: #1A3A7A; }
        .diff-chip.picked { border-color: #8b5cf6; background: rgba(139,92,246,0.08); }
        .diff-chip .dn { font-size: 12px; font-weight: 700; color: #1A3A7A; }
        .diff-chip .ds { font-size: 10px; color: #94a3b8; }

        /* ═══ BOARD ═══ */
        .mem-board {
            display: grid;
            gap: 8px;
            justify-content: center;
            margin: 0 auto;
        }
        .mem-board.grid-4x3 { grid-template-columns: repeat(4, 72px); }
        .mem-board.grid-4x4 { grid-template-columns: repeat(4, 64px); }
        .mem-board.grid-5x4 { grid-template-columns: repeat(5, 56px); }

        .mem-card {
            width: 100%; aspect-ratio: 1;
            perspective: 600px;
            cursor: pointer;
        }
        .mem-card-inner {
            position: relative; width: 100%; height: 100%;
            transition: transform 0.4s;
            transform-style: preserve-3d;
        }
        .mem-card.flipped .mem-card-inner,
        .mem-card.matched .mem-card-inner {
            transform: rotateY(180deg);
        }
        .mem-card-front, .mem-card-back {
            position: absolute; inset: 0;
            backface-visibility: hidden;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
        }
        .mem-card-front {
            background: linear-gradient(135deg, #1A3A7A, #0f2456);
            color: rgba(255,255,255,0.6);
            border: 2px solid rgba(26,58,122,0.15);
            font-size: 20px;
        }
        .mem-card-back {
            background: #fff;
            border: 2px solid rgba(26,58,122,0.08);
            transform: rotateY(180deg);
        }
        .mem-card.matched .mem-card-back {
            background: rgba(139,92,246,0.06);
            border-color: rgba(139,92,246,0.2);
        }
        .mem-card:not(.flipped):not(.matched):hover .mem-card-front {
            border-color: #8b5cf6;
            background: linear-gradient(135deg, #2a4a8a, #1A3A7A);
        }

        @media (max-width: 500px) {
            .mem-board.grid-4x3 { grid-template-columns: repeat(4, 60px); }
            .mem-board.grid-4x4 { grid-template-columns: repeat(4, 56px); }
            .mem-board.grid-5x4 { grid-template-columns: repeat(5, 50px); }
            .game-card { padding: 14px; }
        }

        /* ═══ SIDEBAR ═══ */
        .side-card {
            background: #fff; border: 1px solid rgba(26,58,122,0.06);
            border-radius: 20px; padding: 20px;
            box-shadow: 0 2px 12px rgba(26,58,122,0.04);
            margin-bottom: 16px;
        }
        .side-card-head {
            font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700;
            color: #1A3A7A; margin-bottom: 14px;
            display: flex; align-items: center; gap: 6px;
        }
        .side-card-head i { color: #FF6B1A; }

        .lb-row {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 0; border-bottom: 1px solid rgba(26,58,122,0.04);
        }
        .lb-row:last-child { border-bottom: none; }
        .lb-rank {
            width: 24px; height: 24px; border-radius: 8px;
            display: grid; place-items: center;
            font-size: 11px; font-weight: 800; color: #fff;
            background: #cbd5e1; flex-shrink: 0;
        }
        .lb-rank.r1 { background: #f59e0b; }
        .lb-rank.r2 { background: #94a3b8; }
        .lb-rank.r3 { background: #cd7f32; }
        .lb-name { flex: 1; font-size: 12px; font-weight: 600; color: #1A3A7A; }
        .lb-name small { display: block; font-size: 10px; color: #94a3b8; font-weight: 400; }
        .lb-score { font-size: 14px; font-weight: 800; color: #8b5cf6; font-family: 'Sora', sans-serif; }
        .lb-empty { text-align: center; padding: 20px 0; color: #94a3b8; font-size: 12px; }
        .lb-empty i { font-size: 24px; display: block; margin-bottom: 6px; }

        .stat-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 8px 0; border-bottom: 1px solid rgba(26,58,122,0.04);
        }
        .stat-row:last-child { border-bottom: none; }
        .stat-label { font-size: 12px; color: #64748b; }
        .stat-value { font-size: 14px; font-weight: 700; color: #1A3A7A; font-family: 'Sora', sans-serif; }

        .btn-new-game {
            padding: 10px 24px; border-radius: 10px; border: none; cursor: pointer;
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: #fff; font-size: 13px; font-weight: 700; font-family: 'Sora', sans-serif;
            box-shadow: 0 4px 14px rgba(139,92,246,0.25); transition: all .2s;
        }
        .btn-new-game:hover { transform: translateY(-1px); }
    </style>
</head>
<body>
<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<?php require_once __DIR__.'/assets/includes/navbar.php'; ?>

<div class="content">
    <!-- HERO -->
    <div class="mem-hero">
        <a href="<?= $frontBase ?>/jeux.php" style="color:#fff;font-size:18px;position:relative;z-index:1;" title="Retour aux jeux"><i class="bi bi-arrow-left-circle"></i></a>
        <div class="mem-hero-icon">🧠</div>
        <div>
            <h1>Memory</h1>
            <p>Trouvez toutes les paires le plus rapidement possible !</p>
        </div>
    </div>

    <!-- SCORES -->
    <div class="score-strip">
        <div class="score-box">
            <div class="s-icon">⏱️</div>
            <div class="lbl">Temps</div>
            <div class="val time" id="timeDisplay">0s</div>
        </div>
        <div class="score-box">
            <div class="s-icon">🏆</div>
            <div class="lbl">Meilleur temps</div>
            <div class="val best"><?= $bestTime > 0 ? $bestTime . 's' : '—' ?></div>
        </div>
        <div class="score-box">
            <div class="s-icon">🎯</div>
            <div class="lbl">Paires trouvées</div>
            <div class="val pairs" id="pairsDisplay">0/6</div>
        </div>
        <div class="score-box">
            <div class="s-icon">🎮</div>
            <div class="lbl">Parties</div>
            <div class="val"><?= $totalParties ?></div>
        </div>
    </div>

    <!-- GRID -->
    <div class="mem-grid">
        <!-- GAME -->
        <div class="game-card">
            <div class="game-card-head">
                <div class="game-card-title">
                    <i class="bi bi-grid-3x3-gap"></i> Plateau de jeu
                </div>
                <div class="game-hint">
                    <span class="timer" id="timerDisplay">⏱ 0s</span>
                    <span class="pairs-info" id="movesDisplay">Coups : 0</span>
                </div>
            </div>

            <div class="diff-group">
                <label class="diff-chip picked" id="diff-easy" onclick="setDifficulty(6,'Facile')">
                    <input type="radio" name="diff" value="6">
                    <div class="dn">🟢 6 paires</div>
                    <div class="ds">Facile</div>
                </label>
                <label class="diff-chip" id="diff-med" onclick="setDifficulty(8,'Moyen')">
                    <input type="radio" name="diff" value="8">
                    <div class="dn">🟡 8 paires</div>
                    <div class="ds">Moyen</div>
                </label>
                <label class="diff-chip" id="diff-hard" onclick="setDifficulty(10,'Difficile')">
                    <input type="radio" name="diff" value="10">
                    <div class="dn">🔴 10 paires</div>
                    <div class="ds">Expert</div>
                </label>
            </div>

            <div style="text-align:center;margin-bottom:14px;">
                <button class="btn-new-game" onclick="initGame()">🔄 Nouvelle partie</button>
            </div>

            <div class="mem-board grid-4x3" id="memBoard"></div>
        </div>

        <!-- SIDEBAR -->
        <div>
            <!-- Leaderboard -->
            <div class="side-card">
                <div class="side-card-head"><i class="bi bi-trophy"></i> Classement</div>
                <?php
                $leaderboard = [];
                if ($userId > 0) {
                    try { $leaderboard = JeuMemory::getLeaderboard($db, null, 5); } catch (Exception $e) {}
                }
                ?>
                <?php if (empty($leaderboard)): ?>
                <div class="lb-empty"><i class="bi bi-emoji-smile"></i>Soyez le premier à jouer !</div>
                <?php else: ?>
                <?php $rank = 0; foreach ($leaderboard as $lb): $rank++; ?>
                <div class="lb-row">
                    <div class="lb-rank <?= $rank <= 3 ? 'r' . $rank : '' ?>"><?= $rank ?></div>
                    <div class="lb-name">
                        <?= htmlspecialchars(($lb['prenom'] ?? '') . ' ' . ($lb['nom'] ?? 'Anonyme')) ?>
                        <small><?= htmlspecialchars($lb['difficulte'] ?? '') ?> · <?= (int)$lb['coups'] ?> coups</small>
                    </div>
                    <div class="lb-score"><?= (int)$lb['temps'] ?>s</div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Stats -->
            <?php if ($totalParties > 0): ?>
            <div class="side-card">
                <div class="side-card-head"><i class="bi bi-graph-up"></i> Mes statistiques</div>
                <div class="stat-row">
                    <span class="stat-label">Parties jouées</span>
                    <span class="stat-value"><?= $totalParties ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Meilleur temps</span>
                    <span class="stat-value" style="color:#8b5cf6;"><?= $bestTime > 0 ? $bestTime . 's' : '—' ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Coups moyens</span>
                    <span class="stat-value"><?= (int)($stats['coups_moyen'] ?? 0) ?></span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const EMOJIS = ['🛡️','🏠','🚗','💊','📋','🔑','⭐','🎯','💎','🏆','🎮','🔒'];
let numPairs = 6;
let diffLabel = 'Facile';
let cards = [];
let flippedCards = [];
let matchedPairs = 0;
let moves = 0;
let timerInterval = null;
let elapsed = 0;
let canFlip = true;
let gameStarted = false;

function setDifficulty(pairs, label) {
    numPairs = pairs;
    diffLabel = label;
    document.querySelectorAll('.diff-chip').forEach(el => el.classList.remove('picked'));
    if (pairs === 6) document.getElementById('diff-easy').classList.add('picked');
    else if (pairs === 8) document.getElementById('diff-med').classList.add('picked');
    else document.getElementById('diff-hard').classList.add('picked');
    document.getElementById('pairsDisplay').textContent = '0/' + pairs;
    initGame();
}

function shuffle(arr) {
    for (let i = arr.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
}

function initGame() {
    clearInterval(timerInterval);
    elapsed = 0; moves = 0; matchedPairs = 0;
    flippedCards = []; canFlip = true; gameStarted = false;
    document.getElementById('timeDisplay').textContent = '0s';
    document.getElementById('timerDisplay').textContent = '⏱ 0s';
    document.getElementById('movesDisplay').textContent = 'Coups : 0';
    document.getElementById('pairsDisplay').textContent = '0/' + numPairs;

    const emojis = EMOJIS.slice(0, numPairs);
    const pairs = [...emojis, ...emojis];
    shuffle(pairs);

    const board = document.getElementById('memBoard');
    board.className = 'mem-board';
    if (numPairs <= 6) board.classList.add('grid-4x3');
    else if (numPairs <= 8) board.classList.add('grid-4x4');
    else board.classList.add('grid-5x4');

    board.innerHTML = '';
    cards = [];

    pairs.forEach((emoji, i) => {
        const card = document.createElement('div');
        card.className = 'mem-card';
        card.dataset.emoji = emoji;
        card.dataset.index = i;
        card.innerHTML = `
            <div class="mem-card-inner">
                <div class="mem-card-front"><i class="bi bi-question-lg"></i></div>
                <div class="mem-card-back">${emoji}</div>
            </div>
        `;
        card.addEventListener('click', () => flipCard(card));
        board.appendChild(card);
        cards.push(card);
    });
}

function flipCard(card) {
    if (!canFlip || card.classList.contains('flipped') || card.classList.contains('matched')) return;
    if (flippedCards.length >= 2) return;

    if (!gameStarted) {
        gameStarted = true;
        timerInterval = setInterval(() => {
            elapsed++;
            document.getElementById('timeDisplay').textContent = elapsed + 's';
            document.getElementById('timerDisplay').textContent = '⏱ ' + elapsed + 's';
        }, 1000);
    }

    card.classList.add('flipped');
    flippedCards.push(card);

    if (flippedCards.length === 2) {
        moves++;
        document.getElementById('movesDisplay').textContent = 'Coups : ' + moves;
        canFlip = false;

        if (flippedCards[0].dataset.emoji === flippedCards[1].dataset.emoji) {
            flippedCards[0].classList.add('matched');
            flippedCards[1].classList.add('matched');
            matchedPairs++;
            document.getElementById('pairsDisplay').textContent = matchedPairs + '/' + numPairs;
            flippedCards = [];
            canFlip = true;

            if (matchedPairs === numPairs) {
                clearInterval(timerInterval);
                saveScore(elapsed, moves);
            }
        } else {
            setTimeout(() => {
                flippedCards[0].classList.remove('flipped');
                flippedCards[1].classList.remove('flipped');
                flippedCards = [];
                canFlip = true;
            }, 800);
        }
    }
}

function saveScore(time, coups) {
    const fd = new FormData();
    fd.append('temps', time);
    fd.append('coups', coups);
    fd.append('difficulte', diffLabel);
    fd.append('paires', numPairs);

    fetch('<?= $ctrlBase ?>/JeuController.php?action=save_memory', {
        method: 'POST', body: fd
    }).then(r => r.json()).then(data => {
        if (data.success) {
            setTimeout(() => location.reload(), 1500);
        }
    }).catch(() => {});
}

initGame();
</script>
</body>
</html>


