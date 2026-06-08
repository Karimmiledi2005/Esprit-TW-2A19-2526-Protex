<?php
/**
 * view/BackOffice/toggle_agence.php
 * Endpoint AJAX pour basculer le statut d'une agence (active <-> inactive)
 */

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

// 1. Restriction aux rôles autorisés (superadmin ou admin)
$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['superadmin', 'admin'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Accès refusé. Rôles autorisés : superadmin, admin."]);
    exit;
}

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/PosteModel.php';

// 2. Lecture des données JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$id_agence = $data['id_agence'] ?? 0;

if (!$id_agence) {
    echo json_encode(["success" => false, "message" => "ID agence manquant."]);
    exit;
}

try {
    $pdo = config::getConnexion();
    $model = new PosteModel($pdo);

    // 3. Récupération de l'agence
    $agence = $model->getAgenceById($id_agence);
    if (!$agence) {
        echo json_encode(["success" => false, "message" => "Agence introuvable."]);
        exit;
    }

    // 4. Toggle du statut
    $currentStatut = $agence['statut'] ?? 'inactive';
    $newStatut = ($currentStatut === 'active') ? 'inactive' : 'active';

    // 5. Mise à jour via le modèle
    // On prépare les données pour updateAgence (qui attend un tableau complet)
    $updateData = [
        'id_agence'  => $id_agence,
        'nom_agence' => $agence['nom_agence'],
        'pays'       => $agence['pays'],
        'tel'        => $agence['tel'],
        'email'      => $agence['email'],
        'statut'     => $newStatut,
        'adresse'    => $agence['adresse']
    ];

    if ($model->updateAgence($updateData)) {
        echo json_encode([
            "success" => true,
            "statut"  => $newStatut,
            "message" => "Le statut de l'agence a été mis à jour."
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Erreur lors de la mise à jour en base de données."]);
    }

} catch (Exception $e) {
    error_log('toggle_agence error: ' . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Erreur serveur"]);
}
