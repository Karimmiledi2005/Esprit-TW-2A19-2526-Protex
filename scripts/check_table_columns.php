<?php
require_once __DIR__ . '/../config.php';
$pdo = config::getConnexion();
$tables = ['contrat', 'paiement'];
foreach ($tables as $table) {
    echo "Table: $table\n";
    $stmt = $pdo->prepare('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION');
    $stmt->execute([$table]);
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo implode(', ', $cols) . "\n\n";
}
