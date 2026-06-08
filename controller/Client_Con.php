<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../mailer/mailer.php';
require_once __DIR__ . '/../model/User.php';

class UserController
{
    // ============================================================
    // CONTRÔLE D'ACCÈS PAR RÔLE
    // ============================================================

    public function requireRole(array $rolesAutorises): void
    {
        $role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
        if (!in_array($role, $rolesAutorises, true)) {
            http_response_code(403);
            throw new Exception("Accès refusé");
        }
    }

    public function isSuperAdmin(): bool
    {
        return ($_SESSION['user_role'] ?? $_SESSION['role'] ?? '') === 'superadmin';
    }

    public function isAdminAgence(): bool
    {
        return ($_SESSION['user_role'] ?? $_SESSION['role'] ?? '') === 'admin';
    }

    public function getSessionAgence(): ?int
    {
        if (isset($_SESSION['agence_id'])) {
            return (int) $_SESSION['agence_id'];
        }

        return isset($_SESSION['id_agence']) ? (int)$_SESSION['id_agence'] : null;
    }

    // ============================================================
    // VALIDATION PRIVÉE
    // ============================================================

    private function validateUserFields(string $nom, string $prenom, string $email, ?string $telephone = null): void
    {
        if (empty($nom) || empty($prenom) || empty($email)) throw new Exception("Champs obligatoires manquants");
        if (strlen($nom) < 2 || strlen($prenom) < 2) throw new Exception("Nom et prénom : 2 lettres minimum");
        if (preg_match('/[0-9]/', $nom) || preg_match('/[0-9]/', $prenom)) throw new Exception("Nom/prénom sans chiffres");
        if (!preg_match('/^[a-zA-ZÀ-ÿ\s\'\-]+$/', $nom) || !preg_match('/^[a-zA-ZÀ-ÿ\s\'\-]+$/', $prenom))
            throw new Exception("Nom/prénom : lettres uniquement");
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception("Email invalide");
        if ($telephone !== null && !preg_match('/^[\d\s\-\+\(\)]{8,20}$/', $telephone)) throw new Exception("Téléphone invalide");
    }

    private function validatePassword(string $password): void
    {
        if (strlen($password) < 8)            throw new Exception("Mot de passe : 8 caractères minimum");
        if (!preg_match('/[A-Z]/', $password)) throw new Exception("Mot de passe : au moins une majuscule");
        if (!preg_match('/[0-9]/', $password)) throw new Exception("Mot de passe : au moins un chiffre");
        if (!preg_match('/[\W]/', $password))  throw new Exception("Mot de passe : au moins un symbole");
    }

    private function buildUserFilters(array $filters): array
    {
        $conditions = [];
        $params     = [];

        if (!empty($filters['keyword'])) {
            $kw = '%' . trim($filters['keyword']) . '%';
            $conditions[] = "(u.nom LIKE :kw1 OR u.prenom LIKE :kw2 OR u.email LIKE :kw3 OR u.cin LIKE :kw4 OR c.numero_client LIKE :kw5)";
            $params = array_merge($params, ['kw1'=>$kw,'kw2'=>$kw,'kw3'=>$kw,'kw4'=>$kw,'kw5'=>$kw]);
        }
        if (!empty($filters['role']))      { $conditions[] = "u.role = :role";     $params['role']      = $filters['role']; }
        if (!empty($filters['statut']))    { $conditions[] = "u.statut = :statut"; $params['statut']    = $filters['statut']; }
        if (!empty($filters['date_from'])) { $conditions[] = "u.date_creation >= :date_from"; $params['date_from'] = $filters['date_from'].' 00:00:00'; }
        if (!empty($filters['date_to']))   { $conditions[] = "u.date_creation <= :date_to";   $params['date_to']   = $filters['date_to'].' 23:59:59'; }
        if (!empty($filters['agence']))    { 
            $conditions[] = "(ag.id_agence = :agence OR a.id_agence = :agence2 OR c.id_agence = :agence3)"; 
            $params['agence'] = (int)$filters['agence'];
            $params['agence2'] = (int)$filters['agence'];
            $params['agence3'] = (int)$filters['agence'];
        }
        if (!empty($filters['has_avatar'])) $conditions[] = "u.avatar != 'default.png'";

        // Isolation automatique pour admin_agence
        if ($this->isAdminAgence() && isset($_SESSION['id_agence'])) {
            $conditions[] = "u.role != 'superadmin'"; // FIX 7 : l'admin ne voit jamais le superadmin
            $conditions[] = "(ag.id_agence = :session_agence OR a.id_agence = :session_agence2 OR c.id_agence = :session_agence3)";
            $params['session_agence']  = (int)$_SESSION['id_agence'];
            $params['session_agence2'] = (int)$_SESSION['id_agence'];
            $params['session_agence3'] = (int)$_SESSION['id_agence'];
        }

        // Agents only see clients
        if (($_SESSION['user_role'] ?? $_SESSION['role'] ?? '') === 'agent') {
            $conditions[] = "u.role = 'client'";
            if (isset($_SESSION['id_agence'])) {
                $conditions[] = "c.id_agence = :agent_agence";
                $params['agent_agence'] = (int)$_SESSION['id_agence'];
            }
        }

        // FIX 7 : superadmin n'est jamais visible pour les rôles non-superadmin
        $currentRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
        if ($currentRole !== 'superadmin') {
            $conditions[] = "u.role != 'superadmin'";
        }

        return [$conditions, $params];
    }

    // ============================================================
    // CSRF
    // ============================================================

