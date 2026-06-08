<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';

SessionGuard::requireBackoffice();

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../connexion.php';
require_once __DIR__ . '/../../controller/Client_Con.php';

try {
    $controller = new UserController();
    $page    = max(1,(int)($_GET['page']??1));
    $perPage = max(1,min(100,(int)($_GET['per_page']??20)));
    $users   = $controller->getAllUsers($page,$perPage);
    $total   = $controller->countAllUsers();
    echo json_encode(["success"=>true,"users"=>$users,"total"=>$total,"page"=>$page,"per_page"=>$perPage,"total_pages"=>ceil($total/$perPage)]);
} catch (Exception $e) {
    http_response_code(500);
    error_log('get_all_users error: ' . $e->getMessage());
    echo json_encode(["success"=>false,"message"=>"Erreur serveur"]);
}
