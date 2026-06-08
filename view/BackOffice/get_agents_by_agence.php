<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';
$db = config::getConnexion();

try {
    // Utiliser l'ID agence stocké en session (défini à la connexion)
    $id_agence = $_SESSION['id_agence'] ?? null;

    if (!$id_agence) {
        // Fallback pour superadmin : tous les agents
        $sql = "SELECT u.id_user, u.nom, u.prenom 
                FROM user u 
                JOIN agent a ON u.id_user = a.id_user 
                WHERE u.role = 'agent'";
        $params = [];
    } else {
        $sql = "SELECT u.id_user, u.nom, u.prenom 
                FROM user u 
                JOIN agent a ON u.id_user = a.id_user 
                WHERE u.role = 'agent' AND a.id_agence = ?";
        $params = [$id_agence];
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $agents]);

} catch (Exception $e) {
    error_log('get_agents_by_agence error: ' . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Erreur serveur"]);
}

