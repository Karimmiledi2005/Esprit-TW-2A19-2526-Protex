<?php
/**
 * MODULE 9 — A5 — Prise de Rendez-Vous en Ligne
 * FrontOffice appointment booking form
 */

session_start();
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../helpers/SessionGuard.php';

SessionGuard::requireLogin();
$idUser = (int)$_SESSION['user_id'];
$idAgence = (int)($_GET['agence'] ?? 0);

$db = config::getConnexion();

if ($idAgence === 0) {
    $stmt = $db->prepare("SELECT id_agence FROM client WHERE id_user = ?");
    $stmt->execute([$idUser]);
    $clientRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($clientRow && !empty($clientRow['id_agence'])) {
        $idAgence = (int)$clientRow['id_agence'];
    } else {
        header("Location: /view/FrontOffice/agences.php");
        exit;
    }
}

// Get agency info
$stmt = $db->prepare("SELECT * FROM agence WHERE id_agence = ?");
$stmt->execute([$idAgence]);
$agency = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$agency) {
    header("Location: /view/FrontOffice/agences.php");
    exit;
}

// Get available slots for selected date
if ($_GET['date'] ?? false) {
    $date = $_GET['date'];
    $stmt = $db->prepare("SELECT DATE_FORMAT(date_rdv, '%H:%i') as time FROM rendez_vous WHERE id_agence = ? AND DATE(date_rdv) = ? AND statut != 'annulé'");
    $stmt->execute([$idAgence, $date]);
    $booked = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $slots = [];
    for ($h = 9; $h < 17; $h++) {
        for ($m = 0; $m < 60; $m += 30) {
            $time = sprintf('%02d:%02d', $h, $m);
            if (!in_array($time, $booked)) {
                $slots[] = $time;
            }
        }
    }
    echo json_encode(['success' => true, 'slots' => $slots]);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motif = $_POST['motif'] ?? '';
    $dateRdv = $_POST['date_rdv'] ?? '';
    $time = $_POST['time'] ?? '';
    
    if (!$motif || !$dateRdv || !$time) {
        $_SESSION['error'] = 'Tous les champs sont requis';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    
    $dateTimeRdv = $dateRdv . ' ' . $time;
    
    try {
        $stmt = $db->prepare("INSERT INTO rendez_vous (id_agence, id_client, date_rdv, motif) VALUES (?, ?, ?, ?)");
        $stmt->execute([$idAgence, $idUser, $dateTimeRdv, $motif]);
        
        $_SESSION['success'] = 'Votre rendez-vous a été confirmé!';
        
        // Send confirmation email
        require_once __DIR__ . '/../../controller/EmailService.php';
        $stmt = $db->prepare("SELECT email, nom, prenom FROM `user` WHERE id_user = ?");
        $stmt->execute([$idUser]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $emailService = new EmailService();
        $subject = "📅 Confirmation de rendez-vous — Protex Assurance";
        $body = "
            <h2>Rendez-vous confirmé!</h2>
            <p>Bonjour {$user['nom']},</p>
            <p><strong>Agence:</strong> " . htmlspecialchars($agency['nom_agence']) . "</p>
            <p><strong>Date et heure:</strong> " . date('d/m/Y à H:i', strtotime($dateTimeRdv)) . "</p>
            <p><strong>Motif:</strong> " . htmlspecialchars($motif) . "</p>
            <p>Veuillez vous présenter 5 minutes avant votre rendez-vous.</p>
        ";
        $emailService->send($user['email'], $subject, $body);
        
        header('Location: /view/FrontOffice/client.php?alert=success');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Erreur lors de la réservation: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prendre un Rendez-Vous — Protex</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <style>
        .rdv-container {
            max-width: 650px;
            margin: 40px auto;
            position: relative;
            z-index: 10;
        }
        .agency-header {
            background: rgba(26,58,122,0.04);
            border: 1px solid rgba(26,58,122,0.08);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .agency-icon {
            width: 50px;
            height: 50px;
            background: rgba(255,107,26,0.1);
            color: #FF6B1A;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .agency-details h2 {
            margin: 0 0 4px 0;
            font-family: var(--font-display);
            font-size: 20px;
            color: #15233C;
        }
        .agency-details p {
            margin: 0;
            font-size: 14px;
            color: rgba(21,35,60,0.75);
        }
        .rdv-card {
            background: #ffffff;
            border: 1px solid rgba(26,58,122,0.10);
            box-shadow: 0 10px 30px rgba(26,58,122,0.08);
            border-radius: 16px;
            padding: 32px;
        }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #15233C;
            margin-bottom: 8px;
        }
        .form-control {
            width: 100%;
            border-radius: 12px;
            border: 1px solid rgba(26,58,122,0.12);
            background: #fbfdff;
            padding: 14px;
            color: #15233C;
            transition: all 0.2s;
            margin-bottom: 20px;
            box-sizing: border-box;
            font-family: var(--font-body);
        }
        .form-control:focus {
            border-color: #FF6B1A;
            box-shadow: 0 0 0 4px rgba(255,107,26,0.1);
            outline: none;
        }
        .slot-btn {
            border-radius: 10px;
            padding: 10px 16px;
            margin: 4px;
            border: 1px solid rgba(26,58,122,0.12);
            background: rgba(26,58,122,0.02);
            color: #15233C;
            cursor: pointer;
            transition: all 0.2s;
            font-family: var(--font-body);
            font-weight: 500;
        }
        .slot-btn:hover {
            border-color: #FF6B1A;
            color: #FF6B1A;
            background: rgba(255,107,26,0.05);
        }
        .slot-btn.selected {
            background: #FF6B1A;
            color: white;
            border-color: #FF6B1A;
        }
        .btn-submit {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            background: #FF6B1A;
            color: white;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }
        .btn-submit:hover {
            background: #e65c12;
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(255,107,26,0.25);
        }
        .alert {
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-danger {
            background: rgba(230, 57, 70, 0.1);
            color: #e63946;
            border: 1px solid rgba(230, 57, 70, 0.2);
        }
        .alert-warning {
            background: rgba(255, 107, 26, 0.1);
            color: #FF6B1A;
            border: 1px solid rgba(255, 107, 26, 0.2);
        }
        .agency-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(26,58,122,0.06);
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 600;
            color: #15233C;
            margin-bottom: 24px;
            font-size: 14px;
        }
        .agency-badge i {
            color: #FF6B1A;
        }
    </style>
</head>
<body>
    <div class="background"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="layout">
        <!-- ===== NAVBAR ===== -->
        <?php require_once __DIR__.'/assets/includes/navbar.php'; ?>

        <main class="main">
            <div class="rdv-container">
                <div class="agency-header">
                    <div class="agency-icon"><i class="bi bi-building"></i></div>
                    <div class="agency-details">
                        <div class="agency-badge">
                            <i class="bi bi-building"></i> Agence <?php echo htmlspecialchars($agency['nom_agence']); ?>
                        </div>
                        <p><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($agency['adresse']); ?>, <?php echo htmlspecialchars($agency['ville']); ?></p>
                    </div>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <form method="POST" id="rdvForm" class="rdv-card">
                    <!-- Motif -->
                    <div>
                        <label for="motif" class="form-label">Motif du rendez-vous</label>
                        <select id="motif" name="motif" class="form-control" required>
                            <option value="">Sélectionnez un motif</option>
                            <option value="Souscription">Souscription - Nouveau contrat</option>
                            <option value="Sinistre">Déclaration de sinistre</option>
                            <option value="Contrat">Question sur mon contrat</option>
                            <option value="Renouvellement">Renouvellement de contrat</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>

                    <!-- Date -->
                    <div>
                        <label for="date_rdv" class="form-label">Date du rendez-vous</label>
                        <input type="date" id="date_rdv" name="date_rdv" class="form-control" required>
                    </div>

                    <!-- Available Slots -->
                    <div>
                        <label class="form-label">Créneaux horaires disponibles</label>
                        <div id="slotsContainer" style="display: none; margin-bottom: 20px;">
                            <div id="slotsList" style="display: flex; flex-wrap: wrap; gap: 8px;"></div>
                            <input type="hidden" id="time" name="time" required>
                        </div>
                        <div id="noSlotsMsg" class="alert alert-warning">
                            <i class="bi bi-calendar-event"></i> Sélectionnez une date pour voir les créneaux disponibles
                        </div>
                    </div>

                    <button type="submit" class="btn-submit mt-2">
                        <i class="bi bi-calendar-check"></i> Confirmer le rendez-vous
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        const dateInput = document.getElementById('date_rdv');
        const slotsList = document.getElementById('slotsList');
        const slotsContainer = document.getElementById('slotsContainer');
        const noSlotsMsg = document.getElementById('noSlotsMsg');
        const timeInput = document.getElementById('time');

        // Set minimum date to tomorrow
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        dateInput.min = tomorrow.toISOString().split('T')[0];

        dateInput.addEventListener('change', async function() {
            if (!this.value) return;

            const response = await fetch(`?agence=<?php echo $idAgence; ?>&date=${this.value}`);
            const data = await response.json();

            if (data.success && data.slots.length > 0) {
                slotsList.innerHTML = '';
                data.slots.forEach(slot => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'slot-btn';
                    btn.textContent = slot;
                    btn.onclick = (e) => {
                        e.preventDefault();
                        document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
                        btn.classList.add('selected');
                        timeInput.value = slot;
                    };
                    slotsList.appendChild(btn);
                });
                slotsContainer.style.display = 'block';
                noSlotsMsg.style.display = 'none';
            } else {
                noSlotsMsg.innerHTML = '<i class="bi bi-x-circle"></i> <strong>Aucun créneau disponible à cette date</strong>';
                noSlotsMsg.style.display = 'block';
                slotsContainer.style.display = 'none';
            }
        });

        document.getElementById('rdvForm').addEventListener('submit', function(e) {
            if (!timeInput.value) {
                e.preventDefault();
                alert('Veuillez sélectionner un créneau horaire');
            }
        });
    </script>
</body>
</html>
