<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/ReponseModel.php';

class ReponseController
{
    private $db;

    public function __construct()
    {
        $this->db = config::getConnexion();
    }

    public function getDb() { return $this->db; }

    // ── CREATE ───────────────────────────────────────────────────────────────
    public function addReponse(Reponse $r, int $agentId = 0): void
    {
        $reclamationId = (int)$r->getReclamationId();

        // Prevent duplicate replies for the same reclamation.
        $check = $this->db->prepare("SELECT id_re FROM reponse WHERE reclamation_id = ? LIMIT 1");
        $check->execute([$reclamationId]);
        if ($check->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception('Une réponse existe déjà pour cette réclamation.');
        }

        $stmt = $this->db->prepare(
            "INSERT INTO reponse (date_reponse, contenu, statut, reclamation_id, id_user)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $r->getDateReponse(),
            $r->getContenu(),
            $r->getStatut(),
            $reclamationId,
            $agentId > 0 ? $agentId : null
        ]);
        $reponseId = (int)$this->db->lastInsertId();

        // Mise à jour du statut dans la table reclamation
        $this->db->prepare(
            "UPDATE reclamation SET statut = 'closed' WHERE id = ?"
        )->execute([$reclamationId]);

        $stmtU = $this->db->prepare("SELECT id_user FROM reclamation WHERE id = ?");
        $stmtU->execute([$reclamationId]);
        $uid = (int)$stmtU->fetchColumn();
        if ($uid) {
            $this->db->prepare("INSERT INTO notification (id_user, message, type, lien) VALUES (?, ?, 'reclamation', ?)")
                ->execute([$uid, "Une réponse a été ajoutée à votre réclamation #$reclamationId.", '/view/FrontOffice/reclamationList.php']);
        }

