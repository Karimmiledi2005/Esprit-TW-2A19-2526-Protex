<?php
declare(strict_types=1);

class Paiement
{
    private ?int $id_paiement;
    private ?int $id_offre;
    private ?string $reference;
    private ?float $montant;
    private ?string $methode;
    private ?string $periodicite;
    private ?string $statut;
    private ?string $date_paiement;
    private ?string $date_echeance;
    private ?string $num_carte_masque;
    private ?string $motif_refus;
    private ?float $remboursement_partiel;
    private ?string $remboursement_motif;
    private ?int $remboursement_demande_par;
    private ?int $remboursement_valide_par;
    private ?string $nom_offre;
    private ?string $type_offre;

    public function __construct(
        ?int $id_paiement = null,
        ?int $id_offre = null,
        ?string $reference = null,
        ?float $montant = null,
        ?string $methode = null,
        ?string $periodicite = null,
        ?string $statut = 'en_attente',
        ?string $date_paiement = null,
        ?string $date_echeance = null,
        ?string $num_carte_masque = null,
        ?string $motif_refus = null,
        ?float $remboursement_partiel = null,
        ?string $remboursement_motif = null,
        ?int $remboursement_demande_par = null,
        ?int $remboursement_valide_par = null,
        ?string $nom_offre = null,
        ?string $type_offre = null
    ) {
        $this->id_paiement = $id_paiement;
        $this->id_offre = $id_offre;
        $this->reference = $reference;
        $this->montant = $montant;
        $this->methode = $methode;
        $this->periodicite = $periodicite;
        $this->statut = $statut;
        $this->date_paiement = $date_paiement;
        $this->date_echeance = $date_echeance;
        $this->num_carte_masque = $num_carte_masque;
        $this->motif_refus = $motif_refus;
        $this->remboursement_partiel = $remboursement_partiel;
        $this->remboursement_motif = $remboursement_motif;
        $this->remboursement_demande_par = $remboursement_demande_par;
        $this->remboursement_valide_par = $remboursement_valide_par;
        $this->nom_offre = $nom_offre;
        $this->type_offre = $type_offre;
    }

    /* ========================= GETTERS ========================= */

    public function getIdPaiement(): ?int
    {
        return $this->id_paiement;
    }

