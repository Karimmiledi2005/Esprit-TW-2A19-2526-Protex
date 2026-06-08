<?php
/**
 * cron_sla_alert.php
 * Script à exécuter via tâche CRON (ex: toutes les heures)
 * Alerte les superviseurs si des réclamations urgentes sont sans réponse depuis > 24h.
 */
require_once __DIR__ . '/bootstrap.php';

$db = config::getConnexion();

// Réclamations urgentes sans réponse depuis > 24h
$sql = "SELECT r.id, r.objet, r.email, r.date_depot, c.id_agence
        FROM reclamation r
        LEFT JOIN reponse rep ON rep.reclamation_id = r.id
        LEFT JOIN client c ON r.id_user = c.id_user
        WHERE r.priorite = 'Urgente'
          AND r.statut = 'open'
          AND rep.id_re IS NULL
          AND r.date_depot < DATE_SUB(NOW(), INTERVAL 24 HOUR)";

try {
    $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        echo date('[Y-m-d H:i:s]') . " Aucune alerte SLA à envoyer.\n";
        exit(0);
    }

    require_once __DIR__ . '/service/EmailService.php';

    foreach ($rows as $rec) {
        // Récupérer l'admin de l'agence ou le superadmin
        $stmtAdmin = $db->prepare(
            "SELECT email FROM user WHERE (role = 'admin' AND id_agence = ?) OR role = 'superadmin' LIMIT 1"
        );
        $stmtAdmin->execute([$rec['id_agence']]);
        $admin = $stmtAdmin->fetch();
        
        if (!$admin) continue;

        $subject = '[ALERTE SLA] Réclamation URGENTE en retard (#' . $rec['id'] . ')';
        $message = "La réclamation suivante est en attente depuis plus de 24h :\n\n"
                 . "ID : #" . $rec['id'] . "\n"
                 . "Objet : " . $rec['objet'] . "\n"
                 . "Client : " . $rec['email'] . "\n"
                 . "Date dépôt : " . $rec['date_depot'] . "\n\n"
                 . "Merci d'intervenir rapidement.";

        $sent = ReclamationMailer::envoyerNotificationReponse(
            $admin['email'],
            $subject,
            $message,
            'rejet' // Template rouge pour l'urgence
        );

        echo date('[Y-m-d H:i:s]') . " Alerte envoyée à " . $admin['email'] . " pour la réclamation #" . $rec['id'] . " (Status: " . ($sent?'OK':'ECHEC') . ")\n";
    }
} catch (Exception $e) {
    error_log('cron_sla_alert error: ' . $e->getMessage());
    echo date('[Y-m-d H:i:s]') . " Erreur CRON SLA (voir logs)\n";
}
