<?php
/**
 * bootstrap.php — Point d'entrée commun
 * FIX P-03 : vendor/autoload.php rendu optionnel (conditionné à file_exists)
 */

require_once __DIR__ . '/helpers/SessionGuard.php';

// Jeton CSRF unique par session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Security headers (basic hardening)
if (php_sapi_name() !== 'cli' && !headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer-when-downgrade');
    header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' wss://*.peerjs.com https://*.peerjs.com https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com https://unpkg.com https://*.tile.openstreetmap.org data: blob:; img-src 'self' data: blob: https:; font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com data:;");
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers/CsrfHelper.php';
require_once __DIR__ . '/helpers/RoleHelper.php';
require_once __DIR__ . '/helpers/AuditLogger.php';

// Composer autoload (optionnel — PHPMailer peut être chargé manuellement)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Variables d'environnement depuis config.env.php (déjà chargé dans config.php via require)
// Les constantes MAIL_* sont définies par config_services.php si présent
if (file_exists(__DIR__ . '/config_services.php')) {
    require_once __DIR__ . '/config_services.php';
}

// Modèles
// Models (optional includes to avoid fatal errors if a model file is missing)
$models = [
    '/model/User.php',
    '/model/Contrat.php',
    '/model/Sinistre.php',
    '/model/Traitement.php',
    '/model/ReclamationModel.php',
    '/model/ReponseModel.php',
    '/model/Devis.php',
    '/model/Offre.php',
    '/model/Paiement.php',
    '/model/Roulette.php',
];
foreach ($models as $m) {
    $p = __DIR__ . $m;
    if (file_exists($p)) require_once $p;
}

// Contrôleurs
// Controllers (optional)
$controllers = [
    '/controller/ContratController.php',
    '/controller/SinistreController.php',
    '/controller/TraitementController.php',
    '/controller/ReclamationController.php',
    '/controller/ReponseController.php',
];
foreach ($controllers as $c) {
    $pc = __DIR__ . $c;
    if (file_exists($pc)) require_once $pc;
}

// Services
// FIX P-05 : EmailService unique — controller/EmailService.php en priorité
if (!class_exists('EmailService')) {
    $emailServicePath = __DIR__ . '/controller/EmailService.php';
    if (file_exists($emailServicePath)) require_once $emailServicePath;
}

// Service antifraud (optionnel)
if (!class_exists('FraudeService')) {
    $fraudePath = __DIR__ . '/controller/FraudeService.php';
    if (file_exists($fraudePath)) require_once $fraudePath;
}

// Mailer léger
// Mailer (optional lightweight loader)
$mailerPath = __DIR__ . '/mailer/mailer.php';
if (file_exists($mailerPath)) require_once $mailerPath;
