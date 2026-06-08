<?php
/**
 * MODULE 7 — P2 — Page des Relances de Paiements en Retard
 * BackOffice list of overdue payments with manual/bulk reminders
 */

session_start();
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../helpers/SessionGuard.php';
require_once __DIR__ . '/../../helpers/RoleHelper.php';
require_once __DIR__ . '/../../controller/EmailService.php';

SessionGuard::requireBackoffice();
$user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf = $_POST['csrf'] ?? '';
    if ($csrf !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('CSRF failed');
    }

    $db = config::getConnexion();
    
    if ($_POST['action'] === 'send_reminder') {
        $idContrat = (int)$_POST['id_contrat'];
        $type = $_POST['type'] ?? 'email'; // 'email' or 'sms'
        
        // Get contract + client info
        $stmt = $db->prepare("
            SELECT c.*, u.email, u.telephone, u.nom, u.prenom, u.id_user
            FROM contrat c
            JOIN `user` u ON c.id_user = u.id_user
            WHERE c.id_contrat = ?
        ");
        $stmt->execute([$idContrat]);
        $contrat = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$contrat) {
            echo json_encode(['success' => false, 'message' => 'Contrat introuvable']);
            exit;
        }

        if ($type === 'email') {
            $emailService = new EmailService();
            $subject = "⚠️ Rappel : Votre paiement est en retard";
            $body = "
                <h2>Rappel de Paiement</h2>
                <p>Bonjour {$contrat['nom']} {$contrat['prenom']},</p>
                <p>Nous remarquons que votre paiement pour le contrat <strong>#{$contrat['numero_contrat']}</strong> 
                (Prime: {$contrat['prime_contrat']} DT) n'a pas été reçu à la date d'échéance.</p>
                <p><strong>Date d'échéance:</strong> " . date('d/m/Y', strtotime($contrat['date_fin_contrat'])) . "</p>
                <p><strong>Jours de retard:</strong> " . (int)((strtotime('now') - strtotime($contrat['date_fin_contrat'])) / 86400) . "</p>
                <p>Veuillez régulariser votre situation au plus vite pour éviter la suspension de votre contrat.</p>
                <p><a href='#' class='btn btn-primary'>Payer maintenant</a></p>
                <p>Cordialement,<br>L'équipe Protex Assurance</p>
            ";
            $emailService->send($contrat['email'], $subject, $body);
        } else if ($type === 'sms') {
            // TODO: Implement SMS service integration
            // For now, just log it
        }

        // Log the reminder
        $db->prepare("
            INSERT INTO relance_paiement (id_contrat, type, sent_by)
            VALUES (?, ?, ?)
        ")->execute([$idContrat, $type, $user['id_user']]);

        echo json_encode(['success' => true, 'message' => 'Rappel envoyé avec succès']);
        exit;
    } 
    
    if ($_POST['action'] === 'send_bulk_reminders') {
        $filter = $_POST['filter'] ?? 'all'; // 'all', '7days', '14days', '30days'
        
        $whereClause = "c.statut_contrat = 'actif' AND c.date_fin_contrat < NOW()";
        $whereClause .= " AND c.id_contrat NOT IN (
            SELECT p.id_offre FROM paiement p 
            WHERE p.statut = 'valide' AND MONTH(p.date_paiement) = MONTH(NOW())
        )";
        
        if ($filter === '7days') {
            $whereClause .= " AND c.date_fin_contrat >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        } elseif ($filter === '14days') {
            $whereClause .= " AND c.date_fin_contrat >= DATE_SUB(NOW(), INTERVAL 14 DAY)";
        } elseif ($filter === '30days') {
            $whereClause .= " AND c.date_fin_contrat >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }

        $stmt = $db->query("
            SELECT c.id_contrat, u.email, u.telephone, u.nom, u.prenom
            FROM contrat c
            JOIN `user` u ON c.id_user = u.id_user
            WHERE $whereClause
        ");
        $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $emailService = new EmailService();
        $count = 0;
        foreach ($contracts as $contract) {
            $subject = "⚠️ Rappel : Votre paiement est en retard";
            $body = "<h2>Rappel de Paiement</h2>
                <p>Bonjour {$contract['nom']} {$contract['prenom']},</p>
                <p>Nous remarquons que votre paiement n'a pas été reçu à la date d'échéance.</p>
                <p>Veuillez régulariser votre situation au plus vite.</p>";
            
            $emailService->send($contract['email'], $subject, $body);
            $db->prepare("INSERT INTO relance_paiement (id_contrat, type, sent_by) VALUES (?, 'email', ?)")
                ->execute([$contract['id_contrat'], $user['id_user']]);
            $count++;
        }

        echo json_encode(['success' => true, 'count' => $count, 'message' => "Rappels envoyés à $count clients"]);
        exit;
    }
}

// Get overdue payments
$db = config::getConnexion();
$stmt = $db->query("
    SELECT 
        c.id_contrat,
        c.numero_contrat,
        u.nom,
        u.prenom,
        u.email,
        u.telephone,
        c.prime_contrat,
        c.date_fin_contrat as date_echeance_contrat,
        DATEDIFF(NOW(), c.date_fin_contrat) as jours_retard,
        (SELECT MAX(sent_at) FROM relance_paiement WHERE id_contrat = c.id_contrat) as derniere_relance
    FROM contrat c
    JOIN `user` u ON c.id_user = u.id_user
    WHERE c.statut_contrat = 'actif' 
    AND c.date_fin_contrat < NOW()
    AND c.id_contrat NOT IN (
        SELECT p.id_offre FROM paiement p 
        WHERE p.statut = 'valide' AND MONTH(p.date_paiement) = MONTH(NOW())
    )
    ORDER BY jours_retard DESC
");
$contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiements en Retard — Relances</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .alert-card {
            border-left: 4px solid #f5576c;
            background: linear-gradient(135deg, rgba(245,87,108,0.1), rgba(245,87,108,0.05));
        }
        .days-badge {
            font-weight: 700;
        }
        .days-badge.critical {
            background: #f5576c;
            color: white;
        }
        .days-badge.warning {
            background: #ffa502;
            color: white;
        }
        .days-badge.caution {
            background: #ffd700;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1>⚠️ Paiements en Retard</h1>
                <p class="text-muted">Gérez les relances de paiement</p>
            </div>
            <div class="col-md-4">
                <select id="filterRetard" class="form-select">
                    <option value="">Tous les retards</option>
                    <option value="7days">< 7 jours</option>
                    <option value="14days">7-14 jours</option>
                    <option value="30days">14-30 jours</option>
                    <option value="30days+"> > 30 jours</option>
                </select>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div class="card alert-card mb-4">
            <div class="card-body">
                <h5>Actions en masse</h5>
                <button class="btn btn-warning" id="bulkReminder7" onclick="sendBulkReminders('7days')">
                    <i class="bi bi-send"></i> Relancer tous (< 7j)
                </button>
                <button class="btn btn-danger" id="bulkReminder30" onclick="sendBulkReminders('30days')">
                    <i class="bi bi-send"></i> Relancer tous (< 30j)
                </button>
            </div>
        </div>

        <!-- Table of overdue contracts -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Contrat #</th>
                        <th>Client</th>
                        <th>Prime (DT)</th>
                        <th>Jour de retard</th>
                        <th>Dernière relance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contracts as $c): 
                        $badge_class = $c['jours_retard'] > 30 ? 'critical' : ($c['jours_retard'] > 14 ? 'warning' : 'caution');
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($c['numero_contrat']); ?></strong></td>
                        <td><?php echo htmlspecialchars($c['nom'] . ' ' . $c['prenom']); ?></td>
                        <td><?php echo number_format($c['prime_contrat'], 2, ',', ' ') . ' DT'; ?></td>
                        <td>
                            <span class="badge days-badge <?php echo $badge_class; ?>">
                                <?php echo $c['jours_retard']; ?> j
                            </span>
                        </td>
                        <td>
                            <?php if ($c['derniere_relance']): 
                                echo date('d/m/Y', strtotime($c['derniere_relance']));
                            else: 
                                echo '<span class="badge bg-secondary">Jamais</span>';
                            endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="sendReminder(<?php echo $c['id_contrat']; ?>, 'email')">
                                <i class="bi bi-envelope"></i> Email
                            </button>
                            <button class="btn btn-sm btn-success" onclick="sendReminder(<?php echo $c['id_contrat']; ?>, 'sms')">
                                <i class="bi bi-chat"></i> SMS
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($contracts)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun contrat en retard 🎉</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function sendReminder(idContrat, type) {
            const formData = new FormData();
            formData.append('action', 'send_reminder');
            formData.append('id_contrat', idContrat);
            formData.append('type', type);
            formData.append('csrf', '<?php echo $_SESSION['csrf_token'] ?? ''; ?>');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                }
            })
            .catch(err => console.error(err));
        }

        function sendBulkReminders(filter) {
            if (!confirm(`Êtes-vous sûr d'envoyer les rappels pour les retards de ${filter}?`)) return;

            const formData = new FormData();
            formData.append('action', 'send_bulk_reminders');
            formData.append('filter', filter);
            formData.append('csrf', '<?php echo $_SESSION['csrf_token'] ?? ''; ?>');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(`${data.count} rappels envoyés`);
                    location.reload();
                }
            })
            .catch(err => console.error(err));
        }
    </script>
</body>
</html>
