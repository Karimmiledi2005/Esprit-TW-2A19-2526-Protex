<?php
/**
 * MODULE 9 — A5 — Agenda des rendez-vous en BackOffice
 */

session_start();
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../helpers/SessionGuard.php';
require_once __DIR__ . '/../../helpers/RoleHelper.php';
require_once __DIR__ . '/../../controller/EmailService.php';

SessionGuard::requireBackoffice();
$role = $_SESSION['user']['role'] ?? 'client';
if (!in_array($role, ['superadmin', 'admin', 'agent'], true)) {
    http_response_code(403);
    exit('Accès refusé');
}

$db = config::getConnexion();
$dateFilter = $_GET['date'] ?? date('Y-m-d');
$statusFilter = $_GET['status'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'confirmé';
        if ($id > 0) {
            $stmt = $db->prepare('UPDATE rendez_vous SET statut = ? WHERE id = ?');
            $stmt->execute([$status, $id]);
            $message = 'Statut du rendez-vous mis à jour.';
        }
    }
    if ($action === 'send_reminder') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare('SELECT rv.*, u.email, u.nom, u.prenom, a.nom_agence FROM rendez_vous rv JOIN `user` u ON rv.id_client = u.id_user JOIN agence a ON rv.id_agence = a.id_agence WHERE rv.id = ?');
            $stmt->execute([$id]);
            $rdv = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($rdv) {
                $emailService = new EmailService();
                $subject = '🔔 Rappel de rendez-vous Protex';
                $body = "<h2>Rappel de rendez-vous</h2><p>Bonjour {$rdv['prenom']} {$rdv['nom']},</p>" .
                    "<p>Nous vous rappelons votre rendez-vous prévu le <strong>" . date('d/m/Y à H:i', strtotime($rdv['date_rdv'])) . "</strong> chez <strong>" . htmlspecialchars($rdv['nom_agence']) . "</strong>.</p>" .
                    "<p>Motif : " . htmlspecialchars($rdv['motif']) . "</p>" .
                    "<p>Merci de vous présenter 5 minutes avant l'heure.</p>";
                $emailService->send($rdv['email'], $subject, $body);
                $message = 'Rappel email envoyé au client.';
            }
        }
    }
}

$where = '1=1';
$params = [':date' => $dateFilter];
if ($statusFilter) {
    $where .= ' AND rv.statut = :status';
    $params[':status'] = $statusFilter;
}

