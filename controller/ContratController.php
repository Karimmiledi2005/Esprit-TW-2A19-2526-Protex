<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers/RoleHelper.php';
require_once __DIR__ . '/../helpers/TunnelHelper.php';

require_once __DIR__ . '/../connexion.php';
require_once __DIR__ . '/../model/Contrat.php';
require_once __DIR__ . '/../service/SmsService.php';

class ContratController
{
    private PDO $db;

    public function __construct()
    {
        if (!RoleHelper::getUserId()) {
            header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/view/FrontOffice/login.php');
            exit;
        }
        $this->db = config::getConnexion();
    }

    private function columnExists(string $table, string $column): bool
    {
        $sql = "SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = :table
                AND COLUMN_NAME = :column";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['table' => $table, 'column' => $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function getUserColumn(): string
    {
        return $this->columnExists('contrat', 'id_user') ? 'id_user' : 'id_client';
    }

    private function selectSql(string $where = ''): string
    {
        $userColumn = $this->getUserColumn();
        $formuleSelect = $this->columnExists('contrat', 'id_formule') ? ', f.nom_formule, f.prix_formule, f.franchise_formule' : ', NULL AS nom_formule, NULL AS prix_formule, NULL AS franchise_formule';
        $formuleJoin = $this->columnExists('contrat', 'id_formule') ? 'LEFT JOIN formule f ON c.id_formule = f.id_formule' : '';

        // Join with client to get id_agence for RBAC
        return "SELECT c.*, c.$userColumn AS id_client, cat.nom_categorie, u.nom, u.prenom, u.email, 
                       u.telephone AS telephone_client, cl.id_agence $formuleSelect
                FROM contrat c
                LEFT JOIN categorie cat ON c.id_categorie = cat.id_categorie
                LEFT JOIN `user` u ON c.$userColumn = u.id_user
                LEFT JOIN `client` cl ON u.id_user = cl.id_user
                $formuleJoin
                $where";
    }

    private function hydrate(array $row): Contrat
    {
        $contrat = new Contrat(
            $row['numero_contrat'],
            $row['type_contrat'],
            (int)$row['id_client'],
            (int)$row['id_categorie'],
            (float)$row['prime_contrat'],
            (float)$row['franchise_contrat'],
            $row['date_debut_contrat'],
            $row['date_fin_contrat'],
            $row['statut_contrat'],
            $row['id_formule'] ?? null,
            $row['formule_contrat'] ?? ($row['nom_formule'] ?? null),
            $row['details_contrat'] ?? null
        );

        $contrat->setIdContrat($row['id_contrat']);
        $contrat->setNomCategorie($row['nom_categorie'] ?? '—');
        $contrat->setNomFormule($row['nom_formule'] ?? ($row['formule_contrat'] ?? '—'));
        $contrat->setNomClient($row['nom'] ?? '');
        $contrat->setPrenomClient($row['prenom'] ?? '');
        $contrat->setEmailClient($row['email'] ?? '');
        return $contrat;
    }

    public function getAll(): array
    {
        $role   = RoleHelper::getRole();
        $agence = RoleHelper::getAgenceId();

        $where = '';
        $params = [];

        if ($role !== 'superadmin' && $agence) {
            $where = "WHERE cl.id_agence = :agence";
            $params[':agence'] = $agence;
        }

        $sql = $this->selectSql($where . ' ORDER BY c.id_contrat DESC');
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return array_map(fn($row) => $this->hydrate($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getByClient(int $userId): array
    {
        if (!$userId) return [];
        $userColumn = $this->getUserColumn();
        $stmt = $this->db->prepare($this->selectSql("WHERE c.$userColumn = :id_client ORDER BY c.id_contrat DESC"));
        $stmt->execute(['id_client' => $userId]);
        return array_map(fn($row) => $this->hydrate($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findById(int $id): ?Contrat
    {
        $row = $this->getById($id);
        return $row ? $this->hydrate($row) : null;
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare($this->selectSql('WHERE c.id_contrat = :id LIMIT 1'));
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getFirstClientId(): ?int
    {
        $stmt = $this->db->query("SELECT id_user FROM `user` ORDER BY id_user ASC LIMIT 1");
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }

    public function getAllFormules(): array
    {
        $stmt = $this->db->query("SELECT f.*, c.nom_categorie FROM formule f LEFT JOIN categorie c ON c.id_categorie = f.id_categorie ORDER BY c.nom_categorie ASC, f.id_formule ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFormulesByCategorie(int $idCategorie): array
    {
        $stmt = $this->db->prepare("SELECT * FROM formule WHERE id_categorie = :cat ORDER BY id_formule ASC");
        $stmt->execute(['cat' => $idCategorie]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFormuleById(int $idFormule): ?array
    {
        $stmt = $this->db->prepare("SELECT f.*, c.nom_categorie FROM formule f LEFT JOIN categorie c ON c.id_categorie = f.id_categorie WHERE f.id_formule = :id LIMIT 1");
        $stmt->execute(['id' => $idFormule]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getFormuleByNameAndCategorie(string $formuleName, int $idCategorie): ?array
    {
        $stmt = $this->db->prepare("SELECT f.*, c.nom_categorie FROM formule f LEFT JOIN categorie c ON c.id_categorie = f.id_categorie WHERE f.nom_formule = :nom AND f.id_categorie = :cat LIMIT 1");
        $stmt->execute(['nom' => $formuleName, 'cat' => $idCategorie]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function generateNumero(): string
    {
        do {
            $numero = 'CTR-' . date('Y') . '-' . str_pad((string)random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM contrat WHERE numero_contrat = :numero");
            $stmt->execute(['numero' => $numero]);
        } while ((int)$stmt->fetchColumn() > 0);
        return $numero;
    }

    public function addContrat($contrat): bool
    {
        // ── Vérifier que la date de fin est postérieure à la date de début ──
        $debut = $contrat->getDateDebutContrat();
        $fin   = $contrat->getDateFinContrat();
        if ($fin && $debut && $fin <= $debut) {
            return false;
        }

        $userColumn = $this->getUserColumn();
        $columns = "numero_contrat, type_contrat, $userColumn, id_categorie, prime_contrat, franchise_contrat, date_debut_contrat, date_fin_contrat, statut_contrat";
        $values  = ":numero_contrat, :type_contrat, :id_client, :id_categorie, :prime_contrat, :franchise_contrat, :date_debut_contrat, :date_fin_contrat, :statut_contrat";
        $params = [
            'numero_contrat' => $contrat->getNumeroContrat(),
            'type_contrat' => $contrat->getTypeContrat(),
            'id_client' => $contrat->getIdClient(),
            'id_categorie' => $contrat->getIdCategorie(),
            'prime_contrat' => $contrat->getPrimeContrat(),
            'franchise_contrat' => $contrat->getFranchiseContrat(),
            'date_debut_contrat' => $contrat->getDateDebutContrat(),
            'date_fin_contrat' => $contrat->getDateFinContrat(),
            'statut_contrat' => $contrat->getStatutContrat()
        ];

        if ($this->columnExists('contrat', 'id_formule')) {
            $columns .= ", id_formule";
            $values .= ", :id_formule";
            $params['id_formule'] = $contrat->getIdFormule();
        }
        if ($this->columnExists('contrat', 'formule_contrat')) {
            $columns .= ", formule_contrat";
            $values .= ", :formule_contrat";
            $params['formule_contrat'] = $contrat->getFormuleContrat();
        }
        if ($this->columnExists('contrat', 'details_contrat')) {
            $columns .= ", details_contrat";
            $values .= ", :details_contrat";
            $params['details_contrat'] = $contrat->getDetailsContrat();
        }

        $query = $this->db->prepare("INSERT INTO contrat ($columns) VALUES ($values)");
        return $query->execute($params);
    }

    public function updateContrat(int $id, $contrat): bool
    {
        // ── Vérifier que la date de fin est postérieure à la date de début ──
        $debut = $contrat->getDateDebutContrat();
        $fin   = $contrat->getDateFinContrat();
        if ($fin && $debut && $fin <= $debut) {
            return false;
        }

        // ── Valider la transition de statut ──
        $oldRow = $this->getById($id);
        if ($oldRow) {
            $oldStatut = strtolower(trim((string)($oldRow['statut_contrat'] ?? '')));
            $newStatut = strtolower(trim((string)$contrat->getStatutContrat()));
            $forbidden = [
                'résilié' => ['actif', 'en attente'],
                'expiré'  => ['actif', 'en attente'],
                'refusé'  => ['actif'],
            ];
            if (isset($forbidden[$oldStatut]) && in_array($newStatut, $forbidden[$oldStatut], true)) {
                return false;
            }
        }

        $userColumn = $this->getUserColumn();
        $set = "numero_contrat = :numero_contrat,
                type_contrat = :type_contrat,
                $userColumn = :id_client,
                id_categorie = :id_categorie,
                prime_contrat = :prime_contrat,
                franchise_contrat = :franchise_contrat,
                date_debut_contrat = :date_debut_contrat,
                date_fin_contrat = :date_fin_contrat,
                statut_contrat = :statut_contrat";
        $params = [
            'id' => $id,
            'numero_contrat' => $contrat->getNumeroContrat(),
            'type_contrat' => $contrat->getTypeContrat(),
            'id_client' => $contrat->getIdClient(),
            'id_categorie' => $contrat->getIdCategorie(),
            'prime_contrat' => $contrat->getPrimeContrat(),
            'franchise_contrat' => $contrat->getFranchiseContrat(),
            'date_debut_contrat' => $contrat->getDateDebutContrat(),
            'date_fin_contrat' => $contrat->getDateFinContrat(),
            'statut_contrat' => $contrat->getStatutContrat()
        ];
        if ($this->columnExists('contrat', 'id_formule')) {
            $set .= ", id_formule = :id_formule";
            $params['id_formule'] = $contrat->getIdFormule();
        }
        if ($this->columnExists('contrat', 'formule_contrat')) {
            $set .= ", formule_contrat = :formule_contrat";
            $params['formule_contrat'] = $contrat->getFormuleContrat();
        }
        if ($this->columnExists('contrat', 'details_contrat')) {
            $set .= ", details_contrat = :details_contrat";
            $params['details_contrat'] = $contrat->getDetailsContrat();
        }
        $query = $this->db->prepare("UPDATE contrat SET $set WHERE id_contrat = :id");
        $result = $query->execute($params);

        // C3: Log changes to contrat_historique
        if ($result && $oldRow) {
            $this->logContratChanges($id, $oldRow, $contrat);
        }

        return $result;
    }

    /**
     * C3: Compare old row vs new contrat object and log each changed field.
     */
    private function logContratChanges(int $idContrat, array $oldRow, $newContrat): void
    {
        $userId = RoleHelper::getUserId() ?: 0;

        $fieldsMap = [
            'numero_contrat'     => fn() => $newContrat->getNumeroContrat(),
            'type_contrat'       => fn() => $newContrat->getTypeContrat(),
            'prime_contrat'      => fn() => (string)$newContrat->getPrimeContrat(),
            'franchise_contrat'  => fn() => (string)$newContrat->getFranchiseContrat(),
            'date_debut_contrat' => fn() => $newContrat->getDateDebutContrat(),
            'date_fin_contrat'   => fn() => $newContrat->getDateFinContrat(),
            'statut_contrat'     => fn() => $newContrat->getStatutContrat(),
        ];

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO contrat_historique (id_contrat, id_user, champ_modifie, ancienne_valeur, nouvelle_valeur)
                 VALUES (:id_contrat, :id_user, :champ, :ancien, :nouveau)"
            );

            foreach ($fieldsMap as $field => $getter) {
                $oldVal = trim((string)($oldRow[$field] ?? ''));
                $newVal = trim((string)$getter());
                if ($oldVal !== $newVal) {
                    $stmt->execute([
                        ':id_contrat' => $idContrat,
                        ':id_user'    => $userId,
                        ':champ'      => $field,
                        ':ancien'     => $oldVal,
                        ':nouveau'    => $newVal,
                    ]);
                }
            }
        } catch (Throwable $e) {
            error_log('contrat_historique insert error: ' . $e->getMessage());
        }
    }


public function updateStatut(int $id, string $statut): bool
{
    $allowed = ['en attente', 'actif', 'expiré', 'résilié', 'refusé'];

    if (!in_array($statut, $allowed, true)) {
        return false;
    }

    // ── Lire le statut actuel avant modification ──
    $cur = $this->db->prepare("SELECT statut_contrat FROM contrat WHERE id_contrat = ?");
    $cur->execute([$id]);
    $currentStatut = $cur->fetchColumn();
    if (!$currentStatut) return false;

    // ── Transitions interdites ──
    $forbidden = [
        'résilié' => ['actif', 'en attente'],
        'expiré'  => ['actif', 'en attente'],
        'refusé'  => ['actif'],
    ];
    if (isset($forbidden[$currentStatut]) && in_array($statut, $forbidden[$currentStatut], true)) {
        return false;
    }

    // ── Passage en 'actif' : vérifier qu'au moins un paiement validé existe ──
    if ($statut === 'actif') {
        $paid = $this->db->prepare("SELECT COUNT(*) FROM paiement WHERE id_contrat = ? AND statut = 'valide'");
        $paid->execute([$id]);
        if (!(int)$paid->fetchColumn()) {
            return false;
        }
    }

    $stmt = $this->db->prepare("
        UPDATE contrat
        SET statut_contrat = :statut
        WHERE id_contrat = :id
    ");

    $updated = $stmt->execute([
        'statut' => $statut,
        'id' => $id
    ]);

    if (!$updated) {
        return false;
    }

    // Après changement de statut, on récupère le contrat avec les infos user.
    $contrat = $this->getById($id);
    if (!$contrat) {
        return true;
    }

    $telephone = $this->extractTelephoneFromContratRow($contrat);
    if ($telephone === '') {
        $this->logSmsStatusChange($contrat, $statut, '', 'Aucun téléphone trouvé.', 'sans_telephone', null, null, []);
        return true;
    }

    $details = [];
    if (!empty($contrat['details_contrat'])) {
        $decoded = json_decode((string)$contrat['details_contrat'], true);
        if (is_array($decoded)) {
            $details = $decoded;
        }
    }

    $client = trim((string)($contrat['prenom'] ?? '') . ' ' . (string)($contrat['nom'] ?? ''));
    if ($client === '' && (!empty($details['prenom']) || !empty($details['nom']))) {
        $client = trim((string)($details['prenom'] ?? '') . ' ' . (string)($details['nom'] ?? ''));
    }
    if ($client === '') {
        $client = 'cher client';
    }

    $numeroContrat = (string)($contrat['numero_contrat'] ?? ('#' . $id));

    $messages = [
        'actif' => "Bonjour $client, votre contrat Protex $numeroContrat est maintenant actif.",
        'refusé' => "Bonjour $client, votre demande de contrat Protex $numeroContrat a été refusée.",
        'résilié' => "Bonjour $client, votre contrat Protex $numeroContrat a été résilié.",
        'expiré' => "Bonjour $client, votre contrat Protex $numeroContrat est expiré.",
        'en attente' => "Bonjour $client, votre contrat Protex $numeroContrat est en attente de traitement."
    ];

    $message = $messages[$statut] ?? "Bonjour $client, le statut de votre contrat Protex $numeroContrat est maintenant : $statut.";

    $smsResult = SmsService::sendSms($telephone, $message);
    $response = $smsResult['response'] ?? [];
    $firstMessage = is_array($response) ? ($response['messages'][0] ?? []) : [];

    $messageId = is_array($firstMessage) ? ($firstMessage['messageId'] ?? null) : null;
    $bulkId = is_array($response) ? ($response['bulkId'] ?? null) : null;
    $smsStatus = is_array($firstMessage) ? ($firstMessage['status']['name'] ?? null) : null;
    $success = !empty($smsResult['success']);
    $statutSms = $success ? ($smsStatus ?: 'sent') : 'failed';

    $typeAlert = 'changement_statut_' . str_replace(
        ['é', 'è', 'ê', 'à', 'ù', 'ç', ' '],
        ['e', 'e', 'e', 'a', 'u', 'c', '_'],
        strtolower($statut)
    );

    $this->saveSmsAlert(
        $id,
        isset($contrat['id_client']) ? (int)$contrat['id_client'] : null,
        $telephone,
        $message,
        $typeAlert,
        $statutSms,
        $messageId,
        $bulkId,
        is_array($smsResult) ? $smsResult : ['response' => $smsResult]
    );

    $this->logSmsStatusChange(
        $contrat,
        $statut,
        $telephone,
        $message,
        $statutSms,
        $messageId,
        $bulkId,
        is_array($smsResult) ? $smsResult : ['response' => $smsResult]
    );

    $uid = isset($contrat['id_client']) ? (int)$contrat['id_client'] : 0;
    if ($uid) {
        $notifMsg = $messages[$statut] ?? "Le statut de votre contrat $numeroContrat est maintenant : $statut.";
        $this->db->prepare("INSERT INTO notification (id_user, message, type, lien) VALUES (?, ?, 'contrat', ?)")
            ->execute([$uid, $notifMsg, '/view/FrontOffice/contrat.php']);
    }

    return true;
}

    private function logSmsStatusChange(array $contrat, string $newStatus, string $telephone, string $message, string $smsStatus, ?string $messageId, ?string $bulkId, array $response): void
    {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        file_put_contents(
            $logDir . '/sms_expiration.log',
            "[" . date('Y-m-d H:i:s') . "] STATUS CHANGE SMS\n" .
            "Contract: " . ($contrat['numero_contrat'] ?? '') . "\n" .
            "New Status: " . $newStatus . "\n" .
            "Phone: " . ($telephone ?: 'N/A') . "\n" .
            "Message: " . ($message ?: 'N/A') . "\n" .
            "SMS Status: " . $smsStatus . "\n" .
            "Message ID: " . ($messageId ?? 'N/A') . "\n" .
            "Bulk ID: " . ($bulkId ?? 'N/A') . "\n" .
            "Response: " . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n" .
            "----------------------------------------\n",
            FILE_APPEND
        );
    }

    public function deleteContrat(int $id): bool
    {
        $query = $this->db->prepare("DELETE FROM contrat WHERE id_contrat = :id");
        return $query->execute(['id' => $id]);
    }

    public function countContrats(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM contrat")->fetchColumn();
    }

    public function getContratsSortedByPrime(string $order = 'ASC'): array
    {
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        $stmt = $this->db->query(
            $this->selectSql("ORDER BY c.prime_contrat $order")
        );

        return array_map(
            fn($row) => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function getContratsSortedByDateDebut(string $order = 'DESC'): array
    {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $stmt = $this->db->query(
            $this->selectSql("ORDER BY c.date_debut_contrat $order")
        );

        return array_map(
            fn($row) => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function searchContrats(string $keyword): array
    {
        $keyword = trim($keyword);
        $role    = RoleHelper::getRole();
        $agence  = RoleHelper::getAgenceId();

        $where = [];
        $params = [];

        if ($keyword !== '') {
            $where[] = "(c.numero_contrat LIKE :keyword
                       OR c.type_contrat LIKE :keyword
                       OR cat.nom_categorie LIKE :keyword
                       OR u.nom LIKE :keyword
                       OR u.prenom LIKE :keyword
                       OR u.email LIKE :keyword
                       OR f.nom_formule LIKE :keyword)";
            $params[':keyword'] = '%' . $keyword . '%';
        }

        if ($role !== 'superadmin' && $agence) {
            $where[] = "cl.id_agence = :agence";
            $params[':agence'] = $agence;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = $this->selectSql($whereSql . ' ORDER BY c.id_contrat DESC');

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map(
            fn($row) => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function getContratsByStatut(string $statut): array
    {
        $allowed = ['en attente', 'actif', 'expiré', 'résilié', 'refusé'];

        if (!in_array($statut, $allowed, true)) {
            return [];
        }

        $stmt = $this->db->prepare(
            $this->selectSql("WHERE c.statut_contrat = :statut ORDER BY c.id_contrat DESC")
        );

        $stmt->execute([
            'statut' => $statut
        ]);

        return array_map(
            fn($row) => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function countContratsByStatut(string $statut): int
    {
        $allowed = ['en attente', 'actif', 'expiré', 'résilié', 'refusé'];

        if (!in_array($statut, $allowed, true)) {
            return 0;
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM contrat
            WHERE statut_contrat = :statut
        ");

        $stmt->execute([
            'statut' => $statut
        ]);

        return (int)$stmt->fetchColumn();
    }


    public function getGarantiesByContrat(int $idContrat): array
    {
        if ($idContrat <= 0) {
            return [];
        }

        $hasIdFormule = $this->columnExists('contrat', 'id_formule');
        $hasFormuleContrat = $this->columnExists('contrat', 'formule_contrat');

        $joinCondition = $hasIdFormule
            ? "c.id_formule = f.id_formule"
            : "1 = 0";

        if ($hasFormuleContrat) {
            $joinCondition .= " OR (c.formule_contrat = f.nom_formule AND c.id_categorie = f.id_categorie)";
        }

        $sql = "
            SELECT DISTINCT
                g.id_garantie,
                g.nom_garantie,
                g.description_garantie,
                g.plafond_couvert_garantie,
                fg.niveau_couvert_garantie,
                f.nom_formule
            FROM contrat c
            INNER JOIN formule f ON ($joinCondition)
            INNER JOIN formule_garantie fg ON f.id_formule = fg.id_formule
            INNER JOIN garantie g ON fg.id_garantie = g.id_garantie
            WHERE c.id_contrat = :id_contrat
            ORDER BY
                CASE
                    WHEN fg.niveau_couvert_garantie = 'basique' THEN 1
                    WHEN fg.niveau_couvert_garantie = 'option' THEN 2
                    ELSE 3
                END,
                g.nom_garantie ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id_contrat' => $idContrat
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getSinistresByContrat(int $idContrat): array {
        require_once __DIR__ . '/SinistreController.php';
        $sc = new SinistreController();
        return $sc->getByContrat($idContrat);
    }


    public function getContratsExpirantBientot(int $days = 30): array
    {
        $days = max(1, min($days, 365));

        // Compatibilité intégration User : certains imports utilisent contrat.id_user,
        // d'autres anciens fichiers utilisent encore contrat.id_client.
        $userColumn = $this->getUserColumn();

        $telephoneSelect = $this->columnExists('user', 'telephone')
            ? ', u.telephone AS telephone_client'
            : ', NULL AS telephone_client';

        $formuleSelect = $this->columnExists('contrat', 'id_formule')
            ? ', f.nom_formule, f.prix_formule, f.franchise_formule'
            : ', NULL AS nom_formule, NULL AS prix_formule, NULL AS franchise_formule';

        $formuleJoin = $this->columnExists('contrat', 'id_formule')
            ? 'LEFT JOIN formule f ON c.id_formule = f.id_formule'
            : '';

        $sql = "
            SELECT
                c.*,
                cat.nom_categorie,
                u.nom,
                u.prenom,
                u.email
                $telephoneSelect
                $formuleSelect
            FROM contrat c
            LEFT JOIN categorie cat ON c.id_categorie = cat.id_categorie
            LEFT JOIN `user` u ON c.$userColumn = u.id_user
            $formuleJoin
            WHERE c.statut_contrat = 'actif'
              AND c.date_fin_contrat >= CURDATE()
              AND c.date_fin_contrat <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
            ORDER BY c.date_fin_contrat ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            $row['telephone_final'] = $this->extractTelephoneFromContratRow($row);
            $row['jours_restants'] = $this->daysUntil($row['date_fin_contrat'] ?? null);
        }
        unset($row);

        return $rows;
    }

    private function extractTelephoneFromContratRow(array $row): string
    {
        $details = [];
        if (!empty($row['details_contrat'])) {
            $decoded = json_decode((string)$row['details_contrat'], true);
            if (is_array($decoded)) {
                $details = $decoded;
            }
        }

        $possibleKeys = ['telephone', 'tel', 'phone', 'telephone_client', 'client_telephone'];
        foreach ($possibleKeys as $key) {
            if (!empty($details[$key]) && !is_array($details[$key])) {
                return trim((string)$details[$key]);
            }
        }

        $telephone = trim((string)($row['telephone_client'] ?? ''));
        if ($telephone !== '') {
            return $telephone;
        }

        return '';
    }

    private function daysUntil(?string $date): ?int
    {
        if (!$date) {
            return null;
        }

        try {
            $today = new DateTime(date('Y-m-d'));
            $target = new DateTime($date);
            return (int)$today->diff($target)->format('%r%a');
        } catch (Exception $e) {
            return null;
        }
    }

    private function ensureSmsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS sms_alerts (
                id_alert INT AUTO_INCREMENT PRIMARY KEY,
                id_contrat INT NOT NULL,
                id_client INT NULL,
                telephone VARCHAR(30) NOT NULL,
                message TEXT NOT NULL,
                type_alert VARCHAR(100) NOT NULL DEFAULT 'expiration_contrat',
                statut VARCHAR(80) NOT NULL DEFAULT 'sent',
                infobip_message_id VARCHAR(120) NULL,
                infobip_bulk_id VARCHAR(120) NULL,
                response_json LONGTEXT NULL,
                date_envoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_sms_expiration (id_contrat, type_alert)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";

        $this->db->exec($sql);

        // Si la table existait déjà avec une ancienne structure, on ajoute les colonnes manquantes.
        $columnsToAdd = [
            'id_client' => "ALTER TABLE sms_alerts ADD COLUMN id_client INT NULL AFTER id_contrat",
            'telephone' => "ALTER TABLE sms_alerts ADD COLUMN telephone VARCHAR(30) NOT NULL DEFAULT '' AFTER id_client",
            'message' => "ALTER TABLE sms_alerts ADD COLUMN message TEXT NULL AFTER telephone",
            'type_alert' => "ALTER TABLE sms_alerts ADD COLUMN type_alert VARCHAR(100) NOT NULL DEFAULT 'expiration_contrat' AFTER message",
            'statut' => "ALTER TABLE sms_alerts ADD COLUMN statut VARCHAR(80) NOT NULL DEFAULT 'sent' AFTER type_alert",
            'infobip_message_id' => "ALTER TABLE sms_alerts ADD COLUMN infobip_message_id VARCHAR(120) NULL AFTER statut",
            'infobip_bulk_id' => "ALTER TABLE sms_alerts ADD COLUMN infobip_bulk_id VARCHAR(120) NULL AFTER infobip_message_id",
            'response_json' => "ALTER TABLE sms_alerts ADD COLUMN response_json LONGTEXT NULL AFTER infobip_bulk_id",
            'date_envoi' => "ALTER TABLE sms_alerts ADD COLUMN date_envoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER response_json"
        ];

        foreach ($columnsToAdd as $column => $alterSql) {
            if (!$this->columnExists('sms_alerts', $column)) {
                $this->db->exec($alterSql);
            }
        }

        // Ancienne colonne possible: statut_envoi. Si statut est vide, on récupère sa valeur.
        if ($this->columnExists('sms_alerts', 'statut_envoi')) {
            $this->db->exec("UPDATE sms_alerts SET statut = statut_envoi WHERE (statut IS NULL OR statut = '')");
        }

        // Anti-doublon pour éviter plusieurs SMS expiration pour le même contrat.
        try {
            $this->db->exec("ALTER TABLE sms_alerts ADD UNIQUE KEY unique_sms_expiration (id_contrat, type_alert)");
        } catch (Throwable $e) {
            // Index déjà existant ou doublons déjà présents: on laisse la table utilisable.
        }
    }

    public function smsAlertAlreadySent(int $idContrat, string $typeAlert = 'expiration_contrat'): bool
    {
        $this->ensureSmsTable();

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM sms_alerts
            WHERE id_contrat = :id_contrat
              AND type_alert = :type_alert
              AND statut NOT IN ('failed', 'echec')
        ");

        $stmt->execute([
            'id_contrat' => $idContrat,
            'type_alert' => $typeAlert
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    public function saveSmsAlert(
        int $idContrat,
        ?int $idClient,
        string $telephone,
        string $message,
        string $typeAlert = 'expiration_contrat',
        string $statut = 'sent',
        ?string $messageId = null,
        ?string $bulkId = null,
        ?array $response = null
    ): bool {
        $this->ensureSmsTable();

        $stmt = $this->db->prepare("
            INSERT INTO sms_alerts (
                id_contrat,
                id_client,
                telephone,
                message,
                type_alert,
                statut,
                infobip_message_id,
                infobip_bulk_id,
                response_json,
                date_envoi
            ) VALUES (
                :id_contrat,
                :id_client,
                :telephone,
                :message,
                :type_alert,
                :statut,
                :infobip_message_id,
                :infobip_bulk_id,
                :response_json,
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                id_client = VALUES(id_client),
                telephone = VALUES(telephone),
                message = VALUES(message),
                statut = VALUES(statut),
                infobip_message_id = VALUES(infobip_message_id),
                infobip_bulk_id = VALUES(infobip_bulk_id),
                response_json = VALUES(response_json),
                date_envoi = NOW()
        ");

        return $stmt->execute([
            'id_contrat' => $idContrat,
            'id_client' => $idClient,
            'telephone' => $telephone,
            'message' => $message,
            'type_alert' => $typeAlert,
            'statut' => $statut,
            'infobip_message_id' => $messageId,
            'infobip_bulk_id' => $bulkId,
            'response_json' => $response ? json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null
        ]);
    }

    public function envoyerAlertesSmsExpiration(int $days = 30): array
    {
        $this->ensureSmsTable();

        $contrats = $this->getContratsExpirantBientot($days);

        $result = [
            'total_detectes' => count($contrats),
            'envoyes' => 0,
            'deja_envoyes' => 0,
            'sans_telephone' => 0,
            'erreurs' => 0,
            'details' => []
        ];

        foreach ($contrats as $contrat) {
            $idContrat = (int)$contrat['id_contrat'];
            $telephone = trim((string)($contrat['telephone_final'] ?? ''));

            if ($this->smsAlertAlreadySent($idContrat)) {
                $result['deja_envoyes']++;
                $result['details'][] = [
                    'numero' => $contrat['numero_contrat'] ?? '',
                    'status' => 'deja_envoye',
                    'message' => 'Alerte déjà envoyée pour ce contrat.'
                ];
                continue;
            }

            if ($telephone === '') {
                $result['sans_telephone']++;
                $result['details'][] = [
                    'numero' => $contrat['numero_contrat'] ?? '',
                    'status' => 'sans_telephone',
                    'message' => 'Aucun numéro de téléphone trouvé pour ce client.'
                ];
                continue;
            }

            $jours = (int)($contrat['jours_restants'] ?? 0);
            $client = trim(($contrat['prenom'] ?? '') . ' ' . ($contrat['nom'] ?? ''));
            if ($client === '') {
                $client = 'cher client';
            }

            $numeroContrat = $contrat['numero_contrat'] ?? '';
            $dateFin = date('d/m/Y', strtotime($contrat['date_fin_contrat']));

            $message = "Bonjour $client, votre contrat d'assurance $numeroContrat expire le $dateFin, dans $jours jour(s). Merci de le renouveler avant son expiration.";

            $smsResult = SmsService::send($telephone, $message);
            $response = $smsResult['response'] ?? [];
            $firstMessage = $response['messages'][0] ?? [];

            $messageId = $firstMessage['messageId'] ?? null;
            $bulkId = $response['bulkId'] ?? null;
            $statusName = $firstMessage['status']['name'] ?? null;

            $success = !empty($smsResult['success']);
            $statutSms = $success ? ($statusName ?: 'sent') : 'failed';

            $this->saveSmsAlert(
                $idContrat,
                isset($contrat['id_client']) ? (int)$contrat['id_client'] : null,
                $telephone,
                $message,
                'expiration_contrat',
                $statutSms,
                $messageId,
                $bulkId,
                $smsResult
            );

            if ($success) {
                $result['envoyes']++;
            } else {
                $result['erreurs']++;
            }

            $result['details'][] = [
                'numero' => $numeroContrat,
                'telephone' => $telephone,
                'message' => $message,
                'status' => $statutSms,
                'message_id' => $messageId,
                'bulk_id' => $bulkId,
                'error' => $smsResult['error'] ?? null
            ];
        }

        return $result;
    }

    public function getSmsAlerts(): array
    {
        $this->ensureSmsTable();

        $stmt = $this->db->query("
            SELECT
                sa.*,
                c.numero_contrat,
                c.date_fin_contrat,
                c.statut_contrat
            FROM sms_alerts sa
            LEFT JOIN contrat c ON c.id_contrat = sa.id_contrat
            ORDER BY sa.date_envoi DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function renewContrat(int $idContrat): ?int
    {
        $old = $this->getById($idContrat);

        if (!$old) {
            return null;
        }

        $statut = strtolower((string)($old['statut_contrat'] ?? ''));
        if (!in_array($statut, ['actif', 'expiré', 'résilié'], true)) {
            return null;
        }

        // Vérifier qu'il n'y a pas déjà une demande de renouvellement en attente
        $oldNum = $old['numero_contrat'] ?? ('#' . $idContrat);
        $pendingStmt = $this->db->prepare("
            SELECT COUNT(*) FROM contrat
            WHERE details_contrat LIKE ?
              AND statut_contrat = 'en attente'
        ");
        $pendingStmt->execute(['%"renouvellement_de":"' . $oldNum . '"%']);
        if ((int)$pendingStmt->fetchColumn() > 0) {
            return null;
        }

        $oldDateFin = $old['date_fin_contrat'] ?? date('Y-m-d');
        $startBase = strtotime($oldDateFin) >= strtotime(date('Y-m-d'))
            ? strtotime($oldDateFin . ' +1 day')
            : strtotime(date('Y-m-d'));

        $dateDebut = date('Y-m-d', $startBase);
        $dateFin = date('Y-m-d', strtotime($dateDebut . ' +1 year'));

        $details = [];
        if (!empty($old['details_contrat'])) {
            $decoded = json_decode((string)$old['details_contrat'], true);
            if (is_array($decoded)) {
                $details = $decoded;
            }
        }

        $details['renouvellement_de'] = $old['numero_contrat'] ?? ('#' . $idContrat);
        $details['date_demande_renouvellement'] = date('Y-m-d H:i:s');

        $newContrat = new Contrat(
            $this->generateNumero(),
            $old['type_contrat'],
            (int)$old['id_client'],
            (int)$old['id_categorie'],
            (float)$old['prime_contrat'],
            (float)$old['franchise_contrat'],
            $dateDebut,
            $dateFin,
            'en attente',
            isset($old['id_formule']) ? (int)$old['id_formule'] : null,
            $old['formule_contrat'] ?? ($old['nom_formule'] ?? null),
            json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $ok = $this->addContrat($newContrat);
        if (!$ok) {
            return null;
        }

        $newId = (int)$this->db->lastInsertId();
        $uid = (int)$old['id_client'];
        $oldNum = $old['numero_contrat'] ?? ('#' . $idContrat);
        $this->db->prepare("INSERT INTO notification (id_user, message, type, lien) VALUES (?, ?, 'contrat', ?)")
            ->execute([$uid, "Votre contrat $oldNum a été renouvelé.", '/view/FrontOffice/contrat.php']);

        return $newId;
    }

    public function downloadPdf(int $idContrat): void
    {
        $contrat = $this->getById($idContrat);
        if (!$contrat) {
            die('Contrat introuvable.');
        }

        require_once __DIR__ . '/../vendor/autoload.php';

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Protex Assurance');
        $pdf->SetTitle('Contrat ' . $contrat['numero_contrat']);
        $pdf->SetSubject('Détails du contrat');

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        // --- Ajout du QR Code pour le WOW effect ---
        // Résolution intelligente : Ngrok (session/config) > IP Wi-Fi > localhost
        $publicBase = TunnelHelper::getPublicBaseUrl();
        $qrUrl      = rtrim($publicBase, '/') . '/view/FrontOffice/contratshow.php?id=' . $idContrat;

        $qrStyle = [
            'border' => 0,
            'padding' => 0,
            'fgcolor' => [26, 58, 122], // Bleu foncé Protex
            'bgcolor' => false,
        ];
        // Position: X=150, Y=15, Size=40x40
        $pdf->write2DBarcode($qrUrl, 'QRCODE,M', 150, 15, 40, 40, $qrStyle, 'N');
        // -------------------------------------------

        $html = '
        <h1 style="text-align:left; color:#1A3A7A;">Contrat d\'Assurance PROTEX</h1>
        <p style="color:#666; font-size:10px;">Authentification garantie par QR Code</p>
        <hr>
        <br><br>
        <table cellpadding="5">
            <tr>
                <td width="50%">
                    <h3 style="color:#1A3A7A; border-bottom:1px solid #ccc;">Détails du Souscripteur</h3>
                    <p><strong>Nom & Prénom :</strong> ' . htmlspecialchars(($contrat['prenom'] ?? '') . ' ' . ($contrat['nom'] ?? '')) . '<br>
                    <strong>Email :</strong> ' . htmlspecialchars($contrat['email'] ?? '') . '</p>
                </td>
                <td width="50%">
                    <h3 style="color:#1A3A7A; border-bottom:1px solid #ccc;">Détails du Contrat</h3>
                    <p><strong>Numéro :</strong> ' . htmlspecialchars($contrat['numero_contrat'] ?? '') . '<br>
                    <strong>Type :</strong> ' . htmlspecialchars($contrat['type_contrat'] ?? '') . '<br>
                    <strong>Catégorie :</strong> ' . htmlspecialchars($contrat['nom_categorie'] ?? '') . '<br>
                    <strong>Formule :</strong> ' . htmlspecialchars($contrat['nom_formule'] ?? ($contrat['formule_contrat'] ?? '')) . '<br>
                    <strong>Statut :</strong> ' . htmlspecialchars($contrat['statut_contrat'] ?? '') . '<br>
                    <strong>Date d\'effet :</strong> ' . date('d/m/Y', strtotime($contrat['date_debut_contrat'] ?? date('Y-m-d'))) . '<br>
                    <strong>Date d\'échéance :</strong> ' . date('d/m/Y', strtotime($contrat['date_fin_contrat'] ?? date('Y-m-d'))) . '</p>
                </td>
            </tr>
        </table>

        <br><br>
        <h3 style="color:#1A3A7A; border-bottom:1px solid #ccc;">Conditions Financières</h3>
        <p><strong>Prime annuelle :</strong> ' . number_format((float)($contrat['prime_contrat'] ?? 0), 2, ',', ' ') . ' DT<br>
        <strong>Franchise :</strong> ' . number_format((float)($contrat['franchise_contrat'] ?? 0), 2, ',', ' ') . ' DT</p>
        
        <br><br>
        <p style="font-size:10px; text-align:center;">Ce document est généré électroniquement et vaut preuve de couverture sous réserve du paiement de la prime correspondante.</p>
        ';

        $pdf->writeHTML($html, true, false, true, false, '');

        // Clear all active output buffers to prevent the "Some data has already been output" error
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        $pdf->Output('Contrat_' . ($contrat['numero_contrat'] ?? $idContrat) . '.pdf', 'I');
        exit;
    }
}
