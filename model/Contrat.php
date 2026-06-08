<?php
class Contrat {
    private $id_contrat;
    private $numero_contrat;
    private $type_contrat;
    private $date_debut_contrat;
    private $date_fin_contrat;
    private $prime_contrat;
    private $franchise_contrat;
    private $statut_contrat;
    private $id_client;
    private $id_categorie;
    private $id_formule;
    private $formule_contrat;
    private $details_contrat;

    private $nom_categorie;
    private $nom_formule;
    private $nom_client;
    private $prenom_client;
    private $email_client;

    public function __construct(
        $numero_contrat,
        $type_contrat,
        $id_client,
        $id_categorie,
        $prime_contrat,
        $franchise_contrat,
        $date_debut_contrat = null,
        $date_fin_contrat = null,
        $statut_contrat = 'en attente',
        $id_formule = null,
        $formule_contrat = null,
        $details_contrat = null
    ) {
        $this->numero_contrat = $numero_contrat;
        $this->type_contrat = $type_contrat;
        $this->id_client = $id_client;
        $this->id_categorie = $id_categorie;
        $this->prime_contrat = $prime_contrat;
        $this->franchise_contrat = $franchise_contrat;
        $this->date_debut_contrat = $date_debut_contrat;
        $this->date_fin_contrat = $date_fin_contrat;
        $this->statut_contrat = $statut_contrat;
        $this->id_formule = $id_formule;
        $this->formule_contrat = $formule_contrat;
        $this->details_contrat = $details_contrat;
    }

    public function getIdContrat() { return $this->id_contrat; }
    public function getNumeroContrat() { return $this->numero_contrat; }
    public function getTypeContrat() { return $this->type_contrat; }
    public function getDateDebutContrat() { return $this->date_debut_contrat; }
    public function getDateFinContrat() { return $this->date_fin_contrat; }
    public function getPrimeContrat() { return $this->prime_contrat; }
    public function getFranchiseContrat() { return $this->franchise_contrat; }
    public function getStatutContrat() { return $this->statut_contrat; }
    public function getIdClient() { return $this->id_client; }
    public function getIdCategorie() { return $this->id_categorie; }
    public function getIdFormule() { return $this->id_formule; }
    public function getFormuleContrat() { return $this->formule_contrat; }
    public function getDetailsContrat() { return $this->details_contrat; }
    public function getNomCategorie() { return $this->nom_categorie; }
    public function getNomFormule() { return $this->nom_formule; }
    public function getNomClient() { return $this->nom_client; }
    public function getPrenomClient() { return $this->prenom_client; }
    public function getEmailClient() { return $this->email_client; }

    public function setIdContrat($id) { $this->id_contrat = $id; }
    public function setNumeroContrat($numero) { $this->numero_contrat = $numero; }
    public function setTypeContrat($type) { $this->type_contrat = $type; }
    public function setDateDebutContrat($date) { $this->date_debut_contrat = $date; }
    public function setDateFinContrat($date) { $this->date_fin_contrat = $date; }
    public function setPrimeContrat($prime) { $this->prime_contrat = $prime; }
    public function setFranchiseContrat($franchise) { $this->franchise_contrat = $franchise; }
    public function setStatutContrat($statut) { $this->statut_contrat = $statut; }
    public function setIdClient($id) { $this->id_client = $id; }
    public function setIdCategorie($id) { $this->id_categorie = $id; }
    public function setIdFormule($id) { $this->id_formule = $id; }
    public function setFormuleContrat($formule) { $this->formule_contrat = $formule; }
    public function setDetailsContrat($details) { $this->details_contrat = $details; }
    public function setNomCategorie($nom) { $this->nom_categorie = $nom; }
    public function setNomFormule($nom) { $this->nom_formule = $nom; }
    public function setNomClient($nom) { $this->nom_client = $nom; }
    public function setPrenomClient($prenom) { $this->prenom_client = $prenom; }
    public function setEmailClient($email) { $this->email_client = $email; }
}
