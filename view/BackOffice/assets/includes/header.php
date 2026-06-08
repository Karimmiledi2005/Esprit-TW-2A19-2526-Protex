<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 4) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
$pageTitle = $pageTitle ?? 'Protex Admin';
$extraCss  = $extraCss ?? '';
$baseUrl   = defined('BASE_URL') ? BASE_URL : '/assurance';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Protex Admin</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/BackOffice/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/BackOffice/assets/css/variables.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/BackOffice/assets/css/base.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/BackOffice/assets/css/layout.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/view/BackOffice/assets/css/animations.css">
    <?= $extraCss ?>
</head>
<body>

<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">
