<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers/AgenceHelper.php';

try {
    $db = config::getConnexion();
    $stmt = $db->query("SELECT id_agence, nom_agence, pays, tel, email, statut, adresse, latitude, longitude FROM agence WHERE statut = 'active' ORDER BY nom_agence ASC");
    $agences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($agences as &$a) {
        $a['ouverture'] = AgenceHelper::getStatutOuverture((int)$a['id_agence']);
        $a['latitude'] = $a['latitude'] ? (float)$a['latitude'] : null;
        $a['longitude'] = $a['longitude'] ? (float)$a['longitude'] : null;
    }
    echo json_encode(['success' => true, 'data' => $agences]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
