<?php
require_once __DIR__ . '/../connexion.php';

class RecommandationController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = config::getConnexion();
    }

    public function recommander(array $data): ?array
    {
        $idCategorie = (int)($data['id_categorie'] ?? 0);
        $budgetMax = (float)($data['budget_max'] ?? 0);
        $objectif = trim((string)($data['objectif'] ?? ''));
        $risque = trim((string)($data['risque'] ?? ''));
        $franchisePref = trim((string)($data['franchise_pref'] ?? ''));
        $besoin = mb_strtolower(trim((string)($data['besoin'] ?? '')), 'UTF-8');

        if ($idCategorie <= 0 || $budgetMax <= 0 || $objectif === '' || $risque === '' || $franchisePref === '') {
            return null;
        }

        $sql = "
            SELECT
                f.id_formule,
                f.nom_formule,
                f.description_formule,
                f.prix_formule,
                f.franchise_formule,
                f.niveau_formule,
                f.id_categorie,
                c.nom_categorie,
                COUNT(DISTINCT fg.id_garantie) AS nb_garanties
            FROM formule f
            LEFT JOIN categorie c ON c.id_categorie = f.id_categorie
            LEFT JOIN formule_garantie fg ON fg.id_formule = f.id_formule
            WHERE f.id_categorie = :id_categorie
            GROUP BY
                f.id_formule,
                f.nom_formule,
                f.description_formule,
                f.prix_formule,
                f.franchise_formule,
                f.niveau_formule,
                f.id_categorie,
                c.nom_categorie
            ORDER BY f.prix_formule ASC
        ";

        $query = $this->db->prepare($sql);
        $query->execute(['id_categorie' => $idCategorie]);
        $formules = $query->fetchAll(PDO::FETCH_ASSOC);

        if (!$formules) {
            return null;
        }

        $best = null;
        $bestScore = -999999;

        foreach ($formules as $formule) {
            $prix = (float)($formule['prix_formule'] ?? 0);
            $franchise = (float)($formule['franchise_formule'] ?? 0);
            $nbGaranties = (int)($formule['nb_garanties'] ?? 0);
            $niveau = mb_strtolower((string)($formule['niveau_formule'] ?? ''), 'UTF-8');
            $nom = mb_strtolower((string)($formule['nom_formule'] ?? ''), 'UTF-8');

            $score = 0;
            $raisons = [];

            // Budget : récompense si la formule respecte le budget, pénalité si elle dépasse.
            if ($prix <= $budgetMax) {
                $score += 35;
                $raisons[] = 'La prime respecte le budget maximum indiqué.';
            } else {
                $depassement = $prix - $budgetMax;
                $score -= min(35, $depassement / max(1, $budgetMax) * 35);
            }

            // Objectif principal.
            if ($objectif === 'prix_bas') {
                $score += max(0, 35 - ($prix / max(1, $budgetMax)) * 20);
                $raisons[] = 'Le système privilégie les formules économiques.';
            }

            if ($objectif === 'franchise_faible') {
                $score += max(0, 35 - ($franchise / 10));
                $raisons[] = 'La franchise est prise en compte comme critère prioritaire.';
            }

            if ($objectif === 'couverture_max') {
                $score += ($nbGaranties * 8);
                if (str_contains($niveau, 'premium') || str_contains($niveau, 'complet') || str_contains($niveau, 'risque') || str_contains($nom, 'premium') || str_contains($nom, 'tous')) {
                    $score += 25;
                }
                $raisons[] = 'La recommandation favorise la formule avec le meilleur niveau de couverture.';
            }

            if ($objectif === 'equilibre') {
                $score += 20;
                $score += min(25, $nbGaranties * 5);
                if ($prix <= $budgetMax) {
                    $score += 15;
                }
                $raisons[] = 'Le score cherche un équilibre entre prix, garanties et franchise.';
            }

            // Risque client.
            if ($risque === 'eleve') {
                $score += $nbGaranties * 6;
                if (str_contains($niveau, 'premium') || str_contains($niveau, 'complet') || str_contains($niveau, 'risque') || str_contains($nom, 'tous')) {
                    $score += 20;
                }
                $raisons[] = 'Votre niveau de risque demande une couverture plus complète.';
            } elseif ($risque === 'moyen') {
                $score += min(25, $nbGaranties * 4);
            } else {
                $score += ($prix <= $budgetMax) ? 15 : 0;
            }

            // Franchise préférée.
            if ($franchisePref === 'basse') {
                $score += max(0, 25 - ($franchise / 8));
            } elseif ($franchisePref === 'moyenne') {
                $score += ($franchise >= 50 && $franchise <= 200) ? 18 : 6;
            } else {
                $score += 8;
            }

            // Besoin textuel simple : mini NLP local sans API.
            if ($besoin !== '') {
                if (str_contains($besoin, 'pas cher') || str_contains($besoin, 'moins cher') || str_contains($besoin, 'budget') || str_contains($besoin, 'prix')) {
                    $score += max(0, 20 - ($prix / max(1, $budgetMax)) * 10);
                    $raisons[] = 'Le besoin textuel indique une sensibilité au prix.';
                }

                if (str_contains($besoin, 'maximum') || str_contains($besoin, 'complet') || str_contains($besoin, 'tout') || str_contains($besoin, 'garantie')) {
                    $score += $nbGaranties * 4;
                    $raisons[] = 'Le besoin textuel montre une demande de garanties fortes.';
                }

                if (str_contains($besoin, 'franchise')) {
                    $score += max(0, 15 - ($franchise / 10));
                }
            }

            if (empty($raisons)) {
                $raisons[] = 'Cette formule présente le meilleur score global selon vos réponses.';
            }

            if ($score > $bestScore) {
                $bestScore = $score; //Met à jour le meilleur score trouvé.
                $best = $formule; //Stocke la formule actuelle comme meilleure recommandation.
                $best['raisons'] = array_values(array_unique($raisons));
            }
        }

        if (!$best) {
            return null;
        }

        // Normalisation simple pour afficher un score entre 0 et 100.
        $best['score'] = max(1, min(100, (int)round($bestScore)));

        return $best;
    }
}
