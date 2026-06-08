<?php
/**
 * view/FrontOffice/jeux.php — Hub des jeux Protex
 */
include_once __DIR__ . '/../../config.php';
include_once __DIR__ . '/../../model/JeuSnake.php';
include_once __DIR__ . '/../../model/JeuMemory.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$frontBase = BASE_URL . '/view/FrontOffice';
$userId = (int)($_SESSION['user_id'] ?? 0);

$snakeBest = 0;
$memoryBest = 0;
$totalParties = 0;
if ($userId > 0) {
    try {
        $db = config::getConnexion();
        $snakeBests = JeuSnake::getBestScore($db, $userId);
        foreach ($snakeBests as $b) { if ((int)$b['best_score'] > $snakeBest) $snakeBest = (int)$b['best_score']; }

        $memoryBests = JeuMemory::getBestScore($db, $userId);
        foreach ($memoryBests as $b) { if ((int)$b['best_time'] > 0 && ((int)$b['best_time'] < $memoryBest || $memoryBest === 0)) $memoryBest = (int)$b['best_time']; }

        $snakeStats = JeuSnake::getUserStats($db, $userId);
        $memStats = JeuMemory::getUserStats($db, $userId);
        $totalParties = (int)($snakeStats['total_parties'] ?? 0) + (int)($memStats['total_parties'] ?? 0);
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>🎮 Jeux — Protex</title>
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
        .jeux-hero {
            background: linear-gradient(135deg, #1A3A7A 0%, #0f2456 100%);
            border-radius: 24px; padding: 28px 32px;
            margin-bottom: 28px; position: relative; overflow: hidden;
            display: flex; align-items: center; gap: 20px;
        }
        .jeux-hero::before {
            content: ''; position: absolute; top: -60px; right: -40px;
            width: 220px; height: 220px; background: rgba(255,107,26,0.10); border-radius: 50%;
        }
        .jeux-hero-icon {
            width: 64px; height: 64px; border-radius: 18px;
            display: grid; place-items: center; font-size: 32px;
            background: rgba(255,107,26,0.15); border: 1px solid rgba(255,107,26,0.2);
            flex-shrink: 0; position: relative; z-index: 1;
        }
        .jeux-hero h1 {
            font-family: 'Sora', sans-serif; font-size: 24px; font-weight: 800;
            color: #fff; margin-bottom: 4px; position: relative; z-index: 1;
        }
        .jeux-hero p {
            color: rgba(255,255,255,0.60); font-size: 14px; position: relative; z-index: 1;
        }

        .score-strip {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 28px;
        }
        @media (max-width: 600px) { .score-strip { grid-template-columns: 1fr; } }
        .score-box {
            background: #fff; border: 1px solid rgba(26,58,122,0.06);
            border-radius: 18px; padding: 18px 14px; text-align: center;
            box-shadow: 0 2px 12px rgba(26,58,122,0.04);
        }
        .score-box .s-icon { font-size: 20px; margin-bottom: 4px; }
        .score-box .lbl { font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: .08em; font-weight: 700; }
        .score-box .val { font-size: 26px; font-weight: 900; font-family: 'Sora', sans-serif; margin-top: 2px; color: #1A3A7A; }
        .score-box .val.accent { color: #FF6B1A; }
        .score-box .val.purple { color: #8b5cf6; }

        .games-grid {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 28px;
        }
        @media (max-width: 700px) { .games-grid { grid-template-columns: 1fr; } }

        .game-tile {
            background: #fff;
            border: 1px solid rgba(26,58,122,0.06);
            border-radius: 24px;
            padding: 28px 24px;
            box-shadow: 0 2px 12px rgba(26,58,122,0.04);
            transition: transform .2s, box-shadow .2s;
            display: flex; flex-direction: column; align-items: center; text-align: center;
        }
        .game-tile:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(26,58,122,0.10);
        }
        .game-tile-icon {
            width: 72px; height: 72px; border-radius: 20px;
            display: grid; place-items: center; font-size: 36px;
            margin-bottom: 16px;
        }
        .game-tile-icon.snake {
            background: linear-gradient(135deg, rgba(26,58,122,0.10), rgba(26,58,122,0.03));
        }
        .game-tile-icon.memory {
            background: linear-gradient(135deg, rgba(139,92,246,0.10), rgba(139,92,246,0.03));
        }
        .game-tile h3 {
            font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 800;
            color: #1A3A7A; margin-bottom: 6px;
        }
        .game-tile p {
            font-size: 13px; color: #64748b; margin-bottom: 14px; line-height: 1.5;
        }
        .game-tile-best {
            font-size: 11px; color: #94a3b8; margin-bottom: 16px;
        }
        .game-tile-best strong { color: #FF6B1A; font-weight: 700; }
        .btn-play-tile {
            padding: 10px 28px; border-radius: 12px; border: none; cursor: pointer;
            color: #fff; font-size: 14px; font-weight: 700; font-family: 'Sora', sans-serif;
            transition: all .2s; text-decoration: none;
        }
        .btn-play-tile.snake-btn {
            background: linear-gradient(135deg, #1A3A7A, #0f2456);
            box-shadow: 0 4px 14px rgba(26,58,122,0.25);
        }
        .btn-play-tile.snake-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(26,58,122,0.30); }
        .btn-play-tile.memory-btn {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            box-shadow: 0 4px 14px rgba(139,92,246,0.25);
        }
        .btn-play-tile.memory-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(139,92,246,0.30); }
    </style>
</head>
<body>
<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<?php require_once __DIR__.'/assets/includes/navbar.php'; ?>

<div class="content">
    <!-- HERO -->
    <div class="jeux-hero">
        <div class="jeux-hero-icon">🎮</div>
        <div>
            <h1>Espace Jeux</h1>
            <p>Détendez-vous entre deux devis ! Choisissez votre jeu préféré.</p>
        </div>
    </div>

    <!-- SCORES -->
    <div class="score-strip">
        <div class="score-box">
            <div class="s-icon">🎮</div>
            <div class="lbl">Total parties</div>
            <div class="val accent"><?= $totalParties ?></div>
        </div>
        <div class="score-box">
            <div class="s-icon">🐍</div>
            <div class="lbl">Meilleur Snake</div>
            <div class="val"><?= $snakeBest ?></div>
        </div>
        <div class="score-box">
            <div class="s-icon">🧠</div>
            <div class="lbl">Meilleur Memory</div>
            <div class="val purple"><?= $memoryBest > 0 ? $memoryBest . 's' : '—' ?></div>
        </div>
    </div>

    <!-- GAMES GRID -->
    <div class="games-grid">
        <!-- SNAKE -->
        <div class="game-tile">
            <div class="game-tile-icon snake">🐍</div>
            <h3>Snake</h3>
            <p>Le classique ! Dirigez le serpent et mangez un maximum de fruits sans toucher les bords.</p>
            <div class="game-tile-best">
                Meilleur score : <strong><?= $snakeBest ?></strong>
            </div>
            <a href="<?= $frontBase ?>/snake.php" class="btn-play-tile snake-btn">▶ Jouer</a>
        </div>

        <!-- MEMORY -->
        <div class="game-tile">
            <div class="game-tile-icon memory">🧠</div>
            <h3>Memory</h3>
            <p>Retournez les cartes et retrouvez toutes les paires le plus rapidement possible.</p>
            <div class="game-tile-best">
                Meilleur temps : <strong><?= $memoryBest > 0 ? $memoryBest . 's' : '—' ?></strong>
            </div>
            <a href="<?= $frontBase ?>/memory.php" class="btn-play-tile memory-btn">▶ Jouer</a>
        </div>
    </div>
</div>
</body>
</html>


