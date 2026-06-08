<?php
/**
 * admin/tunnel.php — Interface d'administration du tunnel Ngrok/LocalTunnel
 * Accessible via : http://localhost/assurance/admin/tunnel.php
 *
 * Permet de configurer l'URL publique du tunnel pour les QR codes
 * (pratique pour la soutenance sans modifier config.env.php).
 */
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
require_once __DIR__ . '/../helpers/TunnelHelper.php';

$message = '';
$messageType = '';

// ── Actions POST ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'set_tunnel') {
        $url = trim($_POST['tunnel_url'] ?? '');
        if ($url && TunnelHelper::isValidTunnelUrl($url)) {
            $_SESSION['protex_ngrok_url'] = rtrim($url, '/');
            $message = '✅ URL du tunnel configurée avec succès ! Les QR codes utiliseront désormais cette URL.';
            $messageType = 'success';
        } else {
            $message = '❌ URL invalide. Assurez-vous d\'entrer une URL complète valide (ex: https://abc123.ngrok-free.app)';
            $messageType = 'error';
        }
    } elseif ($action === 'save_to_config') {
        $url = trim($_POST['tunnel_url'] ?? '');
        if ($url && TunnelHelper::isValidTunnelUrl($url)) {
            // Lire le fichier config.env.php actuel
            $configFile = __DIR__ . '/../config.env.php';
            $content    = file_get_contents($configFile);

            // Remplacer ou ajouter la clé ngrok_url
            if (str_contains($content, "'ngrok_url'")) {
                $content = preg_replace(
                    "/'ngrok_url'\s*=>\s*'[^']*'/",
                    "'ngrok_url' => '" . addslashes($url) . "'",
                    $content
                );
            } else {
                // Ajouter avant la fermeture du tableau
                $content = str_replace(
                    "];",
                    "    // Tunnel public (Ngrok/LocalTunnel) pour QR codes mobile\n    'ngrok_url' => '" . addslashes($url) . "',\n];",
                    $content
                );
            }

            if (file_put_contents($configFile, $content) !== false) {
                $_SESSION['protex_ngrok_url'] = rtrim($url, '/');
                $message = '✅ URL sauvegardée dans config.env.php ET session. Permanente jusqu\'au prochain redémarrage.';
                $messageType = 'success';
            } else {
                $message = '❌ Impossible d\'écrire dans config.env.php. Permission refusée. Utilisez la session uniquement.';
                $messageType = 'error';
            }
        } else {
            $message = '❌ URL invalide.';
            $messageType = 'error';
        }
    } elseif ($action === 'clear_tunnel') {
        unset($_SESSION['protex_ngrok_url']);
        $message = '🗑️ Tunnel effacé de la session. Retour à l\'IP locale automatique.';
        $messageType = 'info';
    }
}

