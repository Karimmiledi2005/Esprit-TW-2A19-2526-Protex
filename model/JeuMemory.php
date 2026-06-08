<?php
/**
 * model/JeuMemory.php — Gestion des scores du jeu Memory
 */

class JeuMemory
{
    public static function save(PDO $db, int $userId, int $temps, int $coups, string $difficulte, int $paires): int
    {
        $stmt = $db->prepare("
            INSERT INTO jeu_memory (id_user, temps, coups, difficulte, nb_paires)
            VALUES (:id_user, :temps, :coups, :diff, :paires)
        ");
        $stmt->execute([
            ':id_user'   => $userId,
            ':temps'     => $temps,
            ':coups'     => $coups,
            ':diff'      => $difficulte,
            ':paires'    => $paires,
        ]);
        return (int)$db->lastInsertId();
    }

    public static function getBestScore(PDO $db, int $userId): array
    {
        $stmt = $db->prepare("
            SELECT MIN(temps) as best_time, difficulte
            FROM jeu_memory
            WHERE id_user = ?
            GROUP BY difficulte
            ORDER BY best_time ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getUserStats(PDO $db, int $userId): array
    {
        $stmt = $db->prepare("
            SELECT
                COUNT(*) as total_parties,
                COALESCE(MIN(temps), 0) as meilleur_temps,
                COALESCE(AVG(coups), 0) as coups_moyen
            FROM jeu_memory
            WHERE id_user = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getLeaderboard(PDO $db, ?string $difficulte = null, int $limit = 10): array
    {
        $sql = "
            SELECT j.temps, j.coups, j.difficulte, j.date_jeu,
                   u.nom, u.prenom
            FROM jeu_memory j
            LEFT JOIN user u ON u.id_user = j.id_user
        ";
        $params = [];
        if ($difficulte) {
            $sql .= " WHERE j.difficulte = ?";
            $params[] = $difficulte;
        }
        $sql .= " ORDER BY j.temps ASC, j.coups ASC LIMIT ?";
        $params[] = $limit;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
