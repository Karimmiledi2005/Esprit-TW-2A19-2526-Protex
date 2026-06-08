<?php

if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__) . '/config.php';
    if (file_exists($cfgPath)) {
        require_once $cfgPath;
    }
}

require_once __DIR__ . '/RoleHelper.php';

class SessionGuard
{
    public const SESSION_TIMEOUT = 1800;

    /**
     * Démarre la session PHP avec les mêmes paramètres partout (login, bootstrap, pages client).
     * N'utilise pas de nom de cookie personnalisé pour rester compatible avec login.php / OTP.
     */
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }

    public static function check(): void
    {
        if (!self::isLoggedIn()) {
            self::redirectToLogin();
            exit;
        }

        if (isset($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > self::SESSION_TIMEOUT) {
            self::destroy();
            self::redirectToLogin('Session expirée. Veuillez vous reconnecter.');
            exit;
        }

        $_SESSION['last_activity'] = time();
    }

    public static function requireLogin(): void
    {
        self::check();
    }

    public static function requireBackoffice(): void
    {
        self::check();

        if (!in_array(self::role(), ['superadmin', 'admin', 'agent'], true)) {
            self::redirectToLogin('Accès refusé.', 'backoffice');
            exit;
        }
    }

    public static function requireClient(): void
    {
        self::check();

        if (self::role() !== 'client') {
            self::redirectToLogin('Accès réservé aux clients.', 'frontoffice');
            exit;
        }
    }

    public static function requireRole(string $role): void
    {
        self::check();

        if (self::role() !== $role) {
            http_response_code(403);
            $viewPath = dirname(__DIR__) . '/view/BackOffice/403.php';
            if (file_exists($viewPath)) {
                include $viewPath;
            } else {
                echo 'Accès refusé';
            }
            exit;
        }
    }

    public static function requireRoles(array $roles): void
    {
        self::check();

        if (!in_array(self::role(), $roles, true)) {
            self::redirectToLogin('Accès refusé.');
            exit;
        }
    }

    public static function userId(): int
    {
        return (int) ($_SESSION['user_id'] ?? $_SESSION['id_user'] ?? 0);
    }

    public static function role(): string
    {
        return (string) ($_SESSION['user_role'] ?? $_SESSION['role'] ?? 'client');
    }

    public static function agenceId(): ?int
    {
        if (isset($_SESSION['agence_id'])) {
            return (int) $_SESSION['agence_id'];
        }

        return isset($_SESSION['id_agence']) ? (int) $_SESSION['id_agence'] : null;
    }

    public static function fullName(): string
    {
        return trim((string) ($_SESSION['user_prenom'] ?? $_SESSION['prenom'] ?? '') . ' ' . (string) ($_SESSION['user_nom'] ?? $_SESSION['nom'] ?? ''));
    }

    public static function email(): string
    {
        return (string) ($_SESSION['user_email'] ?? '');
    }

    public static function isLoggedIn(): bool
    {
        return self::userId() > 0;
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }

        session_destroy();
    }

    private static function redirectToLogin(string $message = '', ?string $context = null): void
    {
        if (self::expectsJson()) {
            http_response_code(self::isLoggedIn() ? 403 : 401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => $message !== '' ? $message : 'Non authentifié',
            ]);
            return;
        }

        $base = defined('BASE_URL') ? BASE_URL : '';

        if ($context === null) {
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            $context = (str_contains($scriptName, '/BackOffice/') || str_contains($scriptName, '/controller/')) ? 'backoffice' : 'frontoffice';
        }

        $loginUrl = $context === 'backoffice'
            ? $base . '/view/BackOffice/connexion.php'
            : $base . '/view/FrontOffice/login.php';

        if ($message !== '') {
            $loginUrl .= (str_contains($loginUrl, '?') ? '&' : '?') . 'error=' . rawurlencode($message);
        }

        header('Location: ' . $loginUrl);
    }

    private static function expectsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $requestedWith = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');

        return str_contains($accept, 'application/json') || $requestedWith === 'xmlhttprequest';
    }
}

SessionGuard::start();