    public function getIdOffre(): ?int
    {
        return $this->id_offre;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function getMethode(): ?string
    {
        return $this->methode;
    }

    public function getPeriodicite(): ?string
    {
        return $this->periodicite;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function getDatePaiement(): ?string
    {
        return $this->date_paiement;
    }

    public function getDateEcheance(): ?string
    {
        return $this->date_echeance;
    }

    public function getNumCarteMasque(): ?string
    {
        return $this->num_carte_masque;
    }

    public function getMotifRefus(): ?string
    {
        return $this->motif_refus;
    }

    public function getRemboursementPartiel(): ?float
    {
        return $this->remboursement_partiel;
    }

    public function getRemboursementMotif(): ?string
    {
        return $this->remboursement_motif;
    }

    public function getRemboursementDemandePar(): ?int
    {
        return $this->remboursement_demande_par;
    }

    public function getRemboursementValidePar(): ?int
    {
        return $this->remboursement_valide_par;
    }

    public function getNomOffre(): ?string
    {
        return $this->nom_offre;
    }

    public function getTypeOffre(): ?string
    {
        return $this->type_offre;
    }

    /* ========================= SETTERS ========================= */

    public function setIdPaiement(?int $value): void
    {
        $this->id_paiement = $value;
    }

    public function setIdOffre(?int $value): void
    {
        $this->id_offre = $value;
    }

    public function setReference(?string $value): void
    {
        $this->reference = $value;
    }

    public function setMontant(?float $value): void
    {
        $this->montant = $value;
    }

    public function setMethode(?string $value): void
    {
        $this->methode = $value;
    }

    public function setPeriodicite(?string $value): void
    {
        $this->periodicite = $value;
    }

    public function setStatut(?string $value): void
    {
        $this->statut = $value;
    }

    public function setDatePaiement(?string $value): void
    {
        $this->date_paiement = $value;
    }

    public function setDateEcheance(?string $value): void
    {
        $this->date_echeance = $value;
    }

    public function setNumCarteMasque(?string $value): void
    {
        $this->num_carte_masque = $value;
    }

    public function setMotifRefus(?string $value): void
    {
        $this->motif_refus = $value;
    }

    public function setRemboursementPartiel(?float $value): void
    {
        $this->remboursement_partiel = $value;
    }

    public function setRemboursementMotif(?string $value): void
    {
        $this->remboursement_motif = $value;
    }

    public function setRemboursementDemandePar(?int $value): void
    {
        $this->remboursement_demande_par = $value;
    }

    public function setRemboursementValidePar(?int $value): void
    {
        $this->remboursement_valide_par = $value;
    }

    public function setNomOffre(?string $value): void
    {
        $this->nom_offre = $value;
    }

    public function setTypeOffre(?string $value): void
    {
        $this->type_offre = $value;
    }

    /* ========================= CRUD ========================= */

    public function creer(array $data): ?int
    {
        $db = config::getConnexion();
        $ref = 'PAY-' . date('Y') . '-' . str_pad((string)random_int(1, 99999), 5, '0', STR_PAD_LEFT);

        $stmt = $db->prepare("
            INSERT INTO paiement
                (reference, id_offre, id_user, montant, methode, periodicite, statut, num_carte_masque, date_paiement)
            VALUES
                (:reference, :id_offre, :id_user, :montant, :methode, :periodicite, 'en_attente', :num_carte, NOW())
        ");
        $stmt->execute([
            ':reference'  => $ref,
            ':id_offre'   => (int)$data['id_offre'],
            ':id_user'    => (int)$data['id_user'],
            ':montant'    => (float)$data['montant'],
            ':methode'    => $data['methode'] ?? 'carte',
            ':periodicite'=> $data['periodicite'] ?? 'mensuel',
            ':num_carte'  => $data['num_carte'] ?? '',
        ]);

        return (int)$db->lastInsertId();
    }

    public function getByReference(string $reference): ?array
    {
        $db = config::getConnexion();
        $stmt = $db->prepare("SELECT * FROM paiement WHERE reference = ? LIMIT 1");
        $stmt->execute([$reference]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /* ========================= OUTILS ========================= */

    public function toArray(): array
    {
        return [
            'id_paiement'             => $this->id_paiement,
            'id_offre'                => $this->id_offre,
            'reference'               => $this->reference,
            'montant'                 => $this->montant,
            'methode'                 => $this->methode,
            'periodicite'             => $this->periodicite,
            'statut'                  => $this->statut,
            'date_paiement'           => $this->date_paiement,
            'date_echeance'           => $this->date_echeance,
            'num_carte_masque'        => $this->num_carte_masque,
            'motif_refus'             => $this->motif_refus,
            'remboursement_partiel'   => $this->remboursement_partiel,
            'remboursement_motif'     => $this->remboursement_motif,
            'remboursement_demande_par' => $this->remboursement_demande_par,
            'remboursement_valide_par'  => $this->remboursement_valide_par,
            'nom_offre'               => $this->nom_offre,
            'type_offre'              => $this->type_offre,
        ];
    }

    public static function fromArray(array $data): Paiement
    {
        return new self(
            isset($data['id_paiement']) ? (int)$data['id_paiement'] : null,
            isset($data['id_offre']) ? (int)$data['id_offre'] : null,
            $data['reference'] ?? null,
            isset($data['montant']) ? (float)$data['montant'] : null,
            $data['methode'] ?? null,
            $data['periodicite'] ?? null,
            $data['statut'] ?? 'en_attente',
            $data['date_paiement'] ?? null,
            $data['date_echeance'] ?? null,
            $data['num_carte_masque'] ?? null,
            $data['motif_refus'] ?? null,
            isset($data['remboursement_partiel']) ? (float)$data['remboursement_partiel'] : null,
            $data['remboursement_motif'] ?? null,
            isset($data['remboursement_demande_par']) ? (int)$data['remboursement_demande_par'] : null,
            isset($data['remboursement_valide_par']) ? (int)$data['remboursement_valide_par'] : null,
            $data['nom_offre'] ?? null,
            $data['type_offre'] ?? null
        );
    }
}
?>