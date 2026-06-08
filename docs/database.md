# Base de donnees

## Informations generales

- **SGBD** : MySQL 8.0+
- **Nom** : `assurance`
- **Tables** : 64+
- **Migration** : `migrations/full_schema.sql`

## Schema conceptuel

```
User (1) ---< LoginHistory
User (1) ---< Notification
User (1) ---< LoginAttempts
User (1) ---< OTP_Code
User (1) ---1 Admin
User (1) ---1 Agent ---< Agence
User (1) ---1 Client ---< Agence

Agence (1) ---< Agent
Agence (1) ---< RendezVous
Agence (1) ---< AgenceAvis
Agence (1) ---< AgenceHoraires

Categorie (1) ---< Offre
Categorie (1) ---< Formule
Categorie (1) ---< Garantie
Formule (1) ---< FormuleGarantie >--- Garantie
Offre (1) ---< Devis
Devis (1) ---1 DevisAuto / DevisHabitation / DevisSante

User (1) ---< Contrat >--- Offre
Contrat (1) ---< ContratHistorique
Contrat (1) ---< ContratGarantieOverride >--- Garantie
Contrat (1) ---< Sinistre
Contrat (1) ---< Paiement

Sinistre (1) ---< SinistreCommentaire
Sinistre (1) ---< SinistreFichier
Sinistre (1) ---< MessageSinistre
Sinistre (1) ---< Traitement
Sinistre (1) ---< FraudAnalysis

User (1) ---< Reclamation >--- Contrat
Reclamation (1) ---< Reponse
Reclamation (1) ---< ReclamationSatisfaction
Reclamation (1) ---< AuditReclamation

User (1) ---< Poste
Poste (1) ---< Commentaire
Poste (1) ---< PostReaction
User (1) ---< Story
User (1) ---< Friendships
User (1) ---< SOS_Alert

User (1) ---< Messages
User (1) ---< Parrainage
User (1) ---< PointsFidelite
```

## Principales tables

### Utilisateurs

| Table | Description |
|-------|-------------|
| `user` | Utilisateurs (tous roles) : nom, email, mot_de_passe, role, statut |
| `admin` | Donnees specifiques aux admins : niveau_acces |
| `agent` | Donnees specifiques aux agents : salaire, id_agence |
| `client` | Donnees specifiques aux clients : numero_client |

### Catalogue produits

| Table | Description |
|-------|-------------|
| `categorie` | Categories d'assurance (Auto, Sante, Habitation, Vie) |
| `offre` | Offres avec prix et couverture |
| `formule` | Formules liees aux categories |
| `garantie` | Garanties avec plafond et franchise par defaut |

### Contrats et sinistres

| Table | Description |
|-------|-------------|
| `contrat` | Contrats d'assurance : dates, prime, franchise, statut |
| `contrat_historique` | Audit des modifications |
| `sinistre` | Sinistres : type, description, statut, fraud_score |
| `traitement` | Decisions d'indemnisation |
| `fraud_analysis` | Analyse detailee de fraude (score, recommandation IA) |

### Paiements

| Table | Description |
|-------|-------------|
| `paiement` | Paiements : montant, methode, periodicite, statut, stripe_id |
| `relance_paiement` | Historique des relances |

### Reclamations

| Table | Description |
|-------|-------------|
| `reclamation` | Tickets : objet, type, statut, priorite, sla |
| `reponse` | Reponses aux reclamations |
| `reclamation_satisfaction` | Enquetes de satisfaction |

### Social

| Table | Description |
|-------|-------------|
| `poste` | Publications sociales |
| `commentaire` | Commentaires (support parent/enfant) |
| `story` | Stories ephemeres (expiration 24h) |
| `friendships` | Connexions entre utilisateurs |
| `sos_alerts` | Alertes d'urgence avec coordonnees GPS |

### Gamification

| Table | Description |
|-------|-------------|
| `points_fidelite` | Compteur de points de fidelite |
| `roulette_jeu` | Parties de roulette |
| `jeu_snake` | Scores du jeu Snake |

### Partenaires et parrainage

| Table | Description |
|-------|-------------|
| `partenaires` | Annuaire des partenaires |
| `parrainage` | Historique des parrainages |
| `parrainage_config` | Configuration du systeme de parrainage |
