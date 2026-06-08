<?php
/**
 * MODULE 8 — RC4 — Client Reclamation Tracker
 * FrontOffice tracker with stepper showing reclamation status
 */

session_start();
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../helpers/SessionGuard.php';

SessionGuard::requireLogin();
$idUser = (int)$_SESSION['user_id'];
$idReclamation = (int)($_GET['id'] ?? 0);

$db = config::getConnexion();

// Get reclamation
$stmt = $db->prepare("
    SELECT r.*, u.nom, u.prenom, u.email
    FROM reclamation r
    LEFT JOIN `user` u ON r.id_user = u.id_user
    WHERE r.id = ? AND r.id_user = ?
");
$stmt->execute([$idReclamation, $idUser]);
$reclamation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reclamation) {
    http_response_code(404);
    die('Réclamation non trouvée');
}

// Define stepper steps
$steps = [
    ['key' => 'declaree', 'label' => 'Soumise', 'icon' => 'bi-send', 'desc' => 'Votre réclamation a été enregistrée'],
    ['key' => 'recue', 'label' => 'Reçue', 'icon' => 'bi-check-circle', 'desc' => 'Accusé de réception envoyé'],
    ['key' => 'examen', 'label' => 'En examen', 'icon' => 'bi-hourglass-split', 'desc' => 'Notre équipe analyse votre dossier'],
    ['key' => 'reponse', 'label' => 'Réponse', 'icon' => 'bi-reply', 'desc' => 'Réponse officielle envoyée'],
    ['key' => 'claturee', 'label' => 'Clôturée', 'icon' => 'bi-check2', 'desc' => 'Réclamation clôturée']
];

