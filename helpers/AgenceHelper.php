<?php
class AgenceHelper
{
    public static function getStatutOuverture(int $idAgence): array
    {
        $db = config::getConnexion();
        $jour = (int)date('N'); // 1=Mon..7=Sun
        $now = date('H:i');
        $stmt = $db->prepare("SELECT heure_ouverture, heure_fermeture, ferme FROM agence_horaires WHERE id_agence = ? AND jour = ?");
        $stmt->execute([$idAgence, $jour]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['ferme']) {
            return [
                'ouvert' => false,
                'statut' => 'Fermé',
                'prochain_ouverture' => self::getProchainOuverture($db, $idAgence)
            ];
        }
        $ouverture = substr($row['heure_ouverture'], 0, 5);
        $fermeture = substr($row['heure_fermeture'], 0, 5);
        $ouvert = $now >= $ouverture && $now <= $fermeture;
        return [
            'ouvert' => $ouvert,
            'statut' => $ouvert ? "🟢 Ouvert · Ferme à {$fermeture}" : "🔴 Fermé · Ouvre à {$ouverture}",
            'fermeture' => $fermeture,
            'ouverture' => $ouverture,
        ];
    }

    private static function getProchainOuverture(PDO $db, int $idAgence): ?string
    {
        $jours = [1,2,3,4,5,6,7];
        $today = (int)date('N');
        for ($i = 0; $i < 7; $i++) {
            $d = $jours[($today + $i - 1) % 7];
            $stmt = $db->prepare("SELECT heure_ouverture FROM agence_horaires WHERE id_agence = ? AND jour = ? AND ferme = 0");
            $stmt->execute([$idAgence, $d]);
            $row = $stmt->fetch();
            if ($row) {
                $dayNames = ['', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
                return ucfirst($dayNames[$d]) . ' à ' . substr($row['heure_ouverture'], 0, 5);
            }
        }
        return null;
    }
}
