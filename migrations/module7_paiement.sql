-- MODULE 7 — PAIEMENT MIGRATIONS

-- P2: Relance de paiements
CREATE TABLE IF NOT EXISTS relance_paiement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_contrat INT NOT NULL,
    type ENUM('email','sms') NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    sent_by INT NULL,
    FOREIGN KEY (id_contrat) REFERENCES contrat(id_contrat),
    FOREIGN KEY (sent_by) REFERENCES user(id_user),
    INDEX(id_contrat),
    INDEX(sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- P3: Remboursement partiel
ALTER TABLE paiement ADD COLUMN remboursement_partiel DECIMAL(10,2) NULL AFTER montant;
ALTER TABLE paiement ADD COLUMN remboursement_motif TEXT NULL;
ALTER TABLE paiement ADD COLUMN remboursement_demande_par INT NULL;
ALTER TABLE paiement ADD COLUMN remboursement_valide_par INT NULL;
ALTER TABLE paiement ADD CONSTRAINT fk_remboursement_demande FOREIGN KEY (remboursement_demande_par) REFERENCES user(id_user);
ALTER TABLE paiement ADD CONSTRAINT fk_remboursement_valide FOREIGN KEY (remboursement_valide_par) REFERENCES user(id_user);

-- P5: Modes de paiement
ALTER TABLE contrat ADD COLUMN mode_paiement ENUM('annuel','trimestriel','mensuel') DEFAULT 'annuel' AFTER statut_contrat;

-- P6: Notification paiement imminent (optionnel, utilise table notification existante)
-- Pas de migration supplémentaire, on utilise type='paiement_imminent' dans la table notification existante
