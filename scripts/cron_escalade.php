<?php
/**
 * scripts/cron_escalade.php
 *
 * Usage: php scripts/cron_escalade.php
 * Script d'escalade automatique des réclamations critiques.
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    echo "Ce script doit être exécuté en CLI.\n";
    exit(1);
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controller/EmailService.php';

$db = config::getConnexion();
$now = date('Y-m-d H:i:s');

$sql = "SELECT r.id_reclamation, r.objet, r.id_client, r.priorite, r.date_creation, u.nom, u.prenom, u.email
        FROM reclamation r
        JOIN `user` u ON r.id_client = u.id_user
        WHERE r.escalade = 0
          AND (
              (r.priorite = 'haute' AND r.statut = 'open')
              OR (r.statut = 'open' AND TIMESTAMPDIFF(HOUR, r.date_creation, NOW()) > 72)
          )
        ORDER BY r.date_creation ASC";

$stmt = $db->query($sql);
$reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$reclamations) {
    echo "Aucune réclamation à escalader.\n";
    exit(0);
}

$superStmt = $db->query("SELECT id_user, nom, prenom, email FROM `user` WHERE role = 'superadmin'");
$superAdmins = $superStmt->fetchAll(PDO::FETCH_ASSOC);
$emails = array_column($superAdmins, 'email');
$subject = '⬆️ Escalade automatique de réclamation Protex';
$emailService = new EmailService();

foreach ($reclamations as $reclamation) {
    echo "Escalade réclamation #{$reclamation['id_reclamation']} ({$reclamation['objet']})...\n";
    $db->prepare("UPDATE reclamation SET escalade = 1, escalade_at = NOW(), escalade_par = NULL WHERE id_reclamation = ?")
        ->execute([$reclamation['id_reclamation']]);

    $body = "<h2>Réclamation escaladée automatiquement</h2>"
          . "<p>La réclamation <strong>#{$reclamation['id_reclamation']}</strong> a été escaladée car elle est restée ouverte plus de 72 heures ou possède une priorité élevée.</p>"
          . "<p><strong>Objet :</strong> " . htmlspecialchars($reclamation['objet']) . "</p>"
          . "<p><strong>Client :</strong> {$reclamation['prenom']} {$reclamation['nom']} ({$reclamation['email']})</p>"
          . "<p><strong>Date création :</strong> " . date('d/m/Y H:i', strtotime($reclamation['date_creation'])) . "</p>"
          . "<p>Merci de traiter ce dossier en priorité.</p>";

    foreach ($emails as $email) {
        try {
            $emailService->send($email, $subject, $body);
            echo "  Email envoyé à {$email}\n";
        } catch (Throwable $e) {
            echo "  Échec envoi à {$email} : {$e->getMessage()}\n";
        }
    }
}

echo "Escalade terminée. Total : " . count($reclamations) . " réclamations.\n";
