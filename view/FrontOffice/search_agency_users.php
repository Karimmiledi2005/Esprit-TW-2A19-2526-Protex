<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Non connecté"]);
    exit;
}

require_once __DIR__ . '/../../config.php';

$my_id = (int)$_SESSION['user_id'];
$db = config::getConnexion();

// Récupérer l'agence du client connecté
$stmt = $db->prepare("SELECT c.id_agence, a.nom_agence FROM client c LEFT JOIN agence a ON c.id_agence = a.id_agence WHERE c.id_user = :uid");
$stmt->execute(['uid' => $my_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$my_agence    = $row ? $row['id_agence'] : null;
$my_nom_agence = $row ? $row['nom_agence'] : null;

// Lire les paramètres (GET ou POST JSON)
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (empty($data)) $data = $_GET;
$action = $data['action'] ?? '';

// ─── ACTION: my_agence ───
if ($action === 'my_agence') {
    if (!$my_agence) {
        echo json_encode(["success" => false, "message" => "Aucune agence"]);
    } else {
        echo json_encode(["success" => true, "nom_agence" => $my_nom_agence ?? 'Agence #'.$my_agence, "id_agence" => $my_agence]);
    }
    exit;
}

// ─── ACTION: search ───
if (!$my_agence) {
    echo json_encode(["success" => false, "message" => "Vous n'êtes rattaché à aucune agence"]);
    exit;
}

$query = trim($data['q'] ?? $data['query'] ?? '');

try {
    // Build WHERE clause – if query is empty, show all same-agency users
    $searchCondition = "";
    $params = [
        'me3'    => $my_id,
        'me4'    => $my_id,
        'me5'    => $my_id,
        'agence' => $my_agence,
    ];

    if (strlen($query) >= 1) {
        $search = '%' . $query . '%';
        $searchCondition = "AND (u.nom LIKE :q1 OR u.prenom LIKE :q2 OR CONCAT(u.prenom, ' ', u.nom) LIKE :q3)";
        $params['q1'] = $search;
        $params['q2'] = $search;
        $params['q3'] = $search;
    }

    $sql = "
        SELECT u.id_user, u.nom, u.prenom, u.avatar_url, u.role, u.last_seen,
               CASE WHEN u.last_seen IS NOT NULL AND TIMESTAMPDIFF(MINUTE, u.last_seen, NOW()) < 5
                    THEN 1 ELSE 0 END AS is_online,
               f.status AS friendship_status,
               f.sender_id AS f_sender_id,
               COALESCE(f.is_trusted, 0) AS is_trusted
        FROM user u
        JOIN client c ON u.id_user = c.id_user
        LEFT JOIN friendships f ON (
            (f.sender_id = :me3 AND f.receiver_id = u.id_user)
            OR (f.sender_id = u.id_user AND f.receiver_id = :me4)
        )
        WHERE c.id_agence = :agence
          AND u.id_user != :me5
          AND u.statut = 'actif'
          $searchCondition
        ORDER BY u.prenom ASC, u.nom ASC
        LIMIT 30
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Compute rel_status for each result (as expected by reseau.html)
    foreach ($results as &$u) {
        if (empty($u['friendship_status'])) {
            $u['rel_status'] = null; // no relationship
        } elseif ($u['friendship_status'] === 'accepted') {
            $u['rel_status'] = 'accepted';
        } elseif ($u['friendship_status'] === 'pending') {
            $u['rel_status'] = ($u['f_sender_id'] == $my_id) ? 'pending_sent' : 'pending_recv';
        } else {
            $u['rel_status'] = $u['friendship_status'];
        }
        // Clean up internal fields
        unset($u['friendship_status'], $u['f_sender_id']);
    }
    unset($u);

    echo json_encode(["success" => true, "users" => $results, "agence_id" => $my_agence]);

} catch (Exception $e) {
    error_log("search_agency_users error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Erreur serveur"]);
}


