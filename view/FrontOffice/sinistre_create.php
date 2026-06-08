<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_user']) && !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté.']);
    exit;
}

ob_start();
ini_set('display_errors', '0');
error_reporting(0);
require_once __DIR__ . '/../../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

$userId = (int)($_SESSION['id_user'] ?? $_SESSION['user_id']);
$idContrat = (int)($_POST['id_contrat'] ?? 0);

// Validation: vérifier que le contrat appartient à l'utilisateur
$cc = new ContratController();
$contrat = $cc->getById($idContrat);

// Note: ContratController uses getUserColumn() which might return 'id_user' or 'id_client'
// In hydrate(), it sets 'id_client' to the value of whichever column exists.
$ownerId = isset($contrat['id_client']) ? (int)$contrat['id_client'] : (isset($contrat['id_user']) ? (int)$contrat['id_user'] : 0);

if (!$contrat || $ownerId !== $userId) {
    echo json_encode(['success' => false, 'message' => 'Contrat invalide ou non autorisé.']);
    exit;
}

$sc = new SinistreController();
$result = $sc->create($_POST, $userId, $_FILES['documents'] ?? null);

ob_clean();
echo json_encode($result);
?>

