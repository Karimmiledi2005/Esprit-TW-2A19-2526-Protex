<?php

require_once __DIR__ . '/../connexion.php';
require_once __DIR__ . '/../helpers/SessionGuard.php';

class ParrainageController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = config::getConnexion();
    }

    public static function generateCode(string $nom): string
    {
        $prefix = strtoupper(preg_replace('/[^a-zA-Z]/', '', mb_substr($nom, 0, 3)));
        $prefix = str_pad($prefix, 3, 'X');
        $num    = str_pad((string)random_int(1000, 9999), 4, '0');
        return $prefix . '-' . $num;
    }

    public function getOrCreateCode(int $idUser): string
    {
        $stmt = $this->db->prepare("SELECT code_parrain, nom FROM user WHERE id_user = ?");
        $stmt->execute([$idUser]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return '';

        if (!empty($row['code_parrain'])) return $row['code_parrain'];

        do {
            $code  = self::generateCode($row['nom'] ?? 'USR');
            $check = $this->db->prepare("SELECT COUNT(*) FROM user WHERE code_parrain = ?");
            $check->execute([$code]);
        } while ($check->fetchColumn() > 0);

        $this->db->prepare("UPDATE user SET code_parrain = ? WHERE id_user = ?")->execute([$code, $idUser]);
        return $code;
    }

    public function validateAndApply(int $idFilleul, string $code): bool
    {
        $code = strtoupper(trim($code));
        if (!$code) return false;

        $stmt = $this->db->prepare("SELECT id_user, nom, email FROM user WHERE code_parrain = ? AND id_user != ?");
        $stmt->execute([$code, $idFilleul]);
        $parrain = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$parrain) return false;

        $existing = $this->db->prepare("SELECT COUNT(*) FROM parrainage WHERE id_filleul = ?");
        $existing->execute([$idFilleul]);
        if ($existing->fetchColumn() > 0) return false;

        $stmt = $this->db->prepare("
            INSERT INTO parrainage (id_parrain, id_filleul, code_utilise, statut)
            VALUES (?, ?, ?, 'en_attente')
        ");
        $stmt->execute([$parrain['id_user'], $idFilleul, $code]);

        $this->db->prepare("UPDATE user SET id_parrain_ref = ? WHERE id_user = ?")
                  ->execute([$parrain['id_user'], $idFilleul]);

        $filleulRow = $this->db->prepare("SELECT nom, prenom, email FROM user WHERE id_user = ?");
        $filleulRow->execute([$idFilleul]);
        $filleul = $filleulRow->fetch(PDO::FETCH_ASSOC);

        if ($filleul) {
            $this->envoyerEmail(
                $parrain['email'],
                'Votre filleul a rejoint Protex !',
                "Bonjour {$parrain['nom']},\n\n"
                . "{$filleul['prenom']} {$filleul['nom']} vient de s'inscrire sur Protex grâce à votre code de parrainage !\n\n"
                . "Vous recevrez 150 points fidélité dès qu'il aura souscrit son premier contrat.\n\n"
                . "Merci de faire confiance à Protex !\n\nL'équipe Protex"
            );
        }

        return true;
    }

    public function recompenser(int $idFilleul): bool
    {
        $stmt = $this->db->prepare("SELECT * FROM parrainage WHERE id_filleul = ? AND statut = 'en_attente'");
        $stmt->execute([$idFilleul]);
        $parrainage = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$parrainage) return false;

        $this->db->prepare("UPDATE parrainage SET statut='recompense', recompense_at=NOW() WHERE id=?")
                  ->execute([$parrainage['id']]);

        $this->db->prepare("
            INSERT INTO points_fidelite (id_user, points, motif, created_at)
            VALUES (?, ?, 'Parrainage validé', NOW())
            ON DUPLICATE KEY UPDATE points = points + VALUES(points)
        ")->execute([$parrainage['id_parrain'], $parrainage['pts_parrain']]);

        $this->db->prepare("
            INSERT INTO points_fidelite (id_user, points, motif, created_at)
            VALUES (?, ?, 'Bienvenue — parrainage', NOW())
            ON DUPLICATE KEY UPDATE points = points + VALUES(points)
        ")->execute([$idFilleul, $parrainage['pts_filleul']]);

        return true;
    }

    public function getStatsByParrain(int $idParrain): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*)                          AS total_parrainages,
                SUM(statut = 'recompense')        AS convertis,
                SUM(statut = 'en_attente')        AS en_attente,
                SUM(pts_parrain)                  AS pts_gagnes,
                MAX(created_at)                   AS dernier_parrainage
            FROM parrainage WHERE id_parrain = ?
        ");
        $stmt->execute([$idParrain]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function getFilleuls(int $idParrain): array
    {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nom, u.prenom, u.email, u.created_at AS inscription_filleul
            FROM parrainage p
            JOIN user u ON u.id_user = p.id_filleul
            WHERE p.id_parrain = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$idParrain]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGlobalStats(): array
    {
        return $this->db->query("
            SELECT
                COUNT(*)                        AS total,
                SUM(statut='recompense')        AS convertis,
                SUM(statut='en_attente')        AS en_attente,
                ROUND(SUM(statut='recompense')/NULLIF(COUNT(*),0)*100,1) AS taux_conversion,
                SUM(pts_parrain)                AS pts_distribues_parrains,
                SUM(pts_filleul)                AS pts_distribues_filleuls
            FROM parrainage
        ")->fetch(PDO::FETCH_ASSOC);
    }

    public function getTopParrains(int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT u.nom, u.prenom, u.email,
                   COUNT(p.id)                          AS total_filleuls,
                   SUM(p.statut='recompense')           AS nb_convertis,
                   SUM(p.pts_parrain)                   AS total_points,
                   (SELECT COUNT(*) FROM user u2 WHERE u2.code_parrain IS NOT NULL AND u2.id_parrain_ref = u.id_user) AS codes_actifs
            FROM parrainage p
            JOIN user u ON u.id_user = p.id_parrain
            GROUP BY p.id_parrain
            ORDER BY total_filleuls DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDerniersParrainages(int $limit = 10, string $statut = '', string $search = '', string $dateDebut = '', string $dateFin = ''): array
    {
        $where  = ['1=1'];
        $params = [];
        if ($statut)       { $where[] = 'p.statut = :statut';  $params[':statut']  = $statut; }
        if ($dateDebut)    { $where[] = 'p.created_at >= :dd'; $params[':dd']      = $dateDebut; }
        if ($dateFin)      { $where[] = 'p.created_at <= :df'; $params[':df']      = $dateFin . ' 23:59:59'; }
        if ($search) {
            $where[] = '(parrain.nom LIKE :s OR parrain.email LIKE :s OR filleul.nom LIKE :s OR filleul.email LIKE :s)';
            $params[':s'] = '%' . $search . '%';
        }
        $sql = "
            SELECT p.*,
                   parrain.nom AS parrain_nom, parrain.prenom AS parrain_prenom,
                   filleul.nom AS filleul_nom, filleul.prenom AS filleul_prenom
            FROM parrainage p
            JOIN user parrain ON parrain.id_user = p.id_parrain
            JOIN user filleul ON filleul.id_user = p.id_filleul
            WHERE " . implode(' AND ', $where) . "
            ORDER BY p.created_at DESC
            LIMIT :lim
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getConfig(): array
    {
        $rows = $this->db->query("SELECT `key`, `value` FROM parrainage_config")->fetchAll(PDO::FETCH_KEY_PAIR);
        return $rows ?: [
            'points_parrain' => 150, 'points_filleul' => 50, 'points_bonus' => 100,
            'points_per_dt' => 200, 'validite_jours' => 30, 'min_contrats' => 1,
        ];
    }

    public function saveConfig(array $config): bool
    {
        $stmt = $this->db->prepare("INSERT INTO parrainage_config (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        foreach ($config as $k => $v) {
            $stmt->execute([$k, (string)$v]);
        }
        return true;
    }

    public function valider(int $id): bool
    {
        $this->db->prepare("UPDATE parrainage SET statut='valide', updated_at=NOW() WHERE id=?")->execute([$id]);
        return true;
    }

    public function rejeter(int $id): bool
    {
        $this->db->prepare("UPDATE parrainage SET statut='rejete', updated_at=NOW() WHERE id=?")->execute([$id]);
        return true;
    }

    public function getDetail(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT p.*,
                   parrain.id_user AS parrain_id, parrain.nom AS parrain_nom, parrain.prenom AS parrain_prenom, parrain.email AS parrain_email,
                   filleul.id_user AS filleul_id, filleul.nom AS filleul_nom, filleul.prenom AS filleul_prenom, filleul.email AS filleul_email
            FROM parrainage p
            JOIN user parrain ON parrain.id_user = p.id_parrain
            JOIN user filleul ON filleul.id_user = p.id_filleul
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function ajusterPoints(int $userId, int $points, string $raison = ''): void
    {
        $this->db->prepare("
            INSERT INTO points_fidelite (id_user, points, motif, created_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE points = points + VALUES(points), motif = VALUES(motif), created_at = NOW()
        ")->execute([$userId, $points, $raison ?: 'Ajustement manuel']);
    }

    public function getPointsFidelite(int $userId): int
    {
        $row = $this->db->prepare("SELECT points FROM points_fidelite WHERE id_user = ?");
        $row->execute([$userId]);
        return (int)($row->fetchColumn() ?: 0);
    }

    public function getParrainagesParMois(): array
    {
        return $this->db->query("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') AS mois,
                COUNT(*) AS total,
                SUM(statut='recompense') AS convertis
            FROM parrainage
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY mois
            ORDER BY mois ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    private function envoyerEmail(string $to, string $subject, string $body): void
    {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "From: Protex Assurance <noreply@protex.tn>\r\n";
        $headers .= "X-Mailer: PHP/" . PHP_VERSION;

        error_log("[Parrainage] Email à {$to}: {$subject}");
        @mail($to, $subject, $body, $headers);
    }
}
