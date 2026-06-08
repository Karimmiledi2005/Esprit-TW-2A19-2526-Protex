-- MODULE 9 — AGENCE MIGRATIONS

-- A3: Opening hours management
CREATE TABLE IF NOT EXISTS agence_horaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_agence INT NOT NULL,
    jour TINYINT COMMENT '1=Lun 7=Dim',
    heure_ouverture TIME,
    heure_fermeture TIME,
    ferme BOOL DEFAULT 0,
    UNIQUE(id_agence, jour),
    FOREIGN KEY (id_agence) REFERENCES agence(id_agence),
    INDEX(id_agence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A4: Add coordinates to agencies
ALTER TABLE agence ADD COLUMN latitude DECIMAL(10,8) NULL AFTER adresse;
ALTER TABLE agence ADD COLUMN longitude DECIMAL(11,8) NULL AFTER latitude;

-- A5: Appointment booking
CREATE TABLE IF NOT EXISTS rendez_vous (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_agence INT NOT NULL,
    id_client INT NOT NULL,
    id_agent INT NULL,
    date_rdv DATETIME NOT NULL,
    motif VARCHAR(200),
    statut ENUM('confirmé','annulé','effectué') DEFAULT 'confirmé',
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_agence) REFERENCES agence(id_agence),
    FOREIGN KEY (id_client) REFERENCES user(id_user),
    FOREIGN KEY (id_agent) REFERENCES user(id_user),
    INDEX(id_agence),
    INDEX(id_client),
    INDEX(date_rdv),
    INDEX(statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A6: Agency ratings
CREATE TABLE IF NOT EXISTS agence_avis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_agence INT NOT NULL,
    id_client INT NOT NULL,
    note TINYINT COMMENT '1-5 stars',
    commentaire TEXT NULL,
    reponse_admin TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(id_agence, id_client),
    FOREIGN KEY (id_agence) REFERENCES agence(id_agence),
    FOREIGN KEY (id_client) REFERENCES user(id_user),
    INDEX(note),
    INDEX(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
