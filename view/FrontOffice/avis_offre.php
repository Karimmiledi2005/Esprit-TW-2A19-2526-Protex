<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers/SessionGuard.php';

$id_offre = $_GET['id'] ?? null;
if (!$id_offre) {
    header("Location: offres.php");
    exit;
}

$db = config::getConnexion();
$stmt = $db->prepare("SELECT * FROM offre WHERE id_offre = ? AND statut='active'");
$stmt->execute([$id_offre]);
$offre = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$offre) {
    header("Location: offres.php");
    exit;
}

$message = '';
$is_logged_in = isset($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
    $note = (int)($_POST['note'] ?? 0);
    $commentaire = trim($_POST['commentaire'] ?? '');
    if ($note >= 1 && $note <= 5 && !empty($commentaire)) {
        try {
            $insert = $db->prepare("INSERT INTO avis_offre (id_offre, id_client, note, commentaire, date_avis) VALUES (?, ?, ?, ?, NOW())");
            $insert->execute([$id_offre, $_SESSION['user_id'], $note, $commentaire]);
            $message = "<div class='alert alert-success' style='background:#dcfce7; color:#166534; padding:12px; border-radius:8px; margin-bottom:20px;'>Votre avis a été enregistré avec succès. Merci !</div>";
        } catch (Exception $e) {
            $message = "<div class='alert alert-danger' style='background:#fee2e2; color:#991b1b; padding:12px; border-radius:8px; margin-bottom:20px;'>Une erreur est survenue lors de l'enregistrement de votre avis.</div>";
        }
    } else {
        $message = "<div class='alert alert-warning' style='background:#fef9c3; color:#854d0e; padding:12px; border-radius:8px; margin-bottom:20px;'>Veuillez donner une note de 1 à 5 et écrire un commentaire.</div>";
    }
}

$stmtAvis = $db->prepare("SELECT a.*, u.nom, u.prenom FROM avis_offre a JOIN user u ON a.id_client = u.id_user WHERE a.id_offre = ? AND a.hidden = 0 ORDER BY a.date_avis DESC");
$stmtAvis->execute([$id_offre]);
$avisList = $stmtAvis->fetchAll(PDO::FETCH_ASSOC);

$avgNote = 0;
if (count($avisList) > 0) {
    $sum = array_reduce($avisList, fn($c, $a) => $c + $a['note'], 0);
    $avgNote = round($sum / count($avisList), 1);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Avis sur <?= htmlspecialchars($offre['nom_offre']) ?> — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">

    <style>
        .avis-page-header {
            margin-top: 40px;
            margin-bottom: 40px;
        }
        .avis-page-title {
            font-family: 'Sora', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: #15233C;
            margin-bottom: 12px;
        }
        .avis-page-sub {
            color: rgba(21,35,60,0.6);
            font-size: 16px;
        }
        .avis-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 40px;
        }
        @media (max-width: 900px) {
            .avis-container {
                grid-template-columns: 1fr;
            }
        }
        .avis-card {
            background: #fff;
            border: 1px solid rgba(26,58,122,0.08);
            border-radius: 22px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.03);
        }
        .avis-user {
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            color: #15233C;
        }
        .avis-date {
            font-size: 12px;
            color: rgba(21,35,60,0.5);
            margin-bottom: 12px;
            display: block;
        }
        .avis-stars {
            color: #f59e0b;
            font-size: 14px;
            margin-bottom: 12px;
        }
        .avis-text {
            color: rgba(21,35,60,0.8);
            font-size: 14px;
            line-height: 1.6;
        }
        .review-form {
            background: #f8faff;
            border-radius: 22px;
            padding: 32px;
            border: 1px solid rgba(26,58,122,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #15233C;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid rgba(26,58,122,0.2);
            border-radius: 12px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
        }
        .star-rating-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 4px;
        }
        .star-rating-input input {
            display: none;
        }
        .star-rating-input label {
            cursor: pointer;
            color: #d1d5db;
            font-size: 24px;
            transition: color 0.2s;
        }
        .star-rating-input input:checked ~ label,
        .star-rating-input label:hover,
        .star-rating-input label:hover ~ label {
            color: #f59e0b;
        }
    </style>
