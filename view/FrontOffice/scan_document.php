<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();
require_once __DIR__ . '/../../connexion.php';
$db = config::getConnexion();
$user_id = SessionGuard::userId();
$user_nom = $_SESSION['user_nom'] ?? '';
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Scan Document - Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <style>
        .card { background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: var(--radius-lg); backdrop-filter: blur(20px); overflow: hidden; margin-bottom: 20px; }
        .card-header { padding: 20px 24px; border-bottom: 1px solid var(--glass-border); display: flex; align-items: center; justify-content: space-between; }
        .card-title { font-family: var(--font-display); font-size: 15px; font-weight: 600; color: var(--text-primary); }
        .card-body { padding: 24px; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: var(--radius-md); font-family: var(--font-body); font-size: 13px; font-weight: 500; cursor: pointer; transition: var(--transition); text-decoration: none; border: none; }
        .btn-primary { background: linear-gradient(135deg, var(--accent), var(--accent-dark)); color: #fff; }
        .btn-primary:hover { box-shadow: 0 4px 14px rgba(0, 180, 216, 0.4); }
        .btn-outline { background: transparent; border: 1px solid var(--glass-border); color: var(--text-secondary); }
        .btn-outline:hover { border-color: var(--accent); color: var(--accent); }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .btn-block { width: 100%; justify-content: center; }
        #scanner video { width: 100%; max-width: 480px; border-radius: 12px; transform: scaleX(-1); }
        #scanner { text-align: center; margin-bottom: 20px; }
        #resultTable { width: 100%; border-collapse: collapse; margin-top: 16px; }
        #resultTable th, #resultTable td { padding: 10px 14px; text-align: left; border-bottom: 1px solid var(--glass-border); font-size: 13px; color: var(--text-primary); }
        #resultTable th { color: var(--text-secondary); font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-ok { background: rgba(46, 213, 115, 0.2); color: #2ed573; }
        .status-err { background: rgba(255, 71, 87, 0.2); color: #ff4757; }
    </style>
</head>
<body>
    <div class="background"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="layout">
        <?php require_once __DIR__.'/assets/includes/navbar.php'; ?>
        <main class="main">
            <div class="page-header">
                <div>
                    <div class="page-title-main">Scan Document</div>
                    <div class="page-breadcrumb">
                        <i class="bi bi-house"></i>
                        <a href="client.php" style="color:inherit;text-decoration:none;">Accueil</a>
                        <i class="bi bi-chevron-right" style="font-size:10px"></i>
                        <span>Scan Document</span>
                    </div>
                </div>
            </div>
            <div class="content">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="bi bi-file-earmark-text" style="color:var(--accent);margin-right:8px"></i>Extraction automatique</div>
                    </div>
                    <div class="card-body">
                        <p style="font-size:13px;color:var(--text-secondary);margin-bottom:16px;">Prenez en photo une carte d'identité, un passeport ou tout document officiel pour en extraire automatiquement les informations (CIN, nom, date de naissance...).</p>
                        <div id="scanner">
                            <video id="video" autoplay playsinline></video>
                            <div style="margin-top:12px;display:flex;gap:10px;justify-content:center;">
                                <button class="btn btn-primary btn-sm" id="captureBtn"><i class="bi bi-camera"></i> Capturer</button>
                                <button class="btn btn-outline btn-sm" id="uploadBtn"><i class="bi bi-upload"></i> Choisir un fichier</button>
                                <input type="file" id="fileInput" accept="image/*" style="display:none">
                            </div>
                            <div id="processing" style="display:none;margin-top:12px;">
                                <i class="bi bi-arrow-repeat spin"></i> Analyse en cours...
                            </div>
                        </div>
                        <canvas id="canvas" style="display:none"></canvas>
                        <div id="resultArea" style="display:none;">
                            <div style="margin-top:16px;border-top:1px solid var(--glass-border);padding-top:16px;">
                                <div style="font-size:13px;font-weight:600;color:var(--text-primary);margin-bottom:8px;">Résultats de l'extraction</div>
                                <table id="resultTable">
                                    <tr><th>Champ</th><th>Valeur</th></tr>
                                    <tr><td>Nom</td><td id="r_nom">—</td></tr>
                                    <tr><td>Prénom</td><td id="r_prenom">—</td></tr>
                                    <tr><td>Date de naissance</td><td id="r_date">—</td></tr>
                                    <tr><td>N° CIN</td><td id="r_cin">—</td></tr>
                                    <tr><td>Nationalité</td><td id="r_nat">—</td></tr>
                                    <tr><td>Email</td><td id="r_email">—</td></tr>
                                    <tr><td>Téléphone</td><td id="r_tel">—</td></tr>
                                    <tr><td>Adresse</td><td id="r_adresse">—</td></tr>
                                    <tr><td>Confiance</td><td id="r_confiance">—</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const captureBtn = document.getElementById('captureBtn');
        const uploadBtn = document.getElementById('uploadBtn');
        const fileInput = document.getElementById('fileInput');
        const processing = document.getElementById('processing');
        const resultArea = document.getElementById('resultArea');

        let stream = null;
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(s => { stream = s; video.srcObject = s; })
            .catch(() => { captureBtn.disabled = true; captureBtn.textContent = 'Caméra indisponible'; });

        function sendToOCR(imageData) {
            processing.style.display = 'block';
            resultArea.style.display = 'none';
            fetch('http://localhost:5007/extract_document', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ image: imageData })
            })
            .then(r => r.json())
            .then(res => {
                processing.style.display = 'none';
                if (res.success) {
                    resultArea.style.display = 'block';
                    const d = res.data;
                    document.getElementById('r_nom').textContent = d.nom || '—';
                    document.getElementById('r_prenom').textContent = d.prenom || '—';
                    document.getElementById('r_date').textContent = d.date_naissance || '—';
                    document.getElementById('r_cin').textContent = d.cin_number || '—';
                    document.getElementById('r_nat').textContent = d.nationalite || '—';
                    document.getElementById('r_email').textContent = d.email || '—';
                    document.getElementById('r_tel').textContent = d.telephone || '—';
                    document.getElementById('r_adresse').textContent = d.adresse || '—';
                    document.getElementById('r_confiance').textContent = Math.round(d.confiance * 100) + '%';
                } else {
                    alert('Erreur : ' + (res.message || 'Échec de l\'extraction'));
                }
            })
            .catch(() => {
                processing.style.display = 'none';
                alert('Erreur de connexion au moteur OCR (port 5007). Vérifiez qu\'il est bien lancé.');
            });
        }

        captureBtn.addEventListener('click', () => {
            if (!stream) return;
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
            sendToOCR(dataUrl);
        });

        uploadBtn.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => sendToOCR(ev.target.result);
            reader.readAsDataURL(file);
        });
    </script>
</body>
</html>
