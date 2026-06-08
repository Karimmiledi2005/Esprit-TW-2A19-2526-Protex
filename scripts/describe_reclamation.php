<?php
require_once __DIR__ . '/../config.php';
$pdo = config::getConnexion();
$stmt = $pdo->query('DESCRIBE reclamation');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
