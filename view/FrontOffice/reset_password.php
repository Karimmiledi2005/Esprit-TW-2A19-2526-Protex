<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../controller/Client_Con.php';

$action = $_GET['action'] ?? '';
$token  = $_GET['token'] ?? $_POST['token'] ?? '';
$error  = '';

if (empty($token)) {
    $error = "Lien invalide ou expiré.";
} else {
    $db    = config::getConnexion();
    $stmt  = $db->prepare("SELECT * FROM password_resets WHERE token = :token AND used = 0 LIMIT 1");
    $stmt->execute(['token' => $token]);
    $reset = $stmt->fetch();

    if (!$reset) {
        $error = "Lien invalide ou déjà utilisé.";
    } elseif (new DateTime() > new DateTime($reset['expires_at'])) {
        $error = "Ce lien a expiré.";
    }
}

/* --- MAGIC LINK : auto-login + redirect to profile --- */
if ($action === 'magic' && !$error && $reset) {
    $stmt = $db->prepare("SELECT * FROM user WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $reset['email']]);
    $user = $stmt->fetch();

    if ($user) {
        $db->prepare("UPDATE password_resets SET used = 1 WHERE id = :id")->execute(['id' => $reset['id']]);

        $_SESSION['user_id']       = (int)$user['id_user'];
        $_SESSION['id_user']       = (int)$user['id_user'];
        $_SESSION['role']          = $user['role'] ?? 'client';
        $_SESSION['nom']           = $user['nom'] ?? '';
        $_SESSION['prenom']        = $user['prenom'] ?? '';
        $_SESSION['user_nom']      = $user['nom'] ?? '';
        $_SESSION['user_prenom']   = $user['prenom'] ?? '';
        $_SESSION['user_email']    = $user['email'] ?? '';
        $_SESSION['user_role']     = $user['role'] ?? 'client';
        $_SESSION['user_avatar']   = $user['avatar_url'] ?? 'default.png';
        $_SESSION['id_agence']     = $user['id_agence'] ?? null;
        $_SESSION['agence_id']     = $user['id_agence'] ?? null;
        $_SESSION['last_activity'] = time();
        $_SESSION['magic_login']   = true;

        $base = defined('BASE_URL') ? BASE_URL : '';
        header("Location: " . $base . "/view/FrontOffice/monprofile.php?force_pwd=1#securite");
        exit;
    }
    $error = "Compte introuvable.";
}

/* --- POST : form-based password reset (fallback) --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && $reset) {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = "Le mot de passe doit faire au moins 8 caractères.";
    } elseif ($password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        $email  = $reset['email'];
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $db->prepare("UPDATE user SET mot_de_passe = :mdp WHERE email = :email")->execute(['mdp' => $hashed, 'email' => $email]);
        $db->prepare("UPDATE password_resets SET used = 1 WHERE id = :id")->execute(['id' => $reset['id']]);

        header("Location: confirmation.html?type=reset");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation - Protex</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --ptx-orange: #FF6B1A; --ptx-navy: #1A3A7A; --ptx-dark: #0a0f1e; }
        body {
            margin:0; padding:20px;
            font-family:'Outfit',sans-serif;
            background:radial-gradient(circle at top right,#12244a,#0a0f1e);
            color:#fff; min-height:100vh;
            display:flex; align-items:center; justify-content:center;
        }
        .reset-card {
            background:rgba(255,255,255,0.05); backdrop-filter:blur(15px);
            -webkit-backdrop-filter:blur(15px);
            border:1px solid rgba(255,255,255,0.1); border-radius:24px;
            padding:40px; width:100%; max-width:400px; text-align:center;
            box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);
        }
        .logo-text { font-size:28px; font-weight:700; color:var(--ptx-orange); margin-bottom:30px; display:block; }
        .form-group { margin-bottom:20px; text-align:left; }
        .form-label { display:block; font-size:14px; color:rgba(255,255,255,0.7); margin-bottom:8px; }
        .form-input {
            width:100%; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1);
            padding:12px 16px; border-radius:10px; color:#fff; font-family:inherit;
            font-size:15px; box-sizing:border-box; transition:all 0.3s;
        }
        .form-input:focus { outline:none; border-color:var(--ptx-orange); background:rgba(255,255,255,0.1); }
        .btn-primary {
            background:var(--ptx-orange); color:#fff; border:none; padding:14px;
            border-radius:10px; font-weight:600; font-size:16px; cursor:pointer;
            width:100%; transition:all 0.3s; margin-top:10px;
        }
        .btn-primary:hover { background:#ff8c00; transform:translateY(-1px); }
        .error-msg {
            background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.2);
            color:#ef4444; padding:12px; border-radius:10px; margin-bottom:20px; font-size:14px;
        }
        .back-link { display:inline-block; margin-top:20px; color:rgba(255,255,255,0.5); text-decoration:none; font-size:14px; transition:color 0.3s; }
        .back-link:hover { color:var(--ptx-orange); }
    </style>
</head>
<body>
    <div class="reset-card">
        <span class="logo-text">Protex</span>
        <h1 style="font-size:22px; margin:0 0 10px;">Réinitialisation</h1>
        <p style="color:rgba(255,255,255,0.6); font-size:14px; margin-bottom:30px;">Veuillez saisir votre nouveau mot de passe.</p>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
            <a href="login.html" class="back-link">← Retour à la connexion</a>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="form-group">
                    <label class="form-label">Nouveau mot de passe</label>
                    <input type="password" name="password" class="form-input" required placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label class="form-label">Confirmer le mot de passe</label>
                    <input type="password" name="confirm_password" class="form-input" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn-primary">Mettre à jour le mot de passe</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
