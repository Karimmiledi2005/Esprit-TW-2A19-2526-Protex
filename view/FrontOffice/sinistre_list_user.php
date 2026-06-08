<?php
if (session_status() === PHP_SESSION_NONE) session_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');

$logFile = __DIR__ . '/api_debug.log';
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] API CALL\n", FILE_APPEND);

ob_start();
header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['id_user']) && !isset($_SESSION['user_id'])) {
        $msg = 'Utilisateur non connecté.';
        file_put_contents($logFile, "ERR: $msg\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }

    require_once __DIR__ . '/../../bootstrap.php';
    
    $userId = (int)($_SESSION['id_user'] ?? $_SESSION['user_id']);
    file_get_contents("http://localhost/integrationFINAL/debug_log.php?msg=UserID_" . $userId); // Simple way to log if we can't write to file easily
    
    $sc = new SinistreController();
    $sinistres = $sc->getByUser($userId);
    file_put_contents($logFile, "Found " . count($sinistres) . " sinistres\n", FILE_APPEND);

    $data = [];
    foreach ($sinistres as $s) {
        $data[] = [
            'id_sinistre'      => $s->getIdSinistre(),
            'type'             => $s->getType(),
            'description'      => $s->getDescription(),
            'date_declaration' => $s->getDateDeclaration(),
            'statut'           => $s->getStatut(),
            'photo_url'        => $s->getPhotoUrl(),
            'id_contrat'       => $s->getIdContrat(),
            'fraud_score'      => $s->fraudScore,
            'fraud_niveau'     => $s->fraudNiveau,
            'fraud_suggestion' => $s->fraudSuggestion,
        ];
    }

    ob_clean();
    echo json_encode($data);
    file_put_contents($logFile, "SUCCESS: Sent " . count($data) . " items\n", FILE_APPEND);
} catch (Throwable $e) {
    ob_clean();
    $err = 'Erreur PHP: ' . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
    file_put_contents($logFile, "FATAL: $err\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $err]);
}
?>

