<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../connexion.php';
require_once __DIR__ . '/../../helpers/RoleHelper.php';

RoleHelper::requireRole(['superadmin', 'admin', 'admin_agence']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $idSinistre = (int)($_POST['id_sinistre'] ?? 0);
    $idAgent    = (int)($_POST['id_agent']    ?? 0);

    if (!$idSinistre || !$idAgent) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes.']);
        exit;
    }

    $db = config::getConnexion();
    $stmt = $db->prepare("UPDATE sinistre SET id_agent_assigne = :agent, statut = IF(statut = 'en_attente', 'assigne', statut) WHERE id_sinistre = :sin");
    $success = $stmt->execute([':agent' => $idAgent, ':sin' => $idSinistre]);

    if ($success) {
        // Notifier le client
        $stmtS = $db->prepare("SELECT id_user FROM sinistre WHERE id_sinistre = ?");
        $stmtS->execute([$idSinistre]);
        $idUserSinistre = $stmtS->fetchColumn();
        if ($idUserSinistre) {
            $db->prepare("INSERT INTO notification (id_user, message, type, lien) VALUES (?, ?, 'sinistre', ?)")
                ->execute([$idUserSinistre, "Votre sinistre #$idSinistre a été assigné à un agent.", '/view/FrontOffice/sinistre.php']);
        }
    }

    echo json_encode(['success' => $success, 'message' => $success ? 'Agent assigné avec succès.' : 'Erreur lors de l\'assignation.']);
    exit;
}

// GET Request -> Agent Workload Grid UI
$db = config::getConnexion();
$role = RoleHelper::getRole();
$agence = RoleHelper::getAgenceId();

$agentWhere = "u.role IN ('agent', 'admin_agence')";
$params = [];
if ($role !== 'superadmin' && $agence) {
    $agentWhere .= " AND u.id_agence = :agence";
    $params[':agence'] = $agence;
}
$stmtAgents = $db->prepare("
    SELECT u.id_user, u.prenom, u.nom, u.email,
           COUNT(s.id_sinistre) as nb_assignes,
           AVG(CASE WHEN t.date_traitement IS NOT NULL THEN DATEDIFF(t.date_traitement, s.date_declaration) ELSE NULL END) as temps_moyen_jours
    FROM user u
    LEFT JOIN sinistre s ON s.id_agent_assigne = u.id_user
    LEFT JOIN traitement t ON t.id_sinistre = s.id_sinistre
    WHERE $agentWhere
    GROUP BY u.id_user
    ORDER BY nb_assignes ASC
");
$stmtAgents->execute($params);
$agents = $stmtAgents->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="agent-workload-grid">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Agent</th>
                <th>Dossiers en cours</th>
                <th>Temps moyen de traitement</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($agents as $agent): ?>
            <tr>
                <td><strong><?= htmlspecialchars($agent['prenom'] . ' ' . $agent['nom']) ?></strong><br><small><?= htmlspecialchars($agent['email']) ?></small></td>
                <td><span class="badge bg-primary rounded-pill"><?= $agent['nb_assignes'] ?></span></td>
                <td><?= $agent['temps_moyen_jours'] !== null ? round($agent['temps_moyen_jours'], 1) . ' jours' : '—' ?></td>
                <td><button class="btn btn-sm btn-outline-primary" onclick="assignToAgent(<?= $agent['id_user'] ?>)">Assigner</button></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
