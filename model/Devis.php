<?php
class Devis
{
    private ?int    $id_devis;
    private ?int    $id_offre;
    private ?string $nom;
    private ?string $prenom;
    private ?string $email;
    private ?string $telephone;
    private ?string $type_assurance;
    private ?string $date_demande;
    private ?string $statut;
    private ?float  $montant_estime;
    private ?string $reponse_admin;

    public function __construct(
        ?int    $id_devis        = null,
        ?int    $id_offre        = null,
        ?string $nom             = null,
        ?string $prenom          = null,
        ?string $email           = null,
        ?string $telephone       = null,
        ?string $type_assurance  = null,
        ?string $date_demande    = null,
        ?string $statut          = 'en_attente',
        ?float  $montant_estime  = null,
        ?string $reponse_admin   = null
    ) {
        $this->id_devis       = $id_devis;
        $this->id_offre       = $id_offre;
        $this->nom            = $nom;
        $this->prenom         = $prenom;
        $this->email          = $email;
        $this->telephone      = $telephone;
        $this->type_assurance = $type_assurance;
        $this->date_demande   = $date_demande;
        $this->statut         = $statut;
        $this->montant_estime = $montant_estime;
        $this->reponse_admin  = $reponse_admin;
    }

    public function getIdDevis():        ?int    { return $this->id_devis; }
    public function getIdOffre():        ?int    { return $this->id_offre; }
    public function getNom():            ?string { return $this->nom; }
    public function getPrenom():         ?string { return $this->prenom; }
    public function getEmail():          ?string { return $this->email; }
    public function getTelephone():      ?string { return $this->telephone; }
    public function getTypeAssurance():  ?string { return $this->type_assurance; }
    public function getDateDemande():    ?string { return $this->date_demande; }
    public function getStatut():         ?string { return $this->statut; }
    public function getMontantEstime():  ?float  { return $this->montant_estime; }
    public function getReponseAdmin():   ?string { return $this->reponse_admin; }

    public function setIdDevis(?int $v):               void { $this->id_devis = $v; }
    public function setIdOffre(?int $v):               void { $this->id_offre = $v; }
    public function setNom(?string $v):                void { $this->nom = $v; }
    public function setPrenom(?string $v):             void { $this->prenom = $v; }
    public function setEmail(?string $v):              void { $this->email = $v; }
    public function setTelephone(?string $v):          void { $this->telephone = $v; }
    public function setTypeAssurance(?string $v):      void { $this->type_assurance = $v; }
    public function setDateDemande(?string $v):        void { $this->date_demande = $v; }
    public function setStatut(?string $v):             void { $this->statut = $v; }
    public function setMontantEstime(?float $v):       void { $this->montant_estime = $v; }
    public function setReponseAdmin(?string $v):       void { $this->reponse_admin = $v; }
}
?>