-- Migrations for Module 6 (Garantie)

-- Add custom plafond and franchise to the pivot table between formule and garantie
ALTER TABLE formule_garantie
ADD COLUMN plafond_formule DECIMAL(10,2) NULL,
ADD COLUMN franchise_formule DECIMAL(10,2) NULL;

-- Create the table for per-contract guarantee overrides
CREATE TABLE IF NOT EXISTS contrat_garantie_override (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_contrat INT NOT NULL,
    id_garantie INT NOT NULL,
    plafond_custom DECIMAL(10,2) NULL,
    franchise_custom DECIMAL(10,2) NULL,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_contrat_garantie (id_contrat, id_garantie)
);
