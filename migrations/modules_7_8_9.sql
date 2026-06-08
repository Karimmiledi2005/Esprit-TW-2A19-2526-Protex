-- Module 7 : Paiement
CREATE TABLE IF NOT EXISTS relance_paiement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_contrat INT NOT NULL,
    type ENUM('email','sms') NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    sent_by INT
);

ALTER TABLE paiement 
ADD COLUMN IF NOT EXISTS remboursement_partiel DECIMAL(10,2) NULL,
ADD COLUMN IF NOT EXISTS remboursement_motif TEXT NULL,
ADD COLUMN IF NOT EXISTS remboursement_demande_par INT NULL,
ADD COLUMN IF NOT EXISTS remboursement_valide_par INT NULL;

ALTER TABLE contrat 
ADD COLUMN IF NOT EXISTS mode_paiement ENUM('annuel','trimestriel','mensuel') DEFAULT 'annuel';

-- Module 8 : Réclamation
ALTER TABLE reclamation 
ADD COLUMN IF NOT EXISTS sla_heures INT DEFAULT 48 COMMENT 'Délai max de réponse en heures',
ADD COLUMN IF NOT EXISTS escalade BOOL DEFAULT 0,
ADD COLUMN IF NOT EXISTS escalade_at DATETIME NULL,
ADD COLUMN IF NOT EXISTS escalade_par INT NULL;

CREATE TABLE IF NOT EXISTS reponse_template (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(100) NOT NULL,
    contenu TEXT NOT NULL,
    categorie ENUM('accusé','refus','complement','resolution','autre') NOT NULL,
    id_agence INT NULL,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reclamation_satisfaction (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_reclamation INT UNIQUE NOT NULL,
    note TINYINT NOT NULL,
    commentaire TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Module 9 : Agence
CREATE TABLE IF NOT EXISTS agence_horaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_agence INT NOT NULL,
    jour TINYINT NOT NULL COMMENT '1=Lun 7=Dim',
    heure_ouverture TIME,
    heure_fermeture TIME,
    ferme BOOL DEFAULT 0,
    UNIQUE(id_agence, jour)
);

ALTER TABLE agence 
ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,8) NULL,
ADD COLUMN IF NOT EXISTS longitude DECIMAL(11,8) NULL;

CREATE TABLE IF NOT EXISTS rendez_vous (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_agence INT NOT NULL,
    id_client INT NOT NULL,
    id_agent INT NULL,
    date_rdv DATETIME NOT NULL,
    motif VARCHAR(200) NOT NULL,
    statut ENUM('confirmé','annulé','effectué') DEFAULT 'confirmé',
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS agence_avis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_agence INT NOT NULL,
    id_client INT NOT NULL,
    note TINYINT NOT NULL,
    commentaire TEXT NULL,
    reponse_admin TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(id_agence, id_client)
);
