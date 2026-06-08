<?php

require_once __DIR__ . '/../connexion.php';
require_once __DIR__ . '/../helpers/SessionGuard.php';
require_once __DIR__ . '/../helpers/RoleHelper.php';

class PartenaireController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = config::getConnexion();
    }

    public function getAll(string $type = '', string $ville = '', string $search = '', bool $actifOnly = true): array
    {
        $where  = ['1=1'];
        $params = [];

        if ($actifOnly) { $where[] = 'p.actif = 1'; }
        if ($type)      { $where[] = 'p.type = :type';   $params[':type']   = $type; }
        if ($ville)     { $where[] = 'p.ville = :ville'; $params[':ville']  = $ville; }
        if ($search) {
            $where[] = '(p.nom LIKE :search OR p.description LIKE :search OR p.avantage LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $sql = "SELECT p.*,
                       COUNT(DISTINCT pa.id) AS nb_avis_count,
                       COALESCE(AVG(pa.note), 0) AS note_calculee
                FROM partenaire p
                LEFT JOIN partenaire_avis pa ON pa.id_partenaire = p.id_partenaire
                  AND pa.signale = 0
                WHERE " . implode(' AND ', $where) . "
                GROUP BY p.id_partenaire
                ORDER BY p.ordre ASC, note_calculee DESC, p.nom ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByTypeContrat(string $typeContrat, string $ville = ''): array
    {
        $params = [':type' => $typeContrat];
        $villeClause = '';
        if ($ville) { $villeClause = 'AND p.ville = :ville'; $params[':ville'] = $ville; }

        $stmt = $this->db->prepare("
            SELECT p.*, COALESCE(AVG(pa.note), 0) AS note_calculee
            FROM partenaire p
            JOIN partenaire_type_contrat ptc ON ptc.id_partenaire = p.id_partenaire
              AND ptc.type_contrat = :type
            LEFT JOIN partenaire_avis pa ON pa.id_partenaire = p.id_partenaire
            WHERE p.actif = 1 {$villeClause}
            GROUP BY p.id_partenaire
            ORDER BY note_calculee DESC
            LIMIT 5
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT p.*,
                   COUNT(DISTINCT pa.id) AS nb_avis_count,
                   COALESCE(AVG(pa.note), 0) AS note_calculee
            FROM partenaire p
            LEFT JOIN partenaire_avis pa ON pa.id_partenaire = p.id_partenaire AND pa.signale = 0
            WHERE p.id_partenaire = :id
            GROUP BY p.id_partenaire
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getAvis(int $idPartenaire): array
    {
        $stmt = $this->db->prepare("
            SELECT pa.*, u.nom, u.prenom
            FROM partenaire_avis pa
            JOIN user u ON u.id_user = pa.id_user
            WHERE pa.id_partenaire = :id AND pa.signale = 0
            ORDER BY pa.created_at DESC
            LIMIT 20
        ");
        $stmt->execute([':id' => $idPartenaire]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addAvis(int $idPartenaire, int $idUser, int $note, string $commentaire): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO partenaire_avis (id_partenaire, id_user, note, commentaire)
                VALUES (:p, :u, :n, :c)
                ON DUPLICATE KEY UPDATE note = :n2, commentaire = :c2
            ");
            $stmt->execute([':p'=>$idPartenaire,':u'=>$idUser,':n'=>$note,':c'=>$commentaire,':n2'=>$note,':c2'=>$commentaire]);
            $this->updateNoteMoyenne($idPartenaire);
            return true;
        } catch (PDOException) { return false; }
    }

    private function updateNoteMoyenne(int $id): void
    {
        $this->db->prepare("
            UPDATE partenaire p SET
                note_moyenne = (SELECT COALESCE(AVG(note),0) FROM partenaire_avis WHERE id_partenaire = :id AND signale=0),
                nb_avis      = (SELECT COUNT(*) FROM partenaire_avis WHERE id_partenaire = :id AND signale=0)
            WHERE p.id_partenaire = :id
        ")->execute([':id' => $id]);
    }

    public function logUtilisation(int $idPartenaire, int $idUser, ?int $idSinistre = null, string $contexte = ''): void
    {
        $this->db->prepare("
            INSERT INTO partenaire_utilisation (id_partenaire, id_user, id_sinistre, contexte)
            VALUES (?, ?, ?, ?)
        ")->execute([$idPartenaire, $idUser, $idSinistre, $contexte]);
    }

    public function getVilles(): array
    {
        return $this->db->query("SELECT DISTINCT ville FROM partenaire WHERE actif=1 ORDER BY ville ASC")
                         ->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getStats(): array
    {
        return $this->db->query("
            SELECT
                COUNT(*)                       AS total,
                SUM(actif=1)                   AS actifs,
                SUM(type='garage')             AS garages,
                SUM(type='clinique')           AS cliniques,
                SUM(type='pharmacie')          AS pharmacies,
                COALESCE(AVG(note_moyenne),0)  AS note_globale
            FROM partenaire
        ")->fetch(PDO::FETCH_ASSOC);
    }

    public function getTopPartenaires(int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT p.nom, p.type, p.ville,
                   COUNT(pu.id) AS utilisations,
                   p.note_moyenne
            FROM partenaire p
            LEFT JOIN partenaire_utilisation pu ON pu.id_partenaire = p.id_partenaire
            WHERE p.actif = 1
            GROUP BY p.id_partenaire
            ORDER BY utilisations DESC, p.note_moyenne DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int|false
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO partenaire
                  (nom,type,description,logo_url,adresse,ville,gouvernorat,
                   telephone,email,site_web,latitude,longitude,
                   avantage,avantage_detail,horaires,actif,ordre)
                VALUES
                  (:nom,:type,:desc,:logo,:adr,:ville,:gouv,
                   :tel,:email,:web,:lat,:lng,
                   :avantage,:avdetail,:horaires,:actif,:ordre)
            ");
            $stmt->execute([
                ':nom'      => $data['nom'],
                ':type'     => $data['type'],
                ':desc'     => $data['description']     ?? '',
                ':logo'     => $data['logo_url']        ?? null,
                ':adr'      => $data['adresse']         ?? '',
                ':ville'    => $data['ville']           ?? '',
                ':gouv'     => $data['gouvernorat']     ?? '',
                ':tel'      => $data['telephone']       ?? '',
                ':email'    => $data['email']           ?? null,
                ':web'      => $data['site_web']        ?? null,
                ':lat'      => $data['latitude']        ?? null,
                ':lng'      => $data['longitude']       ?? null,
                ':avantage' => $data['avantage']        ?? '',
                ':avdetail' => $data['avantage_detail'] ?? '',
                ':horaires' => $data['horaires']        ?? 'Lun-Ven 9h-17h',
                ':actif'    => (int)($data['actif']     ?? 1),
                ':ordre'    => (int)($data['ordre']     ?? 0),
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException) { return false; }
    }

    public function update(int $id, array $data): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE partenaire SET
                  nom=:nom, type=:type, description=:desc,
                  adresse=:adr, ville=:ville, gouvernorat=:gouv,
                  telephone=:tel, email=:email, site_web=:web,
                  latitude=:lat, longitude=:lng,
                  avantage=:avantage, avantage_detail=:avdetail,
                  horaires=:horaires, actif=:actif, ordre=:ordre
                WHERE id_partenaire=:id
            ");
            $stmt->execute([
                ':nom'      => $data['nom'],
                ':type'     => $data['type'],
                ':desc'     => $data['description']     ?? '',
                ':adr'      => $data['adresse']         ?? '',
                ':ville'    => $data['ville']           ?? '',
                ':gouv'     => $data['gouvernorat']     ?? '',
                ':tel'      => $data['telephone']       ?? '',
                ':email'    => $data['email']           ?? null,
                ':web'      => $data['site_web']        ?? null,
                ':lat'      => $data['latitude']        ?? null,
                ':lng'      => $data['longitude']       ?? null,
                ':avantage' => $data['avantage']        ?? '',
                ':avdetail' => $data['avantage_detail'] ?? '',
                ':horaires' => $data['horaires']        ?? 'Lun-Ven 9h-17h',
                ':actif'    => (int)($data['actif']     ?? 1),
                ':ordre'    => (int)($data['ordre']     ?? 0),
                ':id'       => $id,
            ]);
            return true;
        } catch (PDOException) { return false; }
    }

    public function delete(int $id): bool
    {
        return (bool)$this->db->prepare("DELETE FROM partenaire WHERE id_partenaire=?")->execute([$id]);
    }

    public function toggleActif(int $id): bool
    {
        return (bool)$this->db->prepare("UPDATE partenaire SET actif = NOT actif WHERE id_partenaire=?")->execute([$id]);
    }
}
