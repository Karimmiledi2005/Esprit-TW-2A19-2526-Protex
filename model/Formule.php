<?php

class Formule
{
    private ?int $id_formule;
    private string $nom_formule;
    private string $description_formule;
    private float $prix_formule;
    private float $franchise_formule;
    private int $id_categorie;

    public function __construct(
        string $nom_formule = "",
        string $description_formule = "",
        float $prix_formule = 0,
        int $id_categorie = 0,
        float $franchise_formule = 0,
        ?int $id_formule = null
    ) {
        $this->id_formule = $id_formule;
        $this->nom_formule = $nom_formule;
        $this->description_formule = $description_formule;
        $this->prix_formule = $prix_formule;
        $this->franchise_formule = $franchise_formule;
        $this->id_categorie = $id_categorie;
    }

    public function getIdFormule(): ?int
    {
        return $this->id_formule;
    }

    public function setIdFormule(?int $id_formule): void
    {
        $this->id_formule = $id_formule;
    }

    public function getNomFormule(): string
    {
        return $this->nom_formule;
    }

    public function setNomFormule(string $nom_formule): void
    {
        $this->nom_formule = $nom_formule;
    }

    public function getDescriptionFormule(): string
    {
        return $this->description_formule;
    }

    public function setDescriptionFormule(string $description_formule): void
    {
        $this->description_formule = $description_formule;
    }

    public function getPrixFormule(): float
    {
        return $this->prix_formule;
    }

    public function setPrixFormule(float $prix_formule): void
    {
        $this->prix_formule = $prix_formule;
    }

    public function getFranchiseFormule(): float
    {
        return $this->franchise_formule;
    }

    public function setFranchiseFormule(float $franchise_formule): void
    {
        $this->franchise_formule = $franchise_formule;
    }

    public function getIdCategorie(): int
    {
        return $this->id_categorie;
    }

    public function setIdCategorie(int $id_categorie): void
    {
        $this->id_categorie = $id_categorie;
    }
}