// Determine current step
$statusMap = [
    'open'     => 'examen',
    'closed'   => 'claturee',
    'rejected' => 'claturee'
];
$currentStep = $statusMap[$reclamation['statut']] ?? 'declaree';
$currentStepIdx = array_search($currentStep, array_column($steps, 'key'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi Réclamation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .stepper {
            display: flex;
            justify-content: space-between;
            margin: 40px 0;
            position: relative;
        }
        .stepper::before {
            content: '';
            position: absolute;
            top: 24px;
            left: 0;
            right: 0;
            height: 2px;
            background: #ddd;
            z-index: 0;
        }
        .step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        .step-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: white;
            border: 3px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-weight: 700;
            font-size: 20px;
            transition: all 0.3s;
        }
        .step.completed .step-circle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
        }
        .step.current .step-circle {
            background: #fff;
            border-color: #43e97b;
            box-shadow: 0 0 0 4px rgba(67, 233, 123, 0.2);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .step.future .step-circle {
            background: #f8f9fa;
        }
        .step-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }
        .step-desc {
            font-size: 12px;
            color: #999;
        }
        .step.completed .step-label { color: #667eea; }
        .step.current .step-label { color: #43e97b; }
        
        .timeline-content {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 24px;
            margin-top: 40px;
        }
        .timeline-item {
            border-left: 3px solid #667eea;
            padding-left: 20px;
            margin-left: 10px;
            padding-bottom: 20px;
        }
        .timeline-item-date {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 8px;
        }
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container p-4" style="max-width: 900px;">
        <h1 class="mb-2">🔍 Suivi de votre Réclamation</h1>
        <p class="text-muted mb-4">
            Dossier #<?php echo $idReclamation; ?> — Créé le <?php echo date('d/m/Y', strtotime($reclamation['date_creation'])); ?>
        </p>

        <!-- Stepper -->
        <div class="stepper">
            <?php foreach ($steps as $idx => $step): 
                $isCompleted = $idx < $currentStepIdx;
                $isCurrent = $idx === $currentStepIdx;
                $stepClass = $isCompleted ? 'completed' : ($isCurrent ? 'current' : 'future');
            ?>
            <div class="step <?php echo $stepClass; ?>">
                <div class="step-circle">
                    <?php if ($isCompleted): ?>
                        ✓
                    <?php else: ?>
                        <?php echo $idx + 1; ?>
                    <?php endif; ?>
                </div>
                <div class="step-label"><?php echo $step['label']; ?></div>
                <div class="step-desc"><?php echo $step['desc']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Current Status Card -->
        <div class="card">
            <div class="card-header">
                📊 Statut Actuel
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Statut:</strong> <span class="badge bg-info"><?php echo ucfirst($reclamation['statut']); ?></span><br>
                        <strong>Priorité:</strong> <span class="badge bg-warning"><?php echo ucfirst($reclamation['priorite']); ?></span><br>
                        <strong>SLA:</strong> 48 heures
                    </div>
                    <div class="col-md-6">
                        <strong>Objet:</strong> <?php echo htmlspecialchars($reclamation['objet']); ?><br>
                        <strong>Jours écoulés:</strong> <?php echo floor((time() - strtotime($reclamation['date_creation'])) / 86400); ?> jours<br>
                        <strong>Dernière mise à jour:</strong> <?php echo date('d/m/Y H:i', strtotime($reclamation['date_update'] ?? $reclamation['date_creation'])); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div class="card">
            <div class="card-header">
                📄 Description
            </div>
            <div class="card-body">
                <?php echo nl2br(htmlspecialchars($reclamation['description'])); ?>
            </div>
        </div>

        <!-- Agent Response (if available) -->
        <?php if ($reclamation['statut'] !== 'open' && $reclamation['reponse']): ?>
        <div class="card">
            <div class="card-header">
                💬 Réponse de l'Agence
            </div>
            <div class="card-body">
                <div class="timeline-item">
                    <div class="timeline-item-date">
                        👤 <?php echo htmlspecialchars($reclamation['nom'] . ' ' . $reclamation['prenom']); ?>
                    </div>
                    <?php echo nl2br(htmlspecialchars($reclamation['reponse'])); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Rating (if closed) -->
        <?php if ($reclamation['statut'] === 'clôturée'): 
            $stmt = $db->prepare("SELECT * FROM reclamation_satisfaction WHERE id_reclamation = ?");
            $stmt->execute([$idReclamation]);
            $satisfaction = $stmt->fetch(PDO::FETCH_ASSOC);
        ?>
        <div class="card">
            <div class="card-header">
                ⭐ Satisfaction
            </div>
            <div class="card-body">
                <?php if ($satisfaction): ?>
                    <div class="mb-3">
                        Note: 
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <i class="bi <?php echo $i < $satisfaction['note'] ? 'bi-star-fill' : 'bi-star'; ?>" style="color: #ffc107;"></i>
                        <?php endfor; ?>
                    </div>
                    <?php if ($satisfaction['commentaire']): ?>
                        <p><?php echo htmlspecialchars($satisfaction['commentaire']); ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <form method="POST" onsubmit="saveRating(event, <?php echo $idReclamation; ?>)">
                        <div class="mb-3">
                            <label>Évaluer votre satisfaction:</label>
                            <div id="starRating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star" data-rating="<?php echo $i; ?>" style="cursor: pointer; font-size: 24px; color: #ddd; margin-right: 8px;"></i>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" id="rating" value="0" required>
                        </div>
                        <textarea name="commentaire" class="form-control mb-3" placeholder="Commentaire optionnel"></textarea>
                        <button type="submit" class="btn btn-primary">Soumettre l'évaluation</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh every 60s
        setInterval(() => location.reload(), 60000);

        // Star rating
        document.querySelectorAll('#starRating i').forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.dataset.rating;
                document.getElementById('rating').value = rating;
                
                document.querySelectorAll('#starRating i').forEach((s, idx) => {
                    if (idx < rating) {
                        s.classList.remove('bi-star');
                        s.classList.add('bi-star-fill');
                        s.style.color = '#ffc107';
                    } else {
                        s.classList.remove('bi-star-fill');
                        s.classList.add('bi-star');
                        s.style.color = '#ddd';
                    }
                });
            });
        });

        function saveRating(e, idReclamation) {
            e.preventDefault();
            const note = document.getElementById('rating').value;
            const commentaire = document.querySelector('textarea[name="commentaire"]').value;

            fetch('/api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=save_satisfaction&id_reclamation=${idReclamation}&note=${note}&commentaire=${encodeURIComponent(commentaire)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Merci pour votre évaluation!');
                    location.reload();
                }
            });
        }
    </script>
</body>
</html>
