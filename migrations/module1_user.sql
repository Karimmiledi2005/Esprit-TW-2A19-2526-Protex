-- U1 Audit Log
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NULL,
  `action` VARCHAR(100) NOT NULL,
  `cible` VARCHAR(200) NOT NULL,
  `details` TEXT NULL,
  `ip` VARCHAR(45) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_user`),
  INDEX(`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- U5 Login History
CREATE TABLE IF NOT EXISTS `login_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NOT NULL,
  `ip` VARCHAR(45) NOT NULL,
  `user_agent` TEXT NOT NULL,
  `ville` VARCHAR(100) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- U6 Loyalty Points table
CREATE TABLE IF NOT EXISTS `points_fidelite` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NOT NULL,
  `points` INT NOT NULL,
  `motif` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- U7 Notification Preferences
CREATE TABLE IF NOT EXISTS `notification_preferences` (
  `id_user` INT NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `canal_email` TINYINT(1) DEFAULT 0,
  `canal_sms` TINYINT(1) DEFAULT 0,
  `canal_app` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id_user`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- R5 last_seen Column
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `last_seen` DATETIME NULL;
