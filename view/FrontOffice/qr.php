<?php
$url = $_GET['url'] ?? 'https://eliminate-masculine-handgun.ngrok-free.dev/assurance/view/FrontOffice/login.html';
$encoded = urlencode($url);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QR Code - Protex</title>
<style>
body {
    margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
    background:radial-gradient(circle at top right,#12244a,#0a0f1e);
    font-family:sans-serif; flex-direction:column; gap:20px;
}
.card {
    background:rgba(255,255,255,0.05); backdrop-filter:blur(15px);
    border:1px solid rgba(255,255,255,0.1); border-radius:24px;
    padding:40px; text-align:center; max-width:400px; width:90%;
}
h1 { color:#FF6B1A; margin:0 0 10px; font-size:24px; }
p { color:rgba(255,255,255,0.6); margin:0 0 20px; font-size:14px; }
.qr img { width:280px; height:280px; border-radius:12px; }
.url { color:#00b4d8; font-size:13px; word-break:break-all; margin-top:15px; }
</style>
</head>
<body>
<div class="card">
    <h1>Protex</h1>
    <p>Scannez pour accéder à l'application</p>
    <div class="qr">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?= $encoded ?>" alt="QR Code">
    </div>
    <div class="url"><?= htmlspecialchars($url) ?></div>
</div>
</body>
</html>
