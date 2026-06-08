<?php

require_once __DIR__ . '/controller/ContratController.php';

echo "=== CRON SMS EXPIRATION ===\n";

try {

    $controller = new ContratController();
    // envoyer alertes pour contrats expirant dans 30 jours
    $result = $controller->envoyerAlertesSmsExpiration(30);

    echo "Alertes SMS traitées avec succès.\n";

    if (is_array($result)) {
        print_r($result);
    }

} catch (Throwable $e) {
    error_log('cron_sms_expiration error: ' . $e->getMessage());
    echo "Erreur (voir journal serveur)\n";
}