<?php

class Garantie
{
    private $id_garantie = null;
    private $nom_garantie;
    private $description_garantie;
    private $plafond_couvert_garantie;
    private $id_categorie;
    private $nom_categorie = null;

    public function __construct($nom_garantie, $description_garantie, $plafond_couvert_garantie, $id_categorie = null)
    {
        $this->nom_garantie = $nom_garantie;
        $this->description_garantie = $description_garantie;
        $this->plafond_couvert_garantie = (float)$plafond_couvert_garantie;
        $this->id_categorie = $id_categorie;
    }

    public function getIdGarantie()
    {
        return $this->id_garantie;
    }

    public function getNomGarantie()
    {
        return $this->nom_garantie;
    }

    public function getDescriptionGarantie()
    {
        return $this->description_garantie;
    }

    public function getPlafond()
    {
        return $this->plafond_couvert_garantie;
    }

    public function getIdCategorie()
    {
        return $this->id_categorie;
    }

    public function getNomCategorie()
    {
        return $this->nom_categorie;
    }

    public function setIdGarantie($id)
    {
        $this->id_garantie = $id;
    }

    public function setNomCategorie($nom)
    {
        $this->nom_categorie = $nom;
    }
}
