<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];

require_once __DIR__ . '/../bootstrap.php';