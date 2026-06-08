<?php
/**
 * model/ReclamationModel.php
 * Classe Reclamation — supporte les plaintes polymorphiques (contrat, devis, sinistre, paiement, poste, general)
 */

class Reclamation
{
    private string    $objet;
    private string    $type;
    private string    $refContrat;
    private string    $priorite;
    private string    $statut;
    private DateTime  $dateDepot;
    private string    $recRef;
    private string    $description;
    private string    $email;
    private string    $objectType;  // contrat|devis|sinistre|paiement|poste|general
    private string    $objectRef;   // numero / id of linked object

    public ?int $id = null;

    /** @var string[] Valid object types */
    public const OBJECT_TYPES = ['contrat', 'devis', 'sinistre', 'paiement', 'poste', 'general'];

    public function __construct(
        ?int $id = null,
        ?string $objet = null,
        ?string $type = null,
        ?string $refContrat = null,
        ?string $priorite = null,
        ?string $statut = null,
        ?DateTime $dateDepot = null,
        ?string $recRef = null,
        ?string $description = null,
        ?string $email = null,
        ?string $objectType = null,
        ?string $objectRef = null
    ) {
        $this->id          = $id;
        $this->objet       = (string)($objet       ?? '');
        $this->type        = (string)($type        ?? 'general');
        $this->refContrat  = (string)($refContrat  ?? '');
        $this->priorite    = (string)($priorite    ?? 'Normale');
        $this->statut      = (string)($statut      ?? 'en_attente');
        $this->dateDepot   = $dateDepot ?? new DateTime();
        $this->recRef      = (string)($recRef      ?? '');
        $this->description = (string)($description ?? '');
        $this->email       = (string)($email       ?? '');
        $this->objectType  = in_array($objectType, self::OBJECT_TYPES, true) ? $objectType : 'general';
        $this->objectRef   = (string)($objectRef   ?? '');
    }

    public function getObjet(): string       { return $this->objet; }
    public function getType(): string        { return $this->type; }
    public function getRefContrat(): string  { return $this->refContrat; }
    public function getPriorite(): string    { return $this->priorite; }
    public function getStatut(): string      { return $this->statut; }
    public function getDateDepot(): DateTime { return $this->dateDepot; }
    public function getRecRef(): string      { return $this->recRef; }
    public function getDescription(): string { return $this->description; }
    public function getEmail(): string       { return $this->email; }
    public function getObjectType(): string  { return $this->objectType; }
    public function getObjectRef(): string   { return $this->objectRef; }

    public function setStatut(string $s): void { $this->statut = $s; }

    /**
     * Factory depuis tableau POST
     */
    public static function fromPost(array $post): self
    {
        $objectType = trim($post['object_type'] ?? 'general');
        $objectRef  = trim($post['object_ref']  ?? '');
        // Backward compat: if old ref_contrat is provided, use it
        if ($objectRef === '' && !empty($post['ref_contrat'])) {
            $objectRef  = trim($post['ref_contrat']);
            $objectType = 'contrat';
        }
        $recRef   = 'REC-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
        $priorite = trim($post['priorite'] ?? 'Normale');

        return new self(
            null,
            trim($post['objet']       ?? ''),
            trim($post['type']        ?? 'Autre'),
            $objectRef,  // backward compat field
            $priorite,
            'open',
            new DateTime(),
            $recRef,
            trim($post['description'] ?? ''),
            trim($post['email']       ?? ''),
            $objectType,
            $objectRef
        );
    }

    /**
     * Return a human-readable label for the object type.
     */
    public static function objectTypeLabel(string $type): string
    {
        return match($type) {
            'contrat'  => 'Contrat',
            'devis'    => 'Devis',
            'sinistre' => 'Sinistre',
            'paiement' => 'Paiement',
            'poste'    => 'Poste social',
            default    => 'Général',
        };
    }
}
