<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireRole('superadmin');

require_once __DIR__ . '/db.php';

try {
    $pdo->exec("ALTER TABLE avis_agence ADD COLUMN hidden TINYINT(1) NOT NULL DEFAULT 0");
    echo "Column `hidden` added to `avis_agence`.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Column `hidden` already exists.\n";
    } else {
        error_log('migrate_review_hidden.php error: ' . $e->getMessage());
        echo "Erreur lors de la migration. Voir logs pour détails.\n";
    }
}
