<?php
/**
 * view/FrontOffice/snake.php — Jeu Snake Protex
 */
include_once __DIR__ . '/../../config.php';
include_once __DIR__ . '/../../model/JeuSnake.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$frontBase = BASE_URL . '/view/FrontOffice';
$ctrlBase  = BASE_URL . '/controller';

$userId = (int)($_SESSION['user_id'] ?? 0);

// Load best scores from DB
$bests = [];
$stats = [];
if ($userId > 0) {
    try {
        $db = config::getConnexion();
        $bests = JeuSnake::getBestScore($db, $userId);
        $stats = JeuSnake::getUserStats($db, $userId);
    } catch (Exception $e) {}
}

// Compute overall best
$overallBest = 0;
foreach ($bests as $b) { if ((int)$b['best_score'] > $overallBest) $overallBest = (int)$b['best_score']; }
$totalParties = (int)($stats['total_parties'] ?? 0);
$scoreMoyen = (int)($stats['score_moyen'] ?? 0);

// Leaderboard (top 5)
$leaderboard = [];
if ($userId > 0) {
    try { $leaderboard = JeuSnake::getLeaderboard($db, null, 5); } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>🐍 Snake — Protex</title>
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
        .snake-hero {
            background: linear-gradient(135deg, #1A3A7A 0%, #0f2456 100%);
            border-radius: 24px; padding: 28px 32px;
            margin-bottom: 28px; position: relative; overflow: hidden;
            display: flex; align-items: center; gap: 20px;
        }
        .snake-hero::before {
            content: ''; position: absolute; top: -60px; right: -40px;
            width: 220px; height: 220px; background: rgba(255,107,26,0.10); border-radius: 50%;
        }
        .snake-hero-icon {
            width: 64px; height: 64px; border-radius: 18px;
            display: grid; place-items: center; font-size: 32px;
            background: rgba(255,107,26,0.15); border: 1px solid rgba(255,107,26,0.2);
            flex-shrink: 0; position: relative; z-index: 1;
        }
        .snake-hero h1 {
            font-family: 'Sora', sans-serif; font-size: 24px; font-weight: 800;
            color: #fff; margin-bottom: 4px; position: relative; z-index: 1;
        }
        .snake-hero p {
            color: rgba(255,255,255,0.60); font-size: 14px; position: relative; z-index: 1;
        }

        /* ═══ SCORE CARDS ═══ */
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
        .score-box .val.cur { color: #FF6B1A; }
        .score-box .val.best { color: #f59e0b; }
        .score-box .val.spd { color: #10b981; }
        .score-box .val.avg { color: #6366f1; }

        /* ═══ GRID 2 COLUMNS ═══ */
        .snake-grid {
            display: grid; grid-template-columns: 1fr 300px; gap: 20px; margin-bottom: 24px;
        }
        @media (max-width: 900px) { .snake-grid { grid-template-columns: 1fr; } }

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
        .game-hint { font-size: 11px; color: #94a3b8; }
        .game-hint kbd {
            display: inline-block; padding: 1px 5px; border-radius: 4px;
            border: 1px solid rgba(26,58,122,0.12); background: #f1f5f9;
            font-size: 10px; color: #64748b;
        }

        .board-wrap { position: relative; display: flex; justify-content: center; }
        .board {
            position: relative; border-radius: 16px;
            border: 2px solid rgba(26,58,122,0.08); background: #f8fafc; overflow: hidden;
        }
        #snakeCanvas { display: block; border-radius: 14px; }

        .overlay {
            position: absolute; inset: 0; background: rgba(26,58,122,0.92);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            border-radius: 14px; z-index: 10; transition: opacity .25s;
            backdrop-filter: blur(6px);
        }
        .overlay.hidden { opacity: 0; pointer-events: none; }
        .ov-emoji { font-size: 48px; margin-bottom: 8px; }
        .ov-title { font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 4px; font-family: 'Sora', sans-serif; }
        .ov-sub { font-size: 13px; color: rgba(255,255,255,0.55); margin-bottom: 16px; }

        .speed-group { display: flex; gap: 8px; margin-bottom: 16px; }
        .speed-chip {
            padding: 7px 14px; border-radius: 10px; cursor: pointer;
            border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.05);
            text-align: center; transition: all .15s;
        }
        .speed-chip:hover { border-color: rgba(255,255,255,0.30); }
        .speed-chip.picked { border-color: #FF6B1A; background: rgba(255,107,26,0.18); }
        .speed-chip input { display: none; }
        .speed-chip .sn { font-size: 12px; font-weight: 700; color: #fff; }
        .speed-chip .ss { font-size: 10px; color: rgba(255,255,255,0.50); }

        .btn-play {
            padding: 11px 36px; border-radius: 12px; border: none; cursor: pointer;
            background: linear-gradient(135deg, #FF6B1A, #e05a0f);
            color: #fff; font-size: 14px; font-weight: 800; font-family: 'Sora', sans-serif;
            box-shadow: 0 4px 14px rgba(255,107,26,0.25); transition: all .2s;
        }
        .btn-play:hover { transform: translateY(-1px); }

        .dpad { display: flex; justify-content: center; margin-top: 18px; }
        .dpad-grid {
            display: grid; grid-template-columns: repeat(3, 48px); grid-template-rows: repeat(2, 48px); gap: 5px;
        }
        .dpad-btn {
            width: 48px; height: 48px; border-radius: 10px;
            border: 1px solid rgba(26,58,122,0.08); background: #f8fafc;
            color: #94a3b8; font-size: 14px; display: grid; place-items: center;
            cursor: pointer; transition: all .12s; user-select: none;
        }
        .dpad-btn:active { background: #1A3A7A; border-color: #1A3A7A; color: #fff; transform: scale(.92); }
        .dpad-u { grid-column: 2; grid-row: 1; }
        .dpad-l { grid-column: 1; grid-row: 2; }
        .dpad-d { grid-column: 2; grid-row: 2; }
        .dpad-r { grid-column: 3; grid-row: 2; }

        /* ═══ SIDEBAR ═══ */
        .side-card {
            background: #fff; border: 1px solid rgba(26,58,122,0.06);
            border-radius: 20px; padding: 20px;
            box-shadow: 0 2px 12px rgba(26,58,122,0.04);
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
        .lb-score { font-size: 14px; font-weight: 800; color: #FF6B1A; font-family: 'Sora', sans-serif; }

        .lb-empty { text-align: center; padding: 20px 0; color: #94a3b8; font-size: 12px; }
        .lb-empty i { font-size: 24px; display: block; margin-bottom: 6px; }

        .stat-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 8px 0; border-bottom: 1px solid rgba(26,58,122,0.04);
        }
        .stat-row:last-child { border-bottom: none; }
        .stat-label { font-size: 12px; color: #64748b; }
        .stat-value { font-size: 14px; font-weight: 700; color: #1A3A7A; font-family: 'Sora', sans-serif; }

        @media (max-width: 600px) {
            .dpad-btn { width: 44px; height: 44px; }
            .dpad-grid { grid-template-columns: repeat(3, 44px); grid-template-rows: repeat(2, 44px); }
            .game-card { padding: 14px; }
            .snake-hero { padding: 18px; gap: 14px; }
            .snake-hero-icon { width: 48px; height: 48px; font-size: 24px; }
            .snake-hero h1 { font-size: 20px; }
        }
    </style>
</head>
<body>
<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<?php require_once __DIR__.'/assets/includes/navbar.php'; ?>

<div class="content">
    <!-- HERO -->
    <div class="snake-hero">
        <a href="<?= $frontBase ?>/jeux.php" style="color:#fff;font-size:18px;position:relative;z-index:1;" title="Retour aux jeux"><i class="bi bi-arrow-left-circle"></i></a>
        <div class="snake-hero-icon">🐍</div>
        <div>
            <h1>Snake</h1>
            <p>Jeu classique — Détendez-vous entre deux devis !</p>
        </div>
    </div>

    <!-- SCORES -->
    <div class="score-strip">
        <div class="score-box">
            <div class="s-icon">🎯</div>
            <div class="lbl">Score</div>
            <div class="val cur" id="scoreDisplay">0</div>
        </div>
        <div class="score-box">
            <div class="s-icon">🏆</div>
            <div class="lbl">Meilleur</div>
            <div class="val best" id="bestDisplay"><?= $overallBest ?></div>
        </div>
        <div class="score-box">
            <div class="s-icon">📊</div>
            <div class="lbl">Moyenne</div>
            <div class="val avg"><?= $scoreMoyen ?></div>
        </div>
        <div class="score-box">
            <div class="s-icon">🎮</div>
            <div class="lbl">Parties</div>
            <div class="val"><?= $totalParties ?></div>
        </div>
    </div>

    <!-- GRID: GAME + SIDEBAR -->
    <div class="snake-grid">
        <!-- GAME -->
        <div class="game-card">
            <div class="game-card-head">
                <div class="game-card-title">
                    <i class="bi bi-controller"></i> Zone de jeu
                </div>
                <div class="game-hint">
                    <kbd>↑</kbd><kbd>↓</kbd><kbd>←</kbd><kbd>→</kbd> ou WASD
                </div>
            </div>

            <div class="board-wrap">
                <div class="board" id="gameBoard">
                    <canvas id="snakeCanvas" width="440" height="440"></canvas>

                    <div class="overlay" id="overlayStart">
                        <div class="ov-emoji">🐍</div>
                        <div class="ov-title">Snake</div>
                        <div class="ov-sub">Choisissez la difficulté</div>
                        <div class="speed-group">
                            <label class="speed-chip" id="sp-easy" onclick="setSpeed(120,'Lent')">
                                <input type="radio" name="speed" value="120">
                                <div class="sn">🟢 Lent</div><div class="ss">Facile</div>
                            </label>
                            <label class="speed-chip picked" id="sp-normal" onclick="setSpeed(80,'Normal')">
                                <input type="radio" name="speed" value="80" checked>
                                <div class="sn">🟡 Normal</div><div class="ss">Moyen</div>
                            </label>
                            <label class="speed-chip" id="sp-hard" onclick="setSpeed(50,'Rapide')">
                                <input type="radio" name="speed" value="50">
                                <div class="sn">🔴 Rapide</div><div class="ss">Expert</div>
                            </label>
                        </div>
                        <button class="btn-play" onclick="startGame()">▶ Jouer</button>
                    </div>

                    <div class="overlay hidden" id="overlayEnd">
                        <div class="ov-emoji" id="endIcon">💀</div>
                        <div class="ov-title" id="endText">Game Over</div>
                        <div class="ov-sub" id="endSub">Score : 0</div>
                        <button class="btn-play" onclick="restartGame()">🔄 Rejouer</button>
                    </div>
                </div>
            </div>

            <div class="dpad">
                <div class="dpad-grid">
                    <button class="dpad-btn dpad-u" ontouchstart="setDir(0,-1);event.preventDefault();" onmousedown="setDir(0,-1)"><i class="bi bi-caret-up-fill"></i></button>
                    <button class="dpad-btn dpad-l" ontouchstart="setDir(-1,0);event.preventDefault();" onmousedown="setDir(-1,0)"><i class="bi bi-caret-left-fill"></i></button>
                    <button class="dpad-btn dpad-d" ontouchstart="setDir(0,1);event.preventDefault();" onmousedown="setDir(0,1)"><i class="bi bi-caret-down-fill"></i></button>
                    <button class="dpad-btn dpad-r" ontouchstart="setDir(1,0);event.preventDefault();" onmousedown="setDir(1,0)"><i class="bi bi-caret-right-fill"></i></button>
                </div>
            </div>
        </div>

        <!-- SIDEBAR -->
        <div>
            <!-- Leaderboard -->
            <div class="side-card" style="margin-bottom:16px;">
                <div class="side-card-head"><i class="bi bi-trophy"></i> Classement</div>
                <?php if (empty($leaderboard)): ?>
                <div class="lb-empty">
                    <i class="bi bi-emoji-smile"></i>
                    Soyez le premier à jouer !
                </div>
                <?php else: ?>
                <?php $rank = 0; foreach ($leaderboard as $lb): $rank++; ?>
                <div class="lb-row">
                    <div class="lb-rank <?= $rank <= 3 ? 'r' . $rank : '' ?>"><?= $rank ?></div>
                    <div class="lb-name">
                        <?= htmlspecialchars(($lb['prenom'] ?? '') . ' ' . ($lb['nom'] ?? 'Anonyme')) ?>
                        <small><?= htmlspecialchars($lb['vitesse'] ?? '') ?> · <?= date('d/m', strtotime($lb['date_jeu'])) ?></small>
                    </div>
                    <div class="lb-score"><?= (int)$lb['score'] ?></div>
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
                    <span class="stat-label">Meilleur score</span>
                    <span class="stat-value" style="color:#FF6B1A;"><?= $overallBest ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Score moyen</span>
                    <span class="stat-value"><?= $scoreMoyen ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Total mangés</span>
                    <span class="stat-value"><?= (int)($stats['total_manges'] ?? 0) ?></span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const canvas = document.getElementById('snakeCanvas');
const ctx = canvas.getContext('2d');
const CELL = 20;
const COLS = canvas.width / CELL;
const ROWS = canvas.height / CELL;

let snake, direction, nextDirection, food, score, best, speed, speedLabel, gameLoop, isRunning, gameStartTime, serpentsManges;

best = <?= $overallBest ?>;
speed = 80;
speedLabel = 'Normal';
serpentsManges = 0;

function setSpeed(s, label) {
    speed = s; speedLabel = label;
    document.getElementById('speedDisplay').textContent = label;
    document.querySelectorAll('.speed-chip').forEach(el => el.classList.remove('picked'));
    if (s === 120) document.getElementById('sp-easy').classList.add('picked');
    else if (s === 80) document.getElementById('sp-normal').classList.add('picked');
    else document.getElementById('sp-hard').classList.add('picked');
}

function startGame() {
    snake = [{x: 10, y: 10}, {x: 9, y: 10}, {x: 8, y: 10}];
    direction = {x: 1, y: 0}; nextDirection = {x: 1, y: 0};
    score = 0; serpentsManges = 0;
    document.getElementById('scoreDisplay').textContent = score;
    placeFood();
    document.getElementById('overlayStart').classList.add('hidden');
    document.getElementById('overlayEnd').classList.add('hidden');
    isRunning = true;
    gameStartTime = Date.now();
    if (gameLoop) clearInterval(gameLoop);
    gameLoop = setInterval(update, speed);
}

function restartGame() {
    document.getElementById('overlayEnd').classList.add('hidden');
    startGame();
}

function placeFood() {
    while (true) {
        food = {x: Math.floor(Math.random() * COLS), y: Math.floor(Math.random() * ROWS)};
        if (!snake.some(s => s.x === food.x && s.y === food.y)) break;
    }
}

function setDir(x, y) {
    if (!isRunning) return;
    if (direction.x === -x && direction.y === -y) return;
    nextDirection = {x, y};
}

function update() {
    direction = nextDirection;
    const head = {x: snake[0].x + direction.x, y: snake[0].y + direction.y};
    if (head.x < 0 || head.x >= COLS || head.y < 0 || head.y >= ROWS || snake.some(s => s.x === head.x && s.y === head.y)) {
        gameOver(); return;
    }
    snake.unshift(head);
    if (head.x === food.x && head.y === food.y) {
        score += 10; serpentsManges++;
        document.getElementById('scoreDisplay').textContent = score;
        placeFood();
    } else { snake.pop(); }
    draw();
}

function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = 'rgba(26,58,122,0.03)';
    for (let i = 0; i < COLS; i++) {
        for (let j = 0; j < ROWS; j++) {
            ctx.fillRect(i * CELL + .5, j * CELL + .5, CELL - 1, CELL - 1);
        }
    }
    snake.forEach((s, i) => {
        const a = 1 - (i / snake.length) * .45;
        ctx.fillStyle = `rgba(26,58,122,${a})`;
        ctx.beginPath();
        ctx.roundRect(s.x * CELL + 1, s.y * CELL + 1, CELL - 2, CELL - 2, 4);
        ctx.fill();
    });
    ctx.fillStyle = '#1A3A7A';
    ctx.shadowColor = 'rgba(26,58,122,0.30)'; ctx.shadowBlur = 8;
    ctx.beginPath();
    ctx.roundRect(snake[0].x * CELL, snake[0].y * CELL, CELL, CELL, 6);
    ctx.fill(); ctx.shadowBlur = 0;
    ctx.fillStyle = '#FF6B1A';
    ctx.shadowColor = 'rgba(255,107,26,0.35)'; ctx.shadowBlur = 10;
    ctx.beginPath();
    ctx.arc(food.x * CELL + CELL / 2, food.y * CELL + CELL / 2, CELL / 2 - 2, 0, Math.PI * 2);
    ctx.fill(); ctx.shadowBlur = 0;
}

function gameOver() {
    isRunning = false; clearInterval(gameLoop);
    const duree = Math.round((Date.now() - gameStartTime) / 1000);

    if (score > best) {
        best = score;
        document.getElementById('bestDisplay').textContent = best;
        document.getElementById('endIcon').textContent = '🏆';
        document.getElementById('endText').textContent = 'Nouveau record !';
    } else {
        document.getElementById('endIcon').textContent = '💀';
        document.getElementById('endText').textContent = 'Game Over';
    }
    document.getElementById('endSub').textContent = `Score : ${score} | Meilleur : ${best}`;
    document.getElementById('overlayEnd').classList.remove('hidden');

    // Save to DB
    saveScore(score, speedLabel, duree, serpentsManges);
}

function saveScore(score, vitesse, duree, serpents) {
    const fd = new FormData();
    fd.append('score', score);
    fd.append('vitesse', vitesse);
    fd.append('duree', duree);
    fd.append('serpents', serpents);

    fetch('<?= $ctrlBase ?>/JeuController.php?action=save_score', {
        method: 'POST', body: fd
    }).then(r => r.json()).then(data => {
        if (data.success) {
            // Reload page after short delay to show updated stats
            setTimeout(() => location.reload(), 1500);
        }
    }).catch(() => {});
}

document.addEventListener('keydown', e => {
    switch (e.key) {
        case 'ArrowUp': case 'w': setDir(0, -1); break;
        case 'ArrowDown': case 's': setDir(0, 1); break;
        case 'ArrowLeft': case 'a': setDir(-1, 0); break;
        case 'ArrowRight': case 'd': setDir(1, 0); break;
    }
});
</script>
</body>
</html>


