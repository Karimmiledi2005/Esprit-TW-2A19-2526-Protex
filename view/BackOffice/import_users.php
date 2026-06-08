<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireRoles(['superadmin', 'admin']);

if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}

$db = config::getConnexion();

// Générer le modèle CSV au format download
if (isset($_GET['template']) && $_GET['template'] === '1') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="template_import_utilisateurs.csv"');
    $out = fopen('php://output', 'w');
    // BOM UTF-8 pour Excel
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ['nom', 'prenom', 'email', 'telephone', 'role', 'agence_id'], ';');
    fputcsv($out, ['Ben Ali', 'Ahmed', 'ahmed.benali@gmail.com', '98123456', 'client', '1'], ';');
    fputcsv($out, ['Trabelsi', 'Sonia', 'sonia.trabelsi@yahoo.com', '55234567', 'agent', '1'], ';');
    fputcsv($out, ['Mnasri', 'Kamel', 'kamel.mnasri@protex.tn', '22345678', 'admin', '2'], ';');
    fclose($out);
    exit;
}

// Traitement de l'upload via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    header('Content-Type: application/json; charset=utf-8');

    // Vérification CSRF
    $csrf = $_POST['csrf_token'] ?? '';
    if (empty($csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
        exit;
    }

    $file = $_FILES['csv_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Erreur lors du téléchargement du fichier.']);
        exit;
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Fichier trop volumineux (max 2 Mo).']);
        exit;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (strtolower($ext) !== 'csv') {
        echo json_encode(['success' => false, 'message' => 'Format de fichier non autorisé. Veuillez fournir un fichier .csv.']);
        exit;
    }

    $filepath = $file['tmp_name'];
    $separator = ',';
    
    // Détection automatique du séparateur (virgule ou point-virgule)
    if (($handle = fopen($filepath, 'r')) !== false) {
        $firstLine = fgets($handle);
        if (str_contains($firstLine, ';')) {
            $separator = ';';
        }
        fclose($handle);
    }

    require_once __DIR__ . '/../../controller/Client_Con.php';
    $controller = new UserController();

    $imported = 0;
    $errors = [];
    $lineNumber = 1;

    // Charger les agences existantes pour validation rapide
    $agencesStmt = $db->query("SELECT id_agence FROM agence");
    $validAgences = $agencesStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    if (($handle = fopen($filepath, 'r')) !== false) {
        // Ignorer la ligne d'en-tête
        $headers = fgetcsv($handle, 1000, $separator);
        
        // Nettoyer les en-têtes (enlever le BOM si présent)
        if ($headers) {
            $headers[0] = preg_replace('/[\x{FEFF}\x{FFFE}]/u', '', $headers[0]);
        }

        while (($row = fgetcsv($handle, 1000, $separator)) !== false) {
            $lineNumber++;
            
            // Ignorer les lignes vides
            if (empty($row) || (count($row) === 1 && empty($row[0]))) {
                continue;
            }

            // Associer colonnes
            $data = [];
            foreach ($headers as $i => $header) {
                $hName = strtolower(trim($header));
                $data[$hName] = isset($row[$i]) ? trim($row[$i]) : '';
            }

            $nom = $data['nom'] ?? '';
            $prenom = $data['prenom'] ?? '';
            $email = $data['email'] ?? '';
            $tel = $data['telephone'] ?? '';
            $role = strtolower($data['role'] ?? 'client');
            $agenceId = $data['agence_id'] !== '' ? (int)$data['agence_id'] : null;

            // VALIDATIONS
            if (empty($nom) || empty($prenom) || empty($email)) {
                $errors[] = ['line' => $lineNumber, 'message' => 'Nom, Prénom et Email sont obligatoires.'];
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = ['line' => $lineNumber, 'message' => "Format d'email invalide: $email."];
                continue;
            }

            if ($controller->getUserByEmail($email)) {
                $errors[] = ['line' => $lineNumber, 'message' => "L'email $email est déjà associé à un compte."];
                continue;
            }

            if (!in_array($role, ['superadmin', 'admin', 'agent', 'client'], true)) {
                $errors[] = ['line' => $lineNumber, 'message' => "Rôle non valide: $role."];
                continue;
            }

            if ($agenceId !== null && !in_array($agenceId, $validAgences, true)) {
                $errors[] = ['line' => $lineNumber, 'message' => "L'agence avec l'ID $agenceId n'existe pas."];
                continue;
            }

            // Génération de mot de passe compliant avec la validation (min 8 chars, 1 Maj, 1 chiffre, 1 symbole)
            $tempPassword = 'Protex@' . bin2hex(random_bytes(4)); // ex: Protex@f09a12bc

            try {
                $controller->addUserAdmin(
                    $nom,
                    $prenom,
                    $email,
                    $tempPassword,
                    $tel ?: null,
                    null, // CIN non fourni
                    $role,
                    'actif', // Statut par défaut
                    $role === 'admin' ? '1' : null, // Niveau d'accès admin
                    $agenceId,
                    $role === 'agent' ? 1500.00 : null, // Salaire agent par défaut
                    null // Numéro client auto-généré
                );
                
                // Journaliser
                AuditLogger::log('importation_user', 'user', "Importé par CSV : $email ($role)");
                $imported++;
            } catch (Exception $e) {
                $errors[] = ['line' => $lineNumber, 'message' => "Erreur lors de l'insertion: " . $e->getMessage()];
            }
        }
        fclose($handle);
    }

    echo json_encode([
        'success' => true,
        'imported' => $imported,
        'errors' => $errors
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Import CSV des utilisateurs — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/admin-users.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <style>
        .import-dropzone {
            border: 2px dashed var(--glass-border);
            border-radius: var(--radius-lg);
            background: rgba(255, 255, 255, 0.02);
            padding: 48px 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .import-dropzone:hover, .import-dropzone.dragover {
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.05);
        }
        .import-progress-bar {
            height: 8px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
            overflow: hidden;
            margin: 20px 0;
            display: none;
        }
        .import-progress-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, var(--accent), var(--accent-dark));
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>

<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">

    <!-- ===== SIDEBAR ===== -->
    <?php require_once __DIR__.'/assets/includes/sidebar.php'; ?>

    <!-- ===== MAIN ===== -->
    <main class="main">

        <!-- TOPBAR -->
        <div class="topbar">
            <div>
                <div class="topbar-title">Importation CSV</div>
                <div class="topbar-sub" id="topbarDate"></div>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="content">

            <!-- Page header -->
            <div class="page-header-bar">
                <div>
                    <div class="page-title">Importer des utilisateurs</div>
                    <div class="page-breadcrumb">
                        <i class="bi bi-house"></i>
                        <a href="admin.php">Accueil</a>
                        <i class="bi bi-chevron-right" style="font-size:10px"></i>
                        <a href="admin-users.php">Utilisateurs</a>
                        <i class="bi bi-chevron-right" style="font-size:10px"></i>
                        <span>Importer</span>
                    </div>
                </div>
                <a href="?template=1" class="btn btn-outline" title="Télécharger le modèle CSV">
                    <i class="bi bi-file-earmark-arrow-down"></i> Modèle CSV
                </a>
            </div>

            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-upload"></i> Fichier CSV d'import</div>
                </div>
                <div class="card-body">
                    <div style="margin-bottom: 20px; font-size: 13px; color: var(--text-secondary); line-height: 1.6;">
                        <p><strong>Règles d'importation :</strong></p>
                        <ul>
                            <li>Le fichier doit être au format <code>.csv</code>.</li>
                            <li>Taille maximale : 2 Mo.</li>
                            <li>Colonnes attendues : <code>nom</code>, <code>prenom</code>, <code>email</code>, <code>telephone</code>, <code>role</code>, <code>agence_id</code>.</li>
                            <li>Rôles acceptés : <code>superadmin</code>, <code>admin</code>, <code>agent</code>, <code>client</code>.</li>
                            <li>Le mot de passe temporaire sera généré automatiquement de manière sécurisée pour chaque utilisateur.</li>
                        </ul>
                    </div>

                    <form id="importForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <div class="import-dropzone" id="dropzone" onclick="document.getElementById('fileInput').click()">
                            <i class="bi bi-file-earmark-spreadsheet" style="font-size: 48px; color: var(--text-secondary); display: block; margin-bottom: 12px;"></i>
                            <span id="dropzoneText">Glissez-déposez votre fichier CSV ici ou cliquez pour parcourir</span>
                            <input type="file" id="fileInput" name="csv_file" accept=".csv" style="display: none;">
                        </div>
                    </form>

                    <div class="import-progress-bar" id="progressBar">
                        <div class="import-progress-fill" id="progressFill"></div>
                    </div>

                    <div style="margin-top: 20px; text-align: right;">
                        <a href="admin-users.php" class="btn btn-outline">Annuler</a>
                        <button type="button" class="btn btn-primary" id="btnUpload" onclick="uploadCSV()" disabled>
                            <i class="bi bi-cloud-arrow-up"></i> Lancer l'importation
                        </button>
                    </div>
                </div>
            </div>

            <!-- RESULTATS -->
            <div class="card" id="resultsCard" style="display: none;">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-check-all"></i> Résultats de l'importation</div>
                </div>
                <div class="card-body">
                    <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                        <div style="background: rgba(46, 213, 115, 0.1); border: 1px solid rgba(46, 213, 115, 0.2); border-radius: 12px; padding: 16px; flex: 1; text-align: center;">
                            <div style="font-size: 24px; font-weight: 700; color: #2ed573;" id="statImported">0</div>
                            <div style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase;">Importés avec succès</div>
                        </div>
                        <div style="background: rgba(255, 71, 87, 0.1); border: 1px solid rgba(255, 71, 87, 0.2); border-radius: 12px; padding: 16px; flex: 1; text-align: center;">
                            <div style="font-size: 24px; font-weight: 700; color: #ff4757;" id="statErrors">0</div>
                            <div style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase;">Lignes ignorées / erreurs</div>
                        </div>
                    </div>

                    <div class="table-wrap" id="errorsTableContainer" style="display: none;">
                        <div style="font-size: 13px; font-weight: 600; color: #ff4757; margin-bottom: 8px;">Détail des erreurs :</div>
                        <table style="border: 1px solid var(--glass-border);">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Ligne</th>
                                    <th>Message d'erreur</th>
                                </tr>
                            </thead>
                            <tbody id="errorsBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
// Date Topbar
const now = new Date();
document.getElementById('topbarDate').textContent =
    now.toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long', year:'numeric' });

const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const btnUpload = document.getElementById('btnUpload');
const dropzoneText = document.getElementById('dropzoneText');

// Drag and drop event listeners
['dragenter', 'dragover'].forEach(eventName => {
    dropzone.addEventListener(eventName, e => {
        e.preventDefault();
        dropzone.classList.add('dragover');
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropzone.addEventListener(eventName, e => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
    }, false);
});

dropzone.addEventListener('drop', e => {
    const dt = e.dataTransfer;
    const files = dt.files;
    if (files.length) {
        fileInput.files = files;
        handleFileSelect();
    }
});

fileInput.addEventListener('change', handleFileSelect);

function handleFileSelect() {
    const file = fileInput.files[0];
    if (file) {
        dropzoneText.innerHTML = `Fichier sélectionné : <strong>${file.name}</strong> (${(file.size / 1024).toFixed(1)} Ko)`;
        btnUpload.disabled = false;
    } else {
        dropzoneText.textContent = "Glissez-déposez votre fichier CSV ici ou cliquez pour parcourir";
        btnUpload.disabled = true;
    }
}

function uploadCSV() {
    const form = document.getElementById('importForm');
    const formData = new FormData(form);
    
    const progressBar = document.getElementById('progressBar');
    const progressFill = document.getElementById('progressFill');
    
    progressBar.style.display = 'block';
    progressFill.style.width = '20%';
    
    btnUpload.disabled = true;
    
    fetch('import_users.php', {
        method: 'POST',
        body: formData
    })
    .then(r => {
        progressFill.style.width = '70%';
        return r.json();
    })
    .then(res => {
        progressFill.style.width = '100%';
        setTimeout(() => {
            progressBar.style.display = 'none';
        }, 300);
        
        if (res.success) {
            document.getElementById('resultsCard').style.display = 'block';
            document.getElementById('statImported').textContent = res.imported;
            document.getElementById('statErrors').textContent = res.errors.length;
            
            const errorsContainer = document.getElementById('errorsTableContainer');
            const errorsBody = document.getElementById('errorsBody');
            
            if (res.errors.length > 0) {
                errorsContainer.style.display = 'block';
                errorsBody.innerHTML = res.errors.map(err => `
                    <tr>
                        <td style="font-family: monospace; font-weight: bold; text-align: center;">${err.line}</td>
                        <td style="color: #ff4757;">${escapeHtml(err.message)}</td>
                    </tr>
                `).join('');
            } else {
                errorsContainer.style.display = 'none';
            }
            
            // Message toast
            if (res.imported > 0) {
                showToast(`${res.imported} utilisateurs importés avec succès !`, 'success');
            } else {
                showToast("Aucun utilisateur n'a été importé.", 'warning');
            }
        } else {
            alert('Erreur: ' + res.message);
            btnUpload.disabled = false;
        }
    })
    .catch(err => {
        console.error(err);
        progressBar.style.display = 'none';
        btnUpload.disabled = false;
        alert("Une erreur de communication est survenue avec le serveur.");
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

function showToast(message, type = 'success') {
    const icons = { success:'check-circle', warning:'exclamation-triangle', danger:'x-circle' };
    const t = document.createElement('div');
    t.className = `toast-notif toast-${type}`;
    t.innerHTML = `<i class="bi bi-${icons[type]}"></i><span>${message}</span>`;
    document.body.appendChild(t);
    setTimeout(() => t.classList.add('show'), 50);
    setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 3000);
}
</script>

<script src="assets/js/main.js"></script>
<script src="assets/js/admin.js"></script>

</body>
</html>
