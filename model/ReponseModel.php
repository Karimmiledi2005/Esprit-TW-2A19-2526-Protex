<?php
/**
 * model/ReponseModel.php
 * Recréé : ce fichier manquait dans le ZIP des camarades.
 * Classe déduite de l'usage dans ReponseController.php.
 */

class Reponse
{
    private ?int   $id;
    private string $dateReponse;
    private string $contenu;
    private string $statut;
    private int    $reclamationId;

    public function __construct(?int $id, string $dateReponse, string $contenu, string $statut, int $reclamationId)
    {
        $this->id            = $id;
        $this->dateReponse   = $dateReponse;
        $this->contenu       = $contenu;
        $this->statut        = $statut;
        $this->reclamationId = $reclamationId;
    }

    public function getId(): ?int           { return $this->id; }
    public function getDateReponse(): string { return $this->dateReponse; }
    public function getContenu(): string    { return $this->contenu; }
    public function getStatut(): string     { return $this->statut; }
    public function getReclamationId(): int { return $this->reclamationId; }

    public function setStatut(string $s): void  { $this->statut = $s; }
    public function setContenu(string $c): void { $this->contenu = $c; }
}
