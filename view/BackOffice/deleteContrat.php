<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
require_once dirname(__DIR__, 2) . '/helpers/CsrfHelper.php';
require_once __DIR__ . '/../../controller/ContratController.php';

SessionGuard::requireBackoffice();
SessionGuard::requireRoles(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

CsrfHelper::verify($_POST['csrf_token'] ?? '');

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: contrats_back.php');
    exit;
}

$contratC = new ContratController();
$contratC->deleteContrat($id);

header('Location: contrats_back.php?success=delete');
exit;
