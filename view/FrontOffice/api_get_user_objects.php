<?php
/**
 * API polymorphique : retourne les objets disponibles pour l'utilisateur
 * selon le type de module demandé.
 *
 * GET  api_get_user_objects.php?type=contrat
 * GET  api_get_user_objects.php?type=devis
 * GET  api_get_user_objects.php?type=sinistre
 * GET  api_get_user_objects.php?type=paiement
 * GET  api_get_user_objects.php?type=poste
 *
 * Retourne JSON : { success: true, items: [{ id, label }, ...] }
 */

require_once __DIR__ . '/../../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// Auth guard
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? $_SESSION['id_user'] ?? 0);
if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID utilisateur invalide']);
    exit;
}

$type = strtolower(trim($_GET['type'] ?? 'contrat'));
$validTypes = ['contrat', 'devis', 'sinistre', 'paiement', 'poste', 'general'];
if (!in_array($type, $validTypes, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "Type invalide: $type"]);
    exit;
}

try {
    $db = config::getConnexion();
    $items = [];

    switch ($type) {
        // ─── CONTRATS ────────────────────────────────────────────────────────
        case 'contrat':
            $stmt = $db->prepare(
                "SELECT numero_contrat AS id,
                        CONCAT(numero_contrat, ' - ', type_contrat, ' (', statut_contrat, ')') AS label
                 FROM contrat
                 WHERE id_user = :uid
                 ORDER BY date_debut DESC"
            );
            $stmt->execute([':uid' => $userId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        // ─── DEVIS ───────────────────────────────────────────────────────────
        case 'devis':
            $stmt = $db->prepare(
                "SELECT id_devis AS id,
                        CONCAT('DEV-', id_devis, ' - ', COALESCE(type_assurance, ''), ' (', COALESCE(statut, ''), ')') AS label
                 FROM devis
                 WHERE id_user = :uid
                 ORDER BY created_at DESC"
            );
            $stmt->execute([':uid' => $userId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        // ─── SINISTRES ───────────────────────────────────────────────────────
        case 'sinistre':
            $stmt = $db->prepare(
                "SELECT id_sinistre AS id,
                        CONCAT('SIN-', id_sinistre, ' - ', COALESCE(type, ''), ' (', COALESCE(statut, ''), ')') AS label
                 FROM sinistre
                 WHERE id_user = :uid
                 ORDER BY date_declaration DESC"
            );
            $stmt->execute([':uid' => $userId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        // ─── PAIEMENTS ───────────────────────────────────────────────────────
        case 'paiement':
            $stmt = $db->prepare(
                "SELECT id_paiement AS id,
                        CONCAT('PAY-', id_paiement, ' - ', COALESCE(montant, ''), ' DZD (', COALESCE(statut, ''), ')') AS label
                 FROM paiement
                 WHERE id_user = :uid
                 ORDER BY date_paiement DESC"
            );
            $stmt->execute([':uid' => $userId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        // ─── POSTES SOCIAUX ──────────────────────────────────────────────────
        case 'poste':
            $stmt = $db->prepare(
                "SELECT COALESCE(id_poste, id) AS id,
                        CONCAT(COALESCE(titre_poste, titre, CONCAT('POSTE-', COALESCE(id_poste,id))),
                               ' (', COALESCE(statut, ''), ')') AS label
                 FROM poste_social
                 WHERE id_user = :uid
                 ORDER BY date_creation DESC"
            );
            $stmt->execute([':uid' => $userId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        // ─── GÉNÉRAL (pas de référence spécifique) ───────────────────────────
        case 'general':
        default:
            $items = []; // No dropdown needed for "general"
            break;
    }

    echo json_encode(['success' => true, 'items' => $items, 'type' => $type]);

} catch (PDOException $e) {
    // Table might not exist — return empty instead of 500
    error_log("api_get_user_objects [$type]: " . $e->getMessage());
    echo json_encode(['success' => true, 'items' => [], 'type' => $type, 'warning' => 'Module non disponible']);
} catch (Exception $e) {
    error_log("api_get_user_objects: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
