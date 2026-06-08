<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();

require_once dirname(__DIR__, 3) . '/config.php';
$baseUrl = defined('BASE_URL') ? BASE_URL : '';
header('Location: ' . rtrim($baseUrl, '/') . '/controller/PaiementController.php');
exit;
