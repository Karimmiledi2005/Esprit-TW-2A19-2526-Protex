<?php
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die("Non connecté");
}

// Seuls superadmin, admin et agent peuvent exporter
$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['superadmin', 'admin', 'agent'])) {
    http_response_code(403);
    error_log("AUDIT: User {$_SESSION['user_id']} (role: $role) attempted unauthorized export");
    die("Accès refusé");
}

require_once __DIR__ . '/../../controller/Client_Con.php';

try {
    $controller = new UserController();
    AuditLogger::log('export_csv', 'user', "Export CSV des utilisateurs");
    
    // Récupérer les filtres depuis GET
    $filters = [];
    if (!empty($_GET['search'])) $filters['keyword'] = trim($_GET['search']);
    if (!empty($_GET['role']))   $filters['role'] = trim($_GET['role']);
    if (!empty($_GET['statut'])) $filters['statut'] = trim($_GET['statut']);
    if (!empty($_GET['order_by'])) $filters['order_by'] = trim($_GET['order_by']);

    // FIX 1 — Isolation agence : admin et agent ne voient que leur agence
    $sessionRole   = $_SESSION['role'] ?? '';
    $sessionAgence = (int)($_SESSION['id_agence'] ?? $_SESSION['agence_id'] ?? 0);
    if (in_array($sessionRole, ['admin', 'agent']) && $sessionAgence > 0) {
        $filters['agence'] = $sessionAgence;
    }

    // Récupérer tous les résultats (limite très haute)
    $users = $controller->searchUsers($filters, 1, 100000);

    // Nom du fichier avec date
    $filename = "export_utilisateurs_" . date('Y-m-d_H-i') . ".csv";

    // En-têtes HTTP pour forcer le téléchargement
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Flux de sortie
    $output = fopen('php://output', 'w');

    // Ajouter le BOM UTF-8 pour une bonne lecture des accents dans Excel
    fputs($output, "\xEF\xBB\xBF");

    // Ligne d'en-tête (délimiteur point-virgule)
    fputcsv($output, [
        'ID', 
        'Nom', 
        'Prénom', 
        'Email', 
        'Téléphone', 
        'CIN', 
        'Rôle', 
        'Statut', 
        'Date Création', 
        'Numéro Client', 
        'Agence', 
        'Salaire'
    ], ';');

    // Lignes de données
    foreach ($users as $user) {
        fputcsv($output, [
            $user['id_user'] ?? '',
            $user['nom'] ?? '',
            $user['prenom'] ?? '',
            $user['email'] ?? '',
            $user['telephone'] ?? '',
            $user['cin'] ?? '',
            ucfirst($user['role'] ?? ''),
            ucfirst($user['statut'] ?? ''),
            $user['date_creation'] ?? '',
            $user['numero_client'] ?? '',
            $user['nom_agence'] ?? '',
            $user['salaire'] ?? ''
        ], ';');
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    error_log('export_users error: ' . $e->getMessage());
    die("Erreur serveur");
}
