-- =====================================================
-- Protex -- Plateforme d'Assurance Digitale
-- ESPRIT School of Engineering -- 2A19 -- 2025-2026
-- Export complet : schema + donnees de demonstration
-- =====================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET time_zone = '+01:00';

CREATE DATABASE IF NOT EXISTS `assurance`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `assurance`;
-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: assurance
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_agence` int(11) DEFAULT NULL,
  `niveau_acces` enum('superadmin','admin','admin_agence') DEFAULT 'admin',
  PRIMARY KEY (`id_admin`),
  UNIQUE KEY `id_user` (`id_user`),
  KEY `id_agence` (`id_agence`),
  CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,1,NULL,'superadmin'),(2,2,1,'admin'),(3,3,2,'admin');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agence`
--

DROP TABLE IF EXISTS `agence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agence` (
  `id_agence` int(11) NOT NULL AUTO_INCREMENT,
  `nom_agence` varchar(200) NOT NULL,
  `pays` varchar(100) DEFAULT 'Tunisie',
  `tel` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `statut` enum('active','inactive') DEFAULT 'active',
  `adresse` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_agence`),
  KEY `statut` (`statut`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agence`
--

LOCK TABLES `agence` WRITE;
/*!40000 ALTER TABLE `agence` DISABLE KEYS */;
INSERT INTO `agence` VALUES (1,'Protex Tunis Centre','Tunisie','+216 71 100 200','tunis@protex.tn','active','Avenue Habib Bourguiba, 1001 Tunis',36.81897000,10.16579000,'2026-06-06 01:14:16'),(2,'Protex Sfax','Tunisie','+216 74 200 300','sfax@protex.tn','active','Avenue de la R??publique, 3000 Sfax',34.74056000,10.76028000,'2026-06-06 01:14:16'),(3,'Protex Sousse','Tunisie','+216 73 300 400','sousse@protex.tn','active','Boulevard de la Corniche, 4000 Sousse',35.82539000,10.63699000,'2026-06-06 01:14:16'),(4,'Protex Monastir','Tunisie','+216 73 400 500','monastir@protex.tn','active','Avenue de l\'Ind??pendance, 5000 Monastir',35.76444000,10.81157000,'2026-06-06 01:14:16'),(5,'Protex Bizerte','Tunisie','+216 72 500 600','bizerte@protex.tn','inactive','Rue Ibn Khaldoun, 7000 Bizerte',37.27440000,9.87390000,'2026-06-06 01:14:16');
/*!40000 ALTER TABLE `agence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agence_avis`
--

DROP TABLE IF EXISTS `agence_avis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agence_avis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_agence` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `note` tinyint(4) NOT NULL,
  `commentaire` text DEFAULT NULL,
  `reponse_admin` text DEFAULT NULL,
  `hidden` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_agence` (`id_agence`,`id_client`),
  KEY `id_client` (`id_client`),
  KEY `note` (`note`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `agence_avis_ibfk_1` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`),
  CONSTRAINT `agence_avis_ibfk_2` FOREIGN KEY (`id_client`) REFERENCES `user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agence_avis`
--

LOCK TABLES `agence_avis` WRITE;
/*!40000 ALTER TABLE `agence_avis` DISABLE KEYS */;
INSERT INTO `agence_avis` VALUES (1,1,8,5,'??quipe tr??s professionnelle et r??active. Je recommande !',NULL,0,'2024-07-01 10:00:00'),(2,1,9,4,'Bonne prise en charge, petit d??lai d\'attente.',NULL,0,'2024-07-15 11:00:00'),(3,2,10,5,'Excellent service, mon sinistre trait?? en 2 semaines.',NULL,0,'2024-06-10 09:00:00'),(4,3,11,4,'Agents comp??tents, locaux modernes.',NULL,0,'2024-08-20 14:00:00'),(5,4,12,5,'Service impeccable, je suis cliente depuis 3 ans.',NULL,0,'2024-09-01 10:00:00'),(6,2,13,3,'Correct mais temps d\'attente parfois long.',NULL,0,'2024-10-10 15:00:00');
/*!40000 ALTER TABLE `agence_avis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agence_horaires`
--

DROP TABLE IF EXISTS `agence_horaires`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agence_horaires` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_agence` int(11) NOT NULL,
  `jour` tinyint(4) NOT NULL COMMENT '1=Lun 7=Dim',
  `heure_ouverture` time DEFAULT NULL,
  `heure_fermeture` time DEFAULT NULL,
  `ferme` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_agence` (`id_agence`,`jour`),
  KEY `id_agence_2` (`id_agence`),
  CONSTRAINT `agence_horaires_ibfk_1` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agence_horaires`
--

LOCK TABLES `agence_horaires` WRITE;
/*!40000 ALTER TABLE `agence_horaires` DISABLE KEYS */;
INSERT INTO `agence_horaires` VALUES (1,1,1,'08:00:00','17:00:00',0),(2,1,2,'08:00:00','17:00:00',0),(3,1,3,'08:00:00','17:00:00',0),(4,1,4,'08:00:00','17:00:00',0),(5,1,5,'08:00:00','17:00:00',0),(6,1,6,'09:00:00','13:00:00',0),(7,1,7,NULL,NULL,1),(8,2,1,'08:00:00','17:00:00',0),(9,2,2,'08:00:00','17:00:00',0),(10,2,3,'08:00:00','17:00:00',0),(11,2,4,'08:00:00','17:00:00',0),(12,2,5,'08:00:00','17:00:00',0),(13,2,6,'09:00:00','12:00:00',0),(14,2,7,NULL,NULL,1),(15,3,1,'08:30:00','17:30:00',0),(16,3,2,'08:30:00','17:30:00',0),(17,3,3,'08:30:00','17:30:00',0),(18,3,4,'08:30:00','17:30:00',0),(19,3,5,'08:30:00','17:30:00',0),(20,3,6,'09:00:00','13:00:00',0),(21,3,7,NULL,NULL,1);
/*!40000 ALTER TABLE `agence_horaires` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agence_virtuelle_message`
--

DROP TABLE IF EXISTS `agence_virtuelle_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agence_virtuelle_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_agence` int(11) NOT NULL,
  `salle` varchar(50) NOT NULL,
  `id_sender` int(11) NOT NULL,
  `sender_nom` varchar(100) NOT NULL,
  `contenu` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_agence` (`id_agence`,`salle`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agence_virtuelle_message`
--

LOCK TABLES `agence_virtuelle_message` WRITE;
/*!40000 ALTER TABLE `agence_virtuelle_message` DISABLE KEYS */;
INSERT INTO `agence_virtuelle_message` VALUES (1,1,'Archives',2,'Sarra Ben Ali','cc','2026-06-06 02:18:28'),(2,1,'Salle Auto',2,'Sarra Ben Ali','cc','2026-06-06 02:52:47');
/*!40000 ALTER TABLE `agence_virtuelle_message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agent`
--

DROP TABLE IF EXISTS `agent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agent` (
  `id_agent` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_agence` int(11) NOT NULL,
  `salaire` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id_agent`),
  UNIQUE KEY `id_user` (`id_user`),
  KEY `id_agence` (`id_agence`),
  CONSTRAINT `agent_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agent`
--

LOCK TABLES `agent` WRITE;
/*!40000 ALTER TABLE `agent` DISABLE KEYS */;
INSERT INTO `agent` VALUES (1,4,1,2800.00),(2,5,2,2600.00),(3,6,3,2500.00),(4,7,4,2400.00);
/*!40000 ALTER TABLE `agent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agent_room`
--

DROP TABLE IF EXISTS `agent_room`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agent_room` (
  `id_user` int(11) NOT NULL,
  `salle` varchar(100) NOT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agent_room`
--

LOCK TABLES `agent_room` WRITE;
/*!40000 ALTER TABLE `agent_room` DISABLE KEYS */;
/*!40000 ALTER TABLE `agent_room` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `cible` varchar(200) NOT NULL,
  `details` text DEFAULT NULL,
  `ip` varchar(45) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_user` (`id_user`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES (1,1,'user.ban','user:15','Utilisateur banni : fraude suspect??e','196.203.12.5','2024-09-05 09:00:00'),(2,2,'contrat.create','contrat:1','Cr??ation contrat PTX-2024-0001 pour user:8','10.0.0.2','2024-04-01 09:00:00'),(3,3,'sinistre.assign','sinistre:2','Assignation agent:4 au sinistre:2','10.0.0.3','2024-07-21 10:00:00'),(4,2,'reclamation.close','reclamation:5','Fermeture r??clamation REC-2024-005 par admin:2','10.0.0.2','2024-09-03 14:05:00'),(5,1,'agence.create','agence:5','Cr??ation agence Bizerte (status: inactive)','196.203.12.5','2024-01-15 10:00:00'),(6,2,'toggle_statut','user','ID: 15, Nouveau statut: actif','::1','2026-06-06 01:58:08');
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_reclamation`
--

DROP TABLE IF EXISTS `audit_reclamation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_reclamation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_reclamation` int(11) NOT NULL,
  `reclamation_id` int(11) DEFAULT NULL,
  `reponse_id` int(11) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_reclamation` (`id_reclamation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_reclamation`
--

LOCK TABLES `audit_reclamation` WRITE;
/*!40000 ALTER TABLE `audit_reclamation` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_reclamation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `avis_agence`
--

DROP TABLE IF EXISTS `avis_agence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `avis_agence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_agence` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `note` int(11) DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `reponse_admin` text DEFAULT NULL,
  `hidden` tinyint(1) DEFAULT 0,
  `date_avis` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_agence` (`id_agence`),
  KEY `id_client` (`id_client`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `avis_agence`
--

LOCK TABLES `avis_agence` WRITE;
/*!40000 ALTER TABLE `avis_agence` DISABLE KEYS */;
INSERT INTO `avis_agence` VALUES (1,1,18,5,'sfdcxhcghchgchgc',NULL,0,'2026-06-07 03:03:46');
/*!40000 ALTER TABLE `avis_agence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `avis_offre`
--

DROP TABLE IF EXISTS `avis_offre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `avis_offre` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_offre` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `note` tinyint(4) NOT NULL,
  `commentaire` text DEFAULT NULL,
  `hidden` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `date_avis` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_offre` (`id_offre`),
  KEY `id_client` (`id_client`),
  KEY `note` (`note`),
  CONSTRAINT `avis_offre_ibfk_1` FOREIGN KEY (`id_offre`) REFERENCES `offre` (`id_offre`),
  CONSTRAINT `avis_offre_ibfk_2` FOREIGN KEY (`id_client`) REFERENCES `user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `avis_offre`
--

LOCK TABLES `avis_offre` WRITE;
/*!40000 ALTER TABLE `avis_offre` DISABLE KEYS */;
INSERT INTO `avis_offre` VALUES (1,1,8,4,'Bon rapport qualit??/prix pour une assurance auto de base.',0,'2024-07-01 10:00:00',NULL),(2,2,9,5,'Assistance 24h vraiment utile, j\'ai test?? une fois de nuit.',0,'2024-08-01 11:00:00',NULL),(3,4,10,4,'Couvre bien les d??g??ts des eaux, satisfaite du remboursement.',0,'2024-06-10 09:00:00',NULL),(4,6,11,5,'Parfait pour un c??libataire, prix raisonnable.',0,'2024-08-20 14:00:00',NULL),(5,8,12,5,'Formule vie tr??s compl??te, conseiller tr??s p??dagogue.',0,'2024-09-05 10:00:00',NULL),(6,3,13,4,'Premium justifi?? pour un v??hicule haut de gamme.',0,'2024-10-10 15:00:00',NULL);
/*!40000 ALTER TABLE `avis_offre` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorie`
--

DROP TABLE IF EXISTS `categorie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categorie` (
  `id_categorie` int(11) NOT NULL AUTO_INCREMENT,
  `nom_categorie` varchar(100) NOT NULL,
  `description_categorie` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_categorie`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorie`
--

LOCK TABLES `categorie` WRITE;
/*!40000 ALTER TABLE `categorie` DISABLE KEYS */;
INSERT INTO `categorie` VALUES (1,'Auto','Assurances pour v??hicules terrestres ?? moteur','2026-06-06 01:14:16'),(2,'Habitation','Assurances pour logements et biens immobiliers','2026-06-06 01:14:16'),(3,'Sant??','Assurances maladie, hospitalisation et soins','2026-06-06 01:14:16'),(4,'Vie','Assurances vie, ??pargne et pr??voyance','2026-06-06 01:14:16');
/*!40000 ALTER TABLE `categorie` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `client`
--

DROP TABLE IF EXISTS `client`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `client` (
  `id_client` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_agence` int(11) DEFAULT NULL,
  `numero_client` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_client`),
  UNIQUE KEY `id_user` (`id_user`),
  KEY `id_agence` (`id_agence`),
  CONSTRAINT `client_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client`
--

LOCK TABLES `client` WRITE;
/*!40000 ALTER TABLE `client` DISABLE KEYS */;
INSERT INTO `client` VALUES (1,8,1,'CLT-2024-0001'),(2,9,1,'CLT-2024-0002'),(3,10,2,'CLT-2024-0003'),(4,11,3,'CLT-2024-0004'),(5,12,4,'CLT-2024-0005'),(6,13,2,'CLT-2024-0006'),(7,14,5,'CLT-2024-0007'),(8,15,1,'CLT-2024-0008'),(9,16,4,'CL-20794'),(11,18,1,'CL-27558');
/*!40000 ALTER TABLE `client` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `commentaire`
--

DROP TABLE IF EXISTS `commentaire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `commentaire` (
  `id_commentaire` int(11) NOT NULL AUTO_INCREMENT,
  `contenu` text NOT NULL,
  `id_poste` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `id_commentaire_parent` int(11) DEFAULT NULL,
  `hidden` tinyint(1) DEFAULT 0,
  `signalements` int(11) DEFAULT 0,
  `date_commentaire` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_commentaire`),
  KEY `id_poste` (`id_poste`),
  CONSTRAINT `commentaire_ibfk_1` FOREIGN KEY (`id_poste`) REFERENCES `poste` (`id_poste`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `commentaire`
--

LOCK TABLES `commentaire` WRITE;
/*!40000 ALTER TABLE `commentaire` DISABLE KEYS */;
INSERT INTO `commentaire` VALUES (1,'F??licitations ! Je suis client depuis le d??but, fier de faire partie de cette aventure.',1,8,NULL,0,0,'2026-06-06 01:14:16'),(2,'Super initiative, j\'avais justement une question sur ma couverture v??lo.',2,9,NULL,0,0,'2026-06-06 01:14:16'),(3,'C\'est vrai ? Je n\'??tais pas au courant pour le v??lo, je vais v??rifier.',2,11,NULL,0,0,'2026-06-06 01:14:16'),(4,'J\'ai aussi utilis?? l\'app, c\'est vraiment intuitif.',4,10,NULL,0,0,'2026-06-06 01:14:16'),(5,'Contenu trompeur, ?? mod??rer.',5,15,NULL,1,0,'2026-06-06 01:14:16'),(6,'Le module SOS est g??nial, je l\'ai activ?? pour mes parents ??g??s.',7,12,NULL,0,0,'2026-06-06 01:14:16'),(7,'C\'est rassurant pour les accidents de nuit.',7,8,NULL,0,0,'2026-06-06 01:14:16'),(8,'fbgvbvjvh',2,18,NULL,0,0,'2026-06-07 03:03:23');
/*!40000 ALTER TABLE `commentaire` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contrat`
--

DROP TABLE IF EXISTS `contrat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contrat` (
  `id_contrat` int(11) NOT NULL AUTO_INCREMENT,
  `numero_contrat` varchar(50) DEFAULT NULL,
  `type_contrat` varchar(100) DEFAULT NULL,
  `date_debut` date DEFAULT NULL,
  `date_debut_contrat` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `date_fin_contrat` date DEFAULT NULL,
  `prime` decimal(10,2) DEFAULT 0.00,
  `prime_contrat` decimal(10,2) DEFAULT NULL,
  `franchise` decimal(10,2) DEFAULT 0.00,
  `franchise_contrat` decimal(10,2) DEFAULT NULL,
  `statut_contrat` enum('en attente','actif','expir├Ü','r├Üsili├Ü','refus├Ü') DEFAULT 'en attente',
  `mode_paiement` enum('annuel','trimestriel','mensuel') DEFAULT 'annuel',
  `id_user` int(11) NOT NULL,
  `id_client` int(11) DEFAULT NULL,
  `id_categorie` int(11) DEFAULT NULL,
  `id_formule` int(11) DEFAULT NULL,
  `id_devis` int(11) DEFAULT NULL,
  `formule_contrat` text DEFAULT NULL,
  `details_contrat` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_contrat`),
  KEY `id_user` (`id_user`),
  KEY `numero_contrat` (`numero_contrat`),
  KEY `statut_contrat` (`statut_contrat`),
  KEY `idx_id_devis` (`id_devis`),
  CONSTRAINT `contrat_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contrat`
--

LOCK TABLES `contrat` WRITE;
/*!40000 ALTER TABLE `contrat` DISABLE KEYS */;
INSERT INTO `contrat` VALUES (1,'PTX-2024-0001','Auto','2024-04-01',NULL,'2025-04-01',NULL,504.00,NULL,200.00,NULL,'actif','annuel',8,NULL,1,1,NULL,NULL,NULL,'2024-04-01 09:00:00'),(2,'PTX-2024-0002','Auto','2024-04-07',NULL,'2025-04-07',NULL,1050.00,NULL,500.00,NULL,'actif','mensuel',9,NULL,1,2,NULL,NULL,NULL,'2024-04-07 09:00:00'),(3,'PTX-2024-0003','Habitation','2024-04-12',NULL,'2025-04-12',NULL,330.00,NULL,0.00,NULL,'actif','annuel',10,NULL,2,4,NULL,NULL,NULL,'2024-04-12 09:00:00'),(4,'PTX-2024-0004','Sant??','2024-04-17',NULL,'2025-04-17',NULL,672.00,NULL,0.00,NULL,'actif','mensuel',11,NULL,3,6,NULL,NULL,NULL,'2024-04-17 09:00:00'),(5,'PTX-2024-0005','Vie','2024-04-22',NULL,'2026-04-22',NULL,900.00,NULL,0.00,NULL,'actif','annuel',12,NULL,4,8,NULL,NULL,NULL,'2024-04-22 09:00:00'),(6,'PTX-2024-0006','Auto','2024-05-03',NULL,'2025-05-03',NULL,1680.00,NULL,300.00,NULL,'actif','trimestriel',13,NULL,1,3,NULL,NULL,NULL,'2024-05-03 09:00:00'),(7,'PTX-2023-0010','Auto','2023-01-15',NULL,'2024-01-15',NULL,504.00,NULL,200.00,NULL,'actif','annuel',8,NULL,1,1,NULL,NULL,NULL,'2023-01-15 09:00:00'),(8,'PTX-2024-0007','Habitation','2024-06-01',NULL,'2025-06-01',NULL,600.00,NULL,0.00,NULL,'actif','mensuel',10,NULL,2,5,NULL,NULL,NULL,'2024-06-01 09:00:00'),(9,'PTX-2024-0008','Sant??','2024-06-10',NULL,'2025-06-10',NULL,1440.00,NULL,0.00,NULL,'actif','mensuel',11,NULL,3,7,NULL,NULL,NULL,'2024-06-10 09:00:00'),(10,'PTX-2024-0009','Vie','2024-07-01',NULL,'2025-07-01',NULL,600.00,NULL,0.00,NULL,'actif','mensuel',13,NULL,4,9,NULL,NULL,NULL,'2024-07-01 09:00:00'),(12,'CTR-2026-170415','Habitation',NULL,'2026-06-06',NULL,'2027-06-06',0.00,0.00,0.00,0.00,'actif','annuel',18,NULL,2,4,NULL,'Habitation Base','{\"garanties\":[\"Incendie Habitation\",\"D??g??ts des Eaux\",\"Incendie Habitation\",\"D??g??ts des Eaux\",\"Vol Habitation\"],\"type_logement\":\"Appartement\",\"statut_occupation\":\"Locataire\",\"adresse_logement\":\"bnjhhy\",\"surface_logement\":\"126\",\"nb_pieces\":\"9\",\"valeur_biens\":\"8768\",\"identite\":\"Monsieur\",\"email\":\"Medkarimmiledi@gmail.com\",\"nom\":\"Miledi\",\"prenom\":\"Mohamed\",\"telephone\":\"54415625\",\"date_naissance\":\"2006-11-03\",\"nationalite\":\"Fran├ºaise\",\"situation_professionnelle\":\"Salari├®\",\"adresse\":\"GREMDA\",\"situation_matrimoniale\":\"Divorc├®(e)\",\"revenu_annuel\":\"Moins de 10 000 DT\"}','2026-06-06 19:59:29'),(14,'CTR-AUTO-20260608-2367','Auto',NULL,'2026-06-01',NULL,'2027-06-01',0.00,130.00,0.00,300.00,'actif','annuel',18,NULL,1,3,NULL,'Tous Risques',NULL,'2026-06-07 23:14:56');
/*!40000 ALTER TABLE `contrat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contrat_garantie_override`
--

DROP TABLE IF EXISTS `contrat_garantie_override`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contrat_garantie_override` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_contrat` int(11) NOT NULL,
  `id_garantie` int(11) NOT NULL,
  `plafond_custom` decimal(10,2) DEFAULT NULL,
  `franchise_custom` decimal(10,2) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_contrat` (`id_contrat`,`id_garantie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contrat_garantie_override`
--

LOCK TABLES `contrat_garantie_override` WRITE;
/*!40000 ALTER TABLE `contrat_garantie_override` DISABLE KEYS */;
/*!40000 ALTER TABLE `contrat_garantie_override` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contrat_historique`
--

DROP TABLE IF EXISTS `contrat_historique`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contrat_historique` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_contrat` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `champ_modifie` varchar(100) DEFAULT NULL,
  `ancienne_valeur` text DEFAULT NULL,
  `nouvelle_valeur` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_contrat` (`id_contrat`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contrat_historique`
--

LOCK TABLES `contrat_historique` WRITE;
/*!40000 ALTER TABLE `contrat_historique` DISABLE KEYS */;
INSERT INTO `contrat_historique` VALUES (1,9,11,'statut_contrat','actif','suspendu','2024-09-01 10:00:00'),(2,2,9,'mode_paiement','annuel','mensuel','2024-06-01 11:00:00'),(3,12,2,'prime_contrat','0.00','0','2026-06-06 20:01:28'),(4,12,2,'franchise_contrat','0.00','0','2026-06-06 20:01:28'),(5,12,2,'statut_contrat','en attente','actif','2026-06-06 20:01:28');
/*!40000 ALTER TABLE `contrat_historique` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversation_participants`
--

DROP TABLE IF EXISTS `conversation_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversation_participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_conversation` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `dernier_message_lu` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_conversation` (`id_conversation`,`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversation_participants`
--

LOCK TABLES `conversation_participants` WRITE;
/*!40000 ALTER TABLE `conversation_participants` DISABLE KEYS */;
INSERT INTO `conversation_participants` VALUES (1,1,2,NULL),(2,1,9,NULL),(3,2,3,NULL),(4,2,10,NULL),(5,3,2,'2026-06-06 20:08:17'),(6,3,3,NULL);
/*!40000 ALTER TABLE `conversation_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(200) DEFAULT NULL,
  `type` enum('privee','prive','groupe') DEFAULT 'privee',
  `cree_par` int(11) DEFAULT NULL,
  `derniere_activite` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `id_conversation` int(11) GENERATED ALWAYS AS (`id`) VIRTUAL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversations`
--

LOCK TABLES `conversations` WRITE;
/*!40000 ALTER TABLE `conversations` DISABLE KEYS */;
INSERT INTO `conversations` VALUES (1,'Support Sinistre #2','privee',2,'2024-07-22 10:00:00','2026-06-06 01:14:17',1),(2,'Support R??clamation #3','privee',3,'2024-07-15 11:00:00','2026-06-06 01:14:17',2),(3,NULL,'prive',2,NULL,'2026-06-06 20:08:17',3);
/*!40000 ALTER TABLE `conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `devis`
--

DROP TABLE IF EXISTS `devis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `devis` (
  `id_devis` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) DEFAULT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `type_assurance` varchar(50) DEFAULT NULL,
  `id_offre` int(11) DEFAULT NULL,
  `montant_estime` decimal(10,2) DEFAULT NULL,
  `statut` enum('en_attente','en_cours','traite','converti','refuse','accepte','expire') DEFAULT 'en_attente',
  `reponse_admin` text DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_agence` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `date_demande` datetime DEFAULT NULL,
  PRIMARY KEY (`id_devis`),
  KEY `id_user` (`id_user`),
  KEY `statut` (`statut`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `devis`
--

LOCK TABLES `devis` WRITE;
/*!40000 ALTER TABLE `devis` DISABLE KEYS */;
INSERT INTO `devis` VALUES (1,'Gharbi','Ines','ines.gharbi@gmail.com','+216 22 100 008','auto',1,504.00,'converti',NULL,8,NULL,'2024-03-02 11:00:00',NULL),(2,'Ayari','Bilel','bilel.ayari@gmail.com','+216 22 100 009','auto',2,1050.00,'converti',NULL,9,NULL,'2024-03-06 11:00:00',NULL),(3,'Jebali','Asma','asma.jebali@gmail.com','+216 22 100 010','habitation',4,330.00,'converti',NULL,10,NULL,'2024-03-11 11:00:00',NULL),(4,'Riahi','Khalil','khalil.riahi@yahoo.fr','+216 22 100 011','sante',6,672.00,'converti',NULL,11,NULL,'2024-03-16 11:00:00',NULL),(5,'Kallel','Fatma','fatma.kallel@outlook.com','+216 22 100 012','vie',8,900.00,'converti',NULL,12,NULL,'2024-03-21 11:00:00',NULL),(6,'Mansouri','Tarek','tarek.mansouri@gmail.com','+216 22 100 013','auto',3,1680.00,'converti',NULL,13,NULL,'2024-04-02 11:00:00',NULL),(7,'Prospect','Ahmed','ahmed.prospect@gmail.com','+216 50 111 222','habitation',5,600.00,'en_attente',NULL,NULL,NULL,'2024-05-01 14:00:00',NULL),(8,'Prospect','Sirine','sirine.p@gmail.com','+216 50 333 444','sante',7,1440.00,'traite',NULL,NULL,NULL,'2024-05-10 10:00:00',NULL),(9,'Marzouki','Hedi','hedi.m@gmail.com','+216 50 555 666','auto',1,504.00,'refuse',NULL,NULL,NULL,'2024-05-15 09:00:00',NULL),(10,'Miledi','Mohamed','medkarimmiledi@gmail.com','54415625','auto',10,NULL,'en_attente',NULL,NULL,1,'2026-06-07 02:32:31',NULL),(11,'Miledi','Mohamed','Medkarimmiledi@gmail.com','54 41 5625','habitation',4,60.00,'en_attente',NULL,NULL,NULL,'2026-06-07 02:33:26','2026-06-07 02:33:26');
/*!40000 ALTER TABLE `devis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `devis_auto`
--

DROP TABLE IF EXISTS `devis_auto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `devis_auto` (
  `id_devis_auto` int(11) NOT NULL AUTO_INCREMENT,
  `id_devis` int(11) NOT NULL,
  `marque` varchar(100) DEFAULT NULL,
  `modele` varchar(100) DEFAULT NULL,
  `annee` int(11) DEFAULT NULL,
  `immatriculation` varchar(50) DEFAULT NULL,
  `puissance` int(11) DEFAULT NULL,
  `carburant` varchar(50) DEFAULT NULL,
  `valeur_vehicule` decimal(10,2) DEFAULT NULL,
  `usage_vehicule` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_devis_auto`),
  KEY `id_devis` (`id_devis`),
  CONSTRAINT `devis_auto_ibfk_1` FOREIGN KEY (`id_devis`) REFERENCES `devis` (`id_devis`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `devis_auto`
--

LOCK TABLES `devis_auto` WRITE;
/*!40000 ALTER TABLE `devis_auto` DISABLE KEYS */;
INSERT INTO `devis_auto` VALUES (1,1,'Volkswagen','Golf 7',2020,'100TU1234',110,'Essence',28000.00,'Personnel'),(2,2,'Toyota','Corolla',2021,'200SF5678',120,'Hybride',35000.00,'Personnel'),(3,6,'BMW','S??rie 3',2022,'300TU9999',184,'Diesel',55000.00,'Personnel'),(4,7,'Renault','Clio 5',2019,'400SU7777',90,'Essence',18000.00,'Personnel'),(5,9,'Peugeot','208',2018,'500TU8888',75,'Essence',14000.00,'Personnel'),(6,10,'bmw','2003',2000,'321TUN3213',3,'Essence',NULL,'Personnel');
/*!40000 ALTER TABLE `devis_auto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `devis_habitation`
--

DROP TABLE IF EXISTS `devis_habitation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `devis_habitation` (
  `id_devis_habitation` int(11) NOT NULL AUTO_INCREMENT,
  `id_devis` int(11) NOT NULL,
  `type_habitation` varchar(100) DEFAULT NULL,
  `type_logement` varchar(100) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `superficie` decimal(10,2) DEFAULT NULL,
  `surface` decimal(10,2) DEFAULT NULL,
  `nombre_pieces` int(11) DEFAULT NULL,
  `valeur_bien` decimal(10,2) DEFAULT NULL,
  `statut_occupation` varchar(100) DEFAULT NULL,
  `proprietaire_locataire` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_devis_habitation`),
  KEY `id_devis` (`id_devis`),
  CONSTRAINT `devis_habitation_ibfk_1` FOREIGN KEY (`id_devis`) REFERENCES `devis` (`id_devis`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `devis_habitation`
--

LOCK TABLES `devis_habitation` WRITE;
/*!40000 ALTER TABLE `devis_habitation` DISABLE KEYS */;
INSERT INTO `devis_habitation` VALUES (1,3,'Appartement',NULL,'Rue de la Libert??, Sfax',NULL,85.00,NULL,4,120000.00,'Propri??taire',NULL),(2,8,'Villa',NULL,'Cit?? El Amel, Sousse',NULL,200.00,NULL,7,350000.00,'Propri??taire',NULL);
/*!40000 ALTER TABLE `devis_habitation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `devis_sante`
--

DROP TABLE IF EXISTS `devis_sante`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `devis_sante` (
  `id_devis_sante` int(11) NOT NULL AUTO_INCREMENT,
  `id_devis` int(11) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `situation_familiale` varchar(50) DEFAULT NULL,
  `nombre_beneficiaires` int(11) DEFAULT NULL,
  `antecedents_medicaux` text DEFAULT NULL,
  `couverture_souhaitee` varchar(200) DEFAULT NULL,
  `profession` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_devis_sante`),
  KEY `id_devis` (`id_devis`),
  CONSTRAINT `devis_sante_ibfk_1` FOREIGN KEY (`id_devis`) REFERENCES `devis` (`id_devis`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `devis_sante`
--

LOCK TABLES `devis_sante` WRITE;
/*!40000 ALTER TABLE `devis_sante` DISABLE KEYS */;
INSERT INTO `devis_sante` VALUES (1,4,28,'C??libataire',1,'Aucun','Soins courants + Optique','Ing??nieur'),(2,5,35,'Mari??',3,'Hypertension','Hospitalisation + Maternit??','Comptable');
/*!40000 ALTER TABLE `devis_sante` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `formule`
--

DROP TABLE IF EXISTS `formule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `formule` (
  `id_formule` int(11) NOT NULL AUTO_INCREMENT,
  `nom_formule` varchar(100) NOT NULL,
  `description_formule` text DEFAULT NULL,
  `id_categorie` int(11) NOT NULL,
  `prix_base` decimal(10,2) DEFAULT 0.00,
  `prix_formule` decimal(10,2) DEFAULT NULL,
  `franchise_formule` decimal(10,2) DEFAULT NULL,
  `niveau_formule` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_formule`),
  KEY `id_categorie` (`id_categorie`),
  CONSTRAINT `formule_ibfk_1` FOREIGN KEY (`id_categorie`) REFERENCES `categorie` (`id_categorie`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `formule`
--

LOCK TABLES `formule` WRITE;
/*!40000 ALTER TABLE `formule` DISABLE KEYS */;
INSERT INTO `formule` VALUES (1,'Tiers Simple','Responsabilit?? civile seule',1,40.00,NULL,NULL,NULL,'2026-06-06 01:14:16'),(2,'Tiers ??tendu','RC + garanties compl??mentaires',1,75.00,NULL,NULL,NULL,'2026-06-06 01:14:16'),(3,'Tous Risques','Couverture totale du v??hicule',1,130.00,NULL,NULL,NULL,'2026-06-06 01:14:16'),(4,'Habitation Base','Sinistres essentiels',2,28.00,NULL,NULL,NULL,'2026-06-06 01:14:16'),(5,'Habitation Multi','Multirisques habitation compl??te',2,50.00,NULL,NULL,NULL,'2026-06-06 01:14:16'),(6,'Sant?? Solo','Individuel sans ayants-droit',3,55.00,NULL,NULL,NULL,'2026-06-06 01:14:16'),(7,'Sant?? Famille','Assur?? + conjoint + enfants',3,120.00,NULL,NULL,NULL,'2026-06-06 01:14:16'),(8,'Vie Terme','Assurance d??c??s terme fixe',4,50.00,NULL,NULL,NULL,'2026-06-06 01:14:16'),(9,'Vie ??pargne','Capital garanti avec participation b??n??fices',4,90.00,NULL,NULL,NULL,'2026-06-06 01:14:16');
/*!40000 ALTER TABLE `formule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `formule_garantie`
--

DROP TABLE IF EXISTS `formule_garantie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `formule_garantie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_formule` int(11) NOT NULL,
  `id_garantie` int(11) NOT NULL,
  `niveau_couvert_garantie` varchar(50) DEFAULT NULL,
  `plafond_formule` decimal(10,2) DEFAULT NULL,
  `franchise_formule` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_formule` (`id_formule`,`id_garantie`),
  KEY `id_garantie` (`id_garantie`),
  CONSTRAINT `formule_garantie_ibfk_1` FOREIGN KEY (`id_formule`) REFERENCES `formule` (`id_formule`),
  CONSTRAINT `formule_garantie_ibfk_2` FOREIGN KEY (`id_garantie`) REFERENCES `garantie` (`id_garantie`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `formule_garantie`
--

LOCK TABLES `formule_garantie` WRITE;
/*!40000 ALTER TABLE `formule_garantie` DISABLE KEYS */;
INSERT INTO `formule_garantie` VALUES (1,1,1,NULL,100000.00,0.00),(2,1,2,NULL,1500.00,0.00),(3,2,1,NULL,100000.00,0.00),(4,2,2,NULL,1500.00,0.00),(5,2,3,NULL,50000.00,500.00),(6,2,4,NULL,50000.00,200.00),(7,3,1,NULL,150000.00,0.00),(8,3,2,NULL,2000.00,0.00),(9,3,3,NULL,80000.00,300.00),(10,3,4,NULL,80000.00,0.00),(11,3,5,NULL,80000.00,300.00),(12,4,6,NULL,200000.00,0.00),(13,4,7,NULL,30000.00,150.00),(14,5,6,NULL,300000.00,0.00),(15,5,7,NULL,50000.00,100.00),(16,5,8,NULL,20000.00,300.00),(17,6,9,NULL,5000.00,0.00),(18,6,11,NULL,3000.00,0.00),(19,7,9,NULL,8000.00,0.00),(20,7,10,NULL,50000.00,0.00),(21,7,11,NULL,5000.00,0.00),(22,8,12,NULL,200000.00,0.00),(23,8,13,NULL,100000.00,0.00),(24,9,12,NULL,300000.00,0.00),(25,9,13,NULL,150000.00,0.00);
/*!40000 ALTER TABLE `formule_garantie` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fraud_analysis`
--

DROP TABLE IF EXISTS `fraud_analysis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fraud_analysis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_sinistre` int(11) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `score_global` decimal(5,2) DEFAULT 0.00,
  `score_texte` decimal(5,2) DEFAULT NULL,
  `score_comportement` decimal(5,2) DEFAULT NULL,
  `score_contrat` decimal(5,2) DEFAULT NULL,
  `score_image` decimal(5,2) DEFAULT NULL,
  `flag_description_vague` tinyint(1) DEFAULT 0,
  `flag_sinistres_multiples` tinyint(1) DEFAULT 0,
  `flag_contrat_recent` tinyint(1) DEFAULT 0,
  `flag_montant_eleve` tinyint(1) DEFAULT 0,
  `flag_image_suspecte` tinyint(1) DEFAULT 0,
  `niveau_risque` enum('faible','moyen','eleve','fraude') DEFAULT 'faible',
  `analyse_texte` text DEFAULT NULL,
  `analyse_comportement` text DEFAULT NULL,
  `analyse_image` text DEFAULT NULL,
  `recommandation_ia` text DEFAULT NULL,
  `suggestion_ia` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `date_analyse` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_sinistre` (`id_sinistre`),
  KEY `niveau_risque` (`niveau_risque`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fraud_analysis`
--

LOCK TABLES `fraud_analysis` WRITE;
/*!40000 ALTER TABLE `fraud_analysis` DISABLE KEYS */;
INSERT INTO `fraud_analysis` VALUES (1,2,9,72.50,NULL,NULL,NULL,NULL,0,0,0,0,0,'eleve','Description vague, contradictions timing','3??me sinistre en 14 mois, m??me agence',NULL,'Demander rapport de police officiel + expertise ind??pendante',NULL,'2024-07-21 08:00:00',NULL),(2,5,13,18.00,NULL,NULL,NULL,NULL,0,0,0,0,0,'faible','Description coh??rente, lieu pr??cis','1er sinistre depuis souscription',NULL,'Expertise standard suffit, risque fraude minimal',NULL,'2024-10-02 09:00:00',NULL),(3,6,10,35.00,NULL,NULL,NULL,NULL,0,0,0,0,0,'moyen','Horaire inhabituel (3h30 du matin)','2??me sinistre en 7 mois',NULL,'Demander rapport pompiers + photos avant/apr??s',NULL,'2024-11-06 10:00:00',NULL),(4,7,8,49.00,9.00,15.00,35.00,0.00,0,0,0,1,0,'','Description textuelle acceptable (score: 9/100). Crit├¿re dominant : incoh├®rence entre le type d├®clar├® et le contenu. D├®tails : Aucune indication temporelle. Aucun verbe d\'action d├®tect├®. Peu de mots-cl├®s coh├®rents avec le type \"Bris de glace\".','0 sinistre(s) en 90 jours (normal). 33% des sinistres refus├®s (mod├®r├®).',NULL,'­ƒöì RISQUE NORMAL (score: 49/100) ÔÇö Points d\'attention : incoh├®rence entre le type et le contenu d├®clar├®, rapport franchise/prime anormal. Le dossier peut ├¬tre avanc├® mais n├®cessite une validation compl├®mentaire. Points ├á v├®rifier : faire ├®valuer le sinistre par un expert ind├®pendant. Traitement possible apr├¿s confirmation des ├®l├®ments manquants. [ALERTE: Aucune photo fournie - vigilance accrue recommand├®e]','investiguer','2026-06-06 19:10:45',NULL),(5,2,9,100.00,85.00,0.00,35.00,0.00,0,0,0,1,0,'fraude','Risque textuel ├®lev├® (score: 85/100). Crit├¿re dominant : incoh├®rence entre le type d├®clar├® et le contenu. D├®tails : Aucune indication temporelle. Aucun verbe d\'action d├®tect├®. Aucun mot-cl├® attendu pour ce type de sinistre (Vol de v??hicule).','0 sinistre(s) en 90 jours (normal).',NULL,'ÔÜá´©Å FRAUDE D├ëTECT├ëE (score: 100/100) ÔÇö Ce sinistre pr├®sente plusieurs signaux d\'alerte s├®rieux : incoh├®rence entre le type et le contenu d├®clar├®, rapport franchise/prime anormal. Ne pas traiter ce dossier avant une investigation approfondie. Actions requises avant tout traitement : faire ├®valuer le sinistre par un expert ind├®pendant; exiger le r├®c├®piss├® de plainte aupr├¿s des autorit├®s; demander la liste des objets vol├®s avec preuves d\'achat. Si les justificatifs ne sont pas fournis dans les 5 jours ouvrables, recommander le refus. [ALERTE: Aucune photo fournie - vigilance accrue recommand├®e]','refuser','2026-06-06 19:28:03',NULL),(6,9,18,100.00,100.00,0.00,35.00,0.00,1,0,1,0,0,'fraude','Risque textuel ├®lev├® (score: 100/100). Crit├¿re dominant : manque de d├®tails concrets. D├®tails : Description courte. Description compos├®e d\'un seul mot (tr├¿s suspect). Aucune indication temporelle.','1 sinistre(s) en 90 jours (normal).',NULL,'ÔÜá´©Å FRAUDE D├ëTECT├ëE (score: 100/100) ÔÇö Ce sinistre pr├®sente plusieurs signaux d\'alerte s├®rieux : description vague ou incompl├¿te, incoh├®rence entre le type et le contenu d├®clar├®, contrat tr├¿s r├®cent au moment de la d├®claration. Ne pas traiter ce dossier avant une investigation approfondie. Actions requises avant tout traitement : demander une description plus d├®taill├®e (lieu exact, heure, circonstances pr├®cises); v├®rifier la date de souscription du contrat et l\'intention d\'assurance; demander le rapport des pompiers ou proc├¿s-verbal d\'intervention. Si les justificatifs ne sont pas fournis dans les 5 jours ouvrables, recommander le refus. [ALERTE: Aucune photo fournie - vigilance accrue recommand├®e]','refuser','2026-06-06 20:03:07',NULL);
/*!40000 ALTER TABLE `fraud_analysis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `friendships`
--

DROP TABLE IF EXISTS `friendships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `friendships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `id_user` int(11) NOT NULL,
  `id_friend` int(11) NOT NULL,
  `statut` enum('pending','accepted','blocked') DEFAULT 'pending',
  `status` enum('pending','accepted','blocked') DEFAULT 'pending',
  `is_trusted` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_user` (`id_user`,`id_friend`),
  KEY `id_user_2` (`id_user`),
  KEY `id_friend` (`id_friend`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `friendships`
--

LOCK TABLES `friendships` WRITE;
/*!40000 ALTER TABLE `friendships` DISABLE KEYS */;
INSERT INTO `friendships` VALUES (1,NULL,NULL,8,9,'accepted','pending',0,'2026-06-06 01:14:16'),(2,NULL,NULL,9,8,'accepted','pending',0,'2026-06-06 01:14:16'),(3,NULL,NULL,8,10,'accepted','pending',0,'2026-06-06 01:14:16'),(4,NULL,NULL,10,8,'accepted','pending',0,'2026-06-06 01:14:16'),(5,NULL,NULL,9,11,'accepted','pending',0,'2026-06-06 01:14:16'),(6,NULL,NULL,11,9,'accepted','pending',0,'2026-06-06 01:14:16'),(7,NULL,NULL,10,12,'pending','pending',0,'2026-06-06 01:14:16'),(8,NULL,NULL,11,13,'accepted','pending',0,'2026-06-06 01:14:16'),(9,NULL,NULL,13,11,'accepted','pending',0,'2026-06-06 01:14:16'),(10,NULL,NULL,12,8,'accepted','pending',0,'2026-06-06 01:14:16'),(11,NULL,NULL,8,12,'accepted','pending',0,'2026-06-06 01:14:16');
/*!40000 ALTER TABLE `friendships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `garantie`
--

DROP TABLE IF EXISTS `garantie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `garantie` (
  `id_garantie` int(11) NOT NULL AUTO_INCREMENT,
  `nom_garantie` varchar(100) NOT NULL,
  `description_garantie` text DEFAULT NULL,
  `id_categorie` int(11) NOT NULL,
  `plafond_defaut` decimal(10,2) DEFAULT NULL,
  `plafond_couvert_garantie` decimal(10,2) DEFAULT NULL,
  `franchise_defaut` decimal(10,2) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_garantie`),
  KEY `id_categorie` (`id_categorie`),
  CONSTRAINT `garantie_ibfk_1` FOREIGN KEY (`id_categorie`) REFERENCES `categorie` (`id_categorie`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `garantie`
--

LOCK TABLES `garantie` WRITE;
/*!40000 ALTER TABLE `garantie` DISABLE KEYS */;
INSERT INTO `garantie` VALUES (1,'Responsabilit?? Civile','Dommages caus??s ?? des tiers',1,100000.00,NULL,0.00,'2026-06-06 01:14:16'),(2,'Bris de Glace','Remplacement pare-brise et vitres',1,1500.00,NULL,0.00,'2026-06-06 01:14:16'),(3,'Vol et Tentative de Vol','Vol total ou partiel du v??hicule',1,50000.00,NULL,500.00,'2026-06-06 01:14:16'),(4,'Incendie et Explosion','Dommages suite ?? incendie',1,50000.00,NULL,200.00,'2026-06-06 01:14:16'),(5,'Dommages Tous Accidents','Dommages mat??riels tous accidents',1,80000.00,NULL,500.00,'2026-06-06 01:14:16'),(6,'Incendie Habitation','Incendie, explosion, foudre',2,200000.00,NULL,0.00,'2026-06-06 01:14:16'),(7,'D??g??ts des Eaux','Infiltrations, ruptures de canalisation',2,30000.00,NULL,150.00,'2026-06-06 01:14:16'),(8,'Vol Habitation','Vol par effraction',2,20000.00,NULL,300.00,'2026-06-06 01:14:16'),(9,'Soins Courants','Consultations et m??dicaments',3,5000.00,NULL,0.00,'2026-06-06 01:14:16'),(10,'Hospitalisation','Frais d\'hospitalisation et chirurgie',3,50000.00,NULL,0.00,'2026-06-06 01:14:16'),(11,'Optique et Dentaire','Remboursement lunettes et soins dentaires',3,3000.00,NULL,0.00,'2026-06-06 01:14:16'),(12,'D??c??s Toutes Causes','Capital vers?? aux b??n??ficiaires',4,200000.00,NULL,0.00,'2026-06-06 01:14:16'),(13,'Invalidit?? Permanente','Indemnit?? en cas d\'invalidit??',4,150000.00,NULL,0.00,'2026-06-06 01:14:16');
/*!40000 ALTER TABLE `garantie` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jeu_memory`
--

DROP TABLE IF EXISTS `jeu_memory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jeu_memory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `temps` int(11) DEFAULT 0,
  `coups` int(11) DEFAULT 0,
  `difficulte` varchar(20) DEFAULT 'facile',
  `nb_paires` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jeu_memory`
--

LOCK TABLES `jeu_memory` WRITE;
/*!40000 ALTER TABLE `jeu_memory` DISABLE KEYS */;
INSERT INTO `jeu_memory` VALUES (1,8,95,22,'facile',8,'2026-06-06 01:14:17'),(2,9,72,18,'normal',12,'2026-06-06 01:14:17'),(3,10,145,35,'facile',8,'2026-06-06 01:14:17'),(4,11,58,16,'normal',12,'2026-06-06 01:14:17'),(5,12,43,14,'difficile',16,'2026-06-06 01:14:17'),(6,13,110,28,'facile',8,'2026-06-06 01:14:17');
/*!40000 ALTER TABLE `jeu_memory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jeu_snake`
--

DROP TABLE IF EXISTS `jeu_snake`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jeu_snake` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `score` int(11) DEFAULT 0,
  `vitesse` int(11) DEFAULT 0,
  `duree_sec` int(11) DEFAULT 0,
  `serpents_manges` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jeu_snake`
--

LOCK TABLES `jeu_snake` WRITE;
/*!40000 ALTER TABLE `jeu_snake` DISABLE KEYS */;
INSERT INTO `jeu_snake` VALUES (1,8,1850,3,180,37,'2026-06-06 01:14:17'),(2,9,2400,4,220,48,'2026-06-06 01:14:17'),(3,10,950,2,120,19,'2026-06-06 01:14:17'),(4,11,3100,5,300,62,'2026-06-06 01:14:17'),(5,12,4200,6,400,84,'2026-06-06 01:14:17'),(6,13,1200,3,150,24,'2026-06-06 01:14:17');
/*!40000 ALTER TABLE `jeu_snake` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `like_post`
--

DROP TABLE IF EXISTS `like_post`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `like_post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_poste` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_poste` (`id_poste`,`id_client`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `like_post`
--

LOCK TABLES `like_post` WRITE;
/*!40000 ALTER TABLE `like_post` DISABLE KEYS */;
INSERT INTO `like_post` VALUES (1,7,18,'2026-06-07 03:02:58'),(4,6,18,'2026-06-07 03:03:02'),(6,5,18,'2026-06-07 03:03:05'),(7,4,18,'2026-06-07 03:03:08'),(9,3,18,'2026-06-07 03:03:14'),(10,2,18,'2026-06-07 03:03:19');
/*!40000 ALTER TABLE `like_post` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `attempted_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`),
  KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_history`
--

DROP TABLE IF EXISTS `login_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_history`
--

LOCK TABLES `login_history` WRITE;
/*!40000 ALTER TABLE `login_history` DISABLE KEYS */;
INSERT INTO `login_history` VALUES (1,1,'196.203.12.5','Mozilla/5.0 Chrome/120 Windows','Sfax','2024-11-01 08:00:00'),(2,1,'196.203.12.5','Mozilla/5.0 Chrome/121 Windows','Sfax','2024-11-15 08:30:00'),(3,8,'41.230.45.100','Mozilla/5.0 Firefox/119 Linux','Ariana','2024-10-28 09:00:00'),(4,8,'41.230.45.100','Mozilla/5.0 Chrome/120 Android','Ariana','2024-11-02 10:00:00'),(5,9,'197.0.56.23','Mozilla/5.0 Safari/17 macOS','Tunis','2024-10-30 14:00:00'),(6,15,'102.17.3.45','Mozilla/5.0 Chrome/115 Windows','Tunis','2024-09-01 02:30:00'),(7,15,'88.99.12.5','Mozilla/5.0 Chrome/115 Windows','Paris','2024-09-01 03:10:00');
/*!40000 ALTER TABLE `login_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message_mentions`
--

DROP TABLE IF EXISTS `message_mentions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message_mentions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_message` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_user_mentionne` int(11) DEFAULT NULL,
  `est_resolu` tinyint(1) DEFAULT 0,
  `date_mention` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_message` (`id_message`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_mentions`
--

LOCK TABLES `message_mentions` WRITE;
/*!40000 ALTER TABLE `message_mentions` DISABLE KEYS */;
/*!40000 ALTER TABLE `message_mentions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message_sinistre`
--

DROP TABLE IF EXISTS `message_sinistre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message_sinistre` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_sinistre` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_sinistre` (`id_sinistre`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_sinistre`
--

LOCK TABLES `message_sinistre` WRITE;
/*!40000 ALTER TABLE `message_sinistre` DISABLE KEYS */;
/*!40000 ALTER TABLE `message_sinistre` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `content` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  KEY `is_read` (`is_read`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (1,8,9,'Salut Bilel, tu as re??u ta carte de renouvellement ?',NULL,1,'2024-10-01 10:00:00'),(2,9,8,'Oui, re??ue hier. Et toi ? Tu as eu des nouvelles de ton sinistre ?',NULL,1,'2024-10-01 10:05:00'),(3,8,9,'Oui tout est r??gl?? ! Remboursement re??u la semaine derni??re.',NULL,1,'2024-10-01 10:08:00'),(4,10,11,'Khalil, recommandes-tu la formule Sant?? Solo ?',NULL,1,'2024-10-05 14:00:00'),(5,11,10,'Oui, tr??s bonne couverture pour le prix. J\'en suis satisfait.',NULL,1,'2024-10-05 14:10:00'),(6,12,8,'Ines, comment tu trouves le service Protex en g??n??ral ?',NULL,0,'2024-11-01 09:00:00');
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages_admin`
--

DROP TABLE IF EXISTS `messages_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_conversation` int(11) DEFAULT NULL,
  `id_expediteur` int(11) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `contenu` text DEFAULT NULL,
  `type_message` varchar(50) DEFAULT 'systeme',
  `fichier_url` varchar(500) DEFAULT NULL,
  `duree_audio` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `date_envoi` datetime GENERATED ALWAYS AS (`created_at`) VIRTUAL,
  `id_message` int(11) GENERATED ALWAYS AS (`id`) VIRTUAL,
  PRIMARY KEY (`id`),
  KEY `id_conversation` (`id_conversation`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages_admin`
--

LOCK TABLES `messages_admin` WRITE;
/*!40000 ALTER TABLE `messages_admin` DISABLE KEYS */;
INSERT INTO `messages_admin` VALUES (1,1,NULL,9,'Bonjour, je n\'ai pas de nouvelles de mon sinistre de vol depuis 48h.',NULL,'systeme',NULL,NULL,1,'2024-07-22 09:00:00','2024-07-22 09:00:00',1),(2,1,NULL,2,'Bonjour M. Ayari, votre dossier est en cours d\'expertise. Nous vous contactons sous 24h.',NULL,'systeme',NULL,NULL,1,'2024-07-22 10:00:00','2024-07-22 10:00:00',2),(3,2,NULL,10,'Vous n\'avez pas r??pondu ?? ma r??clamation concernant le refus de prise en charge.',NULL,'systeme',NULL,NULL,1,'2024-07-15 10:00:00','2024-07-15 10:00:00',3),(4,2,NULL,3,'Nous avons bien re??u votre dossier, il est en cours de r??examen par notre service.',NULL,'systeme',NULL,NULL,1,'2024-07-15 11:00:00','2024-07-15 11:00:00',4);
/*!40000 ALTER TABLE `messages_admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification`
--

DROP TABLE IF EXISTS `notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_notification` int(11) DEFAULT NULL,
  `id_user` int(11) NOT NULL,
  `type` varchar(50) DEFAULT 'system',
  `message` text NOT NULL,
  `lien` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_user` (`id_user`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification`
--

LOCK TABLES `notification` WRITE;
/*!40000 ALTER TABLE `notification` DISABLE KEYS */;
INSERT INTO `notification` VALUES (1,NULL,8,'sinistre','Votre sinistre #1 a ??t?? rembours??. Montant : 3 500 DT.','/sinistre/detail/1',1,'2024-05-20 10:05:00'),(2,NULL,8,'contrat','Votre contrat PTX-2024-0001 expire dans 30 jours.','/contrat/detail/1',0,'2024-03-02 08:00:00'),(3,NULL,9,'paiement','??chec du pr??l??vement mensuel de 95 DT.','/paiement/retenter/9',0,'2024-07-07 09:05:00'),(4,NULL,9,'sinistre','Votre sinistre #2 est en cours de traitement.','/sinistre/detail/2',1,'2024-07-21 08:05:00'),(5,NULL,10,'sinistre','Votre sinistre #3 a ??t?? rembours??. Montant : 1 800 DT.','/sinistre/detail/3',1,'2024-06-05 14:05:00'),(6,NULL,10,'sinistre','Sinistre #6 : demande de documents compl??mentaires.','/sinistre/detail/6',0,'2024-11-06 10:05:00'),(7,NULL,11,'reclamation','Votre r??clamation #4 a bien ??t?? re??ue.','/reclamation/detail/4',1,'2024-08-05 11:05:00'),(8,NULL,12,'system','Bienvenue sur le programme fid??lit?? Protex Gold ????','/fidelite',1,'2024-04-22 09:25:00'),(9,NULL,13,'sinistre','Votre sinistre #5 est en attente d\'expertise.','/sinistre/detail/5',0,'2024-10-02 09:05:00'),(10,NULL,2,'system','Nouveau sinistre ?? haut risque fraude d??tect?? (#2).','/admin/sinistre/2',0,'2024-07-21 08:10:00'),(11,NULL,18,'thanks','Nous vous remercions pour votre commentaire. Votre retour est pr├®cieux pour am├®liorer nos services.',NULL,1,'2026-06-07 03:03:23'),(12,NULL,18,'thanks','Nous vous remercions pour votre avis. Votre retour est essentiel pour l\'am├®lioration continue de la qualit├® de nos services.',NULL,1,'2026-06-07 03:03:46');
/*!40000 ALTER TABLE `notification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_preferences`
--

DROP TABLE IF EXISTS `notification_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_preferences` (
  `id_user` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `canal_email` tinyint(1) DEFAULT 0,
  `canal_sms` tinyint(1) DEFAULT 0,
  `canal_app` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_user`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_preferences`
--

LOCK TABLES `notification_preferences` WRITE;
/*!40000 ALTER TABLE `notification_preferences` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `offre`
--

DROP TABLE IF EXISTS `offre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `offre` (
  `id_offre` int(11) NOT NULL AUTO_INCREMENT,
  `nom_offre` varchar(200) NOT NULL,
  `type_offre` enum('auto','sante','habitation','vie') DEFAULT 'auto',
  `description` text DEFAULT NULL,
  `prix_mensuel` decimal(10,2) DEFAULT 0.00,
  `prix_annuel` decimal(10,2) DEFAULT 0.00,
  `couverture` text DEFAULT NULL,
  `plafond` decimal(15,2) DEFAULT 0.00,
  `duree_min` int(11) DEFAULT 1,
  `statut` enum('active','inactive','archivee') DEFAULT 'active',
  `date_promo_debut` date DEFAULT NULL,
  `date_promo_fin` date DEFAULT NULL,
  `remise_promo` decimal(5,2) DEFAULT NULL,
  `id_categorie` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_offre`),
  KEY `type_offre` (`type_offre`),
  KEY `statut` (`statut`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offre`
--

LOCK TABLES `offre` WRITE;
/*!40000 ALTER TABLE `offre` DISABLE KEYS */;
INSERT INTO `offre` VALUES (1,'Protex Auto Essentiel','auto','Couverture responsabilit?? civile obligatoire + bris de glace',45.00,504.00,'RC, Bris de glace',50000.00,12,'active',NULL,NULL,NULL,1,'2026-06-06 01:14:16'),(2,'Protex Auto Confort','auto','Tous risques avec assistance 24h/24 et v??hicule de remplacement',95.00,1050.00,'RC, Vol, Incendie, Tous risques, Assistance',100000.00,12,'active',NULL,NULL,NULL,1,'2026-06-06 01:14:16'),(3,'Protex Auto Premium','auto','Formule haut de gamme avec valeur ?? neuf pendant 2 ans',150.00,1680.00,'Tous risques + Valeur ?? neuf + Protection conducteur',150000.00,12,'active',NULL,NULL,NULL,1,'2026-06-06 01:14:16'),(4,'Protex Habitation Base','habitation','Protection incendie, d??g??ts des eaux et vol',30.00,330.00,'Incendie, D??g??ts des eaux, Vol',80000.00,12,'active',NULL,NULL,NULL,2,'2026-06-06 01:14:16'),(5,'Protex Habitation Plus','habitation','Couverture ??tendue avec garantie catastrophes naturelles',55.00,600.00,'Incendie, Vol, Cat. nat., RC vie priv??e',150000.00,12,'active',NULL,NULL,NULL,2,'2026-06-06 01:14:16'),(6,'Protex Sant?? Solo','sante','Remboursement soins courants pour c??libataire',60.00,672.00,'Soins courants, Optique, Dentaire',30000.00,12,'active',NULL,NULL,NULL,3,'2026-06-06 01:14:16'),(7,'Protex Sant?? Famille','sante','Couverture compl??te pour toute la famille',130.00,1440.00,'Hospitalisation, Maternit??, Optique, Dentaire',75000.00,12,'active',NULL,NULL,NULL,3,'2026-06-06 01:14:16'),(8,'Protex Vie S??r??nit??','vie','Assurance vie avec ??pargne progressive',80.00,900.00,'D??c??s, Invalidit??, ??pargne',200000.00,24,'active',NULL,NULL,NULL,4,'2026-06-06 01:14:16'),(9,'Protex Vie Pr??voyance','vie','Protection famille en cas de d??c??s ou incapacit??',55.00,600.00,'D??c??s toutes causes, PTIA, ITT',300000.00,12,'active',NULL,NULL,NULL,4,'2026-06-06 01:14:16'),(10,'Protex Auto Jeune','auto','Offre sp??ciale conducteurs novices -25 ans',65.00,720.00,'RC, Bris de glace, Assistance 0km',60000.00,12,'active',NULL,NULL,NULL,1,'2026-06-06 01:14:16');
/*!40000 ALTER TABLE `offre` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `otp_codes`
--

DROP TABLE IF EXISTS `otp_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otp_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `otp_codes`
--

LOCK TABLES `otp_codes` WRITE;
/*!40000 ALTER TABLE `otp_codes` DISABLE KEYS */;
INSERT INTO `otp_codes` VALUES (1,16,'361174','2026-06-06 02:27:16',0,'2026-06-06 01:22:16'),(2,17,'704875','2026-06-06 02:28:06',0,'2026-06-06 01:23:06'),(14,4,'825784','2026-06-06 19:36:38',1,'2026-06-06 19:31:38'),(23,18,'346561','2026-06-08 00:18:03',1,'2026-06-07 23:13:03'),(24,2,'300533','2026-06-08 02:33:47',1,'2026-06-08 01:28:47');
/*!40000 ALTER TABLE `otp_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paiement`
--

DROP TABLE IF EXISTS `paiement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paiement` (
  `id_paiement` int(11) NOT NULL AUTO_INCREMENT,
  `reference` varchar(50) DEFAULT NULL,
  `montant` decimal(10,2) NOT NULL,
  `remboursement_partiel` decimal(10,2) DEFAULT NULL,
  `remboursement_motif` text DEFAULT NULL,
  `remboursement_demande_par` int(11) DEFAULT NULL,
  `remboursement_valide_par` int(11) DEFAULT NULL,
  `methode` varchar(50) DEFAULT NULL,
  `periodicite` varchar(50) DEFAULT NULL,
  `statut` enum('en_attente','paye','echoue','refuse','rembourse','valide','en_attente_remboursement') DEFAULT 'en_attente',
  `date_echeance` date DEFAULT NULL,
  `date_paiement` datetime DEFAULT NULL,
  `num_carte_masque` varchar(20) DEFAULT NULL,
  `motif_refus` text DEFAULT NULL,
  `code_promo` varchar(50) DEFAULT NULL,
  `id_contrat` int(11) DEFAULT NULL,
  `id_offre` int(11) DEFAULT NULL,
  `id_devis` int(11) DEFAULT NULL,
  `id_user` int(11) NOT NULL,
  `id_agence` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_paiement`),
  KEY `id_user` (`id_user`),
  KEY `statut` (`statut`),
  KEY `date_echeance` (`date_echeance`),
  KEY `id_contrat` (`id_contrat`),
  CONSTRAINT `paiement_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paiement`
--

LOCK TABLES `paiement` WRITE;
/*!40000 ALTER TABLE `paiement` DISABLE KEYS */;
INSERT INTO `paiement` VALUES (1,'PAY-2024-0001',504.00,NULL,NULL,NULL,NULL,'Carte bancaire','annuel','paye','2024-04-01','2024-04-01 09:05:00','****1234',NULL,NULL,1,NULL,NULL,8,NULL,'2024-04-01 09:00:00'),(2,'PAY-2024-0002',95.00,NULL,NULL,NULL,NULL,'Stripe','mensuel','paye','2024-04-07','2024-04-07 09:10:00','****5678',NULL,NULL,2,NULL,NULL,9,NULL,'2024-04-07 09:00:00'),(3,'PAY-2024-0003',95.00,NULL,NULL,NULL,NULL,'Stripe','mensuel','paye','2024-05-07','2024-05-07 09:05:00','****5678',NULL,NULL,2,NULL,NULL,9,NULL,'2024-05-07 09:00:00'),(4,'PAY-2024-0004',95.00,NULL,NULL,NULL,NULL,'Stripe','mensuel','paye','2024-06-07','2024-06-07 09:05:00','****5678',NULL,NULL,2,NULL,NULL,9,NULL,'2024-06-07 09:00:00'),(5,'PAY-2024-0005',330.00,NULL,NULL,NULL,NULL,'Virement','annuel','paye','2024-04-12','2024-04-14 11:00:00',NULL,NULL,NULL,3,NULL,NULL,10,NULL,'2024-04-12 09:00:00'),(6,'PAY-2024-0006',60.00,NULL,NULL,NULL,NULL,'Carte bancaire','mensuel','paye','2024-04-17','2024-04-17 09:15:00','****9012',NULL,NULL,4,NULL,NULL,11,NULL,'2024-04-17 09:00:00'),(7,'PAY-2024-0007',900.00,NULL,NULL,NULL,NULL,'Stripe','annuel','paye','2024-04-22','2024-04-22 09:20:00','****3456',NULL,NULL,5,NULL,NULL,12,NULL,'2024-04-22 09:00:00'),(8,'PAY-2024-0008',420.00,NULL,NULL,NULL,NULL,'Carte bancaire','trimestriel','paye','2024-05-03','2024-05-03 09:25:00','****7890',NULL,NULL,6,NULL,NULL,13,NULL,'2024-05-03 09:00:00'),(9,'PAY-2024-0009',95.00,NULL,NULL,NULL,NULL,'Stripe','mensuel','echoue','2024-07-07',NULL,'****5678',NULL,NULL,2,NULL,NULL,9,NULL,'2024-07-07 09:00:00'),(10,'PAY-2024-0010',60.00,NULL,NULL,NULL,NULL,'Carte bancaire','mensuel','en_attente','2024-12-17',NULL,'****9012',NULL,NULL,4,NULL,NULL,11,NULL,'2024-11-17 09:00:00');
/*!40000 ALTER TABLE `paiement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parrainage`
--

DROP TABLE IF EXISTS `parrainage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parrainage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_parrain` int(11) NOT NULL,
  `id_filleul` int(11) NOT NULL,
  `code_utilise` varchar(20) NOT NULL,
  `statut` enum('en_attente','valide','recompense','expire') DEFAULT 'en_attente',
  `pts_parrain` int(11) DEFAULT 150,
  `pts_filleul` int(11) DEFAULT 50,
  `remise_filleul` decimal(5,2) DEFAULT 5.00,
  `remise_parrain` decimal(5,2) DEFAULT 5.00,
  `recompense_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_filleul` (`id_filleul`),
  KEY `id_parrain` (`id_parrain`),
  KEY `code_utilise` (`code_utilise`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parrainage`
--

LOCK TABLES `parrainage` WRITE;
/*!40000 ALTER TABLE `parrainage` DISABLE KEYS */;
/*!40000 ALTER TABLE `parrainage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parrainage_config`
--

DROP TABLE IF EXISTS `parrainage_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parrainage_config` (
  `key` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parrainage_config`
--

LOCK TABLES `parrainage_config` WRITE;
/*!40000 ALTER TABLE `parrainage_config` DISABLE KEYS */;
INSERT INTO `parrainage_config` VALUES ('min_contrats','1'),('points_bonus','100'),('points_filleul','50'),('points_parrain','150'),('points_per_dt','200'),('validite_jours','30');
/*!40000 ALTER TABLE `parrainage_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partenaire`
--

DROP TABLE IF EXISTS `partenaire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partenaire` (
  `id_partenaire` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) NOT NULL,
  `type` enum('garage','clinique','pharmacie','hotel','avocat','serrurier','location_voiture','telemedicine','autre') NOT NULL,
  `description` text DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `gouvernorat` varchar(100) DEFAULT NULL,
  `telephone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `site_web` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `avantage` varchar(255) DEFAULT NULL COMMENT 'R??sum?? court ex: -15% main d oeuvre',
  `avantage_detail` text DEFAULT NULL,
  `horaires` varchar(255) DEFAULT 'Lun-Ven 8h-18h',
  `note_moyenne` decimal(3,2) DEFAULT 0.00,
  `nb_avis` int(11) DEFAULT 0,
  `actif` tinyint(1) DEFAULT 1,
  `ordre` int(11) DEFAULT 0 COMMENT 'Ordre d affichage',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_partenaire`),
  KEY `type` (`type`),
  KEY `ville` (`ville`),
  KEY `actif` (`actif`),
  KEY `note_moyenne` (`note_moyenne`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partenaire`
--

LOCK TABLES `partenaire` WRITE;
/*!40000 ALTER TABLE `partenaire` DISABLE KEYS */;
INSERT INTO `partenaire` VALUES (1,'Garage M??cauto Sfax','garage','Garage agr???? Protex sp??cialis?? carrosserie et m??canique. V??hicule de remplacement disponible.',NULL,'12 Rue de Tunis','Sfax','Sfax','74 234 567',NULL,NULL,34.74060000,10.76030000,'-15% sur main d oeuvre','Remise de 15% sur la main d oeuvre pour tous les clients Protex. V??hicule de pr??t offert 3 jours.','Lun-Sam 8h-18h',0.00,0,1,0,'2026-06-08 01:37:15','2026-06-08 01:37:15'),(2,'Clinique El Amal','clinique','Clinique multidisciplinaire avec tiers payant Protex. Pas d avance de frais pour les clients assur??s Sant??.',NULL,'45 Avenue Bourguiba','Sfax','Sfax','74 456 789',NULL,NULL,34.74150000,10.75950000,'Tiers payant - 0 avance de frais','Prise en charge directe Protex. Pas d avance sur les actes couverts par votre formule Sant??.','Urgences 24h/24 - Consultations Lun-Sam 8h-20h',0.00,0,1,0,'2026-06-08 01:37:15','2026-06-08 01:37:15'),(3,'Pharmacie Centrale Sfax','pharmacie','Pharmacie partenaire avec remise sur les m??dicaments prescrits apr??s sinistre ou consultation.',NULL,'8 Rue des Orangers','Sfax','Sfax','74 123 456',NULL,NULL,34.73920000,10.76120000,'-10% sur ordonnances','Remise de 10% sur tous les m??dicaments sur ordonnance pour les clients Protex actifs.','Lun-Dim 8h-22h',0.00,0,1,0,'2026-06-08 01:37:15','2026-06-08 01:37:15'),(4,'H??tel Novotel Sfax','hotel','H??bergement d urgence en cas de sinistre habitation. Tarifs n??goci??s pour les clients Protex.',NULL,'Route de Tunis Km 3','Sfax','Sfax','74 789 123',NULL,NULL,34.74510000,10.75480000,'-20% tarif urgence','Tarif pr??f??rentiel -20% pour h??bergement d urgence suite ?? sinistre habitation. Sur pr??sentation de la carte Protex.','R??ception 24h/24',0.00,0,1,0,'2026-06-08 01:37:15','2026-06-08 01:37:15'),(5,'Garage Auto Plus Tunis','garage','Centre auto agr???? Protex ?? Tunis. Expertise rapide, prise en charge directe des sinistres auto.',NULL,'89 Avenue de la Libert??','Tunis','Tunis','71 345 678',NULL,NULL,36.80650000,10.18150000,'V??hicule de remplacement 3j offert','V??hicule de remplacement gratuit pendant 3 jours pour tout sinistre auto trait?? chez nous.','Lun-Ven 8h-17h30 / Sam 8h-12h',0.00,0,1,0,'2026-06-08 01:37:15','2026-06-08 01:37:15'),(6,'Polyclinique Hannibal','clinique','Polyclinique de r??f??rence Tunis. T??l??consultation disponible. Tiers payant Protex accept??.',NULL,'34 Rue du Lac','Tunis','Tunis','71 567 890',NULL,NULL,36.83800000,10.23000000,'Tiers payant + t??l??consultation incluse','Tiers payant accept??. T??l??consultation gratuite 2x/an pour les clients Protex Sant?? Premium.','Consultations Lun-Sam 8h-20h / Urgences 24h/24',0.00,0,1,0,'2026-06-08 01:37:15','2026-06-08 01:37:15'),(7,'Cabinet Ma??tre Ben Salem','avocat','Cabinet d avocats sp??cialis?? en droit des assurances. Premi??re consultation gratuite pour clients Protex.',NULL,'15 Rue de la Victoire','Sfax','Sfax','74 999 111',NULL,NULL,34.74000000,10.76000000,'1??re consultation gratuite','Consultation initiale de 30 minutes gratuite pour tout client Protex impliqu?? dans un litige post-sinistre.','Lun-Ven 9h-17h sur RDV',0.00,0,1,0,'2026-06-08 01:37:15','2026-06-08 01:37:15'),(8,'Location Auto Sixt Sfax','location_voiture','Location de v??hicule pendant la r??paration de votre voiture. Tarif Protex exclusif.',NULL,'A??roport Sfax-Thyna','Sfax','Sfax','74 444 555',NULL,NULL,34.71790000,10.69060000,'3 jours offerts apr??s sinistre Auto','Location gratuite 3 jours pour tout sinistre auto avec formule Standard ou Premium. Au-del?? : -25%.','Tous les jours 7h-22h',0.00,0,1,0,'2026-06-08 01:37:15','2026-06-08 01:37:15');
/*!40000 ALTER TABLE `partenaire` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partenaire_agence`
--

DROP TABLE IF EXISTS `partenaire_agence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partenaire_agence` (
  `id_partenaire` int(11) NOT NULL,
  `id_agence` int(11) NOT NULL,
  PRIMARY KEY (`id_partenaire`,`id_agence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partenaire_agence`
--

LOCK TABLES `partenaire_agence` WRITE;
/*!40000 ALTER TABLE `partenaire_agence` DISABLE KEYS */;
/*!40000 ALTER TABLE `partenaire_agence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partenaire_avis`
--

DROP TABLE IF EXISTS `partenaire_avis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partenaire_avis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_partenaire` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `note` tinyint(4) NOT NULL CHECK (`note` between 1 and 5),
  `commentaire` text DEFAULT NULL,
  `signale` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_partenaire` (`id_partenaire`,`id_user`),
  KEY `id_partenaire_2` (`id_partenaire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partenaire_avis`
--

LOCK TABLES `partenaire_avis` WRITE;
/*!40000 ALTER TABLE `partenaire_avis` DISABLE KEYS */;
/*!40000 ALTER TABLE `partenaire_avis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partenaire_type_contrat`
--

DROP TABLE IF EXISTS `partenaire_type_contrat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partenaire_type_contrat` (
  `id_partenaire` int(11) NOT NULL,
  `type_contrat` varchar(50) NOT NULL,
  PRIMARY KEY (`id_partenaire`,`type_contrat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partenaire_type_contrat`
--

LOCK TABLES `partenaire_type_contrat` WRITE;
/*!40000 ALTER TABLE `partenaire_type_contrat` DISABLE KEYS */;
INSERT INTO `partenaire_type_contrat` VALUES (1,'Auto'),(2,'Sant??'),(3,'Sant??'),(4,'Habitation'),(5,'Auto'),(6,'Sant??'),(8,'Auto');
/*!40000 ALTER TABLE `partenaire_type_contrat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partenaire_utilisation`
--

DROP TABLE IF EXISTS `partenaire_utilisation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partenaire_utilisation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_partenaire` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_sinistre` int(11) DEFAULT NULL,
  `contexte` varchar(150) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_partenaire` (`id_partenaire`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partenaire_utilisation`
--

LOCK TABLES `partenaire_utilisation` WRITE;
/*!40000 ALTER TABLE `partenaire_utilisation` DISABLE KEYS */;
INSERT INTO `partenaire_utilisation` VALUES (1,2,18,NULL,'profil_client','2026-06-08 01:46:16'),(2,2,18,NULL,'profil_client','2026-06-08 01:46:22');
/*!40000 ALTER TABLE `partenaire_utilisation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `token` (`token`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
INSERT INTO `password_resets` VALUES (1,'Medkarimmiledi@gmail.com','ed0470950957c9212b6c099ff3040f27abb4d8ca642e42834bec2fff21973a5a','2026-06-08 00:25:25',0,'2026-06-07 23:10:25');
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `points_fidelite`
--

DROP TABLE IF EXISTS `points_fidelite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `points_fidelite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `motif` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `points_fidelite`
--

LOCK TABLES `points_fidelite` WRITE;
/*!40000 ALTER TABLE `points_fidelite` DISABLE KEYS */;
INSERT INTO `points_fidelite` VALUES (1,8,100,'Souscription premier contrat','2024-04-01 09:10:00'),(2,8,20,'Paiement ?? temps contrat PTX-2024-0001','2024-04-15 09:00:00'),(3,8,50,'Parrainage Bilel Ayari','2024-04-07 10:00:00'),(4,9,100,'Souscription premier contrat','2024-04-07 09:15:00'),(5,9,20,'Paiement ?? temps (??3)','2024-06-07 09:10:00'),(6,10,100,'Souscription premier contrat','2024-04-12 09:15:00'),(7,10,30,'Avis v??rifi?? d??pos??','2024-05-01 10:00:00'),(8,10,50,'Parrainage Khalil Riahi','2024-04-17 10:00:00'),(9,11,100,'Souscription premier contrat','2024-04-17 09:15:00'),(10,12,100,'Souscription premier contrat','2024-04-22 09:15:00'),(11,12,100,'Renouvellement fid??lit?? 1 an','2024-04-22 09:20:00'),(12,12,110,'Bonus recommandation r??seau','2024-06-01 10:00:00'),(13,13,100,'Souscription premier contrat','2024-05-03 09:15:00'),(14,13,50,'Sinistre d??clar?? via app mobile','2024-10-02 09:00:00'),(15,12,200,'Bonus 1 an sans sinistre','2026-06-06 02:07:54'),(16,18,100,'profil_complet','2026-06-06 16:28:06');
/*!40000 ALTER TABLE `points_fidelite` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post_reaction`
--

DROP TABLE IF EXISTS `post_reaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `post_reaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_post` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `type` enum('like','love','wow','sad') DEFAULT 'like',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_post` (`id_post`,`id_user`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_reaction`
--

LOCK TABLES `post_reaction` WRITE;
/*!40000 ALTER TABLE `post_reaction` DISABLE KEYS */;
INSERT INTO `post_reaction` VALUES (1,7,18,'love','2026-06-06 17:07:10'),(2,7,18,'like','2026-06-06 17:07:11');
/*!40000 ALTER TABLE `post_reaction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `poste`
--

DROP TABLE IF EXISTS `poste`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poste` (
  `id_poste` int(11) NOT NULL AUTO_INCREMENT,
  `contenu` text NOT NULL,
  `date_publication` datetime DEFAULT current_timestamp(),
  `note` int(11) DEFAULT NULL,
  `auteur` varchar(100) DEFAULT NULL,
  `nb_likes` int(11) DEFAULT 0,
  `nb_commentaires` int(11) DEFAULT 0,
  `id_agence` int(11) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `media_url` varchar(500) DEFAULT NULL,
  `hidden` tinyint(1) DEFAULT 0,
  `signalements` int(11) DEFAULT 0,
  PRIMARY KEY (`id_poste`),
  KEY `id_agence` (`id_agence`),
  KEY `date_publication` (`date_publication`),
  KEY `hidden` (`hidden`),
  CONSTRAINT `poste_ibfk_1` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `poste`
--

LOCK TABLES `poste` WRITE;
/*!40000 ALTER TABLE `poste` DISABLE KEYS */;
INSERT INTO `poste` VALUES (1,'???? Protex c??l??bre ses 10 000 clients assur??s ! Merci ?? toute notre communaut?? pour votre confiance.','2024-08-01 10:00:00',NULL,NULL,45,8,1,2,NULL,0,0),(2,'Saviez-vous ? L\'assurance habitation couvre aussi le vol de v??los en cave. V??rifiez votre contrat ! ????','2024-08-15 14:00:00',NULL,NULL,1,3,2,3,NULL,0,0),(3,'Nos conseillers Protex Sousse vous accueillent d??sormais jusqu\'?? 18h en semaine. Prenez RDV en ligne !','2024-09-01 09:00:00',NULL,NULL,1,0,3,2,NULL,0,0),(4,'Partage d\'exp??rience : j\'ai d??clar?? un sinistre auto via l\'app Protex en 3 minutes. Service top ! ????','2024-09-10 11:00:00',NULL,NULL,1,1,NULL,8,NULL,0,0),(5,'Rappel : la vignette d\'assurance auto doit ??tre affich??e sur le pare-brise. ??vitez l\'amende ! ??????','2024-09-20 08:00:00',NULL,NULL,1,1,1,2,NULL,0,0),(6,'Je recommande l\'offre Protex Sant?? Famille, rapport qualit??/prix excellent pour une famille de 4.','2024-10-05 16:00:00',NULL,NULL,1,0,NULL,12,NULL,0,0),(7,'???? Protex lance son module SOS GPS. En cas d\'accident, vos contacts de confiance sont alert??s automatiquement.','2024-10-15 10:00:00',NULL,NULL,1,2,1,2,NULL,0,0);
/*!40000 ALTER TABLE `poste` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rate_limits`
--

DROP TABLE IF EXISTS `rate_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `endpoint` varchar(100) NOT NULL,
  `hits` int(11) DEFAULT 1,
  `window_start` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`,`endpoint`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rate_limits`
--

LOCK TABLES `rate_limits` WRITE;
/*!40000 ALTER TABLE `rate_limits` DISABLE KEYS */;
INSERT INTO `rate_limits` VALUES (1,'::1','api',1,'2026-06-08 04:09:35'),(2,'0.0.0.0','api',1,'2026-06-08 01:34:50');
/*!40000 ALTER TABLE `rate_limits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reclamation`
--

DROP TABLE IF EXISTS `reclamation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reclamation` (
  `id_reclamation` int(11) NOT NULL AUTO_INCREMENT,
  `objet` varchar(200) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `statut` enum('en_attente','en_cours','resolue','fermee','open','closed','rejected','pending') DEFAULT 'en_attente',
  `sla_heures` int(11) DEFAULT 48,
  `escalade` tinyint(1) DEFAULT 0,
  `escalade_at` datetime DEFAULT NULL,
  `escalade_par` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `object_type` enum('contrat','devis','sinistre','paiement','poste','general') NOT NULL DEFAULT 'general' COMMENT 'Type objet lie',
  `object_ref` varchar(100) DEFAULT NULL COMMENT 'Reference de l objet lie',
  `priorite` enum('basse','normale','haute','critique') DEFAULT 'normale',
  `email` varchar(255) DEFAULT NULL,
  `refContrat` varchar(100) DEFAULT NULL,
  `recRef` varchar(100) DEFAULT NULL,
  `ref_contrat` varchar(100) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `date_depot` datetime DEFAULT NULL,
  `id` int(11) GENERATED ALWAYS AS (`id_reclamation`) VIRTUAL,
  PRIMARY KEY (`id_reclamation`),
  KEY `id_user` (`id_user`),
  KEY `statut` (`statut`),
  KEY `idx_object_type` (`object_type`),
  CONSTRAINT `reclamation_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reclamation`
--

LOCK TABLES `reclamation` WRITE;
/*!40000 ALTER TABLE `reclamation` DISABLE KEYS */;
INSERT INTO `reclamation` VALUES (1,'D??lai de remboursement excessif','Sinistre','resolue',48,0,NULL,NULL,'Mon sinistre du 10/05 n\'a toujours pas ??t?? rembours?? apr??s 3 semaines.','contrat','PTX-2024-0001','haute','ines.gharbi@gmail.com','PTX-2024-0001','REC-2024-001',NULL,8,'2024-06-01 10:00:00',NULL,1),(2,'Erreur de calcul sur la prime','Contrat','resolue',48,0,NULL,NULL,'La prime mensuelle pr??lev??e diff??re du montant indiqu?? dans mon contrat.','contrat','PTX-2024-0002','normale','bilel.ayari@gmail.com','PTX-2024-0002','REC-2024-002',NULL,9,'2024-06-15 14:00:00',NULL,2),(3,'Refus de prise en charge injustifi??','Sinistre','en_cours',48,0,NULL,NULL,'Mon sinistre a ??t?? refus?? sans justification suffisante.','contrat','PTX-2024-0003','critique','asma.jebali@gmail.com','PTX-2024-0003','REC-2024-003',NULL,10,'2024-07-10 09:00:00',NULL,3),(4,'Modification contrat sans accord','Contrat','en_attente',48,0,NULL,NULL,'Des garanties ont ??t?? modifi??es sans mon consentement ??crit.','contrat','PTX-2024-0004','basse','khalil.riahi@yahoo.fr','PTX-2024-0004','REC-2024-004',NULL,11,'2024-08-05 11:00:00',NULL,4),(5,'Service client non joignable','Service','fermee',48,0,NULL,NULL,'Impossible de joindre le service client pendant 2 semaines.','contrat','PTX-2024-0005','normale','fatma.kallel@outlook.com','PTX-2024-0005','REC-2024-005',NULL,12,'2024-09-01 16:00:00',NULL,5),(6,'Retard renouvellement contrat','Contrat','en_attente',48,0,NULL,NULL,'Mon contrat n\'a pas ??t?? renouvel?? automatiquement malgr?? la clause.','contrat','PTX-2024-0006','haute','tarek.mansouri@gmail.com','PTX-2024-0006','REC-2024-006',NULL,13,'2024-10-20 08:00:00',NULL,6),(7,'Test Objet','Autre','open',48,0,NULL,NULL,'Test description','contrat','PTX-2024-0001','normale','test@test.com',NULL,'REC-123456','TEST-REF',8,'2026-06-07 03:01:23','2026-06-07 03:01:23',7),(8,'cgvbhnjm,.','Auto','open',48,0,NULL,NULL,'fgnjmkvhbnjmkl,','contrat','CTR-2026-170415','','Medkarimmiledi@gmail.com',NULL,'REC-20260607030210','CTR-2026-170415',18,'2026-06-07 03:02:10','2026-06-07 03:02:10',8);
/*!40000 ALTER TABLE `reclamation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reclamation_satisfaction`
--

DROP TABLE IF EXISTS `reclamation_satisfaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reclamation_satisfaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_reclamation` int(11) NOT NULL,
  `note` tinyint(4) DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_reclamation` (`id_reclamation`),
  KEY `note` (`note`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reclamation_satisfaction`
--

LOCK TABLES `reclamation_satisfaction` WRITE;
/*!40000 ALTER TABLE `reclamation_satisfaction` DISABLE KEYS */;
INSERT INTO `reclamation_satisfaction` VALUES (1,1,4,'R??solution rapide apr??s relance, satisfait du suivi.','2024-06-07 10:00:00'),(2,2,5,'Tr??s r??actif, probl??me r??solu imm??diatement.','2024-06-20 11:00:00'),(3,5,3,'La fermeture est rapide mais le fond du probl??me n\'est pas r??solu.','2024-09-05 09:00:00');
/*!40000 ALTER TABLE `reclamation_satisfaction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recommandation_historique`
--

DROP TABLE IF EXISTS `recommandation_historique`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recommandation_historique` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) DEFAULT NULL,
  `besoin` varchar(255) DEFAULT NULL,
  `budget` decimal(10,2) DEFAULT NULL,
  `profil_risque` varchar(50) DEFAULT NULL,
  `id_formule_recommandee` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recommandation_historique`
--

LOCK TABLES `recommandation_historique` WRITE;
/*!40000 ALTER TABLE `recommandation_historique` DISABLE KEYS */;
INSERT INTO `recommandation_historique` VALUES (1,8,'assurance auto voiture familiale',100.00,'moyen',2,'2024-03-01 10:00:00'),(2,9,'protection v??hicule neuf premium',150.00,'faible',3,'2024-03-05 11:00:00'),(3,10,'logement appartement locataire',40.00,'faible',4,'2024-03-10 09:00:00'),(4,11,'sant?? individuel jeune actif',70.00,'faible',6,'2024-03-15 10:00:00'),(5,12,'pr??voyance familiale',100.00,'moyen',8,'2024-03-20 11:00:00'),(6,13,'auto haut de gamme',160.00,'moyen',3,'2024-04-01 10:00:00');
/*!40000 ALTER TABLE `recommandation_historique` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recommendation_click`
--

DROP TABLE IF EXISTS `recommendation_click`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recommendation_click` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `id_offre` int(11) NOT NULL,
  `clicked_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_client` (`id_client`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recommendation_click`
--

LOCK TABLES `recommendation_click` WRITE;
/*!40000 ALTER TABLE `recommendation_click` DISABLE KEYS */;
INSERT INTO `recommendation_click` VALUES (1,8,1,'2024-03-01 10:05:00'),(2,8,2,'2024-03-01 10:07:00'),(3,9,2,'2024-03-05 11:05:00'),(4,9,3,'2024-03-05 11:08:00'),(5,10,4,'2024-03-10 09:05:00'),(6,10,5,'2024-03-10 09:10:00'),(7,11,6,'2024-03-15 10:05:00'),(8,12,8,'2024-03-20 11:05:00'),(9,13,3,'2024-04-01 10:05:00');
/*!40000 ALTER TABLE `recommendation_click` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `relance_paiement`
--

DROP TABLE IF EXISTS `relance_paiement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `relance_paiement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_contrat` int(11) NOT NULL,
  `type` enum('email','sms') NOT NULL,
  `sent_at` datetime DEFAULT current_timestamp(),
  `sent_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_contrat` (`id_contrat`),
  KEY `sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `relance_paiement`
--

LOCK TABLES `relance_paiement` WRITE;
/*!40000 ALTER TABLE `relance_paiement` DISABLE KEYS */;
/*!40000 ALTER TABLE `relance_paiement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rendez_vous`
--

DROP TABLE IF EXISTS `rendez_vous`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rendez_vous` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_agence` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `id_agent` int(11) DEFAULT NULL,
  `date_rdv` datetime NOT NULL,
  `motif` varchar(200) DEFAULT NULL,
  `statut` enum('confirme','annule','effectue') DEFAULT 'confirme',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_agence` (`id_agence`),
  KEY `id_client` (`id_client`),
  KEY `date_rdv` (`date_rdv`),
  KEY `statut` (`statut`),
  CONSTRAINT `rendez_vous_ibfk_1` FOREIGN KEY (`id_agence`) REFERENCES `agence` (`id_agence`),
  CONSTRAINT `rendez_vous_ibfk_2` FOREIGN KEY (`id_client`) REFERENCES `user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rendez_vous`
--

LOCK TABLES `rendez_vous` WRITE;
/*!40000 ALTER TABLE `rendez_vous` DISABLE KEYS */;
INSERT INTO `rendez_vous` VALUES (1,1,8,4,'2024-11-05 10:00:00','Renouvellement contrat auto','effectue',NULL,'2024-10-28 09:00:00'),(2,2,10,5,'2024-11-10 14:00:00','Souscription assurance habitation plus','effectue',NULL,'2024-11-03 10:00:00'),(3,3,11,6,'2024-11-20 09:00:00','Question sur couverture sant?? maternit??','confirme',NULL,'2024-11-12 11:00:00'),(4,1,9,4,'2024-12-03 11:00:00','R??clamation paiement','confirme',NULL,'2024-11-20 14:00:00'),(5,4,12,7,'2024-12-10 15:00:00','Audit annuel contrat vie','confirme',NULL,'2024-11-25 09:00:00'),(6,2,13,5,'2024-10-15 10:00:00','D??claration sinistre accident','annule',NULL,'2024-10-10 08:00:00');
/*!40000 ALTER TABLE `rendez_vous` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reponse`
--

DROP TABLE IF EXISTS `reponse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reponse` (
  `id_reponse` int(11) NOT NULL AUTO_INCREMENT,
  `date_reponse` datetime DEFAULT current_timestamp(),
  `contenu` text NOT NULL,
  `statut` varchar(50) DEFAULT NULL,
  `reclamation_id` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_re` int(11) GENERATED ALWAYS AS (`id_reponse`) VIRTUAL,
  PRIMARY KEY (`id_reponse`),
  KEY `id_user` (`id_user`),
  KEY `reclamation_id` (`reclamation_id`),
  CONSTRAINT `reponse_ibfk_1` FOREIGN KEY (`reclamation_id`) REFERENCES `reclamation` (`id_reclamation`),
  CONSTRAINT `reponse_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reponse`
--

LOCK TABLES `reponse` WRITE;
/*!40000 ALTER TABLE `reponse` DISABLE KEYS */;
INSERT INTO `reponse` VALUES (1,'2024-06-05 09:00:00','Nous vous pr??sentons nos excuses pour ce d??lai. Le virement de 3 500 DT a ??t?? effectu?? ce jour. R??f??rence virement : VIR-2024-0155.','R??solu',1,2,1),(2,'2024-06-18 10:00:00','Suite ?? v??rification, une erreur de saisie a ??t?? corrig??e. La diff??rence de 12 DT vous sera rembours??e lors de la prochaine ??ch??ance.','R??solu',2,2,2),(3,'2024-07-15 11:00:00','Votre dossier est en cours de r??-examen par notre service contentieux. Nous vous contacterons sous 5 jours ouvrables.','En cours',3,3,3),(4,'2024-09-03 14:00:00','Apr??s investigation, aucune modification n\'a ??t?? apport??e ?? votre contrat. Nous vous adressons une copie conforme par email.','Ferm??',5,2,4);
/*!40000 ALTER TABLE `reponse` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reponse_history`
--

DROP TABLE IF EXISTS `reponse_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reponse_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_reponse` int(11) NOT NULL,
  `reponse_id` int(11) DEFAULT NULL,
  `contenu_avant` text DEFAULT NULL,
  `ancien_contenu` text DEFAULT NULL,
  `modifie_par` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_reponse` (`id_reponse`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reponse_history`
--

LOCK TABLES `reponse_history` WRITE;
/*!40000 ALTER TABLE `reponse_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `reponse_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reponse_template`
--

DROP TABLE IF EXISTS `reponse_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reponse_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(100) NOT NULL,
  `contenu` text NOT NULL,
  `categorie` enum('accuse','refus','complement','resolution','autre') DEFAULT 'autre',
  `id_agence` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `categorie` (`categorie`),
  KEY `id_agence` (`id_agence`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reponse_template`
--

LOCK TABLES `reponse_template` WRITE;
/*!40000 ALTER TABLE `reponse_template` DISABLE KEYS */;
INSERT INTO `reponse_template` VALUES (1,'Accus?? de r??ception standard','Nous avons bien re??u votre r??clamation et nous en accusons r??ception. Notre ??quipe vous contactera sous 48h ouvrables.','accuse',NULL,2,'2026-06-06 01:14:16'),(2,'Remboursement effectu??','Nous vous informons que votre remboursement a ??t?? trait??. Le montant sera cr??dit?? sous 3 ?? 5 jours ouvrables.','resolution',1,2,'2026-06-06 01:14:16'),(3,'Demande d\'informations compl??mentaires','Afin de traiter votre dossier dans les meilleurs d??lais, nous vous prions de nous fournir les documents suivants : ...','complement',NULL,3,'2026-06-06 01:14:16'),(4,'Refus motiv??','Apr??s examen approfondi de votre dossier, nous regrettons de vous informer que votre demande ne peut ??tre satisfaite en raison de : ...','refus',NULL,2,'2026-06-06 01:14:16');
/*!40000 ALTER TABLE `reponse_template` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roulette_gains`
--

DROP TABLE IF EXISTS `roulette_gains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roulette_gains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) DEFAULT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `paiements` int(11) DEFAULT 0,
  `cadeau_label` varchar(255) DEFAULT NULL,
  `cadeau_icone` varchar(100) DEFAULT NULL,
  `type_recompense` varchar(50) DEFAULT NULL,
  `code_promo` varchar(50) DEFAULT NULL,
  `valeur_reduction` decimal(10,2) DEFAULT 0.00,
  `utilise` tinyint(1) DEFAULT 0,
  `date_utilisation` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `date_jeu` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roulette_gains`
--

LOCK TABLES `roulette_gains` WRITE;
/*!40000 ALTER TABLE `roulette_gains` DISABLE KEYS */;
INSERT INTO `roulette_gains` VALUES (1,'ines.gharbi@gmail.com','Gharbi','Ines',0,'R??duction 10%',NULL,NULL,'ROUL-GHR-10',10.00,1,'2024-07-01 10:00:00','2024-06-15 11:00:00',NULL),(2,'fatma.kallel@outlook.com','Kallel','Fatma',0,'R??duction 15%',NULL,NULL,'ROUL-KAL-15',15.00,0,NULL,'2024-09-10 14:00:00',NULL),(3,'tarek.mansouri@gmail.com','Mansouri','Tarek',0,'Mois gratuit',NULL,NULL,'ROUL-MAN-MG',0.00,0,NULL,'2024-10-25 16:00:00',NULL),(4,'bilel.ayari@gmail.com','Ayari','Bilel',0,'R??duction 5%',NULL,NULL,'ROUL-AYA-05',5.00,1,'2024-08-20 09:00:00','2024-07-30 11:00:00',NULL);
/*!40000 ALTER TABLE `roulette_gains` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roulette_jeu`
--

DROP TABLE IF EXISTS `roulette_jeu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roulette_jeu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_client` varchar(255) DEFAULT NULL,
  `palier` varchar(50) DEFAULT NULL,
  `cadeau_label` varchar(255) DEFAULT NULL,
  `code_promo` varchar(50) DEFAULT NULL,
  `valeur_reduction` decimal(10,2) DEFAULT 0.00,
  `utilise` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roulette_jeu`
--

LOCK TABLES `roulette_jeu` WRITE;
/*!40000 ALTER TABLE `roulette_jeu` DISABLE KEYS */;
/*!40000 ALTER TABLE `roulette_jeu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sinistre`
--

DROP TABLE IF EXISTS `sinistre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sinistre` (
  `id_sinistre` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `statut` enum('en_attente','en_analyse','assigne','en_cours','rembourse','refuse','cloture') DEFAULT 'en_attente',
  `id_contrat` int(11) DEFAULT NULL,
  `id_user` int(11) NOT NULL,
  `id_agence` int(11) DEFAULT NULL,
  `id_agent_assigne` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `date_declaration` datetime DEFAULT current_timestamp(),
  `ai_cost_min` decimal(10,2) DEFAULT NULL COMMENT 'Estimation min IA (DT)',
  `ai_cost_max` decimal(10,2) DEFAULT NULL COMMENT 'Estimation max IA (DT)',
  `ai_cost_estimate` decimal(10,2) DEFAULT NULL COMMENT 'Estimation centrale IA (DT)',
  `ai_remboursement` decimal(10,2) DEFAULT NULL COMMENT 'Remboursement estim├® IA (DT)',
  `ai_analysis` text DEFAULT NULL COMMENT 'Analyse textuelle IA',
  `ai_generated_at` datetime DEFAULT NULL COMMENT 'Date g├®n├®ration estimation',
  PRIMARY KEY (`id_sinistre`),
  KEY `statut` (`statut`),
  KEY `id_user` (`id_user`),
  KEY `id_agent_assigne` (`id_agent_assigne`),
  CONSTRAINT `sinistre_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sinistre`
--

LOCK TABLES `sinistre` WRITE;
/*!40000 ALTER TABLE `sinistre` DISABLE KEYS */;
INSERT INTO `sinistre` VALUES (1,'Accident de la route','Collision au rond-point Boulevard du Lac, Tunis. Dommages avant gauche.',NULL,'rembourse',1,8,1,4,1,'2024-05-10 08:30:00',NULL,NULL,NULL,NULL,NULL,NULL),(3,'D??g??ts des eaux','Fuite canalisation salle de bain, dommages parquet et plafond chambre',NULL,'rembourse',3,10,2,5,1,'2024-05-25 14:00:00',NULL,NULL,NULL,NULL,NULL,NULL),(4,'Hospitalisation','Appendicite aigu??, op??ration CHU Sfax, 4 jours d\'hospitalisation',NULL,'rembourse',4,11,3,6,1,'2024-06-15 22:00:00',NULL,NULL,NULL,NULL,NULL,NULL),(5,'Accident de la route','Accrochage parking supermarch??, dommages mineurs c??t?? conducteur',NULL,'en_attente',6,13,2,5,1,'2024-10-01 16:00:00',NULL,NULL,NULL,NULL,NULL,NULL),(6,'Incendie','Court-circuit ??lectrique, feu dans cuisine, dommages partiels',NULL,'en_cours',8,10,2,5,1,'2024-11-05 03:30:00',NULL,NULL,NULL,NULL,NULL,NULL),(7,'Bris de glace','Pare-brise fissur?? suite ?? projection de gravillon autoroute A1',NULL,'en_attente',1,8,1,4,1,'2024-11-20 11:00:00',NULL,NULL,NULL,NULL,NULL,NULL),(8,'Tentative de vol','Retroviseur cass?? et porti??re forc??e, v??hicule non d??rob??',NULL,'refuse',7,8,1,4,1,'2023-08-10 07:00:00',NULL,NULL,NULL,NULL,NULL,NULL),(9,'Incendie','cvfgtbhynhhbbhbh',NULL,'refuse',12,18,1,NULL,0,'2026-06-06 00:00:00',NULL,NULL,NULL,NULL,NULL,NULL),(10,'Accident auto','YFGHBJKN?LNJHBGCFXCVGHBNJ','uploads/sinistres/10/doc_6a25ee7dc5d79.png','en_attente',14,18,1,NULL,0,'2026-06-07 00:00:00',1200.00,2500.00,1800.00,1425.00,'L&#039;estimation est faite en l&#039;absence de photos, mais en tenant compte de la gravit├® des dommages et des tarifs des carrossiers agr├®├®s en Tunisie. Les zones endommag├®es sont inconnues, mais il est probable que les feux arri├¿re, les phares et les panneaux lat├®raux soient touch├®s. Le d├®lai estim├® de r├®paration est de 5 ├á 7 jours.','2026-06-07 23:19:41');
/*!40000 ALTER TABLE `sinistre` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sinistre_commentaire`
--

DROP TABLE IF EXISTS `sinistre_commentaire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sinistre_commentaire` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_sinistre` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `commentaire` text NOT NULL,
  `mentions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`mentions`)),
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_sinistre` (`id_sinistre`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sinistre_commentaire`
--

LOCK TABLES `sinistre_commentaire` WRITE;
/*!40000 ALTER TABLE `sinistre_commentaire` DISABLE KEYS */;
/*!40000 ALTER TABLE `sinistre_commentaire` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sinistre_fichier`
--

DROP TABLE IF EXISTS `sinistre_fichier`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sinistre_fichier` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_sinistre` int(11) NOT NULL,
  `nom_fichier` varchar(255) NOT NULL,
  `chemin` varchar(512) NOT NULL,
  `type` varchar(100) NOT NULL,
  `taille` int(10) unsigned NOT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_sinistre` (`id_sinistre`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sinistre_fichier`
--

LOCK TABLES `sinistre_fichier` WRITE;
/*!40000 ALTER TABLE `sinistre_fichier` DISABLE KEYS */;
INSERT INTO `sinistre_fichier` VALUES (1,10,'doc_6a25ee7dc5d79.png','uploads/sinistres/10/doc_6a25ee7dc5d79.png','image/png',184,'2026-06-07 23:19:41');
/*!40000 ALTER TABLE `sinistre_fichier` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_alerts`
--

DROP TABLE IF EXISTS `sms_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sms_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_contrat` int(11) DEFAULT NULL,
  `id_client` int(11) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `type_alert` varchar(50) DEFAULT NULL,
  `statut` enum('envoye','echec','en_attente') DEFAULT 'en_attente',
  `infobip_message_id` varchar(255) DEFAULT NULL,
  `infobip_bulk_id` varchar(255) DEFAULT NULL,
  `response_json` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `date_envoi` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_sms_expiration` (`id_contrat`,`type_alert`),
  KEY `id_client` (`id_client`),
  KEY `statut` (`statut`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_alerts`
--

LOCK TABLES `sms_alerts` WRITE;
/*!40000 ALTER TABLE `sms_alerts` DISABLE KEYS */;
INSERT INTO `sms_alerts` VALUES (1,11,18,'54415625','Bonjour Mohamed Miledi, votre contrat Protex CTR-2026-794193 a ├®t├® r├®sili├®.','changement_statut_resilie','',NULL,NULL,'{\"success\":false,\"http_code\":401,\"error\":\"\",\"response\":{\"errorCode\":\"E401\",\"description\":\"The request lacks valid authentication credentials for the requested resource.\",\"action\":\"Check the resources and adjust authentication credentials.\",\"violations\":[],\"resources\":[{\"name\":\"API Authentication\",\"url\":\"https://www.infobip.com/docs/api/essentials/api-authentication\"},{\"name\":\"API endpoint documentation\",\"url\":\"https://www.infobip.com/docs/api/send-sms-messages\"}]}}','2026-06-06 19:48:44','2026-06-06 19:48:44');
/*!40000 ALTER TABLE `sms_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sos_alerts`
--

DROP TABLE IF EXISTS `sos_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sos_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `accuracy` decimal(10,2) DEFAULT NULL,
  `nb_contacts_alertes` int(11) DEFAULT 0,
  `statut` enum('actif','resolu') DEFAULT 'actif',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `statut` (`statut`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sos_alerts`
--

LOCK TABLES `sos_alerts` WRITE;
/*!40000 ALTER TABLE `sos_alerts` DISABLE KEYS */;
INSERT INTO `sos_alerts` VALUES (1,8,36.81897000,10.16579000,15.50,2,'resolu','2026-06-06 01:14:16'),(2,11,35.82539000,10.63699000,22.00,1,'resolu','2026-06-06 01:14:16');
/*!40000 ALTER TABLE `sos_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `story`
--

DROP TABLE IF EXISTS `story`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `story` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `media_url` varchar(500) DEFAULT NULL,
  `contenu` text DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_user` (`id_user`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story`
--

LOCK TABLES `story` WRITE;
/*!40000 ALTER TABLE `story` DISABLE KEYS */;
/*!40000 ALTER TABLE `story` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `traitement`
--

DROP TABLE IF EXISTS `traitement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `traitement` (
  `id_traitement` int(11) NOT NULL AUTO_INCREMENT,
  `id_sinistre` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `decision` varchar(255) DEFAULT NULL,
  `montant_indemnise` decimal(10,2) DEFAULT 0.00,
  `statut` varchar(50) DEFAULT NULL,
  `est_valide` tinyint(1) DEFAULT 0,
  `date_validation` datetime DEFAULT NULL,
  `valide_par` int(11) DEFAULT NULL,
  `nom_agent` varchar(100) DEFAULT NULL,
  `message_agent` text DEFAULT NULL,
  `date_traitement` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_traitement`),
  KEY `id_sinistre` (`id_sinistre`),
  KEY `statut` (`statut`),
  CONSTRAINT `traitement_ibfk_1` FOREIGN KEY (`id_sinistre`) REFERENCES `sinistre` (`id_sinistre`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `traitement`
--

LOCK TABLES `traitement` WRITE;
/*!40000 ALTER TABLE `traitement` DISABLE KEYS */;
INSERT INTO `traitement` VALUES (1,1,NULL,'Remboursement accord?? apr??s expertise',3500.00,'Rembours??',0,NULL,4,'Amira Trabelsi','Expertise favorable, responsabilit?? partag??e 50/50. Remboursement dommages c??t?? assur??.','2024-05-20 10:00:00'),(2,3,NULL,'Remboursement mat??riaux et main-d\'??uvre',1800.00,'Rembours??',0,NULL,5,'Mohamed Hamdi','Constat plombier certifi?? fourni, devis r??paration valid??.','2024-06-05 14:00:00'),(3,4,NULL,'Prise en charge frais hospitaliers',4200.00,'Rembours??',0,NULL,6,'Rania Oueslati','Factures CHU valid??es, s??jour 4 jours en chirurgie g??n??rale.','2024-06-25 09:00:00'),(4,8,NULL,'Sinistre rejet?? ??? contrat expir??',0.00,'Refus??',0,NULL,4,'Amira Trabelsi','Contrat PTX-2023-0010 expir?? au moment du sinistre. Dossier ferm??.','2023-08-15 10:00:00'),(5,7,4,'en_attente',15000.00,'en_cours',0,NULL,NULL,'Amira Trabelsi','xdcfgv','2026-06-06 00:00:00'),(7,9,4,'refuse',6566.00,'refuse',0,NULL,NULL,'Amira Trabelsi','Refus automatique par l\'IA','2026-06-06 00:00:00');
/*!40000 ALTER TABLE `traitement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `id_agence` int(11) DEFAULT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('client','agent','admin','superadmin') DEFAULT 'client',
  `statut` enum('actif','inactif','banni') DEFAULT 'actif',
  `avatar` varchar(255) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `cin` varchar(20) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `face_encoding` text DEFAULT NULL,
  `face_registered` tinyint(1) DEFAULT 0,
  `google_id` varchar(255) DEFAULT NULL,
  `github_id` varchar(255) DEFAULT NULL,
  `points_parrainage` int(11) DEFAULT 0,
  `referral_code` varchar(20) DEFAULT NULL,
  `last_seen` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `hide_online_status` tinyint(1) DEFAULT 0,
  `onboarding_done` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `date_creation` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `code_parrain` varchar(20) DEFAULT NULL,
  `id_parrain_ref` int(11) DEFAULT NULL COMMENT 'ID du client qui a parrain?? cet utilisateur',
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `code_parrain` (`code_parrain`),
  KEY `role` (`role`),
  KEY `statut` (`statut`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,NULL,'Miledi','Karim','superadmin@protex.tn','$2y$10$B5QMSzXYwI8ZfAIClXdPv.AmOXJve9Fd6CPNZIVAW5sV5VLeH2OAO','superadmin','actif',NULL,NULL,'+216 55 000 001',NULL,'Sfax, Tunisie',NULL,'2026-06-06 01:14:16',NULL,0,NULL,NULL,500,'SUPER001',NULL,NULL,0,1,'2024-01-01 08:00:00',NULL,'2026-06-06 01:52:04',NULL,NULL),(2,NULL,'Ben Ali','Sarra','admin.tunis@protex.tn','$2y$10$B5QMSzXYwI8ZfAIClXdPv.AmOXJve9Fd6CPNZIVAW5sV5VLeH2OAO','admin','actif','avatar_2_1780707592.jpg',NULL,'+216 55 000 002',NULL,'Tunis, Tunisie',NULL,'2026-06-06 01:14:16',NULL,0,NULL,NULL,200,'ADM00002','2026-06-08 01:35:58',NULL,0,1,'2024-01-05 09:00:00',NULL,'2026-06-08 01:35:58',NULL,NULL),(3,NULL,'Chaabane','Youssef','admin.sfax@protex.tn','$2y$10$B5QMSzXYwI8ZfAIClXdPv.AmOXJve9Fd6CPNZIVAW5sV5VLeH2OAO','admin','actif',NULL,NULL,'+216 55 000 003',NULL,'Sfax, Tunisie',NULL,'2026-06-06 01:14:16',NULL,0,NULL,NULL,150,'ADM00003',NULL,NULL,0,1,'2024-01-06 09:00:00',NULL,'2026-06-06 01:52:04',NULL,NULL),(4,NULL,'Trabelsi','Amira','amira.trabelsi@protex.tn','$2y$10$B5QMSzXYwI8ZfAIClXdPv.AmOXJve9Fd6CPNZIVAW5sV5VLeH2OAO','agent','actif',NULL,NULL,'+216 55 000 004',NULL,'Tunis, Tunisie',NULL,'2026-06-06 01:14:16',NULL,0,NULL,NULL,80,'AGT00004','2026-06-06 20:20:33',NULL,0,1,'2024-02-01 08:30:00',NULL,'2026-06-06 20:20:33',NULL,NULL),(5,NULL,'Hamdi','Mohamed','mohamed.hamdi@protex.tn','$2y$10$B5QMSzXYwI8ZfAIClXdPv.AmOXJve9Fd6CPNZIVAW5sV5VLeH2OAO','agent','actif',NULL,NULL,'+216 55 000 005',NULL,'Sfax, Tunisie',NULL,'2026-06-06 01:14:16',NULL,0,NULL,NULL,60,'AGT00005',NULL,NULL,0,1,'2024-02-02 08:30:00',NULL,'2026-06-06 01:52:04',NULL,NULL),(6,NULL,'Oueslati','Rania','rania.oueslati@protex.tn','$2y$10$B5QMSzXYwI8ZfAIClXdPv.AmOXJve9Fd6CPNZIVAW5sV5VLeH2OAO','agent','actif',NULL,NULL,'+216 55 000 006',NULL,'Sousse, Tunisie',NULL,'2026-06-06 01:14:16',NULL,0,NULL,NULL,45,'AGT00006',NULL,NULL,0,1,'2024-02-03 08:30:00',NULL,'2026-06-06 01:52:04',NULL,NULL),(7,NULL,'Mzoughi','Slim','slim.mzoughi@protex.tn','$2y$10$B5QMSzXYwI8ZfAIClXdPv.AmOXJve9Fd6CPNZIVAW5sV5VLeH2OAO','agent','actif',NULL,NULL,'+216 55 000 007',NULL,'Monastir, Tunisie',NULL,'2026-06-06 01:14:16',NULL,0,NULL,NULL,30,'AGT00007',NULL,NULL,0,1,'2024-02-04 08:30:00',NULL,'2026-06-06 01:52:04',NULL,NULL),(8,NULL,'Gharbi','Ines','ines.gharbi@gmail.com','$2y$10$B5QMSzXYwI8ZfAIClXdPv.AmOXJve9Fd6CPNZIVAW5sV5VLeH2OAO','client','actif',NULL,NULL,'+216 22 100 008',NULL,'Ariana, Tunisie',NULL,'2026-06-06 01:14:16',NULL,0,NULL,NULL,120,'CLT00008',NULL,NULL,0,1,'2024-03-01 10:00:00',NULL,'2026-06-08 01:37:15','GHA-9812',NULL),(9,NULL,'Ayari','Bilel','bilel.ayari@gmail.com','$2y$10$B5QMSzXYwI8ZfAIClXdPv.AmOXJve9Fd6CPNZIVAW5sV5VLeH2OAO','client','actif',NULL,NULL,'+216 22 100 009',NULL,'La Marsa, Tunisie',NULL,'2026-06-06 01:14:16',NULL,0,NULL,NULL,90,'CLT00009',NULL,NULL,0,1,'2024-03-05 10:00:00',NULL,'2026-06-08 01:37:15','AYA-1782',NULL),(10,NULL,'Jebali','Asma','asma.jebali@gmail.com','$2y$10$B5QMSzXYwI8ZfAIClXdPv.AmOXJve9Fd6CPNZIVAW5sV5VLeH2OAO','client','actif',NULL,NULL,'+216 22 100 010',NULL,'Sfax, Tunisie',NULL,'2026-06-06 01:14:16',NULL,0,NULL,NULL,200,'CLT00010',NULL,NULL,0,1,'2024-03-10 10:00:00',NULL,'2026-06-08 01:37:15','JEB-5475',NULL),(11,NULL,'Riahi','Khalil','khalil.riahi@yahoo.fr','$2y$10$B5QMSzXYwI8ZfAIClXdPv.AmOXJve9Fd6CPNZIVAW5sV5VLeH2OAO','client','actif',NULL,NULL,'+216 22 100 011',NULL,'Sousse, Tunisie',NULL,'2026-06-06 01:14:16',NULL,0,NULL,NULL,50,'CLT00011',NULL,NULL,0,1,'2024-03-15 10:00:00',NULL,'2026-06-08 01:37:15','RIA-3029',NULL),(12,NULL,'Kallel','Fatma','fatma.kallel@outlook.com','$2y$10$B5QMSzXYwI8ZfAIClXdPv.AmOXJve9Fd6CPNZIVAW5sV5VLeH2OAO','client','actif',NULL,NULL,'+216 22 100 012',NULL,'Monastir, Tunisie',NULL,'2026-06-06 01:14:16',NULL,0,NULL,NULL,310,'CLT00012',NULL,NULL,0,1,'2024-03-20 10:00:00',NULL,'2026-06-08 01:37:15','KAL-6721',NULL),(13,NULL,'Mansouri','Tarek','tarek.mansouri@gmail.com','$2y$10$B5QMSzXYwI8ZfAIClXdPv.AmOXJve9Fd6CPNZIVAW5sV5VLeH2OAO','client','actif',NULL,NULL,'+216 22 100 013',NULL,'Gab??s, Tunisie',NULL,'2026-06-06 01:14:16',NULL,0,NULL,NULL,75,'CLT00013',NULL,NULL,0,1,'2024-04-01 10:00:00',NULL,'2026-06-08 01:37:15','MAN-5518',NULL),(14,NULL,'Boughanmi','Leila','leila.boughanmi@gmail.com','$2y$10$B5QMSzXYwI8ZfAIClXdPv.AmOXJve9Fd6CPNZIVAW5sV5VLeH2OAO','client','inactif',NULL,NULL,'+216 22 100 014',NULL,'Bizerte, Tunisie',NULL,NULL,NULL,0,NULL,NULL,0,'CLT00014',NULL,NULL,0,0,'2024-04-10 10:00:00',NULL,'2026-06-08 01:37:15','BOU-6429',NULL),(15,NULL,'Ferjani','Ramzi','ramzi.ferjani@gmail.com','$2y$10$B5QMSzXYwI8ZfAIClXdPv.AmOXJve9Fd6CPNZIVAW5sV5VLeH2OAO','client','actif',NULL,NULL,'+216 22 100 015',NULL,'Tunis, Tunisie',NULL,'2026-06-06 01:14:16',NULL,0,NULL,NULL,5,'CLT00015',NULL,NULL,0,1,'2024-04-15 10:00:00',NULL,'2026-06-08 01:37:15','FER-5589',NULL),(16,NULL,'Miledi','Mohamed','Medkarimmiledi123@gmail.com','$2y$10$B5QMSzXYwI8ZfAIClXdPv.AmOXJve9Fd6CPNZIVAW5sV5VLeH2OAO','client','actif',NULL,NULL,'54 41 5625',NULL,'GREMDA',NULL,NULL,NULL,0,NULL,NULL,0,'PRTX-7A1C19',NULL,NULL,0,0,'2026-06-06 01:22:16',NULL,'2026-06-08 01:37:15','MIL-7661',NULL),(18,NULL,'Miledi','Mohamed','Medkarimmiledi@gmail.com','$2y$10$B5QMSzXYwI8ZfAIClXdPv.AmOXJve9Fd6CPNZIVAW5sV5VLeH2OAO','client','actif',NULL,NULL,'54 41 5625','11204041','GREMDA',NULL,NULL,'configured',0,NULL,NULL,0,'PRTX-4C83B3','2026-06-08 03:13:04','2026-06-08 02:41:53',0,1,'2026-06-06 01:29:43',NULL,'2026-06-08 03:13:04','MIL-2535',NULL);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `voice_sessions`
--

DROP TABLE IF EXISTS `voice_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `voice_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `peer_id` varchar(64) NOT NULL,
  `salle` varchar(100) NOT NULL,
  `id_agence` int(11) DEFAULT NULL,
  `joined_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_salle` (`salle`),
  KEY `idx_voice_agence` (`id_agence`)
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `voice_sessions`
--

LOCK TABLES `voice_sessions` WRITE;
/*!40000 ALTER TABLE `voice_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `voice_sessions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-08  4:09:41