        // Audit & Cache
        $this->audit('reponse_ajoutee', $reclamationId, $reponseId, substr($r->getContenu(), 0, 100));
        $this->invalidateStatsCache();
    }

    // ── READ ALL RECLAMATIONS (Filtrage par agence & Pagination) ─────────────
    public function listAllReclamations(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $role   = RoleHelper::getRole();
        $idAg   = RoleHelper::getAgenceId();
        $params = [];
        $where  = "";

        if ($role === 'admin' || $role === 'agent') {
            if ($idAg === null) throw new Exception('Session invalide : aucune agence associée.');
            $where = " WHERE c.id_agence = :idAg ";
            $params['idAg'] = $idAg;
        } elseif ($role === 'client') {
            $where = " WHERE r.id_user = :uid ";
            $params['uid'] = RoleHelper::getUserId();
        }

        $sql = "SELECT
                      r.id, r.objet, r.type, r.ref_contrat, r.recRef AS rec_ref,
                      r.object_type, r.object_ref,
                      r.description, r.date_depot,
                      r.statut, r.priorite, r.email, r.id_user,
                      u.nom AS client_nom, u.prenom AS client_prenom,
                      c.numero_client, ag.nom_agence,
                      rep.id_re        AS rep_id,
                      rep.contenu      AS reponse_contenu,
                      rep.date_reponse AS rep_date,
                      rep.statut       AS rep_statut,
                      ua.nom           AS agent_nom,
                      ua.prenom        AS agent_prenom
                  FROM reclamation r
                  LEFT JOIN user u ON r.id_user = u.id_user
                  LEFT JOIN client c ON r.id_user = c.id_user
                  LEFT JOIN agence ag ON c.id_agence = ag.id_agence
                  LEFT JOIN (
                    SELECT rr.*
                    FROM reponse rr
                    INNER JOIN (
                        SELECT reclamation_id, MAX(id_re) AS max_id_re
                        FROM reponse
                        GROUP BY reclamation_id
                    ) last_rep ON last_rep.max_id_re = rr.id_re
                  ) rep ON rep.reclamation_id = r.id
                  LEFT JOIN user ua ON rep.id_user = ua.id_user
                  $where
                  ORDER BY r.date_depot DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAllReclamations(): int
    {
        $role  = RoleHelper::getRole();
        $idAg  = RoleHelper::getAgenceId();
        $where = "";
        $params = [];

        if ($role === 'admin' || $role === 'agent') {
            if ($idAg === null) return 0;
            $where = " WHERE c.id_agence = :idAg ";
            $params['idAg'] = $idAg;
        } elseif ($role === 'client') {
            $where = " WHERE r.id_user = :uid ";
            $params['uid'] = RoleHelper::getUserId();
        }

        $sql = "SELECT COUNT(*) FROM reclamation r LEFT JOIN client c ON r.id_user = c.id_user $where";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    // ── SEARCH BY OBJET ───────────────────────────────────────────────────────
    public function searchAllReclamationsByObjet(string $objet): array
    {
        $role    = RoleHelper::getRole();
        $idAg    = RoleHelper::getAgenceId();
        $params  = [':objet' => '%' . $objet . '%'];
        $extraWhere = "";

        if ($role === 'admin' || $role === 'agent') {
            if ($idAg === null) {
                $extraWhere = " AND 1 = 0 ";
            } else {
                $extraWhere = " AND c.id_agence = :idAg ";
                $params['idAg'] = $idAg;
            }
        } elseif ($role === 'client') {
            $extraWhere = " AND r.id_user = :uid ";
            $params['uid'] = RoleHelper::getUserId();
        }

        $sql = "SELECT
                      r.id, r.objet, r.type, r.ref_contrat, r.recRef AS rec_ref,
                      r.object_type, r.object_ref,
                      r.description, r.date_depot,
                      r.statut, r.priorite, r.email, r.id_user,
                      u.nom AS client_nom, u.prenom AS client_prenom,
                      c.numero_client, ag.nom_agence,
                      rep.id_re        AS rep_id,
                      rep.contenu      AS reponse_contenu,
                      rep.date_reponse AS rep_date,
                      rep.statut       AS rep_statut,
                      ua.nom           AS agent_nom,
                      ua.prenom        AS agent_prenom
                  FROM reclamation r
                  LEFT JOIN user u ON r.id_user = u.id_user
                  LEFT JOIN client c ON r.id_user = c.id_user
                  LEFT JOIN agence ag ON c.id_agence = ag.id_agence
                  LEFT JOIN (
                    SELECT rr.*
                    FROM reponse rr
                    INNER JOIN (
                        SELECT reclamation_id, MAX(id_re) AS max_id_re
                        FROM reponse
                        GROUP BY reclamation_id
                    ) last_rep ON last_rep.max_id_re = rr.id_re
                  ) rep ON rep.reclamation_id = r.id
                  LEFT JOIN user ua ON rep.id_user = ua.id_user
                  WHERE r.objet LIKE :objet $extraWhere
                  ORDER BY r.date_depot DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── SHOW ONE ─────────────────────────────────────────────────────────────
    public function showReponse($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM reponse WHERE id_re = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ── UPDATE ───────────────────────────────────────────────────────────────
    public function updateReponse($id, $contenu): void
    {
        // Sauvegarder l'historique
        $old = $this->showReponse($id);
        if ($old) {
            $this->db->prepare(
                "INSERT INTO reponse_history (reponse_id, ancien_contenu, modifie_par) VALUES (?, ?, ?)"
            )->execute([(int)$id, $old['contenu'], RoleHelper::getUserId() ?: null]);
        }

        $this->db->prepare(
            "UPDATE reponse SET contenu = ?, date_reponse = NOW() WHERE id_re = ?"
        )->execute([$contenu, (int)$id]);

        $this->audit('reponse_modifiee', 0, (int)$id, substr($contenu, 0, 100));
    }

    // ── DELETE ───────────────────────────────────────────────────────────────
    public function deleteReponse($id)
    {
        $stmt = $this->db->prepare("SELECT reclamation_id FROM reponse WHERE id_re = ?");
        $stmt->execute([(int)$id]);
        $row = $stmt->fetch();
        
        if ($row) {
            $this->audit('reponse_supprimee', (int)$row['reclamation_id'], (int)$id);
            $this->db->prepare("UPDATE reclamation SET statut = 'open' WHERE id = ?")
                 ->execute([$row['reclamation_id']]);
        }

        $stmt = $this->db->prepare("DELETE FROM reponse WHERE id_re = ?");
        $stmt->execute([(int)$id]);
        $this->invalidateStatsCache();
    }

    // ── STATS BY TYPE (avec Cache) ──────────────────────────────────────────
    public function getStatsByType(): array
    {
        $cacheKey = 'stats_type_' . (RoleHelper::getAgenceId() ?? 'all');
        if (isset($_SESSION[$cacheKey], $_SESSION[$cacheKey . '_time']) && (time() - $_SESSION[$cacheKey . '_time']) < 300) {
            return $_SESSION[$cacheKey];
        }

        $role    = RoleHelper::getRole();
        $idAg    = RoleHelper::getAgenceId();
        $params  = [];
        $where   = "";

        if ($role === 'admin' || $role === 'agent') {
            if ($idAg === null) {
                $where = " WHERE 1 = 0 ";
            } else {
                $where = " WHERE c.id_agence = :idAg ";
                $params['idAg'] = $idAg;
            }
        } elseif ($role === 'client') {
            $where = " WHERE r.id_user = :uid ";
            $params['uid'] = RoleHelper::getUserId();
        }

        $sql = "SELECT r.type, COUNT(*) as total
                FROM reclamation r
                LEFT JOIN client c ON r.id_user = c.id_user
                $where
                GROUP BY r.type";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalAll = 0;
        foreach($rows as $r) $totalAll += (int)$r['total'];

        $stats = [];
        foreach($rows as $r) {
            $stats[] = [
                'type'    => $r['type'] ?: 'Autre',
                'total'   => (int)$r['total'],
                'percent' => $totalAll > 0 ? round(($r['total'] / $totalAll) * 100, 1) : 0
            ];
        }

        $result = ['success' => true, 'stats' => $stats, 'total' => $totalAll];
        $_SESSION[$cacheKey] = $result;
        $_SESSION[$cacheKey . '_time'] = time();
        return $result;
    }

    // ── REJETER ──────────────────────────────────────────────────────────────
    public function rejeterReclamation(int $reclamation_id, string $motif): void
    {
        $check = $this->db->prepare("SELECT id_re FROM reponse WHERE reclamation_id = ? LIMIT 1");
        $check->execute([$reclamation_id]);
        if ($check->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception('Une réponse existe déjà pour cette réclamation.');
        }

        $stmt = $this->db->prepare(
            "INSERT INTO reponse (date_reponse, contenu, statut, reclamation_id, id_user)
             VALUES (?, ?, 'rejetee', ?, ?)"
        );
        $stmt->execute([
            date('Y-m-d'),
            $motif,
            $reclamation_id,
            RoleHelper::getUserId() ?: null
        ]);

        $this->db->prepare(
            "UPDATE reclamation SET statut = 'rejected' WHERE id = ?"
        )->execute([(int)$reclamation_id]);

        $stmtU = $this->db->prepare("SELECT id_user FROM reclamation WHERE id = ?");
        $stmtU->execute([$reclamation_id]);
        $uid = (int)$stmtU->fetchColumn();
        if ($uid) {
            $this->db->prepare("INSERT INTO notification (id_user, message, type, lien) VALUES (?, ?, 'reclamation', ?)")
                ->execute([$uid, "Votre réclamation #$reclamation_id a été rejetée.", '/view/FrontOffice/reclamationList.php']);
        }

        $this->audit('reclamation_rejetee', $reclamation_id, null, substr($motif, 0, 100));
        $this->invalidateStatsCache();
    }

    // ── MÉTHODES AVEC EMAIL ──────────────────────────────────────────────────
    public function addReponseAvecEmail(int $reclamation_id, string $contenu, string $emailClient = ''): array
    {
        $stmt = $this->db->prepare("SELECT * FROM reclamation WHERE id = ?");
        $stmt->execute([$reclamation_id]);
        $rec = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$rec) return ['success' => false, 'message' => 'Réclamation introuvable.'];

        try {
            $reponse = new Reponse(null, date('Y-m-d'), $contenu, 'envoyee', $reclamation_id);
            $this->addReponse($reponse, RoleHelper::getUserId());
        } catch (Exception $e) {
            error_log('ReponseController::envoyer error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur serveur'];
        }

        $email = $emailClient ?: ($rec['email'] ?? '');
        $objet = $rec['objet'] ?? 'Réclamation';
        $idCli = (int)($rec['id_user'] ?? 0);
        
        // Generate satisfaction survey link
        $secretKey = defined('MAIL_SMTP_PASS') && MAIL_SMTP_PASS !== '' ? MAIL_SMTP_PASS : 'protex_assurance_secret';
        $token = hash_hmac('sha256', $reclamation_id . '|' . $idCli, $secretKey);
        $base = defined('BASE_URL') ? BASE_URL : '/assurance';
        $satisfactionUrl = rtrim($base, '/') . '/view/FrontOffice/satisfaction.php?id=' . $reclamation_id . '&token=' . $token;
        
        require_once __DIR__ . '/../service/EmailService.php';
        $sent = ReclamationMailer::envoyerNotificationReponse($email, $objet, $contenu, 'reponse', $satisfactionUrl);

        return ['success' => true, 'email_sent' => $sent, 'message' => $sent ? 'Réponse envoyée.' : 'Réponse enregistrée, erreur email.'];
    }

    public function rejeterAvecEmail(int $reclamation_id, string $motif, string $emailClient = ''): array
    {
        $stmt = $this->db->prepare("SELECT * FROM reclamation WHERE id = ?");
        $stmt->execute([$reclamation_id]);
        $rec = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$rec) return ['success' => false, 'message' => 'Réclamation introuvable.'];

        $this->rejeterReclamation($reclamation_id, $motif);

        $email = $emailClient ?: ($rec['email'] ?? '');
        $objet = $rec['objet'] ?? 'Réclamation';

        require_once __DIR__ . '/../service/EmailService.php';
        $sent = ReclamationMailer::envoyerNotificationReponse($email, $objet, $motif, 'rejet');

        return ['success' => true, 'email_sent' => $sent, 'message' => $sent ? 'Réclamation rejetée.' : 'Rejet enregistré, erreur email.'];
    }

    // ── PRIVÉ ────────────────────────────────────────────────────────────────
    private function audit(string $action, int $reclamation_id, ?int $reponse_id = null, string $details = ''): void
    {
        $this->db->prepare(
            "INSERT INTO audit_reclamation (action, reclamation_id, reponse_id, id_user, ip_address, details)
             VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([
            $action,
            $reclamation_id,
            $reponse_id,
            RoleHelper::getUserId() ?: null,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $details
        ]);
    }

    private function invalidateStatsCache(): void
    {
        $cacheKey = 'stats_type_' . (RoleHelper::getAgenceId() ?? 'all');
        unset($_SESSION[$cacheKey], $_SESSION[$cacheKey . '_time']);
    }
}