</head>
<body class="theme-light">
<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>
<div class="layout">
    <?php include __DIR__ . '/assets/includes/navbar.php'; ?>
    <main class="main" style="min-height: 100vh;">
        <div class="container">
            <div class="avis-page-header">
                <a href="offres.php" class="btn btn-outline btn-sm" style="margin-bottom: 20px;"><i class="bi bi-arrow-left"></i> Retour aux offres</a>
                <h1 class="avis-page-title">Avis : <?= htmlspecialchars($offre['nom_offre']) ?></h1>
                <p class="avis-page-sub">Lisez ce que nos clients pensent de cette offre ou laissez votre propre avis.</p>
            </div>

            <div class="avis-container">
                <div class="avis-list">
                    <div style="display:flex; align-items:center; gap:16px; margin-bottom:32px;">
                        <h2 style="font-family:'Sora', sans-serif; font-size:24px; color:#15233C; margin:0;">
                            Note moyenne
                        </h2>
                        <div style="font-size:24px; font-weight:800; color:#15233C;">
                            <?= count($avisList) > 0 ? $avgNote . '/5' : '—/5' ?>
                        </div>
                        <div style="color:#f59e0b; font-size:20px;">
                            <?php 
                            if (count($avisList) > 0) {
                                for ($i=1; $i<=5; $i++) {
                                    echo $i <= round($avgNote) ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
                                }
                            }
                            ?>
                        </div>
                        <div style="color:rgba(21,35,60,0.5); font-size:14px;">(<?= count($avisList) ?> avis)</div>
                    </div>

                    <?php if (count($avisList) === 0): ?>
                        <div class="avis-card" style="text-align:center; padding:60px 20px;">
                            <i class="bi bi-chat-square-text" style="font-size:40px; color:rgba(21,35,60,0.2); margin-bottom:16px; display:block;"></i>
                            <h3 style="font-family:'Sora',sans-serif; color:#15233C; font-size:18px;">Aucun avis pour le moment</h3>
                            <p style="color:rgba(21,35,60,0.6); font-size:14px;">Soyez le premier à partager votre expérience avec cette offre.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($avisList as $a): ?>
                        <div class="avis-card">
                            <div class="avis-user"><?= htmlspecialchars($a['prenom'] . ' ' . $a['nom']) ?></div>
                            <span class="avis-date">Publié le <?= date('d/m/Y', strtotime($a['date_avis'])) ?></span>
                            <div class="avis-stars">
                                <?php for ($i=1; $i<=5; $i++): ?>
                                    <i class="bi <?= $i <= $a['note'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <div class="avis-text"><?= nl2br(htmlspecialchars($a['commentaire'])) ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="avis-sidebar">
                    <div class="review-form">
                        <h3 style="font-family:'Sora',sans-serif; font-size:18px; color:#15233C; margin-bottom:24px;">Donnez votre avis</h3>
                        <?= $message ?>
                        
                        <?php if ($is_logged_in): ?>
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Votre note</label>
                                <div class="star-rating-input">
                                    <input type="radio" id="star5" name="note" value="5"><label for="star5" title="5 étoiles"><i class="bi bi-star-fill"></i></label>
                                    <input type="radio" id="star4" name="note" value="4"><label for="star4" title="4 étoiles"><i class="bi bi-star-fill"></i></label>
                                    <input type="radio" id="star3" name="note" value="3"><label for="star3" title="3 étoiles"><i class="bi bi-star-fill"></i></label>
                                    <input type="radio" id="star2" name="note" value="2"><label for="star2" title="2 étoiles"><i class="bi bi-star-fill"></i></label>
                                    <input type="radio" id="star1" name="note" value="1"><label for="star1" title="1 étoile"><i class="bi bi-star-fill"></i></label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="commentaire">Votre commentaire</label>
                                <textarea name="commentaire" id="commentaire" class="form-control" rows="5" required placeholder="Partagez votre expérience..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">Publier l'avis</button>
                        </form>
                        <?php else: ?>
                        <p style="color:rgba(21,35,60,0.7); font-size:14px; margin-bottom:20px;">Vous devez être connecté pour laisser un avis.</p>
                        <a href="login.php" class="btn btn-primary" style="width:100%; justify-content:center;">Se connecter</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
    <script src="assets/js/main.js"></script>
</body>
</html>
