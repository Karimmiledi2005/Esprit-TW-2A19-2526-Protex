<?php
/**
 * CsrfHelper.php
 * Gestion simple des jetons CSRF pour les actions BackOffice
 */
class CsrfHelper
{
    public static function generate(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function verify(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::generate(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function initToken(): void
    {
        self::generate();
    }

    public static function getToken(): string
    {
        return self::generate();
    }

    public static function generateField(): string
    {
        return self::field();
    }

    public static function validate(): void
    {
        if (!self::verify($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Validation de sécurité (CSRF) échouée. Veuillez rafraîchir la page.'
            ]);
            exit;
        }
    }
}
