<?php

require_once __DIR__ . '/../config.php';

/**
 * CREATE TABLE rate_limits (
 *   ip VARCHAR(45) NOT NULL,
 *   endpoint VARCHAR(100) NOT NULL,
 *   hits INT NOT NULL DEFAULT 1,
 *   window_start DATETIME NOT NULL,
 *   PRIMARY KEY (ip, endpoint)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 */
class RateLimiter
{
    public static function check(string $endpoint, int $maxHits = 100, int $windowSeconds = 60): void
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ip = trim(explode(',', (string) $ip)[0]);

        try {
            $db = config::getConnexion();
            $db->exec(
                "CREATE TABLE IF NOT EXISTS rate_limits (
                    ip VARCHAR(45) NOT NULL,
                    endpoint VARCHAR(100) NOT NULL,
                    hits INT NOT NULL DEFAULT 1,
                    window_start DATETIME NOT NULL,
                    PRIMARY KEY (ip, endpoint)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );

            $stmt = $db->prepare('SELECT hits, window_start FROM rate_limits WHERE ip = :ip AND endpoint = :endpoint LIMIT 1');
            $stmt->execute([':ip' => $ip, ':endpoint' => $endpoint]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $now = new DateTimeImmutable('now');

            if (!$row) {
                $insert = $db->prepare('INSERT INTO rate_limits (ip, endpoint, hits, window_start) VALUES (:ip, :endpoint, 1, NOW())');
                $insert->execute([':ip' => $ip, ':endpoint' => $endpoint]);
                return;
            }

            $windowStart = new DateTimeImmutable((string) $row['window_start']);
            $elapsed = $now->getTimestamp() - $windowStart->getTimestamp();

            if ($elapsed >= $windowSeconds) {
                $update = $db->prepare('UPDATE rate_limits SET hits = 1, window_start = NOW() WHERE ip = :ip AND endpoint = :endpoint');
                $update->execute([':ip' => $ip, ':endpoint' => $endpoint]);
                return;
            }

            $hits = (int) $row['hits'] + 1;
            if ($hits > $maxHits) {
                http_response_code(429);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Trop de requêtes. Réessayez dans 60 secondes.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $update = $db->prepare('UPDATE rate_limits SET hits = :hits WHERE ip = :ip AND endpoint = :endpoint');
            $update->execute([':hits' => $hits, ':ip' => $ip, ':endpoint' => $endpoint]);
        } catch (Throwable $e) {
            error_log('[RateLimiter] ' . $e->getMessage());
        }
    }
}