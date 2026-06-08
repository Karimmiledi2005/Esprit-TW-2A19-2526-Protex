<?php
require_once __DIR__ . '/Offre.php';

class OffreModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = config::getConnexion();
    }

    public function getActives(): array
    {
        $stmt = $this->db->query("
            SELECT o.*,
                   (SELECT AVG(note) FROM avis_offre WHERE id_offre = o.id_offre) as note_moyenne,
                   (SELECT COUNT(*) FROM avis_offre WHERE id_offre = o.id_offre) as nb_avis
            FROM offre o
            WHERE o.statut = 'active'
            ORDER BY o.prix_mensuel ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByType(string $type): array
    {
        $stmt = $this->db->prepare("
            SELECT o.*,
                   (SELECT AVG(note) FROM avis_offre WHERE id_offre = o.id_offre) as note_moyenne,
                   (SELECT COUNT(*) FROM avis_offre WHERE id_offre = o.id_offre) as nb_avis
            FROM offre o
            WHERE o.statut = 'active' AND o.type_offre = :type
            ORDER BY o.prix_mensuel ASC
        ");
        $stmt->execute([':type' => $type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
