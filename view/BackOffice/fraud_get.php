<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
require_once __DIR__ . '/../../controller/FraudeController.php';

SessionGuard::requireBackoffice();

header('Content-Type: application/json; charset=utf-8');

$ctrl = new FraudeController();
$ctrl->getAnalyse();
