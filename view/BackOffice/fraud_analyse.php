<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
SessionGuard::requireRoles(['superadmin', 'admin', 'agent']);

require_once __DIR__ . '/../../controller/FraudeController.php';
$ctrl = new FraudeController();
$ctrl->lancerAnalyse();
