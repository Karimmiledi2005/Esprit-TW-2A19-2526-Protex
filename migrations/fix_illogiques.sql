-- ============================================================
-- Fix: Ajouter 'accepte' et 'expire' au statut devis (le code les utilise)
-- ============================================================
ALTER TABLE devis
MODIFY COLUMN `statut` ENUM('en_attente','en_cours','traite','converti','refuse','accepte','expire') DEFAULT 'en_attente';

-- ============================================================
-- Fix: Aligner la collation devis (utf8mb4_general_ci) sur user (utf8mb4_unicode_ci)
-- pour permettre les comparaisons email dans la conversion devis->contrat
-- ============================================================
ALTER TABLE devis CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE contrat CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE paiement CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE sinistre CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE reclamation CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================
-- Fix: Ajouter id_devis à contrat pour permettre la vérification de doublon
-- (MariaDB 10.4 ne supporte pas ADD COLUMN IF NOT EXISTS, on utilise une procédure)
-- ============================================================
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_NAME='contrat' AND COLUMN_NAME='id_devis');
SET @sql := IF(@exist = 0, 'ALTER TABLE contrat ADD COLUMN `id_devis` INT DEFAULT NULL AFTER `id_formule`', 'SELECT ''id_devis exists already''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idxExist := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_NAME='contrat' AND INDEX_NAME='idx_id_devis');
SET @sql2 := IF(@idxExist = 0, 'ALTER TABLE contrat ADD INDEX `idx_id_devis` (`id_devis`)', 'SELECT ''idx_id_devis exists already''');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- ============================================================
-- Fix: Ajouter 'valide','refuse','en_attente_remboursement' au statut paiement
-- déjà présents dans le seed mais pas dans full_schema.sql
-- ============================================================
ALTER TABLE paiement
MODIFY COLUMN `statut` ENUM('en_attente','paye','echoue','refuse','rembourse','valide','en_attente_remboursement') DEFAULT 'en_attente';

-- ============================================================
-- Fix: Ajouter id_agence à voice_sessions pour l'isolation multi-agence
-- ============================================================
SET @vsCol := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_NAME='voice_sessions' AND COLUMN_NAME='id_agence');
SET @vsSql := IF(@vsCol = 0, 'ALTER TABLE voice_sessions ADD COLUMN `id_agence` INT DEFAULT NULL AFTER `salle`', 'SELECT ''id_agence exists in voice_sessions''');
PREPARE vsStmt FROM @vsSql;
EXECUTE vsStmt;
DEALLOCATE PREPARE vsStmt;

SET @vsIdx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_NAME='voice_sessions' AND INDEX_NAME='idx_voice_agence');
SET @vsSql2 := IF(@vsIdx = 0, 'ALTER TABLE voice_sessions ADD INDEX `idx_voice_agence` (`id_agence`)', 'SELECT ''idx_voice_agence exists''');
PREPARE vsStmt2 FROM @vsSql2;
EXECUTE vsStmt2;
DEALLOCATE PREPARE vsStmt2;

-- ============================================================
-- Fix: Table agent_room pour assignation persistante des agents aux salles
-- ============================================================
CREATE TABLE IF NOT EXISTS `agent_room` (
  `id_user` INT PRIMARY KEY,
  `salle` VARCHAR(100) NOT NULL,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
