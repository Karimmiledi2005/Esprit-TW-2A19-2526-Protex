<?php

class Categorie {
    private $id_categorie;
    private $nom_categorie;
    private $description_categorie;
    private $slug_categorie;
    private $icone_categorie;
    private $couleur_categorie;
    private $description_front;
    private $lien_front;

    public function __construct(
        $nom_categorie,
        $description_categorie = null,
        $slug_categorie = null,
        $icone_categorie = null,
        $couleur_categorie = null,
        $description_front = null,
        $lien_front = null
    ) {
        $this->nom_categorie = $nom_categorie;
        $this->description_categorie = $description_categorie;
        $this->slug_categorie = $slug_categorie;
        $this->icone_categorie = $icone_categorie;
        $this->couleur_categorie = $couleur_categorie;
        $this->description_front = $description_front;
        $this->lien_front = $lien_front;
    }

    public function getIdCategorie() { return $this->id_categorie; }
    public function getNomCategorie() { return $this->nom_categorie; }
    public function getDescriptionCategorie() { return $this->description_categorie; }
    public function getSlugCategorie() { return $this->slug_categorie; }
    public function getIconeCategorie() { return $this->icone_categorie; }
    public function getCouleurCategorie() { return $this->couleur_categorie; }
    public function getDescriptionFront() { return $this->description_front; }
    public function getLienFront() { return $this->lien_front; }

    public function setIdCategorie($id) { $this->id_categorie = $id; }
    public function setNomCategorie($nom) { $this->nom_categorie = $nom; }
    public function setDescriptionCategorie($description) { $this->description_categorie = $description; }
    public function setSlugCategorie($slug) { $this->slug_categorie = $slug; }
    public function setIconeCategorie($icone) { $this->icone_categorie = $icone; }
    public function setCouleurCategorie($couleur) { $this->couleur_categorie = $couleur; }
    public function setDescriptionFront($description) { $this->description_front = $description; }
    public function setLienFront($lien) { $this->lien_front = $lien; }
}