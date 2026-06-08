-- ============================================================
-- Protex Assurance — Création des tables manquantes
-- Exécuter dans phpMyAdmin (base: assurance)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------
-- 1. USER / AUTH
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user` (
  `id_user` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(100) DEFAULT NULL,
  `prenom` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `mot_de_passe` VARCHAR(255) NOT NULL,
  `role` ENUM('client','agent','admin','superadmin') DEFAULT 'client',
  `statut` ENUM('actif','inactif','banni') DEFAULT 'actif',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `avatar_url` VARCHAR(500) DEFAULT NULL,
  `telephone` VARCHAR(20) DEFAULT NULL,
  `adresse` TEXT DEFAULT NULL,
  `email_verified_at` DATETIME DEFAULT NULL,
  `face_encoding` TEXT DEFAULT NULL,
  `google_id` VARCHAR(255) DEFAULT NULL,
  `github_id` VARCHAR(255) DEFAULT NULL,
  `points_parrainage` INT DEFAULT 0,
  `referral_code` VARCHAR(20) DEFAULT NULL,
  `last_seen` DATETIME DEFAULT NULL,
  `hide_online_status` TINYINT(1) DEFAULT 0,
  `onboarding_done` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX(`role`),
  INDEX(`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin` (
  `id_admin` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NOT NULL UNIQUE,
  `id_agence` INT DEFAULT NULL,
  `niveau_acces` ENUM('superadmin','admin') DEFAULT 'admin',
  FOREIGN KEY (`id_user`) REFERENCES `user`(`id_user`),
  INDEX(`id_agence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `agent` (
  `id_agent` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NOT NULL UNIQUE,
  `id_agence` INT NOT NULL,
  `salaire` DECIMAL(10,2) DEFAULT 0,
  FOREIGN KEY (`id_user`) REFERENCES `user`(`id_user`),
  INDEX(`id_agence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `client` (
  `id_client` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NOT NULL UNIQUE,
  `id_agence` INT DEFAULT NULL,
  `numero_client` VARCHAR(50) DEFAULT NULL,
  FOREIGN KEY (`id_user`) REFERENCES `user`(`id_user`),
  INDEX(`id_agence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `login_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NOT NULL,
  `ip` VARCHAR(45) NOT NULL,
  `user_agent` TEXT NOT NULL,
  `ville` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL,
  `ip` VARCHAR(45) NOT NULL,
  `attempted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`ip`),
  INDEX(`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `otp_codes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NOT NULL,
  `code` VARCHAR(6) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`email`),
  INDEX(`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `cible` VARCHAR(200) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `ip` VARCHAR(45) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_user`),
  INDEX(`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notification` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NOT NULL,
  `type` VARCHAR(50) DEFAULT 'system',
  `message` TEXT NOT NULL,
  `lien` VARCHAR(500) DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_user`),
  INDEX(`is_read`),
  INDEX(`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notification_preferences` (
  `id_user` INT NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `canal_email` TINYINT(1) DEFAULT 0,
  `canal_sms` TINYINT(1) DEFAULT 0,
  `canal_app` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id_user`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 2. PRODUCT CATALOGUE
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categorie` (
  `id_categorie` INT AUTO_INCREMENT PRIMARY KEY,
  `nom_categorie` VARCHAR(100) NOT NULL,
  `description_categorie` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `offre` (
  `id_offre` INT AUTO_INCREMENT PRIMARY KEY,
  `nom_offre` VARCHAR(200) NOT NULL,
  `type_offre` ENUM('auto','sante','habitation','vie') DEFAULT 'auto',
  `description` TEXT DEFAULT NULL,
  `prix_mensuel` DECIMAL(10,2) DEFAULT 0,
  `prix_annuel` DECIMAL(10,2) DEFAULT 0,
  `couverture` TEXT DEFAULT NULL,
  `plafond` DECIMAL(15,2) DEFAULT 0,
  `duree_min` INT DEFAULT 1,
  `statut` ENUM('active','inactive') DEFAULT 'active',
  `id_categorie` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`type_offre`),
  INDEX(`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `formule` (
  `id_formule` INT AUTO_INCREMENT PRIMARY KEY,
  `nom_formule` VARCHAR(100) NOT NULL,
  `description_formule` TEXT DEFAULT NULL,
  `id_categorie` INT NOT NULL,
  `prix_base` DECIMAL(10,2) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_categorie`) REFERENCES `categorie`(`id_categorie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `garantie` (
  `id_garantie` INT AUTO_INCREMENT PRIMARY KEY,
  `nom_garantie` VARCHAR(100) NOT NULL,
  `description_garantie` TEXT DEFAULT NULL,
  `id_categorie` INT NOT NULL,
  `plafond_defaut` DECIMAL(10,2) DEFAULT NULL,
  `franchise_defaut` DECIMAL(10,2) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_categorie`) REFERENCES `categorie`(`id_categorie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `formule_garantie` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_formule` INT NOT NULL,
  `id_garantie` INT NOT NULL,
  `plafond_formule` DECIMAL(10,2) DEFAULT NULL,
  `franchise_formule` DECIMAL(10,2) DEFAULT NULL,
  UNIQUE(`id_formule`, `id_garantie`),
  FOREIGN KEY (`id_formule`) REFERENCES `formule`(`id_formule`),
  FOREIGN KEY (`id_garantie`) REFERENCES `garantie`(`id_garantie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `avis_offre` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_offre` INT NOT NULL,
  `id_client` INT NOT NULL,
  `note` TINYINT NOT NULL,
  `commentaire` TEXT DEFAULT NULL,
  `hidden` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_offre`) REFERENCES `offre`(`id_offre`),
  FOREIGN KEY (`id_client`) REFERENCES `user`(`id_user`),
  INDEX(`note`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 3. DEVIS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `devis` (
  `id_devis` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(100) DEFAULT NULL,
  `prenom` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `telephone` VARCHAR(20) DEFAULT NULL,
  `type_assurance` VARCHAR(50) DEFAULT NULL,
  `id_offre` INT DEFAULT NULL,
  `montant_estime` DECIMAL(10,2) DEFAULT NULL,
  `statut` ENUM('en_attente','en_cours','traite','converti','refuse','accepte','expire') DEFAULT 'en_attente',
  `reponse_admin` TEXT DEFAULT NULL,
  `id_user` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_user`),
  INDEX(`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `devis_auto` (
  `id_devis_auto` INT AUTO_INCREMENT PRIMARY KEY,
  `id_devis` INT NOT NULL,
  `marque` VARCHAR(100) DEFAULT NULL,
  `modele` VARCHAR(100) DEFAULT NULL,
  `annee` INT DEFAULT NULL,
  `immatriculation` VARCHAR(50) DEFAULT NULL,
  `puissance` INT DEFAULT NULL,
  `carburant` VARCHAR(50) DEFAULT NULL,
  `valeur_vehicule` DECIMAL(10,2) DEFAULT NULL,
  `usage_vehicule` VARCHAR(100) DEFAULT NULL,
  FOREIGN KEY (`id_devis`) REFERENCES `devis`(`id_devis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `devis_habitation` (
  `id_devis_habitation` INT AUTO_INCREMENT PRIMARY KEY,
  `id_devis` INT NOT NULL,
  `type_habitation` VARCHAR(100) DEFAULT NULL,
  `adresse` TEXT DEFAULT NULL,
  `superficie` DECIMAL(10,2) DEFAULT NULL,
  `nombre_pieces` INT DEFAULT NULL,
  `valeur_bien` DECIMAL(10,2) DEFAULT NULL,
  `statut_occupation` VARCHAR(100) DEFAULT NULL,
  FOREIGN KEY (`id_devis`) REFERENCES `devis`(`id_devis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `devis_sante` (
  `id_devis_sante` INT AUTO_INCREMENT PRIMARY KEY,
  `id_devis` INT NOT NULL,
  `age` INT DEFAULT NULL,
  `situation_familiale` VARCHAR(50) DEFAULT NULL,
  `nombre_beneficiaires` INT DEFAULT NULL,
  `antecedents_medicaux` TEXT DEFAULT NULL,
  `couverture_souhaitee` VARCHAR(200) DEFAULT NULL,
  `profession` VARCHAR(100) DEFAULT NULL,
  FOREIGN KEY (`id_devis`) REFERENCES `devis`(`id_devis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 4. CONTRATS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contrat` (
  `id_contrat` INT AUTO_INCREMENT PRIMARY KEY,
  `numero_contrat` VARCHAR(50) DEFAULT NULL,
  `type_contrat` VARCHAR(100) DEFAULT NULL,
  `date_debut` DATE DEFAULT NULL,
  `date_fin` DATE DEFAULT NULL,
  `prime` DECIMAL(10,2) DEFAULT 0,
  `franchise` DECIMAL(10,2) DEFAULT 0,
  `statut_contrat` ENUM('actif','resilie','expire','suspendu') DEFAULT 'actif',
  `mode_paiement` ENUM('annuel','trimestriel','mensuel') DEFAULT 'annuel',
  `id_user` INT NOT NULL,
  `id_categorie` INT DEFAULT NULL,
  `id_formule` INT DEFAULT NULL,
  `id_devis` INT DEFAULT NULL,
  `formule_contrat` TEXT DEFAULT NULL,
  `details_contrat` TEXT DEFAULT NULL,
  INDEX(`id_devis`),
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_user`) REFERENCES `user`(`id_user`),
  INDEX(`numero_contrat`),
  INDEX(`statut_contrat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `contrat_historique` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_contrat` INT NOT NULL,
  `id_user` INT NOT NULL,
  `champ_modifie` VARCHAR(100) DEFAULT NULL,
  `ancienne_valeur` TEXT DEFAULT NULL,
  `nouvelle_valeur` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_contrat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `contrat_garantie_override` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_contrat` INT NOT NULL,
  `id_garantie` INT NOT NULL,
  `plafond_custom` DECIMAL(10,2) DEFAULT NULL,
  `franchise_custom` DECIMAL(10,2) DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(`id_contrat`, `id_garantie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 5. SINISTRES
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sinistre` (
  `id_sinistre` INT AUTO_INCREMENT PRIMARY KEY,
  `type` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `photo_url` VARCHAR(500) DEFAULT NULL,
  `statut` ENUM('en_attente','en_traitement','rembourse','refuse') DEFAULT 'en_attente',
  `id_contrat` INT DEFAULT NULL,
  `id_user` INT NOT NULL,
  `id_agence` INT DEFAULT NULL,
  `id_agent_assigne` INT DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `date_declaration` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_user`) REFERENCES `user`(`id_user`),
  INDEX(`statut`),
  INDEX(`id_user`),
  INDEX(`id_agent_assigne`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sinistre_commentaire` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_sinistre` INT NOT NULL,
  `id_user` INT NOT NULL,
  `commentaire` TEXT NOT NULL,
  `mentions` JSON DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_sinistre`),
  INDEX(`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sinistre_fichier` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_sinistre` INT NOT NULL,
  `nom_fichier` VARCHAR(255) NOT NULL,
  `chemin` VARCHAR(512) NOT NULL,
  `type` VARCHAR(100) NOT NULL,
  `taille` INT UNSIGNED NOT NULL,
  `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_sinistre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `message_sinistre` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_sinistre` INT NOT NULL,
  `id_user` INT NOT NULL,
  `contenu` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_sinistre`),
  INDEX(`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `traitement` (
  `id_traitement` INT AUTO_INCREMENT PRIMARY KEY,
  `id_sinistre` INT NOT NULL,
  `decision` VARCHAR(255) DEFAULT NULL,
  `montant_indemnise` DECIMAL(10,2) DEFAULT 0,
  `statut` VARCHAR(50) DEFAULT NULL,
  `valide_par` INT DEFAULT NULL,
  `nom_agent` VARCHAR(100) DEFAULT NULL,
  `message_agent` TEXT DEFAULT NULL,
  `date_traitement` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_sinistre`) REFERENCES `sinistre`(`id_sinistre`),
  INDEX(`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fraud_analysis` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_sinistre` INT DEFAULT NULL,
  `id_user` INT DEFAULT NULL,
  `score_global` DECIMAL(5,2) DEFAULT 0,
  `niveau_risque` ENUM('faible','moyen','eleve','fraude') DEFAULT 'faible',
  `analyse_texte` TEXT DEFAULT NULL,
  `analyse_comportement` TEXT DEFAULT NULL,
  `analyse_image` TEXT DEFAULT NULL,
  `recommandation_ia` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_sinistre`),
  INDEX(`niveau_risque`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 6. PAIEMENTS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `paiement` (
  `id_paiement` INT AUTO_INCREMENT PRIMARY KEY,
  `reference` VARCHAR(50) DEFAULT NULL,
  `montant` DECIMAL(10,2) NOT NULL,
  `remboursement_partiel` DECIMAL(10,2) DEFAULT NULL,
  `remboursement_motif` TEXT DEFAULT NULL,
  `remboursement_demande_par` INT DEFAULT NULL,
  `remboursement_valide_par` INT DEFAULT NULL,
  `methode` VARCHAR(50) DEFAULT NULL,
  `periodicite` VARCHAR(50) DEFAULT NULL,
  `statut` ENUM('en_attente','paye','echoue','refuse','rembourse','valide','en_attente_remboursement') DEFAULT 'en_attente',
  `date_echeance` DATE DEFAULT NULL,
  `date_paiement` DATETIME DEFAULT NULL,
  `num_carte_masque` VARCHAR(20) DEFAULT NULL,
  `motif_refus` TEXT DEFAULT NULL,
  `code_promo` VARCHAR(50) DEFAULT NULL,
  `id_contrat` INT DEFAULT NULL,
  `id_offre` INT DEFAULT NULL,
  `id_user` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_user`) REFERENCES `user`(`id_user`),
  INDEX(`statut`),
  INDEX(`date_echeance`),
  INDEX(`id_contrat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `relance_paiement` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_contrat` INT NOT NULL,
  `type` ENUM('email','sms') NOT NULL,
  `sent_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `sent_by` INT DEFAULT NULL,
  INDEX(`id_contrat`),
  INDEX(`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 7. RECLAMATIONS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reclamation` (
  `id_reclamation` INT AUTO_INCREMENT PRIMARY KEY,
  `objet` VARCHAR(200) NOT NULL,
  `type` VARCHAR(50) DEFAULT NULL,
  `statut` ENUM('en_attente','en_cours','resolue','fermee') DEFAULT 'en_attente',
  `sla_heures` INT DEFAULT 48,
  `escalade` BOOL DEFAULT 0,
  `escalade_at` DATETIME DEFAULT NULL,
  `escalade_par` INT DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `priorite` ENUM('basse','normale','haute','critique') DEFAULT 'normale',
  `email` VARCHAR(255) DEFAULT NULL,
  `refContrat` VARCHAR(100) DEFAULT NULL,
  `recRef` VARCHAR(100) DEFAULT NULL,
  `id_user` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_user`) REFERENCES `user`(`id_user`),
  INDEX(`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `reponse` (
  `id_reponse` INT AUTO_INCREMENT PRIMARY KEY,
  `date_reponse` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `contenu` TEXT NOT NULL,
  `statut` VARCHAR(50) DEFAULT NULL,
  `reclamation_id` INT NOT NULL,
  `id_user` INT DEFAULT NULL,
  FOREIGN KEY (`reclamation_id`) REFERENCES `reclamation`(`id_reclamation`),
  FOREIGN KEY (`id_user`) REFERENCES `user`(`id_user`),
  INDEX(`reclamation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `reponse_template` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `titre` VARCHAR(100) NOT NULL,
  `contenu` TEXT NOT NULL,
  `categorie` ENUM('accuse','refus','complement','resolution','autre') DEFAULT 'autre',
  `id_agence` INT DEFAULT NULL,
  `created_by` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`categorie`),
  INDEX(`id_agence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `reponse_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_reponse` INT NOT NULL,
  `contenu_avant` TEXT DEFAULT NULL,
  `modifie_par` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_reponse`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `reclamation_satisfaction` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_reclamation` INT NOT NULL UNIQUE,
  `note` TINYINT DEFAULT NULL,
  `commentaire` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`note`),
  INDEX(`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `audit_reclamation` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_reclamation` INT NOT NULL,
  `id_user` INT DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_reclamation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 8. AGENCES
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `agence` (
  `id_agence` INT AUTO_INCREMENT PRIMARY KEY,
  `nom_agence` VARCHAR(200) NOT NULL,
  `pays` VARCHAR(100) DEFAULT 'Tunisie',
  `tel` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `statut` ENUM('active','inactive') DEFAULT 'active',
  `adresse` TEXT DEFAULT NULL,
  `latitude` DECIMAL(10,8) DEFAULT NULL,
  `longitude` DECIMAL(11,8) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `agence_horaires` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_agence` INT NOT NULL,
  `jour` TINYINT NOT NULL COMMENT '1=Lun 7=Dim',
  `heure_ouverture` TIME DEFAULT NULL,
  `heure_fermeture` TIME DEFAULT NULL,
  `ferme` BOOL DEFAULT 0,
  UNIQUE(`id_agence`, `jour`),
  FOREIGN KEY (`id_agence`) REFERENCES `agence`(`id_agence`),
  INDEX(`id_agence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `rendez_vous` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_agence` INT NOT NULL,
  `id_client` INT NOT NULL,
  `id_agent` INT DEFAULT NULL,
  `date_rdv` DATETIME NOT NULL,
  `motif` VARCHAR(200) DEFAULT NULL,
  `statut` ENUM('confirme','annule','effectue') DEFAULT 'confirme',
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_agence`) REFERENCES `agence`(`id_agence`),
  FOREIGN KEY (`id_client`) REFERENCES `user`(`id_user`),
  INDEX(`id_agence`),
  INDEX(`id_client`),
  INDEX(`date_rdv`),
  INDEX(`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `agence_avis` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_agence` INT NOT NULL,
  `id_client` INT NOT NULL,
  `note` TINYINT NOT NULL,
  `commentaire` TEXT DEFAULT NULL,
  `reponse_admin` TEXT DEFAULT NULL,
  `hidden` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(`id_agence`, `id_client`),
  FOREIGN KEY (`id_agence`) REFERENCES `agence`(`id_agence`),
  FOREIGN KEY (`id_client`) REFERENCES `user`(`id_user`),
  INDEX(`note`),
  INDEX(`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Legacy avis_agence table (used by some old queries)
CREATE TABLE IF NOT EXISTS `avis_agence` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_agence` INT NOT NULL,
  `id_client` INT NOT NULL,
  `note` INT DEFAULT NULL,
  `commentaire` TEXT DEFAULT NULL,
  `date_avis` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_agence`),
  INDEX(`id_client`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 9. SOCIAL / RESEAU
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `poste` (
  `id_poste` INT AUTO_INCREMENT PRIMARY KEY,
  `contenu` TEXT NOT NULL,
  `date_publication` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `note` INT DEFAULT NULL,
  `auteur` VARCHAR(100) DEFAULT NULL,
  `nb_likes` INT DEFAULT 0,
  `nb_commentaires` INT DEFAULT 0,
  `id_agence` INT DEFAULT NULL,
  `id_user` INT DEFAULT NULL,
  `media_url` VARCHAR(500) DEFAULT NULL,
  `hidden` TINYINT(1) DEFAULT 0,
  `signalements` INT DEFAULT 0,
  FOREIGN KEY (`id_agence`) REFERENCES `agence`(`id_agence`),
  INDEX(`date_publication`),
  INDEX(`hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `commentaire` (
  `id_commentaire` INT AUTO_INCREMENT PRIMARY KEY,
  `contenu` TEXT NOT NULL,
  `id_poste` INT NOT NULL,
  `id_client` INT NOT NULL,
  `id_commentaire_parent` INT DEFAULT NULL,
  `hidden` TINYINT(1) DEFAULT 0,
  `signalements` INT DEFAULT 0,
  `date_commentaire` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_poste`) REFERENCES `poste`(`id_poste`),
  INDEX(`id_poste`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `like_post` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_poste` INT NOT NULL,
  `id_client` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(`id_poste`, `id_client`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `post_reaction` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_post` INT NOT NULL,
  `id_user` INT NOT NULL,
  `type` ENUM('like','love','wow','sad') DEFAULT 'like',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(`id_post`, `id_user`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `story` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NOT NULL,
  `media_url` VARCHAR(500) DEFAULT NULL,
  `contenu` TEXT DEFAULT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_user`),
  INDEX(`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `friendships` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NOT NULL,
  `id_friend` INT NOT NULL,
  `statut` ENUM('pending','accepted','blocked') DEFAULT 'pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(`id_user`, `id_friend`),
  INDEX(`id_user`),
  INDEX(`id_friend`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sos_alerts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `lat` DECIMAL(10,8) DEFAULT NULL,
  `lng` DECIMAL(11,8) DEFAULT NULL,
  `accuracy` DECIMAL(10,2) DEFAULT NULL,
  `nb_contacts_alertes` INT DEFAULT 0,
  `statut` ENUM('actif','resolu') DEFAULT 'actif',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`user_id`),
  INDEX(`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 10. MESSAGERIE
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `contenu` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`sender_id`),
  INDEX(`receiver_id`),
  INDEX(`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `messages_admin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_conversation` INT DEFAULT NULL,
  `id_user` INT DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_conversation`),
  INDEX(`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `conversations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(200) DEFAULT NULL,
  `type` ENUM('privee','groupe') DEFAULT 'privee',
  `cree_par` INT DEFAULT NULL,
  `derniere_activite` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `conversation_participants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_conversation` INT NOT NULL,
  `id_user` INT NOT NULL,
  UNIQUE(`id_conversation`, `id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `message_mentions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_message` INT NOT NULL,
  `id_user` INT NOT NULL,
  INDEX(`id_message`),
  INDEX(`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 11. GAMIFICATION
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `points_fidelite` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NOT NULL,
  `points` INT NOT NULL,
  `motif` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `roulette_jeu` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email_client` VARCHAR(255) DEFAULT NULL,
  `palier` VARCHAR(50) DEFAULT NULL,
  `cadeau_label` VARCHAR(255) DEFAULT NULL,
  `code_promo` VARCHAR(50) DEFAULT NULL,
  `valeur_reduction` DECIMAL(10,2) DEFAULT 0,
  `utilise` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `roulette_gains` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) DEFAULT NULL,
  `nom` VARCHAR(100) DEFAULT NULL,
  `prenom` VARCHAR(100) DEFAULT NULL,
  `cadeau_label` VARCHAR(255) DEFAULT NULL,
  `code_promo` VARCHAR(50) DEFAULT NULL,
  `valeur_reduction` DECIMAL(10,2) DEFAULT 0,
  `utilise` TINYINT(1) DEFAULT 0,
  `date_utilisation` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `jeu_snake` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NOT NULL,
  `score` INT DEFAULT 0,
  `vitesse` INT DEFAULT 0,
  `duree_sec` INT DEFAULT 0,
  `serpents_manges` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `jeu_memory` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NOT NULL,
  `temps` INT DEFAULT 0,
  `coups` INT DEFAULT 0,
  `difficulte` VARCHAR(20) DEFAULT 'facile',
  `nb_paires` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 12. ALERTES / NOTIFICATIONS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sms_alerts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_contrat` INT DEFAULT NULL,
  `id_client` INT NOT NULL,
  `telephone` VARCHAR(20) DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `type_alert` VARCHAR(50) DEFAULT NULL,
  `statut` ENUM('envoye','echec','en_attente') DEFAULT 'en_attente',
  `infobip_message_id` VARCHAR(255) DEFAULT NULL,
  `infobip_bulk_id` VARCHAR(255) DEFAULT NULL,
  `response_json` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_client`),
  INDEX(`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 13. IA / RECOMMANDATIONS
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `recommandation_historique` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT DEFAULT NULL,
  `besoin` VARCHAR(255) DEFAULT NULL,
  `budget` DECIMAL(10,2) DEFAULT NULL,
  `profil_risque` VARCHAR(50) DEFAULT NULL,
  `id_formule_recommandee` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `recommendation_click` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_client` INT NOT NULL,
  `id_offre` INT NOT NULL,
  `clicked_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`id_client`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 14. INFRASTRUCTURE
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip` VARCHAR(45) NOT NULL,
  `endpoint` VARCHAR(100) NOT NULL,
  `hits` INT DEFAULT 1,
  `window_start` DATETIME NOT NULL,
  INDEX(`ip`, `endpoint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- FIN — Toutes les tables ont été créées si elles n'existaient pas
-- ============================================================
