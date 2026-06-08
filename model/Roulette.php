<?php
/**
 * model/Roulette.php
 * Roulette de fidélité — Système de TENTATIVES
 * Chaque 3 paiements validés = +1 tentative (spin)
 */

class Roulette
{
    public const SEUIL_PAR_SPIN = 3;

    public const CADEAUX = [
        [
            'label'  => '-10% sur votre prochaine cotisation',
            'type'   => 'reduction_pct',
            'valeur' => 10,
            'icone'  => '🏷️',
            'couleur' => '#FF6B1A',
            'probabilite' => 50,
        ],
        [
            'label'  => '-20% sur votre prochaine cotisation',
            'type'   => 'reduction_pct',
            'valeur' => 20,
            'icone'  => '🔥',
            'couleur' => '#e55d16',
            'probabilite' => 30,
        ],
        [
            'label'  => '-50% sur votre prochaine cotisation',
            'type'   => 'reduction_pct',
            'valeur' => 50,
            'icone'  => '💎',
            'couleur' => '#3b82f6',
            'probabilite' => 10,
        ],
        [
            'label'  => 'Bonus service gratuit',
            'type'   => 'bonus_service',
            'valeur' => 0,
            'icone'  => '🎁',
            'couleur' => '#10b981',
            'probabilite' => 5,
        ],
        [
            'label'  => 'Pas de chance, retentez !',
            'type'   => 'aucun',
            'valeur' => 0,
            'icone'  => '😅',
            'couleur' => '#64748b',
            'probabilite' => 5,
        ],
        [
            'label'  => '-10 DT sur votre prochaine cotisation',
            'type'   => 'reduction_fixe',
            'valeur' => 10,
            'icone'  => '💰',
            'couleur' => '#f59e0b',
            'probabilite' => 0,
        ],
        [
            'label'  => '-10% sur votre prochaine cotisation',
            'type'   => 'reduction_pct',
            'valeur' => 10,
            'icone'  => '🏷️',
            'couleur' => '#ec4899',
            'probabilite' => 0,
        ],
        [
            'label'  => '-5 DT sur votre prochaine cotisation',
            'type'   => 'reduction_fixe',
            'valeur' => 5,
            'icone'  => '🎉',
            'couleur' => '#8b5cf6',
            'probabilite' => 0,
        ],
    ];

