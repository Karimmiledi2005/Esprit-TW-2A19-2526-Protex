<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';

SessionGuard::destroy();

header('Location: login.html');
exit;