$stmt = $db->prepare("SELECT rv.*, a.nom_agence, u.nom AS client_nom, u.prenom AS client_prenom, ag.nom AS agent_nom, ag.prenom AS agent_prenom
    FROM rendez_vous rv
    JOIN agence a ON rv.id_agence = a.id_agence
    JOIN `user` u ON rv.id_client = u.id_user
    LEFT JOIN `user` ag ON rv.id_agent = ag.id_user
    WHERE DATE(rv.date_rdv) = :date AND {$where}
    ORDER BY rv.date_rdv ASC");
$stmt->execute($params);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtTotals = $db->prepare("SELECT COUNT(*) AS total, SUM(rv.statut = 'confirmé') AS confirme, SUM(rv.statut = 'annulé') AS annule, SUM(rv.statut = 'effectué') AS effectue
    FROM rendez_vous rv
    WHERE DATE(rv.date_rdv) = :date");
$stmtTotals->execute([':date' => $dateFilter]);
$totals = $stmtTotals->fetch(PDO::FETCH_ASSOC);

function badgeClass(string $status): string {
    return match ($status) {
        'confirmé' => 'success',
        'annulé' => 'danger',
        'effectué' => 'primary',
        default => 'secondary',
    };
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda — Protex BackOffice</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .status-badge { text-transform: uppercase; font-size: 11px; letter-spacing: .4px; }
        .appointment-card { border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,.08); }
        .agenda-overview { gap: 18px; }
        .agenda-panel { background: #fff; border-radius: 16px; padding: 20px; border: 1px solid #f1f5f9; }
        .agenda-panel h5 { margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h1>📅 Agenda des rendez-vous</h1>
                <p class="text-muted">Visualisez et gérez les rendez-vous par date.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($dateFilter); ?>">
                    <select name="status" class="form-select">
                        <option value="">Tous statuts</option>
                        <option value="confirmé"<?php echo $statusFilter === 'confirmé' ? ' selected' : ''; ?>>Confirmé</option>
                        <option value="annulé"<?php echo $statusFilter === 'annulé' ? ' selected' : ''; ?>>Annulé</option>
                        <option value="effectué"<?php echo $statusFilter === 'effectué' ? ' selected' : ''; ?>>Effectué</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                </form>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="row agenda-overview">
            <div class="col-md-3">
                <div class="agenda-panel text-center">
                    <h5>Total</h5>
                    <strong style="font-size:32px;"><?php echo (int)$totals['total']; ?></strong>
                    <p class="text-muted mb-0">Rendez-vous prévus</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="agenda-panel text-center">
                    <h5>Confirmés</h5>
                    <strong style="font-size:32px;"><?php echo (int)$totals['confirme']; ?></strong>
                    <p class="text-muted mb-0">Confirmés</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="agenda-panel text-center">
                    <h5>Effectués</h5>
                    <strong style="font-size:32px;"><?php echo (int)$totals['effectue']; ?></strong>
                    <p class="text-muted mb-0">Terminés</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="agenda-panel text-center">
                    <h5>Annulés</h5>
                    <strong style="font-size:32px;"><?php echo (int)$totals['annule']; ?></strong>
                    <p class="text-muted mb-0">Annulés</p>
                </div>
            </div>
        </div>

        <div class="card mt-4 appointment-card">
            <div class="card-body">
                <h4 class="card-title mb-3">Rendez-vous du <?php echo date('d/m/Y', strtotime($dateFilter)); ?></h4>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Heure</th>
                                <th>Client</th>
                                <th>Agence</th>
                                <th>Agent</th>
                                <th>Motif</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($appointments)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Aucun rendez-vous trouvé pour cette date.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($appointments as $rdv): ?>
                                <tr>
                                    <td><?php echo date('H:i', strtotime($rdv['date_rdv'])); ?></td>
                                    <td><?php echo htmlspecialchars($rdv['client_prenom'] . ' ' . $rdv['client_nom']); ?></td>
                                    <td><?php echo htmlspecialchars($rdv['nom_agence']); ?></td>
                                    <td><?php echo htmlspecialchars($rdv['agent_prenom'] ? $rdv['agent_prenom'].' '.$rdv['agent_nom'] : 'Non assigné'); ?></td>
                                    <td><?php echo htmlspecialchars($rdv['motif']); ?></td>
                                    <td><span class="badge bg-<?php echo badgeClass($rdv['statut']); ?> status-badge"><?php echo htmlspecialchars(ucfirst($rdv['statut'])); ?></span></td>
                                    <td>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id" value="<?php echo $rdv['id']; ?>">
                                            <select name="status" class="form-select form-select-sm d-inline-block" style="width:auto; display:inline-block;">
                                                <option value="confirmé"<?php echo $rdv['statut'] === 'confirmé' ? ' selected' : ''; ?>>Confirmé</option>
                                                <option value="effectué"<?php echo $rdv['statut'] === 'effectué' ? ' selected' : ''; ?>>Effectué</option>
                                                <option value="annulé"<?php echo $rdv['statut'] === 'annulé' ? ' selected' : ''; ?>>Annulé</option>
                                            </select>
                                            <button class="btn btn-sm btn-outline-primary mt-1" type="submit">Mettre à jour</button>
                                        </form>
                                        <form method="POST" style="display:inline-block; margin-left:6px;">
                                            <input type="hidden" name="action" value="send_reminder">
                                            <input type="hidden" name="id" value="<?php echo $rdv['id']; ?>">
                                            <button class="btn btn-sm btn-outline-success mt-1">Envoyer rappel</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
