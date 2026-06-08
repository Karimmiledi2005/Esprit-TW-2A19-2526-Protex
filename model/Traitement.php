<?php

class Traitement {
    private $id_traitement;
    private $id_sinistre;
    private $id_user;
    private $nom_agent;
    private $decision;
    private $montant_indemnise;
    private $statut;
    private $date_traitement;
    private $message_agent;

    // Constructor
    public function __construct($id_sinistre, $id_user, $nom_agent, $decision, $montant_indemnise = null) {
        $this->id_sinistre = $id_sinistre;
        $this->id_user = $id_user;
        $this->nom_agent = $nom_agent;
        $this->decision = $decision;
        $this->montant_indemnise = $montant_indemnise;
    }

    // GETTERS
    public function getIdTraitement() { return $this->id_traitement; }
    public function getIdSinistre() { return $this->id_sinistre; }
    public function getIdUser() { return $this->id_user; }
    public function getNomAgent() { return $this->nom_agent; }
    public function getDecision() { return $this->decision; }
    public function getMontantIndemnise() { return $this->montant_indemnise; }
    public function getStatut() { return $this->statut; }
    public function getDateTraitement() { return $this->date_traitement; }
    public function getMessageAgent() { return $this->message_agent; }

    // SETTERS
    public function setIdTraitement($id) { $this->id_traitement = $id; }
    public function setIdSinistre($id) { $this->id_sinistre = $id; }
    public function setIdUser($id) { $this->id_user = $id; }
    public function setNomAgent($nom) { $this->nom_agent = $nom; }
    public function setDecision($decision) { $this->decision = $decision; }
    public function setMontantIndemnise($montant) { $this->montant_indemnise = $montant; }
    public function setStatut($statut) { $this->statut = $statut; }
    public function setDateTraitement($date) { $this->date_traitement = $date; }
    public function setMessageAgent($msg) { $this->message_agent = $msg; }
}
