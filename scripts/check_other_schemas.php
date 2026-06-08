<?php
require_once __DIR__ . '/../config.php';
$db = config::getConnexion();

$tables = ['devis', 'sinistre', 'paiement', 'poste_social'];

foreach ($tables as $table) {
    echo "\n=== TABLE $table ===\n";
    try {
        $stmt = $db->query("SHOW COLUMNS FROM $table");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
            echo $c['Field'] . ' (' . $c['Type'] . ")\n";
        }
    } catch (Exception $e) {
        echo "Table does not exist or error.\n";
    }
}
