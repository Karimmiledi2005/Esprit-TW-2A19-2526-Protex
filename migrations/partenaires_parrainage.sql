-- ─── PARTENAIRES ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS partenaire (
  id_partenaire  INT AUTO_INCREMENT PRIMARY KEY,
  nom            VARCHAR(150) NOT NULL,
  type           ENUM('garage','clinique','pharmacie','hotel',
                      'avocat','serrurier','location_voiture',
                      'telemedicine','autre') NOT NULL,
  description    TEXT,
  logo_url       VARCHAR(255) DEFAULT NULL,
  adresse        VARCHAR(255),
  ville          VARCHAR(100),
  gouvernorat    VARCHAR(100) DEFAULT NULL,
  telephone      VARCHAR(30),
  email          VARCHAR(150) DEFAULT NULL,
  site_web       VARCHAR(255) DEFAULT NULL,
  latitude       DECIMAL(10,8) DEFAULT NULL,
  longitude      DECIMAL(11,8) DEFAULT NULL,
  avantage       VARCHAR(255) COMMENT 'Résumé court ex: -15% main d oeuvre',
  avantage_detail TEXT,
  horaires       VARCHAR(255) DEFAULT 'Lun-Ven 8h-18h',
  note_moyenne   DECIMAL(3,2) DEFAULT 0.00,
  nb_avis        INT DEFAULT 0,
  actif          TINYINT(1) DEFAULT 1,
  ordre          INT DEFAULT 0 COMMENT 'Ordre d affichage',
  created_at     DATETIME DEFAULT NOW(),
  updated_at     DATETIME DEFAULT NOW() ON UPDATE NOW(),
  INDEX(type), INDEX(ville), INDEX(actif), INDEX(note_moyenne)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS partenaire_agence (
  id_partenaire INT NOT NULL,
  id_agence     INT NOT NULL,
  PRIMARY KEY(id_partenaire, id_agence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS partenaire_type_contrat (
  id_partenaire  INT NOT NULL,
  type_contrat   VARCHAR(50) NOT NULL,
  PRIMARY KEY(id_partenaire, type_contrat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS partenaire_avis (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  id_partenaire INT NOT NULL,
  id_user       INT NOT NULL,
  note          TINYINT NOT NULL CHECK(note BETWEEN 1 AND 5),
  commentaire   TEXT,
  signale       TINYINT(1) DEFAULT 0,
  created_at    DATETIME DEFAULT NOW(),
  UNIQUE(id_partenaire, id_user),
  INDEX(id_partenaire)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS partenaire_utilisation (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  id_partenaire INT NOT NULL,
  id_user       INT NOT NULL,
  id_sinistre   INT DEFAULT NULL,
  contexte      VARCHAR(150),
  created_at    DATETIME DEFAULT NOW(),
  INDEX(id_partenaire), INDEX(id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── PARRAINAGE ──────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS parrainage (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  id_parrain      INT NOT NULL,
  id_filleul      INT NOT NULL,
  code_utilise    VARCHAR(20) NOT NULL,
  statut          ENUM('en_attente','valide','recompense','expire')
                  DEFAULT 'en_attente',
  pts_parrain     INT DEFAULT 150,
  pts_filleul     INT DEFAULT 50,
  remise_filleul  DECIMAL(5,2) DEFAULT 5.00,
  remise_parrain  DECIMAL(5,2) DEFAULT 5.00,
  recompense_at   DATETIME DEFAULT NULL,
  created_at      DATETIME DEFAULT NOW(),
  UNIQUE(id_filleul),
  INDEX(id_parrain), INDEX(code_utilise)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Colonnes ajoutées à la table user (MariaDB: sans IF NOT EXISTS)
ALTER TABLE user
  ADD COLUMN code_parrain   VARCHAR(20) UNIQUE DEFAULT NULL,
  ADD COLUMN id_parrain_ref INT DEFAULT NULL
    COMMENT 'ID du client qui a parrainé cet utilisateur';

-- Créer table points_fidelite si elle n'existe pas
CREATE TABLE IF NOT EXISTS points_fidelite (
  id_user       INT NOT NULL,
  points        INT DEFAULT 0,
  motif         VARCHAR(255) DEFAULT '',
  created_at    DATETIME DEFAULT NOW(),
  UNIQUE(id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table de configuration du parrainage
CREATE TABLE IF NOT EXISTS parrainage_config (
  `key`   VARCHAR(50) NOT NULL,
  `value` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO parrainage_config (`key`, `value`) VALUES
  ('points_parrain',  '150'),
  ('points_filleul',  '50'),
  ('points_bonus',    '100'),
  ('points_per_dt',   '200'),
  ('validite_jours',  '30'),
  ('min_contrats',    '1');

-- ─── DONNÉES DE DÉMO ─────────────────────────────────────────

INSERT INTO partenaire
  (nom, type, description, adresse, ville, gouvernorat,
   telephone, latitude, longitude, avantage, avantage_detail, horaires, actif)
VALUES
('Garage Mécauto Sfax',    'garage',
 'Garage agréé Protex spécialisé carrosserie et mécanique. Véhicule de remplacement disponible.',
 '12 Rue de Tunis','Sfax','Sfax','74 234 567',
 34.7406,10.7603,'-15% sur main d oeuvre',
 'Remise de 15% sur la main d oeuvre pour tous les clients Protex. Véhicule de prêt offert 3 jours.',
 'Lun-Sam 8h-18h',1),

('Clinique El Amal',       'clinique',
 'Clinique multidisciplinaire avec tiers payant Protex. Pas d avance de frais pour les clients assurés Santé.',
 '45 Avenue Bourguiba','Sfax','Sfax','74 456 789',
 34.7415,10.7595,'Tiers payant - 0 avance de frais',
 'Prise en charge directe Protex. Pas d avance sur les actes couverts par votre formule Santé.',
 'Urgences 24h/24 - Consultations Lun-Sam 8h-20h',1),

('Pharmacie Centrale Sfax','pharmacie',
 'Pharmacie partenaire avec remise sur les médicaments prescrits après sinistre ou consultation.',
 '8 Rue des Orangers','Sfax','Sfax','74 123 456',
 34.7392,10.7612,'-10% sur ordonnances',
 'Remise de 10% sur tous les médicaments sur ordonnance pour les clients Protex actifs.',
 'Lun-Dim 8h-22h',1),

('Hôtel Novotel Sfax',     'hotel',
 'Hébergement d urgence en cas de sinistre habitation. Tarifs négociés pour les clients Protex.',
 'Route de Tunis Km 3','Sfax','Sfax','74 789 123',
 34.7451,10.7548,'-20% tarif urgence',
 'Tarif préférentiel -20% pour hébergement d urgence suite à sinistre habitation. Sur présentation de la carte Protex.',
 'Réception 24h/24',1),

('Garage Auto Plus Tunis', 'garage',
 'Centre auto agréé Protex à Tunis. Expertise rapide, prise en charge directe des sinistres auto.',
 '89 Avenue de la Liberté','Tunis','Tunis','71 345 678',
 36.8065,10.1815,'Véhicule de remplacement 3j offert',
 'Véhicule de remplacement gratuit pendant 3 jours pour tout sinistre auto traité chez nous.',
 'Lun-Ven 8h-17h30 / Sam 8h-12h',1),

('Polyclinique Hannibal',  'clinique',
 'Polyclinique de référence Tunis. Téléconsultation disponible. Tiers payant Protex accepté.',
 '34 Rue du Lac','Tunis','Tunis','71 567 890',
 36.8380,10.2300,'Tiers payant + téléconsultation incluse',
 'Tiers payant accepté. Téléconsultation gratuite 2x/an pour les clients Protex Santé Premium.',
 'Consultations Lun-Sam 8h-20h / Urgences 24h/24',1),

('Cabinet Maître Ben Salem','avocat',
 'Cabinet d avocats spécialisé en droit des assurances. Première consultation gratuite pour clients Protex.',
 '15 Rue de la Victoire','Sfax','Sfax','74 999 111',
 34.7400,10.7600,'1ère consultation gratuite',
 'Consultation initiale de 30 minutes gratuite pour tout client Protex impliqué dans un litige post-sinistre.',
 'Lun-Ven 9h-17h sur RDV',1),

('Location Auto Sixt Sfax','location_voiture',
 'Location de véhicule pendant la réparation de votre voiture. Tarif Protex exclusif.',
 'Aéroport Sfax-Thyna','Sfax','Sfax','74 444 555',
 34.7179,10.6906,'3 jours offerts après sinistre Auto',
 'Location gratuite 3 jours pour tout sinistre auto avec formule Standard ou Premium. Au-delà : -25%.',
 'Tous les jours 7h-22h',1);

-- Lier les garages aux contrats Auto
INSERT INTO partenaire_type_contrat (id_partenaire, type_contrat)
SELECT p.id_partenaire, 'Auto' FROM partenaire p WHERE p.type = 'garage';
INSERT INTO partenaire_type_contrat (id_partenaire, type_contrat)
SELECT p.id_partenaire, 'Auto' FROM partenaire p WHERE p.type = 'location_voiture';
INSERT INTO partenaire_type_contrat (id_partenaire, type_contrat)
SELECT p.id_partenaire, 'Santé' FROM partenaire p WHERE p.type IN ('clinique','pharmacie','telemedicine');
INSERT INTO partenaire_type_contrat (id_partenaire, type_contrat)
SELECT p.id_partenaire, 'Habitation' FROM partenaire p WHERE p.type IN ('serrurier','hotel');

-- Générer les codes parrain pour les clients existants
UPDATE user
SET code_parrain = CONCAT(
    UPPER(LEFT(REGEXP_REPLACE(nom, '[^a-zA-Z]', ''), 3)),
    '-',
    LPAD(FLOOR(1000 + RAND() * 9000), 4, '0')
)
WHERE role = 'client' AND code_parrain IS NULL;

-- Sans REGEXP_REPLACE (MariaDB < 10.2) :
UPDATE user
SET code_parrain = CONCAT(
    UPPER(LEFT(TRIM(LEADING ' ' FROM nom), 3)),
    '-',
    LPAD(FLOOR(1000 + RAND() * 9000), 4, '0')
)
WHERE role = 'client' AND code_parrain IS NULL AND code_parrain IS NULL;
