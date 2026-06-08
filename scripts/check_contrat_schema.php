<?php
require_once __DIR__ . '/../config.php';
$db = config::getConnexion();

// Show contrat table structure
echo "=== TABLE CONTRAT ===\n";
$stmt = $db->query("SHOW COLUMNS FROM contrat");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
    echo $c['Field'] . ' (' . $c['Type'] . ")\n";
}

// Check how clients are linked
echo "\n=== SAMPLE DATA (3 rows) ===\n";
$stmt = $db->query("SELECT * FROM contrat LIMIT 3");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    print_r($row);
}

// Check user table link column name
echo "\n=== USER TABLE COLUMNS ===\n";
$stmt = $db->query("SHOW COLUMNS FROM user");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
    echo $c['Field'] . ' (' . $c['Type'] . ")\n";
}
