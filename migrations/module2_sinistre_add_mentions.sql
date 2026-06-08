-- S4: Ajouter colonne mentions JSON à sinistre_commentaire
ALTER TABLE sinistre_commentaire ADD COLUMN mentions JSON NULL AFTER commentaire;
