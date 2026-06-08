<?php
/**
 * MODULE 9 — A3 — Gestion Horaires d'Ouverture des Agences
 * BackOffice agency opening hours management
 */

session_start();
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../helpers/SessionGuard.php';
require_once __DIR__ . '/../../helpers/RoleHelper.php';

SessionGuard::requireBackoffice();
$user = $_SESSION['user'];

$idAgence = (int)($_GET['id'] ?? $user['id_agence']);

$db = config::getConnexion();

// Get agency info
$stmt = $db->prepare("SELECT * FROM agence WHERE id_agence = ?");
$stmt->execute([$idAgence]);
$agency = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$agency) {
    http_response_code(404);
    die('Agence non trouvée');
}

// Get current hours
$stmt = $db->prepare("SELECT * FROM agence_horaires WHERE id_agence = ? ORDER BY jour");
$stmt->execute([$idAgence]);
$horaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
$horairesByJour = [];
foreach ($horaires as $h) {
    $horairesByJour[$h['jour']] = $h;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? '';
    if ($csrf !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        exit;
    }

    for ($jour = 1; $jour <= 7; $jour++) {
        $ferme = isset($_POST["jour_{$jour}_ferme"]) ? 1 : 0;
        $ouverture = $_POST["jour_{$jour}_ouverture"] ?? null;
        $fermeture = $_POST["jour_{$jour}_fermeture"] ?? null;

        if ($ferme || (!$ouverture && !$fermeture)) {
            // Delete existing entry
            $db->prepare("DELETE FROM agence_horaires WHERE id_agence = ? AND jour = ?")->execute([$idAgence, $jour]);
        } else {
            $db->prepare("
                INSERT INTO agence_horaires (id_agence, jour, heure_ouverture, heure_fermeture, ferme)
                VALUES (?, ?, ?, ?, 0)
                ON DUPLICATE KEY UPDATE heure_ouverture = VALUES(heure_ouverture), heure_fermeture = VALUES(heure_fermeture), ferme = VALUES(ferme)
            ")->execute([$idAgence, $jour, $ouverture, $fermeture]);
        }
    }

    $_SESSION['success'] = 'Horaires mis à jour avec succès';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horaires d'Ouverture — <?php echo htmlspecialchars($agency['nom_agence']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-radius: 12px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            font-weight: 600;
        }
        .jour-row {
            display: grid;
            grid-template-columns: 120px 1fr 1fr 1fr 80px;
            gap: 12px;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .jour-row:last-child {
            border-bottom: none;
        }
        .jour-label {
            font-weight: 600;
            color: #333;
        }
        .time-input {
            border-radius: 6px;
            border: 1px solid #ddd;
            padding: 8px 12px;
            font-size: 14px;
        }
        .time-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .ferme-checkbox {
            cursor: pointer;
        }
        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 32px;
        }
        .btn-save:hover {
            opacity: 0.9;
            color: white;
        }
        .example-hours {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px;
            font-size: 13px;
            color: #666;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="container p-4" style="max-width: 700px;">
        <h1 class="mb-2">🕐 Horaires d'Ouverture</h1>
        <p class="text-muted mb-4"><?php echo htmlspecialchars($agency['nom_agence']); ?> — <?php echo htmlspecialchars($agency['ville']); ?></p>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                ⏰ Planning Hebdomadaire
            </div>
            <div class="card-body p-4">
                <div class="example-hours">
                    💡 Format: HH:MM (ex: 09:00, 17:30)
                </div>

                <form method="POST">
                    <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                    <?php for ($jour = 1; $jour <= 7; $jour++): 
                        $horaire = $horairesByJour[$jour] ?? null;
                        $ferme = $horaire['ferme'] ?? 0;
                        $ouverture = $horaire['heure_ouverture'] ?? '';
                        $fermeture = $horaire['heure_fermeture'] ?? '';
                    ?>
                    <div class="jour-row">
                        <div class="jour-label">
                            <?php echo $jours[$jour - 1]; ?>
                        </div>
                        <div>
                            <input type="time" 
                                   class="time-input" 
                                   name="jour_<?php echo $jour; ?>_ouverture" 
                                   value="<?php echo $ouverture; ?>"
                                   <?php echo $ferme ? 'disabled' : ''; ?>>
                        </div>
                        <div>
                            <input type="time" 
                                   class="time-input" 
                                   name="jour_<?php echo $jour; ?>_fermeture" 
                                   value="<?php echo $fermeture; ?>"
                                   <?php echo $ferme ? 'disabled' : ''; ?>>
                        </div>
                        <div style="text-align: center;">
                            <span class="text-muted" id="duration_<?php echo $jour; ?>">
                                <?php 
                                if ($ouverture && $fermeture && !$ferme) {
                                    $start = new DateTime($ouverture);
                                    $end = new DateTime($fermeture);
                                    $diff = $start->diff($end);
                                    echo $diff->h . 'h' . ($diff->i ? $diff->i . 'm' : '');
                                }
                                ?>
                            </span>
                        </div>
                        <div>
                            <input type="checkbox" 
                                   class="ferme-checkbox" 
                                   name="jour_<?php echo $jour; ?>_ferme" 
                                   data-jour="<?php echo $jour; ?>"
                                   <?php echo $ferme ? 'checked' : ''; ?>>
                            <label class="mb-0">Fermé</label>
                        </div>
                    </div>
                    <?php endfor; ?>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-save">
                            <i class="bi bi-save"></i> Enregistrer les horaires
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Presets -->
        <div class="card mt-4">
            <div class="card-header">
                ⚡ Raccourcis
            </div>
            <div class="card-body">
                <button class="btn btn-outline-secondary me-2" onclick="fillStandardHours()">
                    📋 Horaires standards (9-17)
                </button>
                <button class="btn btn-outline-secondary" onclick="fillAllClosed()">
                    🔒 Tout fermer
                </button>
            </div>
        </div>
    </div>

    <script>
        // Handle duration calculation
        const timeInputs = document.querySelectorAll('.time-input');
        timeInputs.forEach(input => {
            input.addEventListener('change', calculateDuration);
        });

        function calculateDuration() {
            for (let jour = 1; jour <= 7; jour++) {
                const ouverture = document.querySelector(`input[name="jour_${jour}_ouverture"]`).value;
                const fermeture = document.querySelector(`input[name="jour_${jour}_fermeture"]`).value;
                const durationEl = document.getElementById(`duration_${jour}`);
                
                if (ouverture && fermeture) {
                    const start = new Date(`2000-01-01T${ouverture}`);
                    const end = new Date(`2000-01-01T${fermeture}`);
                    const diff = (end - start) / (1000 * 60); // minutes
                    const hours = Math.floor(diff / 60);
                    const mins = diff % 60;
                    durationEl.textContent = hours + 'h' + (mins ? mins + 'm' : '');
                } else {
                    durationEl.textContent = '';
                }
            }
        }

        // Handle closed days
        document.querySelectorAll('.ferme-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const jour = this.dataset.jour;
                const ouverture = document.querySelector(`input[name="jour_${jour}_ouverture"]`);
                const fermeture = document.querySelector(`input[name="jour_${jour}_fermeture"]`);
                
                if (this.checked) {
                    ouverture.disabled = true;
                    fermeture.disabled = true;
                } else {
                    ouverture.disabled = false;
                    fermeture.disabled = false;
                }
            });
        });

        function fillStandardHours() {
            for (let jour = 1; jour <= 5; jour++) { // Mon-Fri
                document.querySelector(`input[name="jour_${jour}_ouverture"]`).value = '09:00';
                document.querySelector(`input[name="jour_${jour}_fermeture"]`).value = '17:00';
                document.querySelector(`input[name="jour_${jour}_ferme"]`).checked = false;
                document.querySelector(`input[name="jour_${jour}_ouverture"]`).disabled = false;
                document.querySelector(`input[name="jour_${jour}_fermeture"]`).disabled = false;
            }
            // Sat-Sun closed
            for (let jour = 6; jour <= 7; jour++) {
                document.querySelector(`input[name="jour_${jour}_ferme"]`).checked = true;
                document.querySelector(`input[name="jour_${jour}_ouverture"]`).disabled = true;
                document.querySelector(`input[name="jour_${jour}_fermeture"]`).disabled = true;
            }
            calculateDuration();
        }

        function fillAllClosed() {
            for (let jour = 1; jour <= 7; jour++) {
                document.querySelector(`input[name="jour_${jour}_ferme"]`).checked = true;
                document.querySelector(`input[name="jour_${jour}_ouverture"]`).disabled = true;
                document.querySelector(`input[name="jour_${jour}_fermeture"]`).disabled = true;
            }
        }
    </script>
</body>
</html>
