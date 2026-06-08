<?php
require_once __DIR__ . '/../../controller/SinistreController.php';
require_once __DIR__ . '/../../connexion.php';

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'ID sinistre manquant.']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../helpers/RoleHelper.php';
RoleHelper::requireRole(['superadmin', 'admin', 'agent']);

$db = config::getConnexion();
$controller = new SinistreController();
$sinistre = $controller->getById($id);

$role = RoleHelper::getRole();
$idAgence = RoleHelper::getAgenceId();

header('Content-Type: application/json; charset=utf-8');
if ($sinistre) {
    // RBAC: si pas superadmin, vérifier l'agence
    if ($role !== 'superadmin' && $idAgence) {
        // Il faut vérifier si le sinistre appartient à l'agence de l'admin/agent
        // On récupère l'id_agence du sinistre via la DB
        $stmtCheck = $db->prepare("SELECT id_agence FROM sinistre WHERE id_sinistre = ?");
        $stmtCheck->execute([$id]);
        $sAgence = $stmtCheck->fetchColumn();
        if ((int)$sAgence !== (int)$idAgence) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé: ce sinistre appartient à une autre agence.']);
            exit;
        }
    }
    // Récupérer l'agent assigné
    $stmtAgent = $db->prepare("
        SELECT u.prenom, u.nom 
        FROM user u 
        JOIN sinistre s ON s.id_agent_assigne = u.id_user 
        WHERE s.id_sinistre = :id
    ");
    $stmtAgent->execute([':id' => $id]);
    $agent = $stmtAgent->fetch(PDO::FETCH_ASSOC);

    // Récupérer les informations de traitement
    $stmtTrait = $db->prepare("
        SELECT t.*, u.prenom as agent_prenom, u.nom as agent_nom 
        FROM traitement t 
        LEFT JOIN user u ON t.id_user = u.id_user 
        WHERE t.id_sinistre = :id
    ");
    $stmtTrait->execute([':id' => $id]);
    $traitement = $stmtTrait->fetch(PDO::FETCH_ASSOC);

    // Get comments
    $stmtCom = $db->prepare("SELECT c.id, c.commentaire, c.created_at, u.prenom, u.nom FROM sinistre_commentaire c JOIN user u ON c.id_user = u.id_user WHERE c.id_sinistre = ? ORDER BY c.created_at DESC");
    $stmtCom->execute([$id]);
    $comments = $stmtCom->fetchAll(PDO::FETCH_ASSOC);

    // Get files
    $stmtFile = $db->prepare("SELECT id, nom_fichier, chemin, taille, uploaded_at FROM sinistre_fichier WHERE id_sinistre = ? ORDER BY uploaded_at DESC");
    $stmtFile->execute([$id]);
    $files = $stmtFile->fetchAll(PDO::FETCH_ASSOC);


    // Construction de l'historique (Timeline)
    $history = [];
    
    // 1. Création
    $history[] = [
        'event' => 'Création',
        'date' => $sinistre->getDateDeclaration(),
        'author' => 'Client',
        'icon' => 'bi-plus-circle',
        'status' => 'done'
    ];

    // 2. Assignation (si agent_assigne est présent)
    if ($agent) {
        $history[] = [
            'event' => 'Assignation',
            'date' => null, // On n'a pas la date exacte d'assignation dans la table
            'author' => $agent['prenom'] . ' ' . $agent['nom'],
            'icon' => 'bi-person-check',
            'status' => 'done'
        ];
    }

    // 3. Traitement
    if ($traitement) {
        $history[] = [
            'event' => 'Traitement',
            'date' => $traitement['date_traitement'],
            'author' => $traitement['agent_prenom'] . ' ' . $traitement['agent_nom'],
            'icon' => 'bi-gear-wide-connected',
            'status' => 'done'
        ];
        
        // 4. Validation / Décision
        if ($sinistre->getStatut() !== 'en_attente') {
            $history[] = [
                'event' => $sinistre->getStatut() === 'rembourse' ? 'Validation' : 'Refus',
                'date' => $traitement['date_traitement'],
                'author' => $traitement['agent_prenom'] . ' ' . $traitement['agent_nom'],
                'icon' => $sinistre->getStatut() === 'rembourse' ? 'bi-check-all' : 'bi-x-circle',
                'status' => 'done'
            ];
        }
    }

    // Récupérer l'estimation IA
    $stmtAI = $db->prepare("SELECT ai_cost_min, ai_cost_max, ai_cost_estimate, ai_remboursement, ai_analysis, ai_generated_at FROM sinistre WHERE id_sinistre = ?");
    $stmtAI->execute([$id]);
    $aiData = $stmtAI->fetch(PDO::FETCH_ASSOC) ?: [];

    echo json_encode([
        'success' => true,
        'data' => [
            'id_sinistre' => $sinistre->getIdSinistre(),
            'type' => $sinistre->getType(),
            'description' => $sinistre->getDescription(),
            'photo_url' => $sinistre->getPhotoUrl(),
            'date_declaration' => $sinistre->getDateDeclaration(),
            'statut' => $sinistre->getStatut(),
            'history' => $history,
            'comments' => $comments,
            'files' => $files,
            'ai_estimate' => !empty($aiData['ai_cost_estimate']) ? [
                'cost_min'      => (float)$aiData['ai_cost_min'],
                'cost_max'      => (float)$aiData['ai_cost_max'],
                'cost_estimate' => (float)$aiData['ai_cost_estimate'],
                'remboursement' => (float)$aiData['ai_remboursement'],
                'analysis'      => $aiData['ai_analysis'],
                'generated_at'  => $aiData['ai_generated_at'],
            ] : null,
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Sinistre non trouvé.']);
}
?>
