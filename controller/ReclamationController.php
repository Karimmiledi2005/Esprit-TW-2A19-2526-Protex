<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers/RoleHelper.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/ReclamationModel.php';

class ReclamationController
{
    private $db;

    public function __construct()
    {
        if (!RoleHelper::getUserId()) {
            $loginUrl = (defined('BASE_URL') ? BASE_URL : '/assurance') . '/view/FrontOffice/login.php';
            header('Location: ' . $loginUrl);
            exit;
        }
        $this->db = config::getConnexion();
    }

    public function addReclamation(Reclamation $r, int $userId = 0)
    {
        $sql = "INSERT INTO reclamation
                (objet, type, ref_contrat, priorite, statut, date_depot, recRef, description, email, id_user, object_type, object_ref)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $r->getObjet(),
            $r->getType(),
            $r->getRefContrat(),
            $r->getPriorite(),
            $r->getStatut(),
            $r->getDateDepot()->format('Y-m-d H:i:s'),
            $r->getRecRef(),
            $r->getDescription(),
            $r->getEmail(),
            $userId > 0 ? $userId : null,
            $r->getObjectType(),
            $r->getObjectRef() !== '' ? $r->getObjectRef() : null,
        ]);
    }

    public function listReclamations(int $page = 1, int $perPage = 20): array
    {
        $role   = RoleHelper::getRole();
        $agence = RoleHelper::getAgenceId();
        $userId = RoleHelper::getUserId();

        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT r.id, r.objet, r.type, r.ref_contrat, r.priorite, r.statut,
                       r.date_depot, r.recRef AS rec_ref, r.description, r.email, r.id_user,
                       r.object_type, r.object_ref
                FROM reclamation r";
        
        $where = [];
        $params = [];

        if ($role === 'admin' && $agence) {
            $sql .= " LEFT JOIN client c ON r.id_user = c.id_user";
            $where[] = "c.id_agence = :agence";
            $params[':agence'] = $agence;
        } elseif ($role === 'agent' && $agence) {
            // Un agent voit les réclamations des clients de son agence
            $sql .= " LEFT JOIN client c ON r.id_user = c.id_user";
            $where[] = "c.id_agence = :agence";
            $params[':agence'] = $agence;
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY r.date_depot DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recherche des réclamations par objet (recherche partielle, insensible à la casse)
     * Résultats triés alphabétiquement par objet
     */
    public function searchByObjet(string $objet): array
    {
        $role   = RoleHelper::getRole();
        $agence = RoleHelper::getAgenceId();

        $sql = "SELECT r.id, r.objet, r.type, r.ref_contrat, r.priorite, r.statut,
                       r.date_depot, r.recRef AS rec_ref, r.description, r.email
                FROM reclamation r";
        
        $where = ["r.objet LIKE :objet"];
        $params = [':objet' => '%' . $objet . '%'];

        if ($role !== 'superadmin' && $agence) {
            $sql .= " LEFT JOIN client c ON r.id_user = c.id_user";
            $where[] = "c.id_agence = :agence";
            $params[':agence'] = $agence;
        }

        $sql .= " WHERE " . implode(" AND ", $where);
        $sql .= " ORDER BY r.objet ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats(): array
    {
        $role   = RoleHelper::getRole();
        $agence = RoleHelper::getAgenceId();

        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(r.statut = 'open')     AS open_count,
                    SUM(r.statut = 'closed')   AS closed_count,
                    SUM(r.statut = 'rejected') AS rejected_count
                FROM reclamation r";

        $params = [];
        if ($role !== 'superadmin' && $agence) {
            $sql .= " LEFT JOIN client c ON r.id_user = c.id_user WHERE c.id_agence = :agence";
            $params[':agence'] = $agence;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function countReclamations(): int
    {
        $role   = RoleHelper::getRole();
        $agence = RoleHelper::getAgenceId();

        $sql = "SELECT COUNT(*) FROM reclamation r";
        $params = [];

        if ($role !== 'superadmin' && $agence) {
            $sql .= " LEFT JOIN client c ON r.id_user = c.id_user WHERE c.id_agence = :agence";
            $params[':agence'] = $agence;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function deleteReclamation($id)
    {
        $role   = RoleHelper::getRole();
        $agence = RoleHelper::getAgenceId();

        if ($role === 'superadmin') {
            $stmt = $this->db->prepare("DELETE FROM reclamation WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            // Sécurité : on ne peut supprimer que si le client appartient à l'agence
            $sql = "DELETE r FROM reclamation r 
                    JOIN client c ON r.id_user = c.id_user 
                    WHERE r.id = :id AND c.id_agence = :agence";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id, ':agence' => $agence]);
        }
    }

    public function updateReclamation(Reclamation $r, $id)
    {
        $role   = RoleHelper::getRole();
        $agence = RoleHelper::getAgenceId();

        $stmtU = $this->db->prepare("SELECT id_user, statut FROM reclamation WHERE id = ?");
        $stmtU->execute([$id]);
        $row = $stmtU->fetch(PDO::FETCH_ASSOC);
        $uid = (int)($row['id_user'] ?? 0);
        $oldStatut = $row['statut'] ?? '';

        // Valider la transition de statut
        $newStatut = $r->getStatut();
        if ($newStatut !== $oldStatut) {
            $forbidden = [
                'closed'   => ['open'],
                'rejected' => ['open', 'closed'],
            ];
            if (isset($forbidden[$oldStatut]) && in_array($newStatut, $forbidden[$oldStatut], true)) {
                throw new \Exception("Transition de statut interdite : $oldStatut -> $newStatut");
            }
            if ($role === 'client' && !in_array($newStatut, ['open', $oldStatut], true)) {
                throw new \Exception("Un client ne peut pas modifier le statut de sa réclamation.");
            }
        }

        $sql = "UPDATE reclamation r";
        if ($role !== 'superadmin' && $agence) {
            $sql .= " JOIN client c ON r.id_user = c.id_user";
        }
        
        $sql .= " SET r.objet = :obj, r.type = :type, r.ref_contrat = :refc, r.priorite = :prio,
                      r.statut = :stat, r.date_depot = :date, r.recRef = :recref, r.description = :desc, r.email = :email,
                      r.object_type = :object_type, r.object_ref = :object_ref
                  WHERE r.id = :id";
        
        if ($role !== 'superadmin' && $agence) {
            $sql .= " AND c.id_agence = :agence";
        }

        $params = [
            ':obj'         => $r->getObjet(),
            ':type'        => $r->getType(),
            ':refc'        => $r->getRefContrat(),
            ':prio'        => $r->getPriorite(),
            ':stat'        => $r->getStatut(),
            ':date'        => $r->getDateDepot()->format('Y-m-d H:i:s'),
            ':recref'      => $r->getRecRef(),
            ':desc'        => $r->getDescription(),
            ':email'       => $r->getEmail(),
            ':object_type' => $r->getObjectType(),
            ':object_ref'  => $r->getObjectRef() !== '' ? $r->getObjectRef() : null,
            ':id'          => $id
        ];
        
        if ($role !== 'superadmin' && $agence) {
            $params[':agence'] = $agence;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        if ($uid) {
            $this->db->prepare("INSERT INTO notification (id_user, message, type, lien) VALUES (?, ?, 'reclamation', ?)")
                ->execute([$uid, "Votre réclamation #$id a été mise à jour.", '/view/FrontOffice/reclamationList.php']);
        }
    }

    public function showReclamation($id)
    {
        $role   = RoleHelper::getRole();
        $agence = RoleHelper::getAgenceId();
        $userId = RoleHelper::getUserId();

        $sql = "SELECT r.* FROM reclamation r";
        $params = [':id' => $id];

        if ($role === 'superadmin') {
            $sql .= " WHERE r.id = :id";
        } elseif ($role === 'client') {
            $sql .= " WHERE r.id = :id AND r.id_user = :userId";
            $params[':userId'] = $userId;
        } elseif ($agence) {
            $sql .= " JOIN client c ON r.id_user = c.id_user WHERE r.id = :id AND c.id_agence = :agence";
            $params[':agence'] = $agence;
        } else {
            $sql .= " WHERE r.id = :id";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Détecte automatiquement la priorité en fonction des mots-clés dans la description.
     */
    public static function detecterPriorite(string $description): string
    {
        $motsCles = ['urgent', 'urgence', 'immédiat', 'critique', 'grave',
                     'accident', 'décès', 'hospitalisation', 'incendie', 'vol', 
                     'plainte', 'tribunal', 'avocat', 'juridique', 'poursuite'];
        $desc = mb_strtolower($description, 'UTF-8');
        foreach ($motsCles as $mot) {
            if (strpos($desc, $mot) !== false) return 'Urgente';
        }
        return 'Normale';
    }
}
?>