// ── Diagnostic actuel ─────────────────────────────────────────────────────────
$diag = TunnelHelper::getDiagnostic();
$base = defined('BASE_URL') ? BASE_URL : '/assurance';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌐 Configuration Tunnel — Protex Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #1a3a7a;
            --accent:  #f59e0b;
            --success: #10b981;
            --error:   #ef4444;
            --info:    #3b82f6;
            --bg:      #0f172a;
            --card:    #1e293b;
            --border:  #334155;
            --text:    #e2e8f0;
            --muted:   #94a3b8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 2rem 1rem;
        }
        .container { max-width: 900px; margin: 0 auto; }

        /* Header */
        .admin-header {
            display: flex; align-items: center; gap: 1rem;
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        .admin-header .logo {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, var(--primary), #2563eb);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }
        .admin-header h1 { font-size: 1.75rem; font-weight: 700; }
        .admin-header p  { color: var(--muted); font-size: 0.9rem; margin-top: .25rem; }

        /* Cards */
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .card-title {
            font-size: 1.1rem; font-weight: 600;
            margin-bottom: 1rem;
            display: flex; align-items: center; gap: .5rem;
            color: var(--accent);
        }

        /* Status badge */
        .status-badge {
            display: inline-flex; align-items: center; gap: .4rem;
            padding: .35rem .85rem;
            border-radius: 999px;
            font-size: 0.8rem; font-weight: 600;
            letter-spacing: .03em;
        }
        .badge-session  { background: rgba(16,185,129,.15); color: #10b981; border: 1px solid rgba(16,185,129,.3); }
        .badge-config   { background: rgba(59,130,246,.15); color: #60a5fa; border: 1px solid rgba(59,130,246,.3); }
        .badge-local    { background: rgba(245,158,11,.15); color: #f59e0b; border: 1px solid rgba(245,158,11,.3); }
        .badge-fallback { background: rgba(239,68,68,.15);  color: #f87171; border: 1px solid rgba(239,68,68,.3);  }

        /* Diagnostic grid */
        .diag-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
        }
        .diag-item {
            background: rgba(255,255,255,.03);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1rem;
        }
        .diag-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); margin-bottom: .3rem; }
        .diag-value { font-size: .95rem; font-family: monospace; color: var(--text); word-break: break-all; }
        .diag-value.active { color: #10b981; font-weight: 600; }

        /* Form */
        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; font-size: .85rem; font-weight: 600; color: var(--muted); margin-bottom: .5rem; }
        .form-input {
            width: 100%; padding: .75rem 1rem;
            background: rgba(255,255,255,.05);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-size: .95rem;
            font-family: monospace;
            transition: border-color .2s;
        }
        .form-input:focus { outline: none; border-color: var(--accent); }
        .form-input::placeholder { color: var(--muted); }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .75rem 1.5rem;
            border: none; border-radius: 10px;
            font-size: .9rem; font-weight: 600; cursor: pointer;
            text-decoration: none; transition: all .2s;
        }
        .btn-primary { background: linear-gradient(135deg, var(--primary), #2563eb); color: white; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 20px rgba(37,99,235,.4); }
        .btn-success { background: linear-gradient(135deg, #059669, #10b981); color: white; }
        .btn-success:hover { transform: translateY(-1px); box-shadow: 0 4px 20px rgba(16,185,129,.4); }
        .btn-danger  { background: rgba(239,68,68,.1); color: #f87171; border: 1px solid rgba(239,68,68,.3); }
        .btn-danger:hover { background: rgba(239,68,68,.2); }
        .btn-group   { display: flex; gap: .75rem; flex-wrap: wrap; margin-top: 1rem; }

        /* Alert */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex; align-items: flex-start; gap: .75rem;
        }
        .alert-success { background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.3); color: #6ee7b7; }
        .alert-error   { background: rgba(239,68,68,.1);  border: 1px solid rgba(239,68,68,.3);  color: #fca5a5; }
        .alert-info    { background: rgba(59,130,246,.1); border: 1px solid rgba(59,130,246,.3); color: #93c5fd; }

        /* QR Preview */
        .qr-preview { text-align: center; padding: 1rem; }
        .qr-preview img {
            max-width: 200px; border-radius: 12px;
            background: white; padding: .75rem;
        }
        .qr-url { font-family: monospace; font-size: .8rem; color: var(--muted); margin-top: .5rem; word-break: break-all; }

        /* Steps */
        .steps { counter-reset: step; }
        .step {
            display: flex; gap: 1rem;
            padding: .75rem 0;
            border-bottom: 1px solid var(--border);
        }
        .step:last-child { border-bottom: none; }
        .step-num {
            width: 28px; height: 28px; min-width: 28px;
            background: linear-gradient(135deg, var(--primary), #2563eb);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: .8rem; font-weight: 700;
        }
        .step-content { flex: 1; }
        .step-title { font-weight: 600; margin-bottom: .25rem; }
        .step-desc  { font-size: .85rem; color: var(--muted); }
        .code-inline {
            font-family: monospace; font-size: .85rem;
            background: rgba(245,158,11,.1); color: var(--accent);
            padding: .15rem .5rem; border-radius: 5px;
        }

        /* Back link */
        .back-link {
            display: inline-flex; align-items: center; gap: .5rem;
            color: var(--muted); font-size: .9rem; text-decoration: none;
            margin-top: 2rem; transition: color .2s;
        }
        .back-link:hover { color: var(--text); }

        /* Method indicator */
        .method-indicator {
            display: flex; align-items: center; gap: .75rem;
            padding: 1rem 1.25rem;
            background: rgba(255,255,255,.03);
            border-radius: 10px; border: 1px solid var(--border);
            margin-bottom: 1.25rem;
        }
    </style>
</head>
<body>
<div class="container">

    <!-- Header -->
    <div class="admin-header">
        <div class="logo">🌐</div>
        <div>
            <h1>Configuration Tunnel Public</h1>
            <p>Protex Admin — QR Code Mobile pour Soutenance</p>
        </div>
    </div>

    <!-- Message flash -->
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'error' : 'info') ?>">
        <span style="font-size:1.25rem">
            <?= $messageType === 'success' ? '✅' : ($messageType === 'error' ? '❌' : 'ℹ️') ?>
        </span>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
    <?php endif; ?>

    <!-- Statut actuel -->
    <div class="card">
        <div class="card-title"><i class="bi bi-activity"></i> Statut Actuel de Résolution</div>

        <div class="method-indicator">
            <span>Méthode active :</span>
            <?php
            $method = $diag['method'];
            $badges = [
                'session'          => ['class' => 'badge-session',  'label' => '🟢 Session admin',          'desc' => 'URL configurée dans cette session'],
                'config_env'       => ['class' => 'badge-config',   'label' => '🔵 config.env.php',         'desc' => 'URL lue depuis le fichier de config'],
                'local_ip'         => ['class' => 'badge-local',    'label' => '🟡 IP locale Wi-Fi',        'desc' => 'IP réseau détectée automatiquement'],
                'localhost_fallback'=> ['class' => 'badge-fallback','label' => '🔴 Localhost (fallback)',    'desc' => 'Non accessible depuis mobile'],
            ];
            $b = $badges[$method] ?? $badges['localhost_fallback'];
            ?>
            <span class="status-badge <?= $b['class'] ?>"><?= $b['label'] ?></span>
            <span style="color:var(--muted); font-size:.85rem">— <?= $b['desc'] ?></span>
        </div>

        <div class="diag-grid">
            <div class="diag-item">
                <div class="diag-label">URL résolue (utilisée dans QR)</div>
                <div class="diag-value active"><?= htmlspecialchars($diag['resolved_url']) ?></div>
            </div>
            <div class="diag-item">
                <div class="diag-label">IP locale Wi-Fi</div>
                <div class="diag-value"><?= htmlspecialchars($diag['local_ip']) ?></div>
            </div>
            <div class="diag-item">
                <div class="diag-label">HTTP_HOST serveur</div>
                <div class="diag-value"><?= htmlspecialchars($diag['localhost_host']) ?></div>
            </div>
            <div class="diag-item">
                <div class="diag-label">Ngrok (config.env.php)</div>
                <div class="diag-value"><?= $diag['env_ngrok_url'] ? htmlspecialchars($diag['env_ngrok_url']) : '<em style="color:var(--muted)">Non configuré</em>' ?></div>
            </div>
            <div class="diag-item">
                <div class="diag-label">Ngrok (session active)</div>
                <div class="diag-value"><?= $diag['session_ngrok_url'] ? htmlspecialchars($diag['session_ngrok_url']) : '<em style="color:var(--muted)">Non configuré</em>' ?></div>
            </div>
        </div>
    </div>

    <!-- QR Code aperçu -->
    <div class="card">
        <div class="card-title"><i class="bi bi-qr-code"></i> Aperçu QR Code (Contrat #1)</div>
        <div style="display:flex; gap:2rem; align-items:flex-start; flex-wrap:wrap;">
            <div class="qr-preview">
                <img src="<?= $base ?>/view/FrontOffice/qrcode_contrat.php?id=1" alt="QR Code contrat 1" onerror="this.style.display='none'">
                <div class="qr-url"><?= htmlspecialchars($diag['resolved_url']) ?>/view/FrontOffice/contratshow.php?id=1</div>
            </div>
            <div style="flex:1; min-width:200px;">
                <p style="color:var(--muted); font-size:.9rem; line-height:1.6; margin-bottom:.75rem;">
                    Scannez ce QR code avec votre smartphone pour vérifier la connectivité.
                    Si le scan échoue, configurez un tunnel Ngrok ci-dessous.
                </p>
                <?php if ($method === 'local_ip'): ?>
                <div class="alert alert-info">
                    <span>ℹ️</span>
                    <span>Mode <strong>IP Wi-Fi</strong> actif — fonctionne si votre téléphone est sur le <strong>même réseau Wi-Fi</strong> que cet ordinateur.</span>
                </div>
                <?php elseif ($method === 'localhost_fallback'): ?>
                <div class="alert alert-error">
                    <span>⚠️</span>
                    <span>Mode <strong>localhost</strong> — le QR code ne fonctionnera <strong>pas</strong> depuis un smartphone. Configurez Ngrok ci-dessous.</span>
                </div>
                <?php else: ?>
                <div class="alert alert-success">
                    <span>🎉</span>
                    <span>Tunnel configuré ! Le QR code est accessible depuis <strong>n'importe quel réseau</strong>.</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Configurer tunnel -->
    <div class="card">
        <div class="card-title"><i class="bi bi-plug-fill"></i> Configurer le Tunnel</div>

        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="tunnel_url">URL du tunnel (Ngrok, LocalTunnel, etc.)</label>
                <input
                    type="url"
                    id="tunnel_url"
                    name="tunnel_url"
                    class="form-input"
                    placeholder="https://abc123.ngrok-free.app"
                    value="<?= htmlspecialchars($diag['session_ngrok_url'] ?: $diag['env_ngrok_url']) ?>"
                    required
                >
                <div style="font-size:.8rem; color:var(--muted); margin-top:.5rem;">
                    Format attendu : <span class="code-inline">https://xxxxxxxx.ngrok-free.app</span> (sans slash final)
                </div>
            </div>

            <div class="btn-group">
                <button type="submit" name="action" value="set_tunnel" class="btn btn-primary">
                    <i class="bi bi-lightning-charge-fill"></i> Activer (session uniquement)
                </button>
                <button type="submit" name="action" value="save_to_config" class="btn btn-success">
                    <i class="bi bi-floppy2-fill"></i> Sauvegarder dans config.env.php
                </button>
            </div>
        </form>

        <?php if ($diag['session_ngrok_url']): ?>
        <form method="POST" style="margin-top:1rem;">
            <button type="submit" name="action" value="clear_tunnel" class="btn btn-danger">
                <i class="bi bi-x-circle"></i> Effacer le tunnel de la session
            </button>
        </form>
        <?php endif; ?>
    </div>

    <!-- Guide Ngrok -->
    <div class="card">
        <div class="card-title"><i class="bi bi-book-half"></i> Guide Ngrok — 3 Étapes pour la Soutenance</div>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-content">
                    <div class="step-title">Télécharger & Installer Ngrok</div>
                    <div class="step-desc">
                        Allez sur <strong>ngrok.com</strong> → Téléchargez l'exécutable Windows → Extrayez <span class="code-inline">ngrok.exe</span> dans <span class="code-inline">C:\xampp\htdocs\assurance\</span>
                    </div>
                </div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-content">
                    <div class="step-title">Lancer le tunnel dans un terminal</div>
                    <div class="step-desc">
                        Ouvrez PowerShell dans le dossier et exécutez :<br>
                        <span class="code-inline">ngrok http 80</span><br>
                        Vous verrez une URL du type <span class="code-inline">https://abc123.ngrok-free.app</span>
                    </div>
                </div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-content">
                    <div class="step-title">Copiez l'URL ici et activez</div>
                    <div class="step-desc">
                        Collez l'URL Forwarding dans le champ ci-dessus, cliquez <strong>"Activer"</strong>.
                        Les QR codes générés seront immédiatement accessibles depuis n'importe quel smartphone !
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alternatives -->
    <div class="card">
        <div class="card-title"><i class="bi bi-shuffle"></i> Alternatives à Ngrok</div>
        <div class="diag-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <div class="diag-item">
                <div class="diag-label">📡 LocalTunnel (gratuit, sans compte)</div>
                <div class="diag-value" style="font-size:.85rem;">
                    <span class="code-inline">npm install -g localtunnel</span><br>
                    <span class="code-inline">lt --port 80</span>
                </div>
            </div>
            <div class="diag-item">
                <div class="diag-label">🔗 Cloudflare Tunnel (gratuit)</div>
                <div class="diag-value" style="font-size:.85rem;">
                    <span class="code-inline">cloudflared tunnel --url http://localhost:80</span>
                </div>
            </div>
            <div class="diag-item">
                <div class="diag-label">📶 Même Wi-Fi (déjà actif)</div>
                <div class="diag-value" style="font-size:.85rem;">
                    IP détectée : <strong><?= htmlspecialchars($diag['local_ip']) ?></strong><br>
                    <span style="color:var(--muted);">Fonctionne si téléphone = même Wi-Fi</span>
                </div>
            </div>
        </div>
    </div>

    <a href="<?= $base ?>/view/FrontOffice/contrat.php" class="back-link">
        <i class="bi bi-arrow-left"></i> Retour aux contrats
    </a>
</div>

<script>
// Auto-refresh du QR aperçu si l'URL change
document.getElementById('tunnel_url')?.addEventListener('change', function() {
    const img = document.querySelector('.qr-preview img');
    if (img) {
        img.src = img.src.split('?')[0] + '?id=1&_ts=' + Date.now();
    }
});
</script>
</body>
</html>
