<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
if (!in_array($role, ['superadmin','admin','agent'])) {
    http_response_code(403);
    echo json_encode(["success"=>false,"message"=>"Accès refusé"]); exit;
}

header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../controller/Client_Con.php';

$filters = [
    'keyword'    => $_GET['keyword']   ?? '',
    'role'       => $_GET['role']      ?? '',
    'statut'     => $_GET['statut']    ?? '',
    'date_from'  => $_GET['date_from'] ?? '',
    'date_to'    => $_GET['date_to']   ?? '',
    'agence'     => $_GET['agence']    ?? '',
    'has_avatar' => isset($_GET['has_avatar']),
    'order_by'   => $_GET['order_by']  ?? 'date_desc',
];

$page    = max(1,(int)($_GET['page']??1));
$perPage = max(1,min(100,(int)($_GET['per_page']??20)));

try {
    $controller = new UserController();
    $results = $controller->searchUsers($filters,$page,$perPage);
    $total   = $controller->countSearchUsers($filters);
    echo json_encode(['success'=>true,'data'=>$results,'total'=>$total,'page'=>$page,'per_page'=>$perPage,'total_pages'=>ceil($total/$perPage)]);
} catch (Exception $e) {
    error_log('search_users error: ' . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Erreur serveur']);
}

