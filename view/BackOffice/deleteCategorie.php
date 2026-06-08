<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
require_once dirname(__DIR__, 2) . '/helpers/CsrfHelper.php';
require_once __DIR__ . '/../../controller/CategorieController.php';

SessionGuard::requireBackoffice();
SessionGuard::requireRoles(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

if (!CsrfHelper::verify($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Jeton CSRF invalide.');
}

if (isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    $categorieC = new CategorieController();
    $categorieC->deleteCategorie($id);
}

header('Location: categories_back.php');
exit;