    /**
     * Compte les paiements validés pour un email
     */
    public static function compterPaiementsValides(PDO $db, string $email): int
    {
        try {
            $stmt = $db->prepare("
                SELECT COUNT(*) as total
                FROM paiement p
                WHERE p.statut = 'valide'
                AND EXISTS (
                    SELECT 1 FROM devis d 
                    WHERE d.email = :email
                )
            ");
            $stmt->execute([':email' => $email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Calcule le nombre de spins gagnés selon les paiements
     * Ex: 3 paiements = 1 spin, 6 = 2 spins, 9 = 3 spins
     */
    public static function calculerSpinsGagnes(int $nbPaiements): int
    {
        return intdiv($nbPaiements, self::SEUIL_PAR_SPIN);
    }

    /**
     * Calcule les spins restants (gagnés - utilisés)
     */
    public static function calculerSpinsRestants(PDO $db, string $email): int
    {
        $nbPaiements = self::compterPaiementsValides($db, $email);
        $spinsGagnes = self::calculerSpinsGagnes($nbPaiements);
        $spinsUtilises = self::compterSpinsUtilises($db, $email);
        return max(0, $spinsGagnes - $spinsUtilises);
    }

    /**
     * Compte les spins déjà utilisés par un email
     */
    public static function compterSpinsUtilises(PDO $db, string $email): int
    {
        try {
            $stmt = $db->prepare("
                SELECT COUNT(*) as total
                FROM roulette_gains
                WHERE email = :email
            ");
            $stmt->execute([':email' => $email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Peut jouer si spins restants > 0
     */
    public static function peutJouer(PDO $db, string $email): bool
    {
        return self::calculerSpinsRestants($db, $email) > 0;
    }

    /**
     * Retourne les cadeaux pour la roulette (8 segments)
     */
    public static function getCadeaux(): array
    {
        return self::CADEAUX;
    }

    /**
     * Tire un cadeau selon les probabilités pondérées
     */
    public static function tirerCadeau(): array
    {
        $cadeaux = self::CADEAUX;
        $totalProb = 0;
        foreach ($cadeaux as $c) {
            $totalProb += $c['probabilite'];
        }

        $rand = mt_rand(1, $totalProb);
        $cumul = 0;

        foreach ($cadeaux as $index => $cadeau) {
            $cumul += $cadeau['probabilite'];
            if ($rand <= $cumul) {
                return ['cadeau' => $cadeau, 'index' => $index];
            }
        }

        // Fallback
        return ['cadeau' => $cadeaux[0], 'index' => 0];
    }

    /**
     * Génère un code promo unique
     */
    public static function genererCodePromo(): string
    {
        $random = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        return 'PROTEX-SPIN-' . $random;
    }

    /**
     * Enregistre un gain en BDD
     */
    public static function enregistrerGain(PDO $db, array $gain): bool
    {
        try {
            $stmt = $db->prepare("
                INSERT INTO roulette_gains 
                (email, nom, prenom, paiements, cadeau_label, cadeau_icone, code_promo, valeur_reduction, type_recompense, date_jeu, utilise)
                VALUES 
                (:email, :nom, :prenom, :paiements, :cadeau_label, :cadeau_icone, :code_promo, :valeur_reduction, :type_recompense, NOW(), 0)
            ");
            return $stmt->execute([
                ':email'             => $gain['email'],
                ':nom'               => $gain['nom'],
                ':prenom'            => $gain['prenom'],
                ':paiements'         => $gain['paiements'],
                ':cadeau_label'      => $gain['cadeau_label'],
                ':cadeau_icone'      => $gain['cadeau_icone'],
                ':code_promo'        => $gain['code_promo'],
                ':valeur_reduction'  => $gain['valeur_reduction'],
                ':type_recompense'   => $gain['type_recompense'],
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Historique des gains d'un client
     */
    public static function getHistorique(PDO $db, string $email): array
    {
        try {
            $stmt = $db->prepare("
                SELECT *
                FROM roulette_gains
                WHERE email = :email
                ORDER BY date_jeu DESC
            ");
            $stmt->execute([':email' => $email]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Valide un code promo issu de la roulette
     * Retourne le gain ou null si invalide/expiré/déjà utilisé
     */
    public static function validerCodePromo(PDO $db, string $code): ?array
    {
        try {
            $stmt = $db->prepare("
                SELECT * FROM roulette_gains
                WHERE code_promo = :code
                LIMIT 1
            ");
            $stmt->execute([':code' => strtoupper(trim($code))]);
            $gain = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$gain) {
                return null;
            }

            if ((int)$gain['utilise'] === 1) {
                return ['error' => 'Ce code promo a déjà été utilisé.'];
            }

            return $gain;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Marque un code promo comme utilisé
     */
    public static function marquerCodeUtilise(PDO $db, string $code): bool
    {
        try {
            $hasDateCol = false;
            try {
                $cols = $db->query("SHOW COLUMNS FROM roulette_gains LIKE 'date_utilisation'");
                $hasDateCol = $cols && $cols->rowCount() > 0;
            } catch (Throwable $ignore) {}

            if ($hasDateCol) {
                $stmt = $db->prepare("
                    UPDATE roulette_gains
                    SET utilise = 1, date_utilisation = NOW()
                    WHERE code_promo = :code
                ");
            } else {
                $stmt = $db->prepare("
                    UPDATE roulette_gains
                    SET utilise = 1
                    WHERE code_promo = :code
                ");
            }
            return $stmt->execute([':code' => strtoupper(trim($code))]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Calcule le montant final après application d'un code promo
     */
    public static function appliquerReduction(array $gain, float $montantOriginal): float
    {
        if (!$gain || isset($gain['error'])) {
            return $montantOriginal;
        }

        $type = $gain['type_recompense'] ?? $gain['cadeau_type'] ?? '';
        $valeur = (float)($gain['valeur_reduction'] ?? $gain['valeur'] ?? 0);

        if ($type === 'reduction_pct') {
            return max(0, $montantOriginal * (1 - $valeur / 100));
        }

        if ($type === 'reduction_fixe') {
            return max(0, $montantOriginal - $valeur);
        }

        return $montantOriginal;
    }
}
