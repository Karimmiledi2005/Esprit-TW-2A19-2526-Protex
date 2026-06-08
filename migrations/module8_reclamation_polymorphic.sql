-- =============================================================
-- MODULE 8 — Polymorphic Complaints Migration
-- Adds object_type and object_ref columns to reclamation table
-- to allow linking complaints to any module object
-- =============================================================

-- Step 1: Add object_type column
ALTER TABLE reclamation 
  ADD COLUMN IF NOT EXISTS object_type 
    ENUM('contrat','devis','sinistre','paiement','poste','general') 
    NOT NULL DEFAULT 'general' 
    COMMENT 'Type of object this complaint is linked to'
  AFTER description;

-- Step 2: Add object_ref column
ALTER TABLE reclamation 
  ADD COLUMN IF NOT EXISTS object_ref 
    VARCHAR(100) DEFAULT NULL 
    COMMENT 'Reference/ID of the linked object (numero_contrat, id_devis, etc.)'
  AFTER object_type;

-- Step 3: Index for faster lookups by object type
ALTER TABLE reclamation 
  ADD INDEX IF NOT EXISTS idx_object_type (object_type);

-- Step 4: Migrate existing contract references
-- Copy refContrat/ref_contrat into object_ref and set object_type = 'contrat'
UPDATE reclamation 
  SET object_type = 'contrat',
      object_ref  = COALESCE(NULLIF(TRIM(refContrat), ''), NULLIF(TRIM(ref_contrat), ''))
WHERE (TRIM(COALESCE(refContrat, '')) != '' OR TRIM(COALESCE(ref_contrat, '')) != '')
  AND (object_type = 'general' OR object_type IS NULL);
