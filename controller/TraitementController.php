<?php
require_once __DIR__ . '/../connexion.php';
require_once __DIR__ . '/../model/Traitement.php';

if (file_exists(__DIR__ . '/../controller/EmailService.php')) {
    require_once __DIR__ . '/../controller/EmailService.php';
}
if (file_exists(__DIR__ . '/../service/EmailService.php')) {
    require_once __DIR__ . '/../service/EmailService.php';
} elseif (file_exists(__DIR__ . '/../model/EmailService.php')) {
    require_once __DIR__ . '/../model/EmailService.php';
}

class TraitementController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = config::getConnexion();
    }

    public function checkSinistre(int $id): ?array
    {
        if (!$id) return null;
        $stmt = $this->db->prepare("
            SELECT s.id_sinistre, s.type, s.statut,
                   CONCAT(u.prenom,' ',u.nom) AS client_nom
            FROM sinistre s
            LEFT JOIN user u ON s.id_user = u.id_user
            WHERE s.id_sinistre = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("
            SELECT t.id_traitement, t.decision, t.montant_indemnise, t.statut, t.date_traitement, t.message_agent,
                   t.id_sinistre, t.id_user,
                   COALESCE(t.nom_agent, CONCAT(u.prenom,' ',u.nom), CONCAT('Agent #', t.id_user)) AS agent_nom,
                   s.type AS sinistre_type
            FROM traitement t
            LEFT JOIN user u ON t.id_user = u.id_user
            LEFT JOIN sinistre s ON t.id_sinistre = s.id_sinistre
            ORDER BY t.date_traitement DESC
        ");
        $traitements = [];
        foreach ($stmt->fetchAll() as $row) {
            $traitement = new Traitement($row['id_sinistre'], $row['id_user'], $row['agent_nom'], $row['decision']);
            $traitement->setIdTraitement($row['id_traitement']);
            $traitement->setMontantIndemnise($row['montant_indemnise']);
            $traitement->setStatut($row['statut']);
            $traitement->setDateTraitement($row['date_traitement']);
            $traitement->setMessageAgent($row['message_agent']);
            $traitements[] = $traitement;
        }
        return $traitements;
    }

    public function getAllByRole(): array {
        require_once __DIR__ . '/../helpers/RoleHelper.php';

        $role   = RoleHelper::getRole();
        $agence = RoleHelper::getAgenceId();
        $userId = RoleHelper::getUserId();

        $select = "
            SELECT t.id_traitement, t.decision, t.montant_indemnise, t.statut,
                   t.date_traitement, t.message_agent, t.id_sinistre, t.id_user,
                   t.est_valide, t.valide_par, t.date_validation,
                   COALESCE(t.nom_agent, CONCAT(u.prenom,' ',u.nom)) AS agent_nom,
                   s.type AS sinistre_type, s.id_agence
            FROM traitement t
            LEFT JOIN user u    ON t.id_user      = u.id_user
            LEFT JOIN sinistre s ON t.id_sinistre = s.id_sinistre
        ";

        if ($role === 'superadmin') {
            $stmt = $this->db->query($select . " ORDER BY t.date_traitement DESC");

        } elseif ($role === 'admin') {
            $stmt = $this->db->prepare($select . " WHERE s.id_agence = :agence ORDER BY t.date_traitement DESC");
            $stmt->execute([':agence' => $agence]);

        } else {
            // agent : ses propres traitements uniquement
            $stmt = $this->db->prepare($select . "
                WHERE t.id_user = :userId
                  AND s.id_agence = :agence
                ORDER BY t.date_traitement DESC
            ");
            $stmt->execute([':userId' => $userId, ':agence' => $agence]);
        }

        $traitements = [];
        foreach ($stmt->fetchAll() as $row) {
            $traitement = new Traitement($row['id_sinistre'], $row['id_user'], $row['agent_nom'], $row['decision']);
            $traitement->setIdTraitement($row['id_traitement']);
            $traitement->setMontantIndemnise($row['montant_indemnise']);
            $traitement->setStatut($row['statut']);
            $traitement->setDateTraitement($row['date_traitement']);
            $traitement->setMessageAgent($row['message_agent']);
            $traitements[] = $traitement;
        }
        return $traitements;
    }

    public function valider(int $idTraitement): array {
        require_once __DIR__ . '/../helpers/RoleHelper.php';
        RoleHelper::requireRole(['superadmin', 'admin']);

        $role   = RoleHelper::getRole();
        $agence = RoleHelper::getAgenceId();

        if ($role === 'superadmin') {
            $stmt = $this->db->prepare("UPDATE traitement SET est_valide = 1, valide_par = :userId, date_validation = NOW() WHERE id_traitement = :id");
            $stmt->execute([':userId' => RoleHelper::getUserId(), ':id' => $idTraitement]);
        } else {
            // Admin agence: validate only if it belongs to his agency (join with sinistre)
            $stmt = $this->db->prepare("
                UPDATE traitement t
                JOIN sinistre s ON t.id_sinistre = s.id_sinistre
                SET t.est_valide = 1, t.valide_par = :userId, t.date_validation = NOW()
                WHERE t.id_traitement = :id AND s.id_agence = :agence
            ");
            $stmt->execute([':userId' => RoleHelper::getUserId(), ':id' => $idTraitement, ':agence' => $agence]);
        }

        return $stmt->rowCount() > 0 
            ? ['success' => true, 'message' => 'Traitement validé avec succès.'] 
            : ['success' => false, 'message' => 'Validation échouée ou accès refusé.'];
    }

    public function getBySinistre(int $sinistreId): array
    {
        $stmt = $this->db->prepare("
            SELECT t.*, CONCAT(u.prenom,' ',u.nom) AS agent_nom
            FROM traitement t
            LEFT JOIN user u ON t.id_user = u.id_user
            WHERE t.id_sinistre = :id
            ORDER BY t.date_traitement ASC
        ");
        $stmt->execute([':id' => $sinistreId]);
        $traitements = [];
        foreach ($stmt->fetchAll() as $row) {
            $traitement = new Traitement($row['id_sinistre'], $row['id_user'], $row['agent_nom'], $row['decision']);
            $traitement->setIdTraitement($row['id_traitement']);
            $traitement->setMontantIndemnise($row['montant_indemnise']);
            $traitement->setStatut($row['statut']);
            $traitement->setDateTraitement($row['date_traitement']);
            $traitement->setMessageAgent($row['message_agent']);
            $traitements[] = $traitement;
        }
        return $traitements;
    }

    public function traitementExists(int $sinistreId): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM traitement WHERE id_sinistre = :id AND statut NOT IN ('refuse','annule')");
        $stmt->execute([':id' => $sinistreId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function create(array $data, int $userId): array
    {
        require_once __DIR__ . '/../helpers/RoleHelper.php';
        if (!RoleHelper::canCreateTraitement()) return ['success' => false, 'message' => 'Action non autorisée.'];

        $idSinistre = (int)($data['id_sinistre'] ?? 0);
        $nomAgent   = trim($data['nom_agent']   ?? '');
        $assignedId = (int)($data['assigned_agent_id'] ?? 0);
        $decision   = trim($data['decision']    ?? '');
        $montantRaw = isset($data['montant']) ? trim($data['montant']) : '';
        $montant    = ($montantRaw !== '' && is_numeric($montantRaw)) ? (float)$montantRaw : null;
        $statut     = $data['statut'] ?? 'en_cours';
        $message    = trim($data['message_agent'] ?? '');

        if (!$idSinistre) return ['success' => false, 'message' => 'ID sinistre requis.'];
        
        // Isolation agence pour Admin
        if (RoleHelper::isAdminAgence()) {
            $checkAg = $this->db->prepare("SELECT id_agence FROM sinistre WHERE id_sinistre = ?");
            $checkAg->execute([$idSinistre]);
            if ($checkAg->fetchColumn() != RoleHelper::getAgenceId()) {
                return ['success' => false, 'message' => 'Sinistre hors agence.'];
            }
            
            if (!$assignedId || $assignedId == $userId) {
                return ['success' => false, 'message' => 'Vous devez assigner ce traitement à un agent de votre agence. Vous ne pouvez pas le traiter vous-même.'];
            }
            if ($decision !== 'en_attente' || $statut !== 'en_cours') {
                return ['success' => false, 'message' => 'L\'administrateur peut uniquement assigner le traitement (Décision "En attente" et Statut "En cours").'];
            }
            $checkAgent = $this->db->prepare("SELECT id_agence FROM agent WHERE id_user = ?");
            $checkAgent->execute([$assignedId]);
            if ($checkAgent->fetchColumn() != RoleHelper::getAgenceId()) {
                return ['success' => false, 'message' => 'L\'agent assigné n\'appartient pas à votre agence.'];
            }
        }

        if (RoleHelper::isAgent()) {
            $checkSinistre = $this->db->prepare("SELECT id_agent_assigne FROM sinistre WHERE id_sinistre = ?");
            $checkSinistre->execute([$idSinistre]);
            if ($checkSinistre->fetchColumn() != $userId) {
                return ['success' => false, 'message' => 'Ce sinistre ne vous est pas assigné.'];
            }
        }

        $effectiveUserId = $assignedId ?: $userId;
        if (!$decision)   return ['success' => false, 'message' => 'Decision requise.'];
        if (!$nomAgent)   return ['success' => false, 'message' => 'Nom de l\'agent requis.'];
        if ($montantRaw === '') return ['success' => false, 'message' => 'Montant requis.'];
        if (!$statut)     return ['success' => false, 'message' => 'Statut requis.'];
        if ($this->traitementExists($idSinistre)) {
            return ['success' => false, 'message' => "Le sinistre #$idSinistre a deja un traitement enregistre.", 'code' => 409];
        }

        $stmt = $this->db->prepare("
            INSERT INTO traitement (id_sinistre, id_user, nom_agent, decision, montant_indemnise, statut, date_traitement, message_agent)
            VALUES (:id_sinistre, :id_user, :nom_agent, :decision, :montant, :statut, CURDATE(), :message_agent)
        ");
        $stmt->execute([
            ':id_sinistre' => $idSinistre,
            ':id_user'     => $effectiveUserId,
            ':nom_agent'   => $nomAgent ?: null,
            ':decision'    => $decision,
            ':montant'     => $montant,
            ':statut'      => $statut,
            ':message_agent'=> $message ?: null,
        ]);

        $id = (int)$this->db->lastInsertId();

        if (in_array($statut, ['accepte', 'refuse'])) {
            $newStat = $statut === 'accepte' ? 'rembourse' : 'refuse';
            $s = $this->db->prepare("UPDATE sinistre SET statut=:s WHERE id_sinistre=:id");
            $s->execute([':s' => $newStat, ':id' => $idSinistre]);

            // ── Auto-créer un paiement de remboursement si accepté ──
            if ($statut === 'accepte' && $montant > 0) {
                $sin = $this->db->prepare("SELECT id_contrat, id_user FROM sinistre WHERE id_sinistre = ?");
                $sin->execute([$idSinistre]);
                $srow = $sin->fetch(PDO::FETCH_ASSOC);
                if ($srow) {
                    $this->db->prepare("
                        INSERT INTO paiement (montant, statut, id_contrat, id_user, methode, date_paiement)
                        VALUES (?, 'valide', ?, ?, 'remboursement', NOW())
                    ")->execute([$montant, $srow['id_contrat'] ?: null, $srow['id_user'] ?: null]);
                    // Points fidélité pour remboursement
                    if (!empty($srow['id_user'])) {
                        $this->db->prepare("INSERT INTO points_fidelite (id_user, points, motif) VALUES (?, 20, 'Remboursement sinistre')")->execute([(int)$srow['id_user']]);
                    }
                }
            }

            try {
                $emailService = new EmailService($this->db);
                if ($statut === 'accepte') {
                    $emailService->sendSinistreRembourse($idSinistre, $montant);
                } else {
                    $emailService->sendSinistreRefuse($idSinistre, $message ?: null);
                }
            } catch (Exception $e) {
                error_log('[TraitementController] Email send error: ' . $e->getMessage());
            }
        }

        return ['success' => true, 'message' => 'Traitement enregistre.', 'id' => $id];
    }

    public function update(int $id, array $data): array
    {
        require_once __DIR__ . '/../helpers/RoleHelper.php';
        if (!$id) return ['success' => false, 'message' => 'ID manquant.'];

        // Vérification permission via RoleHelper
        $checkStmt = $this->db->prepare("SELECT id_user, est_valide, decision, montant_indemnise, statut FROM traitement WHERE id_traitement = ?");
        $checkStmt->execute([$id]);
        $t = $checkStmt->fetch();
        if (!$t) return ['success' => false, 'message' => 'Traitement introuvable.'];

        if (!RoleHelper::canModifyTraitement((int)$t['id_user'], (bool)$t['est_valide'])) {
            return ['success' => false, 'message' => 'Action non autorisée.'];
        }

        $montantRaw = isset($data['montant']) ? trim($data['montant']) : '';
        $montant    = ($montantRaw !== '' && is_numeric($montantRaw)) ? (float)$montantRaw : null;
        $nomAgent   = trim($data['nom_agent'] ?? '');
        $assignedId = (int)($data['assigned_agent_id'] ?? 0);
        $decision   = trim($data['decision']  ?? '');
        $message    = trim($data['message_agent'] ?? '');

        if (!$nomAgent) return ['success' => false, 'message' => 'Nom de l\'agent requis.'];
        if ($decision === '')   return ['success' => false, 'message' => 'Decision requise.'];
        if ($montantRaw === '') return ['success' => false, 'message' => 'Montant requis.'];
        if (($data['statut'] ?? '') === '') return ['success' => false, 'message' => 'Statut requis.'];

        if (RoleHelper::isAdminAgence()) {
            if ($assignedId && $assignedId == RoleHelper::getUserId()) {
                return ['success' => false, 'message' => 'Vous ne pouvez pas vous assigner ce traitement.'];
            }
            $oldDecision = $t['decision'];
            $oldMontant = $t['montant_indemnise'] !== null ? (float)$t['montant_indemnise'] : null;
            $oldStatut = $t['statut'];
            $newStatut = $data['statut'] ?? 'en_cours';
            
            if ($decision !== $oldDecision || $montant !== $oldMontant || $newStatut !== $oldStatut) {
                return ['success' => false, 'message' => 'L\'admin ne peut pas modifier la décision, le montant ou le statut. Il peut uniquement réassigner l\'agent.'];
            }
            
            if ($assignedId) {
                $checkAgent = $this->db->prepare("SELECT id_agence FROM agent WHERE id_user = ?");
                $checkAgent->execute([$assignedId]);
                if ($checkAgent->fetchColumn() != RoleHelper::getAgenceId()) {
                    return ['success' => false, 'message' => 'L\'agent assigné n\'appartient pas à votre agence.'];
                }
            }
        }

        $sql = "UPDATE traitement SET nom_agent=:nom_agent, decision=:decision, montant_indemnise=:montant, statut=:statut, message_agent=:message_agent";
        $params = [
            ':nom_agent'     => $nomAgent,
            ':decision'      => $decision,
            ':montant'       => $montant,
            ':statut'        => $data['statut'] ?? 'en_cours',
            ':message_agent' => $message ?: null,
            ':id'            => $id,
        ];

        if ($assignedId && (RoleHelper::isSuperAdmin() || RoleHelper::isAdminAgence())) {
            $sql .= ", id_user=:id_user";
            $params[':id_user'] = $assignedId;
        }

        $sql .= " WHERE id_traitement=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $newStatut = $data['statut'] ?? 'en_cours';
        if (in_array($newStatut, ['accepte', 'refuse'])) {
            $stmtSin = $this->db->prepare("SELECT id_sinistre FROM traitement WHERE id_traitement=:id");
            $stmtSin->execute([':id' => $id]);
            $idSinistre = (int)$stmtSin->fetchColumn();

            if ($idSinistre) {
                $newSinistreStat = $newStatut === 'accepte' ? 'rembourse' : 'refuse';
                $sStmt = $this->db->prepare("UPDATE sinistre SET statut=:s WHERE id_sinistre=:id");
                $sStmt->execute([':s' => $newSinistreStat, ':id' => $idSinistre]);

                try {
                    $emailService = new EmailService($this->db);
                    if ($newStatut === 'accepte') {
                        $emailService->sendSinistreRembourse($idSinistre, $montant);
                    } else {
                        $emailService->sendSinistreRefuse($idSinistre, $message ?: null);
                    }
                } catch (Exception $e) {
                    error_log('[TraitementController] Email update error: ' . $e->getMessage());
                }
            }
        }

        return ['success' => true, 'message' => 'Traitement mis a jour.'];
    }

    public function delete(int $id): array
    {
        require_once __DIR__ . '/../helpers/RoleHelper.php';
        if (!RoleHelper::canDeleteTraitement()) return ['success' => false, 'message' => 'Action non autorisée.'];

        if (!$id) return ['success' => false, 'message' => 'ID manquant.'];
        
        $role   = RoleHelper::getRole();
        $agence = RoleHelper::getAgenceId();

        // 1. Récupérer l'id_sinistre associé avant suppression (avec isolation agence pour Admin)
        if ($role === 'superadmin') {
            $stmtGet = $this->db->prepare("SELECT id_sinistre FROM traitement WHERE id_traitement=:id");
            $stmtGet->execute([':id' => $id]);
        } else {
            $stmtGet = $this->db->prepare("
                SELECT t.id_sinistre FROM traitement t
                JOIN sinistre s ON t.id_sinistre = s.id_sinistre
                WHERE t.id_traitement=:id AND s.id_agence = :agence
            ");
            $stmtGet->execute([':id' => $id, ':agence' => $agence]);
        }
        $idSinistre = $stmtGet->fetchColumn();
        if (!$idSinistre && $role !== 'superadmin') return ['success' => false, 'message' => 'Traitement introuvable ou accès refusé.'];

        // 2. Supprimer le traitement
        $stmt = $this->db->prepare("DELETE FROM traitement WHERE id_traitement=:id");
        $stmt->execute([':id' => $id]);

        return ['success' => true, 'message' => 'Traitement supprimé.'];
    }

    public function getStats(): array
    {
        require_once __DIR__ . '/../helpers/RoleHelper.php';
        $role   = RoleHelper::getRole();
        $agence = RoleHelper::getAgenceId();

        if ($role === 'superadmin') {
            $stmt = $this->db->query("
                SELECT COUNT(*) AS total,
                       COUNT(DISTINCT id_sinistre) AS nb_sinistres,
                       SUM(statut='en_cours') AS en_cours,
                       SUM(statut='accepte') AS accepte,
                       SUM(statut='refuse') AS refuse
                FROM traitement
            ");
        } else {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) AS total,
                       COUNT(DISTINCT t.id_sinistre) AS nb_sinistres,
                       SUM(t.statut='en_cours') AS en_cours,
                       SUM(t.statut='accepte') AS accepte,
                       SUM(t.statut='refuse') AS refuse
                FROM traitement t
                JOIN sinistre s ON t.id_sinistre = s.id_sinistre
                WHERE s.id_agence = :agence
            ");
            $stmt->execute([':agence' => $agence]);
        }
        return $stmt->fetch();
    }
}
