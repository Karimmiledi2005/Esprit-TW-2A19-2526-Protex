<?php
/**
 * model/JeuSnake.php — Gestion des scores du jeu Snake
 */

class JeuSnake
{
    public static function save(PDO $db, int $userId, int $score, string $vitesse, int $dureeSec, int $serpentsManges): int
    {
        $stmt = $db->prepare("
            INSERT INTO jeu_snake (id_user, score, vitesse, duree_sec, serpents_manges)
            VALUES (:id_user, :score, :vitesse, :duree, :serpents)
        ");
        $stmt->execute([
            ':id_user'   => $userId,
            ':score'     => $score,
            ':vitesse'   => $vitesse,
            ':duree'     => $dureeSec,
            ':serpents'  => $serpentsManges,
        ]);
        return (int)$db->lastInsertId();
    }

    public static function getBestScore(PDO $db, int $userId): array
    {
        $stmt = $db->prepare("
            SELECT MAX(score) as best_score, vitesse
            FROM jeu_snake
            WHERE id_user = ?
            GROUP BY vitesse
            ORDER BY best_score DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getGlobalBest(PDO $db, ?string $vitesse = null): ?array
    {
        $sql = "
            SELECT j.*, u.nom, u.prenom
            FROM jeu_snake j
            LEFT JOIN user u ON u.id_user = j.id_user
        ";
        $params = [];
        if ($vitesse) {
            $sql .= " WHERE j.vitesse = ?";
            $params[] = $vitesse;
        }
        $sql .= " ORDER BY j.score DESC LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function getHistory(PDO $db, int $userId, int $limit = 20): array
    {
        $stmt = $db->prepare("
            SELECT * FROM jeu_snake
            WHERE id_user = ?
            ORDER BY date_jeu DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getUserStats(PDO $db, int $userId): array
    {
        $stmt = $db->prepare("
            SELECT
                COUNT(*) as total_parties,
                COALESCE(MAX(score), 0) as meilleur_score,
                COALESCE(AVG(score), 0) as score_moyen,
                COALESCE(SUM(duree_sec), 0) as temps_total_sec,
                COALESCE(SUM(serpents_manges), 0) as total_manges
            FROM jeu_snake
            WHERE id_user = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getLeaderboard(PDO $db, ?string $vitesse = null, int $limit = 10): array
    {
        $sql = "
            SELECT j.score, j.vitesse, j.date_jeu,
                   u.nom, u.prenom
            FROM jeu_snake j
            LEFT JOIN user u ON u.id_user = j.id_user
        ";
        $params = [];
        if ($vitesse) {
            $sql .= " WHERE j.vitesse = ?";
            $params[] = $vitesse;
        }
        $sql .= " ORDER BY j.score DESC, j.date_jeu ASC LIMIT ?";
        $params[] = $limit;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
