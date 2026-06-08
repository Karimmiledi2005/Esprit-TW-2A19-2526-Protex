<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
require_once dirname(__DIR__, 2) . '/config.php';

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

// Must be logged in
$client_id = $_SESSION['user_id'] ?? $_SESSION['id_client'] ?? null;
if (!$client_id) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté.']);
    exit;
}

$id_offre      = (int)($_POST['id_offre'] ?? 0);
$couverture    = trim($_POST['couverture'] ?? '');
$prime         = (float)($_POST['prime_mensuelle'] ?? 0);
$date_debut    = trim($_POST['date_debut'] ?? '');
$duree         = max(1, (int)($_POST['duree_mois'] ?? 12));

if ($id_offre <= 0 || $prime <= 0 || empty($date_debut)) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes ou invalides.']);
    exit;
}

try {
    $db = config::getConnexion();

    // Verify the offer exists and is active
    $stmt = $db->prepare("SELECT * FROM offre WHERE id_offre = ? AND statut = 'active'");
    $stmt->execute([$id_offre]);
    $offre = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$offre) {
        echo json_encode(['success' => false, 'message' => "L'offre sélectionnée n'est pas disponible."]);
        exit;
    }

    // Compute end date
    $dt_debut = new DateTime($date_debut);
    $dt_fin   = clone $dt_debut;
    $dt_fin->add(new DateInterval("P{$duree}M"));

    $date_debut_fmt = $dt_debut->format('Y-m-d');
    $date_fin_fmt   = $dt_fin->format('Y-m-d');

    // Fetch user info to fill in required fields
    $userStmt = $db->prepare("SELECT nom, prenom, email, telephone FROM user WHERE id_user = ?");
    $userStmt->execute([$client_id]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC) ?? [];

    // Override with POST values if provided
    $nom       = trim($_POST['nom']      ?? $user['nom']       ?? '');
    $prenom    = trim($_POST['prenom']   ?? $user['prenom']    ?? '');
    $email     = trim($_POST['email']    ?? $user['email']     ?? '');
    $telephone = trim($_POST['telephone']?? $user['telephone'] ?? '');

    // Insert into devis using the real schema
    $insertDevis = $db->prepare("
        INSERT INTO devis (nom, prenom, email, telephone, type_assurance, id_offre, montant_estime, statut, date_demande)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'en_attente', NOW())
    ");
    $insertDevis->execute([
        $nom,
        $prenom,
        $email,
        $telephone,
        $offre['type_offre'],
        $id_offre,
        $prime
    ]);

    $id_devis = (int)$db->lastInsertId();

    echo json_encode([
        'success'  => true,
        'id_devis' => $id_devis,
        'id_offre' => $id_offre,
        'message'  => 'Souscription enregistrée avec succès.'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
