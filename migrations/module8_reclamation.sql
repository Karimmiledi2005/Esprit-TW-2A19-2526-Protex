-- MODULE 8 — RÉCLAMATION MIGRATIONS

-- RC1: SLA tracking
ALTER TABLE reclamation ADD COLUMN sla_heures INT DEFAULT 48 COMMENT 'Délai max de réponse en heures' AFTER statut;

-- RC2: Response templates
CREATE TABLE IF NOT EXISTS reponse_template (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(100) NOT NULL,
    contenu TEXT NOT NULL,
    categorie ENUM('accusé','refus','complement','resolution','autre') DEFAULT 'autre',
    id_agence INT NULL,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES user(id_user),
    FOREIGN KEY (id_agence) REFERENCES agence(id_agence),
    INDEX(categorie),
    INDEX(id_agence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RC3: Escalation tracking
ALTER TABLE reclamation ADD COLUMN escalade BOOL DEFAULT 0 AFTER sla_heures;
ALTER TABLE reclamation ADD COLUMN escalade_at DATETIME NULL;
ALTER TABLE reclamation ADD COLUMN escalade_par INT NULL;
ALTER TABLE reclamation ADD CONSTRAINT fk_escalade_par FOREIGN KEY (escalade_par) REFERENCES user(id_user);

-- RC5: Satisfaction rating
CREATE TABLE IF NOT EXISTS reclamation_satisfaction (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_reclamation INT NOT NULL UNIQUE,
    note TINYINT COMMENT '1-5 stars',
    commentaire TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_reclamation) REFERENCES reclamation(id_reclamation),
    INDEX(note),
    INDEX(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