    public static function getCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    private function verifyCsrf(string $token): void
    {
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token))
            throw new Exception("Token CSRF invalide");
    }

    // ============================================================
    // AUTHENTIFICATION
    // ============================================================

    public function login(string $email, string $password): array
    {
        if (empty($email) || empty($password)) return ['success'=>false,'message'=>'Champs requis'];
        try {
            $db   = config::getConnexion();
            
            // === Anti-brute-force : blocage après 3 tentatives échouées en 15 min ===
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $stmt_attempts = $db->prepare("SELECT COUNT(*) as nb FROM login_attempts WHERE ip = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
            $stmt_attempts->execute([$ip]);
            $nb_failed = (int)$stmt_attempts->fetch()['nb'];
            if ($nb_failed >= 3) {
                $stmt_reste = $db->prepare("SELECT TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(MIN(attempted_at), INTERVAL 15 MINUTE)) as reste FROM login_attempts WHERE ip = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
                $stmt_reste->execute([$ip]);
                $reste_sec = max(60, (int)$stmt_reste->fetch()['reste']);
                $reste_min = ceil($reste_sec / 60);
                return ['success'=>false,'blocked'=>true,'minutes'=>$reste_min,'message'=>"Compte bloqué. Réessayez dans {$reste_min} minute(s)."];
            }
            // === Fin anti-brute-force ===
            
            $stmt = $db->prepare("SELECT id_user,nom,prenom,email,mot_de_passe,role,statut FROM user WHERE email=:email LIMIT 1");
            $stmt->execute(['email'=>$email]);
            $user = $stmt->fetch();
            if (!$user) {
                $db->prepare("INSERT INTO login_attempts (email,ip) VALUES (?,?)")->execute([$email,$ip]);
                return ['success'=>false,'message'=>'Email incorrect'];
            }
            if (!password_verify($password,$user['mot_de_passe'])) {
                $db->prepare("INSERT INTO login_attempts (email,ip) VALUES (?,?)")->execute([$email,$ip]);
                return ['success'=>false,'message'=>'Mot de passe incorrect'];
            }
            if ($user['statut']==='bloque') return ['success'=>false,'message'=>'Compte bloqué'];

            // ACTIVATION OTP
            $otp = str_pad(random_int(0,999999),6,'0',STR_PAD_LEFT);
            $exp = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            
            $db->prepare("DELETE FROM otp_codes WHERE id_user=:id")->execute(['id'=>$user['id_user']]);
            $db->prepare("INSERT INTO otp_codes (id_user,code,expires_at) VALUES (:id,:code,:exp)")
               ->execute(['id'=>$user['id_user'],'code'=>$otp,'exp'=>$exp]);

            // Tentative d'envoi d'email
            $mailError = null;
            try {
                $mailer = new Mailer();
                $mailer->sendOTP($user['email'], $user['prenom'], $otp);
            } catch (Throwable $e) {
                $mailError = $e->getMessage();
                error_log("Erreur envoi OTP: " . $mailError);
            }

            session_regenerate_id(true);

            $_SESSION['otp_user_id'] = $user['id_user'];
            $_SESSION['otp_role']    = $user['role'];
            $_SESSION['otp_nom']     = $user['nom'];
            $_SESSION['otp_prenom']  = $user['prenom'];
            $_SESSION['otp_email']   = $user['email'];

            $response = ['success'=>true, 'otp_required'=>true, 'role'=>$user['role']];
            if ($mailError) $response['mail_error'] = $mailError;
            return $response;
        } catch(Exception $e){ 
            error_log('login: '.$e->getMessage()); 
            return ['success'=>false,'message'=>'Erreur serveur']; 
        }
    }

    public function verifyOTP(string $code): array
    {
        if (!isset($_SESSION['otp_user_id'])) return ['success'=>false,'message'=>'Session expirée'];
        $id = $_SESSION['otp_user_id'];
        try {
            $db   = config::getConnexion();
            $stmt = $db->prepare("SELECT id,code,expires_at,used FROM otp_codes WHERE id_user=:id AND used=0 ORDER BY created_at DESC LIMIT 1");
            $stmt->execute(['id'=>$id]);
            $otp = $stmt->fetch();
            if (!$otp)                                          return ['success'=>false,'message'=>'Code invalide'];
            if (new DateTime() > new DateTime($otp['expires_at'])) return ['success'=>false,'message'=>'Code expiré'];
            if (!hash_equals($otp['code'],$code))               return ['success'=>false,'message'=>'Code incorrect'];

            $db->prepare("UPDATE otp_codes SET used=1 WHERE id=:id")->execute(['id'=>$otp['id']]);

            // Charger id_agence selon le rôle
            $id_agence = null;
            $role = $_SESSION['otp_role'];
            if ($role === 'admin') {
                $r = $db->prepare("SELECT id_agence FROM admin WHERE id_user=:id");
                $r->execute(['id'=>$id]);
                $id_agence = $r->fetchColumn() ?: null;
            } elseif ($role === 'agent') {
                $r = $db->prepare("SELECT id_agence FROM agent WHERE id_user=:id");
                $r->execute(['id'=>$id]);
                $id_agence = $r->fetchColumn() ?: null;
            } elseif ($role === 'client') {
                $r = $db->prepare("SELECT id_agence FROM client WHERE id_user=:id");
                $r->execute(['id'=>$id]);
                $id_agence = $r->fetchColumn() ?: null;
            }

            session_regenerate_id(true);
            $_SESSION['user_id']     = $id;
            $_SESSION['id_user']     = $id;
            $_SESSION['role']        = $role;
            $_SESSION['user_role']   = $role;
            $_SESSION['nom']         = $_SESSION['otp_nom'];
            $_SESSION['prenom']      = $_SESSION['otp_prenom'];
            $_SESSION['user_nom']    = $_SESSION['otp_nom'];
            $_SESSION['user_prenom'] = $_SESSION['otp_prenom'];
            $_SESSION['user_email']  = $_SESSION['otp_email'] ?? '';
            $_SESSION['user_avatar'] = 'default.png';
            $_SESSION['id_agence']   = $id_agence;
            $_SESSION['agence_id']   = $id_agence;
            $_SESSION['last_activity'] = time();

            // Log successful login
            $email_log = $_SESSION['otp_email'] ?? '';
            $ip_log = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            if ($email_log) {
                $db->prepare("INSERT INTO login_attempts (email,ip) VALUES (?,?)")
                   ->execute([$email_log, $ip_log]);
                // Nettoyer les tentatives échouées de cette IP
                $db->prepare("DELETE FROM login_attempts WHERE ip=?")->execute([$ip_log]);
            }
            
            unset($_SESSION['otp_user_id'],$_SESSION['otp_role'],$_SESSION['otp_nom'],$_SESSION['otp_prenom'],$_SESSION['otp_email']);
            
            // Mise à jour last_seen
            $db->prepare("UPDATE user SET last_seen=NOW() WHERE id_user=?")->execute([$id]);

            return ['success'=>true,'role'=>$_SESSION['role']];
        } catch(Exception $e){ error_log('verifyOTP: '.$e->getMessage()); return ['success'=>false,'message'=>'Erreur serveur']; }
    }
    public function findOrCreateGithubUser(array $githubUser): array
    {
        $githubId = $githubUser['id'];
        $email = $githubUser['email'] ?? '';
        $nom = $githubUser['name'] ?? $githubUser['login'];
        $prenom = ''; // GitHub ne sépare pas toujours nom/prénom
        $avatar = $githubUser['avatar_url'] ?? 'default.png';

        try {
            $db = config::getConnexion();
            $stmt = $db->prepare("SELECT id_user, nom, prenom, role, statut FROM user WHERE github_id = :gid OR (email = :email AND email != '') LIMIT 1");
            $stmt->execute(['gid' => $githubId, 'email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                if ($user['statut'] === 'bloque') return ['success' => false, 'message' => 'Compte bloqué'];
                
                // Mettre à jour github_id et avatar si nécessaire
                $db->prepare("UPDATE user SET github_id = :gid, avatar_url = :av WHERE id_user = :id")
                   ->execute(['gid' => $githubId, 'av' => $avatar, 'id' => $user['id_user']]);
                
                $id = $user['id_user'];
                $role = $user['role'];
                $db_nom = $user['nom'];
                $db_prenom = $user['prenom'];
            } else {
                // Créer nouvel utilisateur
                $db->prepare("INSERT INTO user (nom, prenom, email, github_id, avatar_url, role, statut, date_creation, mot_de_passe) 
                             VALUES (:nom, :prenom, :email, :gid, :av, 'client', 'actif', NOW(), 'GITHUB_AUTH')")
                   ->execute(['nom' => $nom, 'prenom' => $prenom, 'email' => $email, 'gid' => $githubId, 'av' => $avatar]);
                
                $id = (int)$db->lastInsertId();
                $role = 'client';
                $db_nom = $nom;
                $db_prenom = $prenom;
                
                // Créer record client
                $num = $this->generateClientNumber($db);
                $db->prepare("INSERT INTO client (id_user, numero_client) VALUES (:id, :num)")->execute(['id' => $id, 'num' => $num]);
            }

            session_regenerate_id(true);
            $_SESSION['user_id']     = $id;
            $_SESSION['id_user']     = $id;
            $_SESSION['role']        = $role;
            $_SESSION['user_role']   = $role;
            $_SESSION['nom']         = $db_nom;
            $_SESSION['prenom']      = $db_prenom;
            $_SESSION['user_nom']    = $db_nom;
            $_SESSION['user_prenom'] = $db_prenom;
            $_SESSION['user_email']  = $email;
            $_SESSION['user_avatar'] = $avatar;

            // Charger id_agence
            $id_agence = null;
            if ($role === 'admin') {
                $r = $db->prepare("SELECT id_agence FROM admin WHERE id_user=:id");
                $r->execute(['id'=>$id]);
                $id_agence = $r->fetchColumn() ?: null;
            } elseif ($role === 'agent') {
                $r = $db->prepare("SELECT id_agence FROM agent WHERE id_user=:id");
                $r->execute(['id'=>$id]);
                $id_agence = $r->fetchColumn() ?: null;
            } elseif ($role === 'client') {
                $r = $db->prepare("SELECT id_agence FROM client WHERE id_user=:id");
                $r->execute(['id'=>$id]);
                $id_agence = $r->fetchColumn() ?: null;
            }
            $_SESSION['id_agence'] = $id_agence;
            $_SESSION['agence_id'] = $id_agence;
            $_SESSION['last_activity'] = time();
            
            return ['success' => true];
        } catch (Exception $e) {
            error_log('findOrCreateGithubUser: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la connexion GitHub'];
        }
    }

    // ============================================================
    // CRÉATION
    // ============================================================

    public function addclient(User $user, ?string $referred_by = null, ?int $id_agence = null): void
    {
        $this->validateUserFields($user->getNom(),$user->getPrenom(),$user->getEmail(),$user->getTelephone());
        $this->validatePassword($user->getMotDePasse());
        if ($this->getUserByEmail($user->getEmail())) throw new Exception("Email déjà utilisé");

        $db = config::getConnexion();

        // Normaliser et valider le code parrain AVANT la création de l'utilisateur
        if ($referred_by) {
            $referred_by = strtoupper(trim($referred_by));
            $chk = $db->prepare("SELECT id_user FROM user WHERE referral_code = ? LIMIT 1");
            $chk->execute([$referred_by]);
            $found = $chk->fetch();
            if (!$found) {
                throw new Exception("Code parrain invalide");
            }
        }

        $my_ref_code = 'PRTX-' . strtoupper(substr(md5(uniqid($user->getEmail(), true)), 0, 6));

        $db->prepare("INSERT INTO user (nom,prenom,email,mot_de_passe,telephone,cin,adresse,role,statut,created_at,google_id,avatar_url,referral_code) 
                     VALUES (:nom,:prenom,:email,:mdp,:tel,:cin,:adr,'client','actif',NOW(),:gid,:avurl,:ref)")
           ->execute([
               'nom'=>htmlspecialchars($user->getNom()),
               'prenom'=>htmlspecialchars($user->getPrenom()),
               'email'=>$user->getEmail(),
               'mdp'=>password_hash($user->getMotDePasse(),PASSWORD_DEFAULT),
               'tel'=>$user->getTelephone(),
               'cin'=>$user->getCin(),
               'adr'=>htmlspecialchars($user->getAdresse()??''),
               'gid'=>$user->getGoogleId(),
               'avurl'=>$user->getAvatarUrl(),
               'ref'=>$my_ref_code
           ]);

        $uid = (int)$db->lastInsertId();

        // Gérer le parrainage si présent (le code a déjà été normalisé et validé)
        if ($referred_by) {
            $stmt = $db->prepare("SELECT id_user FROM user WHERE referral_code = ?");
            $stmt->execute([$referred_by]);
            $refId = $stmt->fetchColumn();
            if ($refId) {
                $db->prepare("UPDATE user SET points_parrainage = points_parrainage + 50 WHERE id_user = ?")->execute([$refId]);
                $db->prepare("INSERT INTO points_fidelite (id_user, points, motif) VALUES (?, 150, 'Parrainage')")->execute([$refId]);
            } else {
                error_log("Referral update: no rows affected for code: $referred_by");
            }
        }
        $num = $this->generateClientNumber($db);
        $db->prepare("INSERT INTO client (id_user,numero_client,id_agence) VALUES (:id,:num,:agence)")->execute(['id'=>$uid,'num'=>$num,'agence'=>$id_agence]);

        // ACTIVATION OTP POUR L'INSCRIPTION
        $otp = str_pad(random_int(0,999999),6,'0',STR_PAD_LEFT);
        $exp = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        $db->prepare("INSERT INTO otp_codes (id_user,code,expires_at) VALUES (:id,:code,:exp)")
           ->execute(['id'=>$uid,'code'=>$otp,'exp'=>$exp]);

        // Tentative d'envoi d'email
        try {
            $mailer = new Mailer();
            $mailer->sendOTP($user->getEmail(), $user->getPrenom(), $otp);
        } catch (Exception $e) {
            error_log("Erreur envoi OTP inscription: " . $e->getMessage());
        }
        
        // On ne crée pas encore la session complète
        $_SESSION['otp_user_id'] = $uid;
        $_SESSION['otp_role']    = 'client';
        $_SESSION['otp_nom']     = $user->getNom();
        $_SESSION['otp_prenom']  = $user->getPrenom();
        $_SESSION['otp_email']   = $user->getEmail();

        $db->prepare("UPDATE user SET last_seen=NOW() WHERE id_user=?")->execute([$uid]);
    }

    private function generateClientNumber(\PDO $db): string
    {
        $tries=0;
        do {
            if(++$tries>10) throw new Exception("Numéro client impossible à générer");
            $n='CL-'.str_pad(random_int(0,99999),5,'0',STR_PAD_LEFT);
            $s=$db->prepare("SELECT COUNT(*) FROM client WHERE numero_client=:n"); $s->execute(['n'=>$n]);
        } while($s->fetchColumn()>0);
        return $n;
    }

    /**
     * Création d'un user par admin/superadmin.
     * Règles :
     *   superadmin  → peut créer tous rôles
     *   admin_agence → agent (dans son agence) + client
     *   agent        → client uniquement
     */
    public function addUserAdmin(
        string $nom, string $prenom, string $email, string $password,
        ?string $telephone=null, ?string $cin=null, string $role='client', string $statut='actif',
        ?string $niveau_acces=null, ?int $id_agence=null, ?float $salaire=null, ?string $numero_client=null
    ): void {
        $this->validateUserFields($nom,$prenom,$email,$telephone);
        $this->validatePassword($password);
        if ($this->getUserByEmail($email)) throw new Exception("Email déjà utilisé");

        $sessionRole = $_SESSION['role'] ?? '';
        $allowed = match($sessionRole) {
            'superadmin' => ['superadmin','admin','agent','client'],
            'admin'      => ['agent','client'],
            'agent'      => ['client'],
            default      => [],
        };
        if (!in_array($role,$allowed,true)) throw new Exception("Rôle non autorisé pour votre niveau d'accès");
        
        // Seul superadmin et admin peuvent créer des agents
        if ($role === 'agent' && !in_array($sessionRole, ['superadmin','admin'])) {
            throw new Exception("Vous n'avez pas la permission de créer des agents");
        }

        // Forcer l'agence de session pour admin_agence
        if ($sessionRole==='admin' && $role==='agent') {
            $id_agence = $this->getSessionAgence();
            if (!$id_agence) throw new Exception("Agence introuvable");
        }

        $db = config::getConnexion();
        $db->prepare("INSERT INTO user (nom,prenom,email,mot_de_passe,telephone,cin,role,statut,created_at) VALUES (:nom,:prenom,:email,:pw,:tel,:cin,:role,:statut,NOW())")
           ->execute(['nom'=>htmlspecialchars($nom),'prenom'=>htmlspecialchars($prenom),'email'=>$email,'pw'=>password_hash($password,PASSWORD_DEFAULT),'tel'=>$telephone,'cin'=>$cin,'role'=>$role,'statut'=>$statut]);

        $newId=(int)$db->lastInsertId();

        if ($role==='superadmin' || $role==='admin') {
            $niv = ($role==='superadmin') ? 'superadmin' : 'admin_agence';
            $db->prepare("INSERT INTO admin (id_user,niveau_acces,id_agence) VALUES (:id,:niv,:ag)")->execute(['id'=>$newId,'niv'=>$niv,'ag'=>($role==='admin'?$id_agence:null)]);
        } elseif ($role==='agent') {
            $db->prepare("INSERT INTO agent (id_user,id_agence,salaire) VALUES (:id,:ag,:sal)")->execute(['id'=>$newId,'ag'=>$id_agence,'sal'=>$salaire]);
        } elseif ($role==='client') {
            $n=$this->generateClientNumber($db);
            $clientAgence = null;
            if (in_array($sessionRole, ['admin', 'agent'], true)) {
                $clientAgence = $this->getSessionAgence();
            } elseif ($id_agence !== null) {
                $clientAgence = $id_agence;
            }
            $db->prepare("INSERT INTO client (id_user,numero_client,id_agence) VALUES (:id,:n,:ag)")->execute(['id'=>$newId,'n'=>$n,'ag'=>$clientAgence]);
        }
    }

    // ============================================================
    // LECTURE
    // ============================================================

    private function baseUserSelect(): string
    {
        return "SELECT u.id_user,u.nom,u.prenom,u.email,u.telephone,u.cin,u.avatar,
                       u.role,u.statut,u.date_creation,u.date_naissance,u.face_encoding,
                       u.google_id, u.avatar_url, u.github_id, u.points_parrainage, u.referral_code,
                       a.niveau_acces, a.id_agence AS admin_id_agence,
                       ag.id_agence, ag.salaire,
                  c.numero_client, c.id_agence AS client_id_agence,
                  anc.nom_agence
                FROM user u
                LEFT JOIN admin  a   ON u.id_user=a.id_user
                LEFT JOIN agent  ag  ON u.id_user=ag.id_user
                LEFT JOIN client c   ON u.id_user=c.id_user
              LEFT JOIN agence anc ON (c.id_agence=anc.id_agence OR ag.id_agence=anc.id_agence OR a.id_agence=anc.id_agence)";
    }

    public function getAllUsers(int $page=1, int $perPage=20): array
    {
        $perPage=max(1,min(100,$perPage)); $offset=max(0,($page-1)*$perPage);
        $params=[];
        $sql=$this->baseUserSelect();
        if ($this->isAdminAgence() && isset($_SESSION['id_agence'])) {
            $sql.=" WHERE u.role != 'superadmin' AND (ag.id_agence=:ag OR a.id_agence=:ag2 OR c.id_agence=:ag3)";
            $params=['ag'=>(int)$_SESSION['id_agence'],'ag2'=>(int)$_SESSION['id_agence'],'ag3'=>(int)$_SESSION['id_agence']];
        } elseif (($_SESSION['role']??'') === 'agent' && isset($_SESSION['id_agence'])) {
            // Agents only see clients from their agency
            $sql.=" WHERE u.role='client' AND c.id_agence=:ag";
            $params=['ag'=>(int)$_SESSION['id_agence']];
        }
        $sql.=" ORDER BY u.date_creation DESC LIMIT :lim OFFSET :off";
        $stmt=config::getConnexion()->prepare($sql);
        foreach($params as $k=>$v) $stmt->bindValue(":$k",$v,\PDO::PARAM_INT);
        $stmt->bindValue(':lim',$perPage,\PDO::PARAM_INT);
        $stmt->bindValue(':off',$offset,\PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countAllUsers(): int
    {
        $db=config::getConnexion();
        if ($this->isAdminAgence() && isset($_SESSION['id_agence'])) {
            $s=$db->prepare("SELECT COUNT(*) FROM user u LEFT JOIN agent ag ON u.id_user=ag.id_user LEFT JOIN admin a ON u.id_user=a.id_user LEFT JOIN client c ON u.id_user=c.id_user WHERE u.role != 'superadmin' AND (ag.id_agence=:ag OR a.id_agence=:ag2 OR c.id_agence=:ag3)");
            $s->execute(['ag'=>(int)$_SESSION['id_agence'],'ag2'=>(int)$_SESSION['id_agence'],'ag3'=>(int)$_SESSION['id_agence']]);
            return (int)$s->fetchColumn();
        }
        if (($_SESSION['role']??'') === 'agent') {
            $s=$db->prepare("SELECT COUNT(*) FROM user u LEFT JOIN client c ON u.id_user=c.id_user WHERE u.role='client' AND c.id_agence=:ag");
            $s->execute(['ag'=>(int)($_SESSION['id_agence'] ?? 0)]);
            return (int)$s->fetchColumn();
        }
        return (int)$db->query("SELECT COUNT(*) FROM user")->fetchColumn();
    }

    public function getUserById(int $id): ?array
    {
        $sql = $this->baseUserSelect();
        $params = ['id' => $id];
        $where = " WHERE u.id_user=:id";

        if ($this->isAdminAgence() && isset($_SESSION['id_agence'])) {
            $where .= " AND u.role != 'superadmin' AND (ag.id_agence=:ag OR a.id_agence=:ag2 OR c.id_agence=:ag3)";
            $params['ag'] = (int)$_SESSION['id_agence'];
            $params['ag2'] = (int)$_SESSION['id_agence'];
            $params['ag3'] = (int)$_SESSION['id_agence'];
        } elseif (($_SESSION['role'] ?? '') === 'agent' && isset($_SESSION['id_agence'])) {
            $where .= " AND u.role = 'client' AND c.id_agence = :ag";
            $params['ag'] = (int)$_SESSION['id_agence'];
        }

        $stmt = config::getConnexion()->prepare($sql . $where);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function getUserByEmail(string $email): ?array
    {
        $stmt=config::getConnexion()->prepare("SELECT id_user,email FROM user WHERE email=:email LIMIT 1");
        $stmt->execute(['email'=>$email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    // ============================================================
    // GESTION DES AGENCES (superadmin uniquement)
    // ============================================================

    public function getAllAgences(): array
    {
        $this->requireRole(['superadmin', 'admin', 'agent']);
        return config::getConnexion()->query("SELECT * FROM agence ORDER BY nom_agence ASC")->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function addAgence(string $nom, ?string $pays, ?string $tel, ?string $email): void
    {
        $this->requireRole(['superadmin']);
        config::getConnexion()->prepare("INSERT INTO agence (nom_agence,pays,tel,email,statut) VALUES (:n,:p,:t,:e,'active')")
            ->execute(['n'=>$nom,'p'=>$pays,'t'=>$tel,'e'=>$email]);
    }

    public function toggleStatutAgence(int $id): string
    {
        $this->requireRole(['superadmin']);
        $db=config::getConnexion();
        $s=$db->prepare("SELECT statut FROM agence WHERE id_agence=:id"); $s->execute(['id'=>$id]);
        $row=$s->fetch(); if(!$row) throw new Exception("Agence introuvable");
        $new=($row['statut']==='active')?'inactive':'active';
        $db->prepare("UPDATE agence SET statut=:s WHERE id_agence=:id")->execute(['s'=>$new,'id'=>$id]);
        return $new;
    }

    // ============================================================
    // MISE À JOUR
    // ============================================================

    public function updateClient(int $id_user, string $nom, string $prenom, string $email, ?string $telephone=null, ?string $adresse=null, ?string $date_naissance=null): void
    {
        $this->validateUserFields($nom,$prenom,$email,$telephone);
        $existing=$this->getUserById($id_user);
        if (!$existing) throw new Exception("Utilisateur introuvable");
        if ($email!==$existing['email'] && $this->getUserByEmail($email)) throw new Exception("Email déjà utilisé");
        $db=config::getConnexion();
        $db->prepare("UPDATE user SET nom=:n,prenom=:p,email=:e,telephone=:t,adresse=:a,date_naissance=:dn WHERE id_user=:id")
            ->execute(['id'=>$id_user,'n'=>htmlspecialchars(trim($nom)),'p'=>htmlspecialchars(trim($prenom)),'e'=>trim($email),'t'=>$telephone?trim($telephone):null,'a'=>$adresse?htmlspecialchars(trim($adresse)):null,'dn'=>$date_naissance?:null]);
        $db->prepare("INSERT INTO notification (id_user, message, type) VALUES (?, ?, 'info')")
            ->execute([$id_user, "Votre profil a été mis à jour."]);
    }

    public function updateUserAdmin(int $id_user, string $nom, string $prenom, string $email, ?string $telephone=null, ?string $cin=null, string $role='client', string $statut='actif', ?string $niveau_acces=null, ?int $id_agence=null, ?float $salaire=null, ?string $numero_client=null): void
    {
        $this->validateUserFields($nom,$prenom,$email,$telephone);
        if (!in_array($role,['superadmin','admin','agent','client'],true)) throw new Exception("Rôle invalide");
        if (!in_array($statut,['actif','bloque'],true)) throw new Exception("Statut invalide");

        $existingUser = $this->getUserById($id_user);
        if (!$existingUser) throw new Exception("Utilisateur introuvable");

        $sessionRole = $_SESSION['role'] ?? '';
        
        if ($this->isAdminAgence()) {
            // Admin agence cannot modify superadmin/admin
            if (in_array($existingUser['role'],['superadmin','admin'])) throw new Exception("Accès refusé");
            if ($role !== $existingUser['role']) throw new Exception("Admin agence ne peut pas modifier les rôles");
            // Admin agence can only modify users in their agency
            $userAgence = $existingUser['id_agence'] ?? $existingUser['admin_id_agence'] ?? $existingUser['client_id_agence'] ?? null;
            if (!$userAgence || $userAgence !== $this->getSessionAgence()) throw new Exception("Utilisateur d'une autre agence");
        } elseif ($sessionRole === 'agent') {
            // Agents can only modify clients in their agency
            if ($existingUser['role'] !== 'client') throw new Exception("Accès refusé");
            if ($existingUser['client_id_agence'] !== $this->getSessionAgence()) throw new Exception("Client d'une autre agence");
            if ($role !== 'client') throw new Exception("Agent ne peut pas modifier le rôle");
        }

        $db=config::getConnexion();
        $db->prepare("UPDATE user SET nom=:n,prenom=:p,email=:e,telephone=:t,cin=:c,role=:r,statut=:s WHERE id_user=:id")
           ->execute(['id'=>$id_user,'n'=>htmlspecialchars(trim($nom)),'p'=>htmlspecialchars(trim($prenom)),'e'=>trim($email),'t'=>$telephone?trim($telephone):null,'c'=>$cin?trim($cin):null,'r'=>$role,'s'=>$statut]);

        if (in_array($role,['superadmin','admin']) && $niveau_acces!==null)
            $db->prepare("UPDATE admin SET niveau_acces=:niv,id_agence=:ag WHERE id_user=:id")->execute(['niv'=>$niveau_acces,'ag'=>$id_agence,'id'=>$id_user]);
        elseif ($role==='agent')
            $db->prepare("UPDATE agent SET id_agence=:ag,salaire=:sal WHERE id_user=:id")->execute(['ag'=>$id_agence,'sal'=>$salaire,'id'=>$id_user]);

        if ($role === 'client') {
            $db->prepare("INSERT INTO notification (id_user, message, type) VALUES (?, ?, 'info')")
                ->execute([$id_user, "Votre compte a été mis à jour par un administrateur."]);
        }
    }

    public function changePassword(int $id_user, string $ancienMdp, string $nouveauMdp): array
    {
        try { $this->validatePassword($nouveauMdp); } catch(Exception $e){ return ['success'=>false,'message'=>$e->getMessage()]; }
        try {
            $db=config::getConnexion();
            $s=$db->prepare("SELECT mot_de_passe FROM user WHERE id_user=:id"); $s->execute(['id'=>$id_user]); $row=$s->fetch();
            if (!$row||!password_verify($ancienMdp,$row['mot_de_passe'])) return ['success'=>false,'message'=>'Ancien mot de passe incorrect'];
            $db->prepare("UPDATE user SET mot_de_passe=:mdp WHERE id_user=:id")->execute(['mdp'=>password_hash($nouveauMdp,PASSWORD_DEFAULT),'id'=>$id_user]);
            return ['success'=>true,'message'=>'Mot de passe mis à jour'];
        } catch(Exception $e){ return ['success'=>false,'message'=>'Erreur serveur']; }
    }

    public function changePasswordWithoutOld(int $id_user, string $nouveauMdp): array
    {
        try { $this->validatePassword($nouveauMdp); } catch(Exception $e){ return ['success'=>false,'message'=>$e->getMessage()]; }
        try {
            $db=config::getConnexion();
            $db->prepare("UPDATE user SET mot_de_passe=:mdp WHERE id_user=:id")->execute(['mdp'=>password_hash($nouveauMdp,PASSWORD_DEFAULT),'id'=>$id_user]);
            return ['success'=>true,'message'=>'Mot de passe mis à jour'];
        } catch(Exception $e){ return ['success'=>false,'message'=>'Erreur serveur']; }
    }

    public function updateAdminProfile(int $id_user, string $nom, string $prenom, string $email, ?string $telephone): void
    {
        $this->validateUserFields($nom,$prenom,$email,$telephone);
        config::getConnexion()->prepare("UPDATE user SET nom=:n,prenom=:p,email=:e,telephone=:t WHERE id_user=:id")
            ->execute(['id'=>$id_user,'n'=>htmlspecialchars(trim($nom)),'p'=>htmlspecialchars(trim($prenom)),'e'=>trim($email),'t'=>!empty($telephone)?trim($telephone):null]);
    }

    // ============================================================
    // SUPPRESSION / STATUT
    // ============================================================

    public function deleteUser(int $id_user, string $csrfToken=''): void
    {
        if ($csrfToken) $this->verifyCsrf($csrfToken);
        $t=$this->getUserById($id_user);
        if (!$t) throw new Exception("Utilisateur introuvable");
        
        $sessionRole = $_SESSION['role'] ?? '';
        
        if ($this->isAdminAgence()) {
            // Admin agence cannot delete superadmin/admin
            if (in_array($t['role'],['superadmin','admin'])) throw new Exception("Accès refusé");
            // Admin agence can only delete users in their agency
            $userAgence = $t['id_agence'] ?? $t['admin_id_agence'] ?? $t['client_id_agence'] ?? null;
            if (!$userAgence || $userAgence !== $this->getSessionAgence()) throw new Exception("Utilisateur d'une autre agence");
        } elseif ($sessionRole === 'agent') {
            // Agents can only delete clients in their agency
            if ($t['role'] !== 'client') throw new Exception("Accès refusé");
            if ($t['client_id_agence'] !== $this->getSessionAgence()) throw new Exception("Client d'une autre agence");
        }
        
        $db=config::getConnexion();
        foreach(['admin','agent','client','otp_codes'] as $tbl)
            $db->prepare("DELETE FROM $tbl WHERE id_user=:id")->execute(['id'=>$id_user]);
        $db->prepare("DELETE FROM user WHERE id_user=:id")->execute(['id'=>$id_user]);
    }

    public function toggleStatutUser(int $id_user, string $csrfToken=''): string
    {
        if ($csrfToken) $this->verifyCsrf($csrfToken);
        $t=$this->getUserById($id_user);
        if (!$t) throw new Exception("Utilisateur introuvable");
        
        $sessionRole = $_SESSION['role'] ?? '';
        
        if ($this->isAdminAgence()) {
            // Admin agence : peut toggle toute personne inscrite dans son agence (Agents et Clients)
            // Mais il ne peut pas modifier un SuperAdmin ou un autre Admin
            if (in_array($t['role'],['superadmin','admin'])) throw new Exception("Accès refusé");
            
            // Vérification de l'agence (doit correspondre à celle de l'admin)
            $userAgence = $t['id_agence'] ?? $t['admin_id_agence'] ?? $t['agent_id_agence'] ?? $t['client_id_agence'] ?? null;
            if (!$userAgence || (int)$userAgence !== (int)$this->getSessionAgence()) {
                throw new Exception("L'utilisateur n'appartient pas à votre agence");
            }
        } elseif ($sessionRole === 'agent') {
            // Agents can only toggle clients in their agency
            if ($t['role'] !== 'client') throw new Exception("Accès refusé");
            if ($t['client_id_agence'] !== $this->getSessionAgence()) throw new Exception("Client d'une autre agence");
        }
        
        $db=config::getConnexion();
        $s=$db->prepare("SELECT statut,email,nom FROM user WHERE id_user=:id"); $s->execute(['id'=>$id_user]); $row=$s->fetch();
        if (!$row) throw new Exception("Utilisateur introuvable");
        $new=($row['statut']==='actif')?'bloque':'actif';
        $db->prepare("UPDATE user SET statut=:s WHERE id_user=:id")->execute(['s'=>$new,'id'=>$id_user]);
        try { $m=new Mailer(); $new==='bloque'?$m->sendCompteBloque($row['email'],$row['nom']):$m->sendCompteDebloque($row['email'],$row['nom']); } catch(Exception $e){ error_log($e->getMessage()); }
        return $new;
    }

    // ============================================================
    // PROFILS
    // ============================================================

    public function getAdminProfile(int $id_user): ?array
    {
        $stmt=config::getConnexion()->prepare(
            "SELECT u.nom,u.prenom,u.email,u.telephone,u.cin,u.role,u.statut,u.avatar,u.avatar_url,u.date_creation,u.face_encoding,
                    a.niveau_acces, anc.nom_agence, a.id_agence AS admin_id_agence, ag.id_agence AS agent_id_agence
             FROM user u 
             LEFT JOIN admin a ON u.id_user=a.id_user 
             LEFT JOIN agent ag ON u.id_user=ag.id_user
             LEFT JOIN agence anc ON (a.id_agence=anc.id_agence OR ag.id_agence=anc.id_agence)
             WHERE u.id_user=:id"
        );
        $stmt->execute(['id'=>$id_user]);
        $user=$stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$user) return null;
        if (!empty($user['date_creation'])) {
            $dt=new DateTime($user['date_creation']);
            $mois=['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
            $user['date_creation_formatted']=$dt->format('d').' '.$mois[(int)$dt->format('n')-1].' '.$dt->format('Y').' · '.$dt->format('H:i');
        }
        return $user;
    }

    public function getClientProfile(int $id_user): ?array
    {
        $stmt=config::getConnexion()->prepare("SELECT u.nom,u.prenom,u.email,u.telephone,u.adresse,u.cin,u.avatar,u.date_naissance,u.face_encoding,u.google_id,u.avatar_url,u.points_parrainage,u.referral_code,c.numero_client,anc.nom_agence FROM user u LEFT JOIN client c ON u.id_user=c.id_user LEFT JOIN agence anc ON c.id_agence=anc.id_agence WHERE u.id_user=:id");
        $stmt->execute(['id'=>$id_user]);
        return $stmt->fetch(\PDO::FETCH_ASSOC)?:null;
    }

    // ============================================================
    // RECHERCHE
    // ============================================================

    public function searchUsers(array $filters=[], int $page=1, int $perPage=20): array
    {
        $perPage=max(1,min(100,(int)$perPage)); $offset=max(0,(int)(($page-1)*$perPage));
        $sql=$this->baseUserSelect();
        [$conds,$params]=$this->buildUserFilters($filters);
        if (!empty($conds)) $sql.=' WHERE '.implode(' AND ',$conds);
        $valid=['date_asc'=>'u.date_creation ASC','date_desc'=>'u.date_creation DESC','nom_asc'=>'u.nom ASC','nom_desc'=>'u.nom DESC'];
        $sql.=' ORDER BY '.($valid[$filters['order_by']??'']??'u.date_creation DESC').' LIMIT :lim OFFSET :off';
        $stmt=config::getConnexion()->prepare($sql);
        foreach($params as $k=>$v) $stmt->bindValue(":$k",$v);
        $stmt->bindValue(':lim',$perPage,\PDO::PARAM_INT); $stmt->bindValue(':off',$offset,\PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countSearchUsers(array $filters=[]): int
    {
        $sql="SELECT COUNT(*) FROM user u LEFT JOIN admin a ON u.id_user=a.id_user LEFT JOIN agent ag ON u.id_user=ag.id_user LEFT JOIN client c ON u.id_user=c.id_user LEFT JOIN agence anc ON (ag.id_agence=anc.id_agence OR a.id_agence=anc.id_agence OR c.id_agence=anc.id_agence)";
        [$conds,$params]=$this->buildUserFilters($filters);
        if (!empty($conds)) $sql.=' WHERE '.implode(' AND ',$conds);
        $stmt=config::getConnexion()->prepare($sql);
        foreach($params as $k=>$v) $stmt->bindValue(":$k",$v);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    // ============================================================
    // STATS
    // ============================================================

    public function getStats(?int $days=null): array
    {
        $db=config::getConnexion();
        $conds=[]; $params=[];
        if ($days) { $conds[]="date_creation >= DATE_SUB(NOW(), INTERVAL :days DAY)"; $params['days']=$days; }
        if ($this->isAdminAgence() && isset($_SESSION['id_agence'])) {
            $conds[] = "user.role != 'superadmin'";
            $conds[] = "(ag.id_agence=:ag OR a.id_agence=:ag2 OR c.id_agence=:ag3)";
            $params['ag'] = (int)$_SESSION['id_agence']; 
            $params['ag2'] = (int)$_SESSION['id_agence']; 
            $params['ag3'] = (int)$_SESSION['id_agence'];
            $join = "LEFT JOIN agent ag ON user.id_user=ag.id_user LEFT JOIN admin a ON user.id_user=a.id_user LEFT JOIN client c ON user.id_user=c.id_user";
        } else { $join = ""; }
        $where=empty($conds)?'':' WHERE '.implode(' AND ',$conds);
        $stmt=$db->prepare("SELECT COUNT(*) AS total, SUM(statut='actif') AS actifs, SUM(statut='bloque') AS bloques, SUM(role='superadmin') AS superadmins, SUM(role='admin') AS admins, SUM(role='agent') AS agents, SUM(role='client') AS clients FROM user $join $where");
        foreach($params as $k=>$v) $stmt->bindValue(":$k",$v,$k==='days'?\PDO::PARAM_INT:\PDO::PARAM_INT);
        $stmt->execute();
        return array_map('intval',$stmt->fetch(\PDO::FETCH_ASSOC));
    }

    public function getAdvancedStats(?int $days=null): array
    {
        $db=config::getConnexion();
        $stats=$this->getStats($days);
        
        // Apply agency filter if admin_agence
        $agencyFilter = '';
        $params = [];
        if ($this->isAdminAgence() && ($agId = $this->getSessionAgence())) {
            $agencyFilter = " LEFT JOIN agent ag ON u.id_user=ag.id_user LEFT JOIN admin a ON u.id_user=a.id_user LEFT JOIN client c ON u.id_user=c.id_user WHERE u.role != 'superadmin' AND (ag.id_agence=:ag OR a.id_agence=:ag2 OR c.id_agence=:ag3)";
            $params = ['ag' => $agId, 'ag2' => $agId, 'ag3' => $agId];
        }
        
        // users_by_month with agency filter
        $stmt = $db->prepare("SELECT DATE_FORMAT(u.date_creation,'%Y-%m') AS month,COUNT(*) AS cnt FROM user u" . ($agencyFilter ? $agencyFilter : "") . " GROUP BY month ORDER BY month ASC LIMIT 12");
        $stmt->execute($params);
        $stats['users_by_month'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // by_role with agency filter
        $stmt = $db->prepare("SELECT u.role,COUNT(*) AS cnt FROM user u" . ($agencyFilter ? $agencyFilter : "") . " GROUP BY u.role");
        $stmt->execute($params);
        $stats['by_role'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // connections_by_day with agency filter
        if ($agencyFilter) {
            // agencyFilter already contains WHERE clause
            $connSql = "SELECT DATE(u.last_login) AS jour,COUNT(*) AS cnt FROM user u" . $agencyFilter . " AND u.last_login>=DATE_SUB(CURDATE(),INTERVAL 7 DAY) GROUP BY jour ORDER BY jour ASC";
        } else {
            $connSql = "SELECT DATE(u.last_login) AS jour,COUNT(*) AS cnt FROM user u WHERE u.last_login>=DATE_SUB(CURDATE(),INTERVAL 7 DAY) GROUP BY jour ORDER BY jour ASC";
        }
        $stmt = $db->prepare($connSql);
        $stmt->execute($params);
        $stats['connections_by_day'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // new_this_month: inscriptions du mois courant
        $nmWhere = $agencyFilter ? $agencyFilter . " AND" : " WHERE";
        $stmtNtm = $db->prepare("SELECT COUNT(*) FROM user u{$nmWhere} MONTH(u.date_creation)=MONTH(CURDATE()) AND YEAR(u.date_creation)=YEAR(CURDATE())");
        $stmtNtm->execute($params);
        $newThisMonth = (int)$stmtNtm->fetchColumn();
        $stats['new_this_month'] = $newThisMonth;

        // evolution vs mois précédent
        $stmtPrev = $db->prepare("SELECT COUNT(*) FROM user u{$nmWhere} MONTH(u.date_creation)=MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) AND YEAR(u.date_creation)=YEAR(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))");
        $stmtPrev->execute($params);
        $prevMonth = (int)$stmtPrev->fetchColumn();
        $stats['evolution'] = $prevMonth > 0 ? round((($newThisMonth - $prevMonth) / $prevMonth) * 100) : null;

        // online_users: ceux vus ces 15 dernières minutes
        $onlineSql = "SELECT u.id_user, u.nom, u.prenom, u.role, u.avatar, u.avatar_url, u.last_seen 
                      FROM user u" . ($agencyFilter ? $agencyFilter . " AND" : " WHERE") . " 
                      u.last_seen >= DATE_SUB(NOW(), INTERVAL 15 MINUTE) 
                      ORDER BY u.last_seen DESC LIMIT 10";
        $stmtOnline = $db->prepare($onlineSql);
        $stmtOnline->execute($params);
        $stats['online_users'] = $stmtOnline->fetchAll(\PDO::FETCH_ASSOC);

        return $stats;
    }

    // ============================================================
    // RÉSEAU SOCIAL
    // ============================================================

    public function handleFriendAction(int $my_id, int $friend_id, string $action): array {
        $db = config::getConnexion();
        if ($action === 'add') {
            if ($friend_id === $my_id) throw new \Exception("Impossible de s'ajouter soi-même");
            
            // Vérifier si une relation existe déjà (dans n'importe quel sens)
            $stmt = $db->prepare("SELECT status, sender_id FROM friendships WHERE (sender_id = :u AND receiver_id = :f) OR (sender_id = :f2 AND receiver_id = :u2)");
            $stmt->execute(['u' => $my_id, 'f' => $friend_id, 'f2' => $friend_id, 'u2' => $my_id]);
            $existing = $stmt->fetch();

            if ($existing) {
                if ($existing['status'] === 'accepted') return ["success" => false, "message" => "Vous êtes déjà amis"];
                if ($existing['status'] === 'pending') {
                    if ($existing['sender_id'] == $my_id) return ["success" => false, "message" => "Invitation déjà envoyée"];
                    else return ["success" => true, "message" => "Vous avez déjà une invitation de cet utilisateur, allez dans l'onglet 'Invitations'"];
                }
                // Si c'est un autre statut (ex: blocked ou rejected), on peut décider de laisser l'erreur ou de gérer. 
                // Pour l'instant on bloque pour éviter le duplicata.
                return ["success" => false, "message" => "Une relation existe déjà ou a été refusée"];
            }

            $stmt = $db->prepare("INSERT INTO friendships (sender_id, receiver_id, status) VALUES (:u, :f, 'pending')");
            $stmt->execute(['u' => $my_id, 'f' => $friend_id]);
            return ["success" => true, "message" => "Invitation envoyée"];
        } elseif ($action === 'accept') {
            $stmt = $db->prepare("UPDATE friendships SET status = 'accepted' WHERE sender_id = :f AND receiver_id = :u");
            $stmt->execute(['f' => $friend_id, 'u' => $my_id]);
            return ["success" => true, "message" => "Invitation acceptée"];
        } elseif ($action === 'remove') {
            $stmt = $db->prepare("DELETE FROM friendships WHERE (sender_id = :u AND receiver_id = :f) OR (sender_id = :f2 AND receiver_id = :u2)");
            $stmt->execute(['u' => $my_id, 'f' => $friend_id, 'f2' => $friend_id, 'u2' => $my_id]);
            return ["success" => true, "message" => "Contact supprimé"];
        }
        throw new \Exception("Action inconnue");
    }

    public function getSocialData(int $my_id): array {
        $db = config::getConnexion();
        
        // Mise à jour last_seen
        $db->prepare("UPDATE user SET last_seen = NOW() WHERE id_user = ?")->execute([$my_id]);
        
        // Amis (avec is_online calculé SQL, respecte hide_online_status)
        $stmt = $db->prepare("
            SELECT u.id_user, u.nom, u.prenom, u.avatar_url, u.role, u.last_seen, f.is_trusted,
                   CASE WHEN u.hide_online_status = 1 THEN 0
                        WHEN u.last_seen IS NOT NULL AND TIMESTAMPDIFF(MINUTE, u.last_seen, NOW()) < 5
                        THEN 1 ELSE 0 END AS is_online
            FROM user u
            JOIN friendships f ON (u.id_user = f.receiver_id OR u.id_user = f.sender_id)
            WHERE (f.sender_id = :u1 OR f.receiver_id = :u2)
              AND u.id_user != :u3
              AND f.status = 'accepted'");
        $stmt->execute(['u1' => $my_id, 'u2' => $my_id, 'u3' => $my_id]);
        $friends = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // En attente
        $stmt = $db->prepare("SELECT u.id_user, u.nom, u.prenom, u.avatar_url FROM user u JOIN friendships f ON u.id_user = f.sender_id WHERE f.receiver_id = :u AND f.status = 'pending'");
        $stmt->execute(['u' => $my_id]);
        $pending = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Obtenir l'agence de l'utilisateur courant s'il est client
        $stmt_agence = $db->prepare("SELECT id_agence FROM client WHERE id_user = :u");
        $stmt_agence->execute(['u' => $my_id]);
        $mon_agence = $stmt_agence->fetchColumn();

        // Suggestions (clients de la même agence, non amis, pas d'invitation en cours)
        if ($mon_agence) {
            $stmt = $db->prepare("
                SELECT u.id_user, u.nom, u.prenom, u.avatar_url 
                FROM user u 
                JOIN client c ON u.id_user = c.id_user 
                WHERE c.id_agence = :agence 
                  AND u.id_user != :u1 
                  AND u.id_user NOT IN (
                      SELECT receiver_id FROM friendships WHERE sender_id = :u2 
                      UNION 
                      SELECT sender_id FROM friendships WHERE receiver_id = :u3
                  ) 
                LIMIT 10
            ");
            $stmt->execute(['agence' => $mon_agence, 'u1' => $my_id, 'u2' => $my_id, 'u3' => $my_id]);
            $suggestions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $suggestions = []; // Si pas de client ou pas d'agence, pas de suggestion spécifique
        }

        return ["friends" => $friends, "pending" => $pending, "suggestions" => $suggestions];
    }

    public function toggleTrust(int $my_id, int $friend_id): array {
        $db = config::getConnexion();
        $check = $db->prepare("SELECT id, is_trusted FROM friendships WHERE status='accepted' AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))");
        $check->execute([$my_id, $friend_id, $friend_id, $my_id]);
        $rel = $check->fetch(\PDO::FETCH_ASSOC);
        if (!$rel) throw new \Exception("Vous n'êtes pas amis.");
        $new_status = $rel['is_trusted'] ? 0 : 1;
        
        // Limite : maximum 3 contacts de confiance
        if ($new_status === 1) {
            $count = $db->prepare("SELECT COUNT(*) FROM friendships WHERE (sender_id = ? OR receiver_id = ?) AND is_trusted = 1 AND status = 'accepted'");
            $count->execute([$my_id, $my_id]);
            if ($count->fetchColumn() >= 3) {
                return ["success" => false, "message" => "Maximum 3 contacts de confiance autorisés"];
            }
        }
        
        $db->prepare("UPDATE friendships SET is_trusted = ? WHERE id = ?")->execute([$new_status, $rel['id']]);
        return ["success" => true, "is_trusted" => $new_status, "message" => $new_status ? "Ajouté aux contacts de confiance" : "Retiré des contacts de confiance"];
    }


    public function triggerSOS(int $my_id, ?float $lat = null, ?float $lng = null, ?int $accuracy = null): array {
        $db = config::getConnexion();

        // Récupérer les infos de l'expéditeur
        $senderStmt = $db->prepare("SELECT nom, prenom FROM user WHERE id_user = :id");
        $senderStmt->execute(['id' => $my_id]);
        $sender = $senderStmt->fetch(\PDO::FETCH_ASSOC);
        if (!$sender) throw new \Exception("Utilisateur introuvable.");

        // Construire le lien Google Maps si position disponible
        $mapsLink = null;
        if ($lat !== null && $lng !== null) {
            $mapsLink = "https://www.google.com/maps?q={$lat},{$lng}";
        }

        // Récupérer les contacts de confiance avec leur email
        $stmt = $db->prepare("
            SELECT u.id_user, u.prenom, u.nom, u.email
            FROM user u
            JOIN friendships f ON (u.id_user = f.receiver_id OR u.id_user = f.sender_id)
            WHERE (f.sender_id = :u1 OR f.receiver_id = :u2)
              AND u.id_user != :u3
              AND f.status = 'accepted'
              AND f.is_trusted = 1
        ");
        $stmt->execute(['u1' => $my_id, 'u2' => $my_id, 'u3' => $my_id]);
        $trusted = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (count($trusted) === 0) {
            throw new \Exception("Aucun contact de confiance défini !");
        }

        $mailer = new Mailer();
        $sent = 0;
        $errors = [];

        foreach ($trusted as $contact) {
            try {
                $mailer->sendSOS(
                    $contact['email'],
                    $contact['prenom'],
                    $sender['nom'],
                    $sender['prenom'],
                    $mapsLink,
                    $accuracy
                );
                $sent++;
            } catch (\Exception $e) {
                error_log("SOS email failed for {$contact['email']}: " . $e->getMessage());
                $errors[] = $contact['prenom'];
            }
        }

        $names = implode(', ', array_column($trusted, 'prenom'));

        if ($sent === 0) {
            return ["success" => false, "message" => "Echec d'envoi des alertes SOS. Vérifiez la configuration email."];
        }

        $locMsg = $mapsLink ? " 📍 Position GPS incluse." : " (sans position GPS).";
        $msg = "🆘 Alerte SOS envoyée à {$sent} contact(s) de confiance ({$names}).{$locMsg}";
        if (!empty($errors)) {
            $msg .= " Echec pour : " . implode(', ', $errors) . ".";
        }

        if ($sent > 0) {
            $stmt = $db->prepare("INSERT INTO sos_alerts (user_id, lat, lng, accuracy, nb_contacts_alertes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$my_id, $lat, $lng, $accuracy, $sent]);
        }

        return ["success" => true, "message" => $msg];
    }

    public function getSOSHistory(int $my_id): array {
        $db = config::getConnexion();
        $stmt = $db->prepare("SELECT lat, lng, accuracy, nb_contacts_alertes, created_at FROM sos_alerts WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$my_id]);
        return ["success" => true, "history" => $stmt->fetchAll(\PDO::FETCH_ASSOC)];
    }

    public function ensureMessagesTable(): void {
        $db = config::getConnexion();
        $db->exec("CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            INDEX idx_messages_sender_receiver (sender_id, receiver_id),
            INDEX idx_messages_receiver_read (receiver_id, is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function getMessages(int $my_id, int $friend_id): array {
        $db = config::getConnexion();
        $this->ensureMessagesTable();
        $db->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?")->execute([$friend_id, $my_id]);
        $stmt = $db->prepare("SELECT id, sender_id, content, created_at, is_read FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
        $stmt->execute([$my_id, $friend_id, $friend_id, $my_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function sendMessage(int $my_id, int $friend_id, string $content): bool {
        if (empty(trim($content))) return false;
        $db = config::getConnexion();
        $this->ensureMessagesTable();
        $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
        return $stmt->execute([$my_id, $friend_id, $content]);
    }
}


