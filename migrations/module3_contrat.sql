CREATE TABLE IF NOT EXISTS contrat_historique(
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_contrat INT NOT NULL,
    id_user INT NOT NULL,
    champ_modifie VARCHAR(100),
    ancienne_valeur TEXT,
    nouvelle_valeur TEXT,
    created_at DATETIME DEFAULT NOW()
);
