<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
require_once dirname(__DIR__, 2) . '/helpers/CsrfHelper.php';
require_once __DIR__ . '/../../controller/FormuleController.php';

SessionGuard::requireBackoffice();
SessionGuard::requireRoles(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

CsrfHelper::verify($_POST['csrf_token'] ?? '');

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    $formuleC = new FormuleController();
    $formuleC->deleteFormule($id);
}

header('Location: formules_back.php');
exit;