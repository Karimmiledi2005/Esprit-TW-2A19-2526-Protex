<?php
require_once __DIR__ . '/../../bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
RoleHelper::requireRole(['superadmin', 'admin', 'agent']);

try {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) throw new Exception('ID manquant.');

    $db = config::getConnexion();
    // On utilise une requête complète comme dans listAllReclamations mais pour un seul ID
    $sql = "SELECT
                r.*,
                u.nom AS client_nom, u.prenom AS client_prenom,
                c.numero_client, ag.nom_agence,
                rep.id_re        AS rep_id,
                rep.contenu      AS reponse_contenu,
                rep.date_reponse AS rep_date,
                rep.statut       AS rep_statut,
                ua.nom           AS agent_nom,
                ua.prenom        AS agent_prenom
            FROM reclamation r
            LEFT JOIN user u ON r.id_user = u.id_user
            LEFT JOIN client c ON r.id_user = c.id_user
            LEFT JOIN agence ag ON c.id_agence = ag.id_agence
            LEFT JOIN (
                SELECT rr.* FROM reponse rr
                INNER JOIN (
                    SELECT reclamation_id, MAX(id_re) AS max_id_re FROM reponse GROUP BY reclamation_id
                ) last_rep ON last_rep.max_id_re = rr.id_re
            ) rep ON rep.reclamation_id = r.id
            LEFT JOIN user ua ON rep.id_user = ua.id_user
            WHERE r.id = ?
            LIMIT 1";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    $rec = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rec) {
        throw new Exception('Réclamation introuvable.');
    }

    echo json_encode(['success' => true, 'rec' => $rec]);

} catch (Exception $e) {
    http_response_code(400);
    error_log('get_reclamation error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
