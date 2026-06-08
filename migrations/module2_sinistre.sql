-- Table for internal comments on sinistres
CREATE TABLE IF NOT EXISTS `sinistre_commentaire` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_sinistre` INT NOT NULL,
  `id_user` INT NOT NULL,
  `commentaire` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (`id_sinistre`),
  INDEX (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for storing uploaded files per sinistre
CREATE TABLE IF NOT EXISTS `sinistre_fichier` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_sinistre` INT NOT NULL,
  `nom_fichier` VARCHAR(255) NOT NULL,
  `chemin` VARCHAR(512) NOT NULL,
  `type` VARCHAR(100) NOT NULL,
  `taille` INT UNSIGNED NOT NULL,
  `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (`id_sinistre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for sinistre chat messages
CREATE TABLE IF NOT EXISTS `message_sinistre` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_sinistre` INT NOT NULL,
  `id_user` INT NOT NULL,
  `contenu` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (`id_sinistre`),
  INDEX (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
