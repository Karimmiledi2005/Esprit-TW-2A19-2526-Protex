<?php
if (session_status() === PHP_SESSION_NONE) session_start();
error_reporting(0);
header('Content-Type: application/json');
ob_clean();

require_once __DIR__ . '/../../helpers/SessionGuard.php';
SessionGuard::requireBackoffice();

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/PosteModel.php';

try {
    $pdo = config::getConnexion();
    $model = new PosteModel($pdo);

    $id_agence_session = $_SESSION['id_agence'] ?? null;
    $role = $_SESSION['role'] ?? '';

    // Si on demande une agence spécifique (via filtre)
    $idAgenceRequested = isset($_GET['id_agence']) ? (int)$_GET['id_agence'] : 0;

    $where = [];
    $params = [];

    // RBAC: si l'utilisateur n'est pas superadmin, il est forcé sur son agence
    if ($role !== 'superadmin' && $id_agence_session) {
        $where[] = "p.id_agence = ?";
        $params[] = $id_agence_session;
    } elseif ($idAgenceRequested > 0) {
        // Le superadmin peut filtrer
        $where[] = "p.id_agence = ?";
        $params[] = $idAgenceRequested;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("
        SELECT 
            p.id_poste,
            p.contenu,
            p.date_publication,
            p.note,
            p.auteur,
            p.nb_likes,
            p.nb_commentaires,
            p.id_agence,
            a.nom_agence
        FROM poste p
        LEFT JOIN agence a ON p.id_agence = a.id_agence
        $whereSql
        ORDER BY p.id_poste DESC
    ");
    $stmt->execute($params);
    $postes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $postes]);
} catch (Exception $e) {
    error_log('get_postes_agence error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
