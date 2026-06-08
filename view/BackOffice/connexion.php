<?php
/**
 * view/BackOffice/connexion.php — Page de connexion Protex
 */
if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 3) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
require_once dirname(__DIR__, 2) . '/helpers/CsrfHelper.php';
$__base = defined('BASE_URL') ? BASE_URL : '';
$err    = $err ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; min-height:100vh; display:flex; align-items:center; justify-content:center; background:#0f141e; overflow:hidden; }
        .orb { position:fixed; border-radius:50%; filter:blur(80px); pointer-events:none; }
        .orb-1 { width:400px; height:400px; background:rgba(26,58,122,.4); top:-100px; left:-100px; }
        .orb-2 { width:350px; height:350px; background:rgba(255,107,26,.2); bottom:-80px; right:-80px; }
        .wrap { position:relative; z-index:10; width:100%; max-width:420px; padding:24px; }
        .card { background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08); border-radius:24px; padding:44px 40px; backdrop-filter:blur(20px); }
        .logo { text-align:center; margin-bottom:32px; }
        .logo img { width:56px; height:56px; border-radius:14px; object-fit:cover; margin-bottom:14px; }
        .logo h1 { font-family:'Sora',sans-serif; font-size:24px; font-weight:800; color:#fff; }
        .logo p  { font-size:13px; color:rgba(255,255,255,.45); margin-top:5px; }
        .alert { padding:12px 16px; border-radius:12px; font-size:13px; margin-bottom:20px; background:rgba(230,57,70,.12); border:1px solid rgba(230,57,70,.25); color:#ff6b6b; }
        .fg { margin-bottom:20px; }
        .fg label { display:block; font-size:11px; font-weight:700; color:rgba(255,255,255,.55); text-transform:uppercase; letter-spacing:.06em; margin-bottom:8px; }
        .iw { position:relative; }
        .iw i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:rgba(255,255,255,.3); font-size:15px; }
        .iw input { width:100%; padding:13px 14px 13px 42px; border-radius:12px; border:1px solid rgba(255,255,255,.1); background:rgba(255,255,255,.04); color:#fff; font-size:14px; font-family:'DM Sans',sans-serif; outline:none; transition:border-color .2s; }
        .iw input:focus { border-color:#FF6B1A; box-shadow:0 0 0 3px rgba(255,107,26,.15); }
        .iw input::placeholder { color:rgba(255,255,255,.25); }
        .eye-btn { position:absolute; right:14px; top:50%; transform:translateY(-50%); background:none; border:none; color:rgba(255,255,255,.3); cursor:pointer; font-size:16px; padding:0; }
        .btn-login { width:100%; padding:14px; border-radius:12px; border:none; background:linear-gradient(135deg,#FF6B1A,#e55d16); color:#fff; font-size:15px; font-weight:700; font-family:'Sora',sans-serif; cursor:pointer; transition:transform .2s,box-shadow .2s; margin-top:6px; }
        .btn-login:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(255,107,26,.35); }
        .links { text-align:center; margin-top:20px; font-size:13px; color:rgba(255,255,255,.4); }
        .links a { color:#FF6B1A; text-decoration:none; }
        .links a:hover { text-decoration:underline; }
        .hint { margin-top:22px; padding:14px; border-radius:12px; background:rgba(46,196,182,.08); border:1px solid rgba(46,196,182,.15); font-size:12px; color:rgba(255,255,255,.5); text-align:center; line-height:1.7; }
        .hint strong { color:#2ec4b6; }
    </style>
</head>
<body>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="wrap">
    <div class="card">
        <div class="logo">
            <img src="<?= htmlspecialchars($__base) ?>/view/FrontOffice/logo.png" alt="Protex"
                 onerror="this.style.cssText='background:#FF6B1A;border-radius:14px;'">
            <h1>Protex</h1>
            <p>Espace Back-Office</p>
        </div>

        <?php if (!empty($err)): ?>
        <div class="alert"><i class="bi bi-exclamation-triangle-fill"></i>
            <?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>

        <form action="<?= htmlspecialchars($__base) ?>/controller/AuthController.php" method="POST">
            <?= CsrfHelper::field() ?>
            <div class="fg">
                <label>Adresse email</label>
                <div class="iw">
                    <i class="bi bi-envelope"></i>
                    <input type="email" name="email" placeholder="admin@protex.tn"
                           value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>" required autofocus>
                </div>
            </div>
            <div class="fg">
                <label>Mot de passe</label>
                <div class="iw">
                    <i class="bi bi-lock"></i>
                    <input type="password" name="password" id="pwd" placeholder="••••••••" required>
                    <button type="button" class="eye-btn" onclick="togglePwd()">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right"></i> Se connecter
            </button>
        </form>

        <div class="links">
            <a href="<?= htmlspecialchars($__base) ?>/view/FrontOffice/login.html">
                Espace client &rarr;
            </a>
        </div>

        <div class="hint">
            <strong>Comptes de test (mot de passe : Azerty123!)</strong><br>
            Admin : medkarimmiledi@gmail.com<br>
            Client : sansbibi@gmail.com
        </div>
    </div>
</div>

<script>
function togglePwd() {
    const p = document.getElementById('pwd');
    const e = document.getElementById('eyeIcon');
    if (p.type === 'password') { p.type = 'text';     e.className = 'bi bi-eye-slash'; }
    else                        { p.type = 'password'; e.className = 'bi bi-eye'; }
}
</script>
</body>
</html>
