<?php
// view/BackOffice/403.php — Page d'erreur 403 BackOffice
if (!isset($errMsg)) {
    $errMsg = 'Accès non autorisé.';
}
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>403 — Accès refusé | Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <style>
        body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:var(--bg-deep,#0a0f1e); margin:0; font-family:'Inter',sans-serif; }
        .err-box { text-align:center; padding:48px 40px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,71,87,0.3); border-radius:20px; max-width:460px; }
        .err-code { font-size:80px; font-weight:900; color:#ff4757; line-height:1; }
        .err-title { font-size:22px; font-weight:700; color:#fff; margin:12px 0 8px; }
        .err-msg { font-size:14px; color:rgba(255,255,255,0.55); margin-bottom:28px; }
        .btn-back { display:inline-flex; align-items:center; gap:8px; padding:10px 22px; background:linear-gradient(135deg,#1A3A7A,#2f5fc2); color:#fff; border-radius:10px; text-decoration:none; font-size:14px; font-weight:600; transition:.2s; }
        .btn-back:hover { opacity:.85; transform:translateY(-1px); }
    </style>
</head>
<body>
    <div class="err-box">
        <div class="err-code">403</div>
        <div class="err-title"><i class="bi bi-shield-lock" style="color:#ff4757;margin-right:8px;"></i>Accès refusé</div>
        <div class="err-msg"><?= htmlspecialchars($errMsg, ENT_QUOTES, 'UTF-8') ?></div>
        <a href="javascript:history.back()" class="btn-back">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>
</body>
</html>
