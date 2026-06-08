<?php
/**
 * scripts/apply_migration_polymorphic.php
 * Applies the polymorphic complaints migration to the reclamation table.
 * Run from project root: php scripts/apply_migration_polymorphic.php
 */

require_once __DIR__ . '/../config.php';

try {
    $db = config::getConnexion();
    echo "Connecte a la base de donnees.\n\n";

    // Check which columns already exist
    $existingCols = [];
    $stmt = $db->query("SHOW COLUMNS FROM reclamation");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $existingCols[] = $col['Field'];
    }
    echo "Colonnes existantes: " . implode(', ', $existingCols) . "\n\n";

    $tasks = [];

    // 1. Add or modify object_type
    if (!in_array('object_type', $existingCols)) {
        $tasks[] = [
            'label' => "Ajout colonne object_type",
            'sql'   => "ALTER TABLE reclamation ADD COLUMN object_type ENUM('contrat','devis','sinistre','paiement','poste','general') NOT NULL DEFAULT 'general' COMMENT 'Type objet lie' AFTER description"
        ];
    } else {
        $tasks[] = [
            'label' => "Mise a jour ENUM object_type (ajout paiement/poste)",
            'sql'   => "ALTER TABLE reclamation MODIFY COLUMN object_type ENUM('contrat','devis','sinistre','paiement','poste','general') NOT NULL DEFAULT 'general' COMMENT 'Type objet lie'"
        ];
    }

    // 2. Add object_ref if missing
    if (!in_array('object_ref', $existingCols)) {
        $tasks[] = [
            'label' => "Ajout colonne object_ref",
            'sql'   => "ALTER TABLE reclamation ADD COLUMN object_ref VARCHAR(100) DEFAULT NULL COMMENT 'Reference de l objet lie' AFTER object_type"
        ];
    } else {
        echo "  Colonne object_ref existe deja — ignoree.\n";
    }

    // 3. Index
    $tasks[] = [
        'label' => "Creation index idx_object_type",
        'sql'   => "ALTER TABLE reclamation ADD INDEX idx_object_type (object_type)"
    ];

    // 4. Migrate existing contract references
    $tasks[] = [
        'label' => "Migration references contrats existants",
        'sql'   => "UPDATE reclamation SET object_type='contrat', object_ref=COALESCE(NULLIF(TRIM(refContrat),''),NULLIF(TRIM(ref_contrat),'')) WHERE (TRIM(COALESCE(refContrat,''))!='' OR TRIM(COALESCE(ref_contrat,''))!='') AND object_type='general'"
    ];

    $ok   = 0;
    $skip = 0;
    foreach ($tasks as $task) {
        echo "  -> " . $task['label'] . " ... ";
        try {
            $affected = $db->exec($task['sql']);
            echo "OK (affected: $affected)\n";
            $ok++;
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, '1060') !== false || strpos($msg, 'Duplicate column') !== false) {
                echo "Existe deja (ignore)\n";
                $skip++;
            } elseif (strpos($msg, '1061') !== false || strpos($msg, 'Duplicate key name') !== false) {
                echo "Index existe deja (ignore)\n";
                $skip++;
            } else {
                echo "ERREUR: $msg\n";
            }
        }
    }

    echo "\nMigration terminee. Applique: $ok, Ignore: $skip\n";

    // Verify
    $check = $db->query("SHOW COLUMNS FROM reclamation WHERE Field IN ('object_type', 'object_ref')");
    $cols  = array_column($check->fetchAll(PDO::FETCH_ASSOC), 'Field');
    echo "Colonnes verifiees: " . implode(', ', $cols) . "\n";

    if (in_array('object_type', $cols) && in_array('object_ref', $cols)) {
        echo "\nTable reclamation prete pour les reclamations polymorphiques!\n";
    } else {
        echo "\nATTENTION: Des colonnes sont manquantes. Verifiez les erreurs ci-dessus.\n";
    }

} catch (Exception $e) {
    echo "Erreur fatale: " . $e->getMessage() . "\n";
    exit(1);
}
