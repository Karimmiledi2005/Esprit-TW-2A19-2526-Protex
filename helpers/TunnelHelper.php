<?php
/**
 * TunnelHelper.php — Helper pour résolution d'URL publique (Ngrok / LocalTunnel / IP locale)
 *
 * Stratégie de résolution (priorité décroissante) :
 *  1. URL Ngrok stockée en session admin (via admin/tunnel.php)
 *  2. URL Ngrok dans config.env.php   (clé 'ngrok_url')
 *  3. IP locale du réseau Wi-Fi       (gethostbyname())
 *  4. $_SERVER['HTTP_HOST'] tel quel  (localhost fallback)
 */
class TunnelHelper
{
    /** Retourne la base URL publique utilisable par un smartphone **/
    public static function getPublicBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $base   = defined('BASE_URL') ? BASE_URL : '/assurance';

        // ── 1. Session admin : tunnel configuré via interface ────────────────
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $sessionTunnel = $_SESSION['protex_ngrok_url'] ?? null;
        if ($sessionTunnel && self::isValidTunnelUrl($sessionTunnel)) {
            return rtrim($sessionTunnel, '/') . $base;
        }

        // ── 2. config.env.php : clé 'ngrok_url' ─────────────────────────────
        $envFile = __DIR__ . '/../config.env.php';
        if (file_exists($envFile)) {
            $env = require $envFile;
            $ngrokUrl = $env['ngrok_url'] ?? '';
            if ($ngrokUrl && self::isValidTunnelUrl($ngrokUrl)) {
                return rtrim($ngrokUrl, '/') . $base;
            }
        }

        // ── 3. IP locale Wi-Fi ───────────────────────────────────────────────
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        if (str_contains($host, 'localhost') || str_contains($host, '127.0.0.1')) {
            $localIp = gethostbyname(gethostname());
            if ($localIp && $localIp !== '127.0.0.1' && filter_var($localIp, FILTER_VALIDATE_IP)) {
                $port = parse_url('http://' . $host, PHP_URL_PORT);
                $host = $localIp . ($port ? ':' . $port : '');
            }
        }

        // ── 4. Fallback ──────────────────────────────────────────────────────
        return $scheme . '://' . $host . $base;
    }

    /** Valide que l'URL est bien un tunnel (http/https + domaine non-localhost) **/
    public static function isValidTunnelUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) return false;
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        return !empty($host)
            && $host !== 'localhost'
            && $host !== '127.0.0.1'
            && str_contains($url, '//');
    }

    /** Retourne un tableau diagnostic (utile pour la page admin) **/
    public static function getDiagnostic(): array
    {
        $localIp = gethostbyname(gethostname());

        // Lecture config.env.php
        $envNgrok = '';
        $envFile  = __DIR__ . '/../config.env.php';
        if (file_exists($envFile)) {
            $env = require $envFile;
            $envNgrok = $env['ngrok_url'] ?? '';
        }

        $sessionNgrok = $_SESSION['protex_ngrok_url'] ?? '';

        return [
            'local_ip'        => $localIp,
            'localhost_host'  => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'env_ngrok_url'   => $envNgrok,
            'session_ngrok_url' => $sessionNgrok,
            'resolved_url'    => self::getPublicBaseUrl(),
            'method'          => self::getResolutionMethod(),
        ];
    }

    /** Indique quelle méthode est utilisée actuellement **/
    public static function getResolutionMethod(): string
    {
        $sessionTunnel = $_SESSION['protex_ngrok_url'] ?? null;
        if ($sessionTunnel && self::isValidTunnelUrl($sessionTunnel)) {
            return 'session';
        }

        $envFile = __DIR__ . '/../config.env.php';
        if (file_exists($envFile)) {
            $env = require $envFile;
            if (!empty($env['ngrok_url']) && self::isValidTunnelUrl($env['ngrok_url'])) {
                return 'config_env';
            }
        }

        $localIp = gethostbyname(gethostname());
        if ($localIp && $localIp !== '127.0.0.1' && filter_var($localIp, FILTER_VALIDATE_IP)) {
            return 'local_ip';
        }

        return 'localhost_fallback';
    }
}
