<?php
require_once __DIR__ . '/../config.php';
$files = [
    __DIR__ . '/../migrations/module7_paiement.sql',
    __DIR__ . '/../migrations/module8_reclamation.sql',
    __DIR__ . '/../migrations/module9_agence.sql',
];
$pdo = config::getConnexion();
foreach ($files as $file) {
    echo "Traitement de $file\n";
    $sql = file_get_contents($file);
    if ($sql === false) {
        echo "Impossible de lire $file\n";
        exit(1);
    }
    $parts = preg_split('/;\s*\n/', $sql);
    foreach ($parts as $part) {
        $statement = trim($part);
        if ($statement === '') {
            continue;
        }
        echo 'Execution : ' . substr(preg_replace('/\s+/', ' ', $statement), 0, 140) . "...\n";
        try {
            $pdo->exec($statement);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'MySQL server has gone away')) {
                echo 'Connexion perdue, reconnexion...\n';
                $pdo = config::getConnexion();
                try {
                    $pdo->exec($statement);
                    continue;
                } catch (Exception $e2) {
                    $msg = $e2->getMessage();
                }
            }
            if (str_contains($msg, 'Duplicate column name') || str_contains($msg, 'Duplicate key name') || str_contains($msg, 'Duplicate key on write or update') || str_contains($msg, 'errno: 121') || (str_contains($msg, 'Table') && str_contains($msg, 'already exists')) || str_contains($msg, "Can't DROP") || str_contains($msg, 'already exists in')) {
                echo 'Ignoré (existe déjà) : ' . trim(preg_replace('/\s+/', ' ', $statement)) . "\n";
                continue;
            }
            echo 'Erreur sur ' . basename($file) . ': ' . $msg . "\n";
            exit(1);
        }
    }
    echo "OK " . basename($file) . "\n";
}
echo "Toutes les migrations ont été appliquées ou déjà présentes.\n";
