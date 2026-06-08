<?php
/**
 * Cron script: Alerte paiement à J-7
 * 
 * Usage: php scripts/cron_payment_alerts.php
 * Schedule: 0 8 * * * (daily at 8am)
 *
 * Sends email alerts for contracts with echeance in the next 7 days
 * that haven't been paid yet this month.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

$db = config::getConnexion();

echo "[" . date('Y-m-d H:i:s') . "] Starting payment alert cron...\n";

// Find all active contracts with echeance in next 7 days, not yet paid this month
$stmt = $db->prepare("
    SELECT 
        c.id_contrat, c.numero_contrat, c.prime_contrat, c.date_fin_contrat,
        DATEDIFF(c.date_fin_contrat, NOW()) as jours_restants,
        u.id_user, u.nom, u.prenom, u.email
    FROM contrat c
    JOIN `user` u ON c.id_user = u.id_user
    WHERE c.statut_contrat = 'actif'
      AND c.date_fin_contrat BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
      AND c.id_contrat NOT IN (
          SELECT p.id_offre FROM paiement p 
          WHERE p.statut = 'valide'
            AND MONTH(p.date_paiement) = MONTH(NOW())
            AND YEAR(p.date_paiement) = YEAR(NOW())
      )
    ORDER BY c.date_fin_contrat ASC
");
$stmt->execute();
$contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "[" . date('Y-m-d H:i:s') . "] Found " . count($contracts) . " contracts to alert.\n";

$sent = 0;
$errors = 0;

foreach ($contracts as $c) {
    $email    = $c['email'];
    $name     = $c['prenom'] . ' ' . $c['nom'];
    $contrat  = $c['numero_contrat'];
    $prime    = number_format((float)$c['prime_contrat'], 3, ',', ' ') . ' TND';
    $echeance = date('d/m/Y', strtotime($c['date_fin_contrat']));
    $jours    = max(0, (int)$c['jours_restants']);

    $subject = "⚠️ Rappel paiement — Contrat {$contrat} — Échéance dans {$jours} jour(s)";
    
    $body = "Bonjour {$name},\n\n"
          . "Nous vous rappelons que le paiement de votre contrat d'assurance arrive bientôt à échéance.\n\n"
          . "📋 Contrat N° : {$contrat}\n"
          . "📅 Date d'échéance : {$echeance}\n"
          . "⏰ Dans : {$jours} jour(s)\n"
          . "💰 Prime : {$prime}\n\n"
          . "Veuillez effectuer votre paiement avant la date d'échéance pour maintenir votre couverture.\n\n"
          . "👉 Connectez-vous à votre espace client : " . (defined('BASE_URL') ? BASE_URL : 'http://localhost/assurance') . "/view/FrontOffice/client.php\n\n"
          . "Cordialement,\n"
          . "L'équipe Protex Assurance\n"
          . "contact@protex.tn | +216 71 123 456";

    $headers = [
        'From: Protex Assurance <noreply@protex.tn>',
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion()
    ];

    // Log the attempt
    echo "[" . date('Y-m-d H:i:s') . "] Sending alert to {$email} (Contrat {$contrat}, J-{$jours})...\n";

    // Send email (using PHP mail() — replace with SMTP in production)
    $result = mail($email, $subject, $body, implode("\r\n", $headers));

    if ($result) {
        // Log in relance_paiement table
        try {
            $stmtLog = $db->prepare(
                "INSERT INTO relance_paiement (id_contrat, type, sent_at) VALUES (?, 'email', NOW())"
            );
            $stmtLog->execute([$c['id_contrat']]);
        } catch (Exception $e) {
            // Log error but don't stop
            echo "[ERROR] Could not log relance: " . $e->getMessage() . "\n";
        }
        $sent++;
        echo "[OK] Alert sent.\n";
    } else {
        $errors++;
        echo "[FAIL] Could not send email.\n";
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Done. Sent: {$sent}, Errors: {$errors}.\n";
