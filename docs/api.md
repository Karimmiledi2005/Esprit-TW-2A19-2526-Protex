# API REST

Tous les endpoints sont accessibles via `api.php?action=<action>`. L'authentification se fait par session PHP.

## Authentification

- Session PHP requise pour tous les endpoints sauf `login`, `register`
- CSRF token obligatoire sur les requetes POST
- Rate limiting : 100 requetes/min par IP

## Endpoints

### Dashboard

| Action | Method | Description |
|--------|--------|-------------|
| `dashboard_stats` | GET | Statistiques globales (utilisateurs, contrats, sinistres, revenus) |
| `sinistres_by_region` | GET | Sinistres groupes par gouvernorat |
| `chart_sinistres_monthly` | GET | Sinistres mensuels (6 mois) |
| `chart_contracts_by_type` | GET | Contrats par type |
| `chart_fraud_distribution` | GET | Distribution des scores de fraude |
| `heartbeat` | GET | Met a jour `last_seen` de l'utilisateur |

### Devis

| Action | Method | Description | Role |
|--------|--------|-------------|------|
| `offres` | GET | Liste des offres actives | Tous |
| `devis_liste` | GET | Liste des devis (client voit les siens) | Tous |
| `devis_ajouter` | POST | Soumettre un nouveau devis | Tous |
| `devis_modifier` | POST | Modifier statut/montant d'un devis | Admin/Agent |
| `devis_supprimer` | GET | Supprimer un devis | Admin/Agent |

### Sinistres

| Action | Method | Description |
|--------|--------|-------------|
| `sinistre_dashboard_stats` | GET | Statistiques des sinistres |
| `sinistre_agent_workload` | GET | Charge de travail d'un agent |
| `sinistre_export_pdf` | GET | Export PDF des statistiques |
| `sinistre_export_excel` | GET | Export Excel des statistiques |
| `sinistre_add_comment` | POST | Ajouter un commentaire |
| `sinistre_upload_files` | POST | Uploader des fichiers |
| `sinistre_post_message` | POST | Poster un message |
| `sinistre_fetch_messages` | GET | Recuperer les messages |

### Contrats

| Action | Method | Description |
|--------|--------|-------------|
| `contrats_calendar` | GET | Calendrier des echeances |
| `contrat_send_reminder` | POST | Rappel par email pour un contrat |
| `contrat_bulk_reminder` | POST | Rappels groupés |
| `contrat_history` | GET | Historique des modifications |

### Paiements

| Action | Method | Description | Role |
|--------|--------|-------------|------|
| `paiement_dashboard_stats` | GET | Statistiques de revenus | Admin/Agent |
| `relancer_paiement` | POST | Relance d'un paiement | Admin/Agent |
| `relancer_tous` | POST | Relance groupée | Admin/Agent |
| `upcoming_payments` | GET | Paiements a venir (7 jours) | Tous |

### Utilisateurs

| Action | Method | Description |
|--------|--------|-------------|
| `search_users` | GET | Recherche avancee d'utilisateurs |
| `logout_all_sessions` | POST | Deconnexion de toutes les sessions |
| `get_login_history` | GET | Historique des connexions |
| `award_completion_points` | POST | Attribution de points de fidelite |

### Social / Reseau

| Action | Method | Description |
|--------|--------|-------------|
| `get_all_posts_admin` | GET | Moderation des posts |
| `moderate_post` | POST | Masquer/supprimer un post |
| `get_sos_admin` | GET | Alertes SOS actives |
| `resolve_sos` | POST | Resoudre une alerte SOS |
| `add_reaction` | POST | Ajouter une reaction |
| `suggestions_amis` | GET | Suggestions d'amis |
| `get_stories` | GET | Stories actives |
| `add_story` | POST | Creer une story |
| `get_online_status` | GET | Statut en ligne d'utilisateurs |

### Partenaires

| Action | Method | Description |
|--------|--------|-------------|
| `partenaires_list` | GET | Liste des partenaires |
| `partenaire_avis_add` | POST | Ajouter un avis |
| `partenaire_save` | POST | Creer/modifier un partenaire |
| `partenaire_delete` | POST | Supprimer un partenaire |

### Parrainage

| Action | Method | Description |
|--------|--------|-------------|
| `get_mon_code_parrain` | GET | Code parrain de l'utilisateur |
| `validate_code_parrain` | POST | Valider un code parrain |
| `parrainage_stats` | GET | Statistiques globales |
| `parrainage_top` | GET | Top 50 parrains |

### Reclamations

| Action | Method | Description |
|--------|--------|-------------|
| `escalader_reclamation` | POST | Escalader une reclamation |
| `save_satisfaction` | POST | Enregistrer un score de satisfaction |
| `ai_assist_reclamation` | POST | Aide IA pour rediger une reclamation |

### Agences

| Action | Method | Description |
|--------|--------|-------------|
| `save_agence_horaires` | POST | Horaires d'ouverture |
| `creer_rdv` | POST | Creer un rendez-vous |
| `disponibilites_agence` | GET | Creneaux disponibles |
| `add_agence_avis` | POST | Noter une agence |

### Salles virtuelles / Voice

| Action | Method | Description |
|--------|--------|-------------|
| `room_kpis` | GET | KPIs d'une salle virtuelle |
| `agents_online` | GET | Agents en ligne dans l'agence |
| `send_room_message` | POST | Message dans une salle |
| `voice_join` | POST | Rejoindre un salon vocal |
| `agent_ai_reply` | POST | Reponse IA d'un agent |

## Format des reponses

```json
{
  "success": true,
  "data": { ... },
  "message": "Operation reussie"
}
```

En cas d'erreur :

```json
{
  "success": false,
  "error": "Description de l'erreur"
}
```
