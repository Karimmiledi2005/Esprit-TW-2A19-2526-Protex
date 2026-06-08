<?php

class Sinistre {
    private $id_sinistre;
    private $id_contrat;
    private $id_user;
    private $type;
    private $description;
    private $photo_url;
    private $date_declaration;
    private $statut;
    public $fraudScore;
    public $fraudNiveau;
    public $fraudSuggestion;
    public $clientNom;
    public $numeroContrat;

    // Constructor
    public function __construct($id_contrat, $id_user, $type, $description, $photo_url = null) {
        $this->id_contrat = $id_contrat;
        $this->id_user = $id_user;
        $this->type = $type;
        $this->description = $description;
        $this->photo_url = $photo_url;
    }

    // GETTERS
    public function getIdSinistre() { return $this->id_sinistre; }
    public function getIdContrat() { return $this->id_contrat; }
    public function getIdUser() { return $this->id_user; }
    public function getType() { return $this->type; }
    public function getDescription() { return $this->description; }
    public function getPhotoUrl() { return $this->photo_url; }
    public function getDateDeclaration() { return $this->date_declaration; }
    public function getStatut() { return $this->statut; }

    // SETTERS
    public function setIdSinistre($id) { $this->id_sinistre = $id; }
    public function setIdContrat($id) { $this->id_contrat = $id; }
    public function setIdUser($id) { $this->id_user = $id; }
    public function setType($type) { $this->type = $type; }
    public function setDescription($desc) { $this->description = $desc; }
    public function setPhotoUrl($url) { $this->photo_url = $url; }
    public function setDateDeclaration($date) { $this->date_declaration = $date; }
    public function setStatut($statut) { $this->statut = $statut; }
}