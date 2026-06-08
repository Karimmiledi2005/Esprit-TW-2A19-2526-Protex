<?php
/**
 * scripts/cron_fidelite_annuel.php
 *
 * Usage: php scripts/cron_fidelite_annuel.php
 * Crédite +200 points aux clients ayant un contrat actif depuis ≥1 an sans sinistre.
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    echo "Ce script doit être exécuté en CLI.\n";
    exit(1);
}

require_once __DIR__ . '/../config.php';

$db = config::getConnexion();
$now = date('Y-m-d H:i:s');

// Clients avec contrat actif depuis ≥1 an, aucun sinistre sur tous leurs contrats
$sql = "SELECT DISTINCT c.id_user, u.nom, u.prenom
        FROM contrat c
        JOIN user u ON c.id_user = u.id_user
        WHERE c.statut_contrat = 'actif'
          AND c.date_debut IS NOT NULL
          AND c.date_debut <= DATE_SUB(NOW(), INTERVAL 1 YEAR)
          AND c.id_user NOT IN (
              SELECT DISTINCT s.id_user FROM sinistre s WHERE s.statut NOT IN ('refuse')
          )
          AND c.id_user NOT IN (
              SELECT pf.id_user FROM points_fidelite pf
              WHERE pf.motif LIKE '%1 an sans sinistre%'
                AND pf.created_at >= DATE_SUB(NOW(), INTERVAL 11 MONTH)
          )
        ORDER BY c.id_user";

$stmt = $db->query($sql);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$clients) {
    echo "Aucun client éligible pour le bonus annuel.\n";
    exit(0);
}

$insert = $db->prepare("INSERT INTO points_fidelite (id_user, points, motif, created_at) VALUES (?, 200, 'Bonus 1 an sans sinistre', NOW())");
$count = 0;

foreach ($clients as $c) {
    $insert->execute([(int)$c['id_user']]);
    echo "  +200 pts pour {$c['nom']} {$c['prenom']} (ID {$c['id_user']})\n";
    $count++;
}

echo "Bonus annuel terminé. {$count} client(s) crédité(s).\n";
