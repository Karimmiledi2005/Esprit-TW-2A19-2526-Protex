<?php
/**
 * config.php — Point de configuration unique Protex Assurance
 * FIX P-06 : BASE_URL dynamique (plus de /projet_web1/ hardcodé)
 */

if (!defined('BASE_URL')) {
    // Calcule l'URL de base à partir du chemin réel du projet
    $scriptDir  = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $projectDir = str_replace('\\', '/', rtrim(dirname(__DIR__) === dirname(dirname(__DIR__)) ? dirname($_SERVER['SCRIPT_NAME'] ?? '/') : '', '/'));
    // Méthode fiable : chemin relatif depuis DOCUMENT_ROOT jusqu'à __DIR__
    $docRoot    = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? 'C:/xampp/htdocs'));
    $selfDir    = str_replace('\\', '/', realpath(__DIR__));
    $relative   = substr($selfDir, strlen($docRoot));
    define('BASE_URL', rtrim($relative, '/'));
}

if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', false);
}

// Charger config.env.php une seule fois et définir toutes les constantes
if (!defined('_PROTEX_ENV_LOADED')) {
    define('_PROTEX_ENV_LOADED', true);
    $__envFile = __DIR__ . '/config.env.php';
    if (file_exists($__envFile)) {
        $__env = require $__envFile;
        // SMTP
        if (!defined('MAIL_SMTP_HOST'))     define('MAIL_SMTP_HOST',     $__env['mail_host']      ?? 'smtp.gmail.com');
        if (!defined('MAIL_SMTP_PORT'))     define('MAIL_SMTP_PORT',     $__env['mail_port']      ?? 587);
        if (!defined('MAIL_SMTP_USER'))     define('MAIL_SMTP_USER',     $__env['mail_username']  ?? '');
        if (!defined('MAIL_SMTP_PASS'))     define('MAIL_SMTP_PASS',     $__env['mail_password']  ?? '');
        if (!defined('MAIL_FROM'))          define('MAIL_FROM',          $__env['mail_from']      ?? '');
        if (!defined('MAIL_FROM_EMAIL'))    define('MAIL_FROM_EMAIL',    $__env['mail_from']      ?? ($__env['mail_username'] ?? ''));
        if (!defined('MAIL_FROM_NAME'))     define('MAIL_FROM_NAME',     $__env['mail_from_name'] ?? 'Protex Assurance');
        // XAMPP local : bypass SSL cert (verify_peer = false)
        if (!defined('MAIL_SMTP_ALLOW_INSECURE_SSL')) define('MAIL_SMTP_ALLOW_INSECURE_SSL', true);
        // Stripe
        if (!defined('STRIPE_SECRET_KEY'))      define('STRIPE_SECRET_KEY',      $__env['stripe_secret_key']      ?? '');
        if (!defined('STRIPE_PUBLISHABLE_KEY')) define('STRIPE_PUBLISHABLE_KEY', $__env['stripe_publishable_key'] ?? '');
        // Infobip
        if (!defined('INFOBIP_API_KEY'))    define('INFOBIP_API_KEY',    $__env['infobip_api_key']  ?? '');
        if (!defined('INFOBIP_BASE_URL'))   define('INFOBIP_BASE_URL',   $__env['infobip_base_url'] ?? '');
        if (!defined('INFOBIP_SMS_SENDER')) define('INFOBIP_SMS_SENDER', $__env['infobip_sender']   ?? 'ServiceSMS');
        // Groq
        if (!defined('GROQ_API_KEY'))       define('GROQ_API_KEY',       $__env['groq_api_key']     ?? '');
        // Claude
        if (!defined('CLAUDE_API_KEY'))     define('CLAUDE_API_KEY',     $__env['claude_api_key']   ?? '');
        // Gemini (vision)
        if (!defined('GEMINI_API_KEY'))     define('GEMINI_API_KEY',     $__env['gemini_api_key']   ?? '');

        // GitHub OAuth
        if (!defined('GITHUB_CLIENT_ID'))     define('GITHUB_CLIENT_ID',     $__env['github_client_id']     ?? '');
        if (!defined('GITHUB_CLIENT_SECRET')) define('GITHUB_CLIENT_SECRET', $__env['github_client_secret'] ?? '');
        // QR verification secret
        if (!defined('QR_VERIFICATION_SECRET')) define('QR_VERIFICATION_SECRET', $__env['qr_secret'] ?? 'protex_secret_2026');
        unset($__env);
    }
    unset($__envFile);
}

if (!class_exists('config')) {
    class config
    {
        private static ?PDO $pdo = null;

        public static function getConnexion(): PDO
        {
            if (self::$pdo === null) {
                $configFile = __DIR__ . '/config.env.php';

                if (file_exists($configFile)) {
                    $env        = require $configFile;
                    $servername = $env['db_host']     ?? 'localhost';
                    $username   = $env['db_user']     ?? 'root';
                    $password   = $env['db_password'] ?? '';
                    $dbname     = $env['db_name']     ?? 'assurance';
                } else {
                    $servername = 'localhost';
                    $username   = 'root';
                    $password   = '';
                    $dbname     = 'assurance';
                }

                try {
                    self::$pdo = new PDO(
                        "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
                        $username,
                        $password,
                        [
                            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES   => false,
                        ]
                    );
                } catch (Exception $e) {
                    error_log('DB connection error: ' . $e->getMessage());
                    throw new Exception('Erreur de connexion à la base de données.');
                }
            }

            return self::$pdo;
        }
    }
}
