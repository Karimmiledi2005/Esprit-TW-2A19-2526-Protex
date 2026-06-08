<?php
require_once __DIR__ . '/../config.php';
$pdo = config::getConnexion();
$stmt = $pdo->prepare('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION');
$stmt->execute(['contrat']);
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo 'contrat: ' . implode(', ', $cols) . "\n";
