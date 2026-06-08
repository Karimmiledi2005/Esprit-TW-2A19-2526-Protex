# 🛡️ Protex — Plateforme d'Assurance Digitale

![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?logo=mysql&logoColor=white)
![Python](https://img.shields.io/badge/Python-3.10+-3776AB?logo=python&logoColor=white)
![Flask](https://img.shields.io/badge/Flask-3.0-000000?logo=flask&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?logo=bootstrap&logoColor=white)
![Stripe](https://img.shields.io/badge/Stripe-Payments-635BFF?logo=stripe&logoColor=white)

> Plateforme de gestion d'assurance digitale complète, développée dans le cadre du projet intégration ESPRIT School of Engineering — Année 2025-2026.

---

## 📋 Description

**Protex** est une application web full-stack de gestion d'assurance digitale intégrant :

- 🔐 **Authentification avancée** — Face ID (OpenCV LBPH), OCR CIN, OTP email, GitHub OAuth, CAPTCHA puzzle
- 👥 **4 rôles distincts** — SuperAdmin, Admin Agence, Agent, Client avec RBAC complet
- 📄 **Gestion des contrats** — Devis, contrats, garanties, formules, PDF + QR code
- 🚨 **Sinistres** — Déclaration, tracker visuel, estimation IA du coût (Groq LLaMA)
- 🤖 **IA Anti-fraude** — Scoring 5 modules (NLP, comportemental, image, doublons, contrat)
- 💳 **Paiements Stripe** — Intégration complète avec webhooks et reçus PDF
- 🌐 **Réseau social** — Posts, amis, messagerie, stories, SOS GPS
- 🎮 **Gamification** — Roulette, snake, memory, points fidélité
- 🤝 **Partenaires** — Réseau de garages, cliniques, pharmacies avec carte interactive
- 🎁 **Parrainage** — Système de referral avec codes et récompenses
- 📊 **Dashboard IA** — KPIs temps réel, graphiques Chart.js, carte Tunisie
- 🏢 **Agence Virtuelle 3D** — Environnement isométrique Three.js avec chat temps réel

---

## 🏗️ Architecture

```
Browser
   │
   ▼
PHP MVC (natif, sans framework)
   │
   ├──► MySQL (PDO)          — Base de données principale
   │
   ├──► Flask :5000          — Microservice Face ID (OpenCV LBPH)
   ├──► Flask :5007          — Microservice OCR (Tesseract)
   └──► Flask :5006          — Microservice CAPTCHA puzzle
            │
            └──► Groq API    — IA Chatbot + Estimation coût sinistre
```

---

## 🛠️ Technologies utilisées

| Couche | Technologies |
|--------|-------------|
| **Frontend** | HTML5, CSS3, Bootstrap 5, Vanilla JS, Three.js, Chart.js, Leaflet.js |
| **Backend** | PHP 8.1+ (MVC natif), PDO/MySQL |
| **IA / Microservices** | Python 3.10+, Flask, OpenCV, Tesseract, DeepFace, Groq LLaMA |
| **Paiement** | Stripe API (test mode) |
| **Email** | PHPMailer + Gmail SMTP |
| **Auth** | GitHub OAuth, OTP email, Face ID, OCR CIN |
| **PDF** | Dompdf + QR Code |

---

## ✅ Prérequis

- **XAMPP** avec Apache (port 8081) + MySQL (port 3306)
- **PHP 8.1+** (inclus dans XAMPP)
- **Python 3.10+** avec pip
- **Tesseract OCR** — [Télécharger](https://github.com/UB-Mannheim/tesseract/wiki)
- **Composer** (gestionnaire de dépendances PHP)
- **Navigateur moderne** (Chrome, Firefox, Edge)

---

## ⚡ Installation en moins de 10 minutes

### Étape 1 — Cloner le projet

```bash
git clone https://github.com/Karimmiledi2005/Esprit-TW-2A19-2526-Protex.git
cd Esprit-TW-2A19-2526-Protex
```

### Étape 2 — Copier dans XAMPP

```bash
# Windows
xcopy /E /I . C:\xampp\htdocs\assurance

# Linux / Mac
cp -r . /opt/lampp/htdocs/assurance
```

### Étape 3 — Base de données

1. Démarrer **XAMPP** (Apache + MySQL)
2. Ouvrir **phpMyAdmin** → `http://localhost/phpmyadmin`
3. Créer une base nommée `assurance`
4. Importer le fichier SQL :

```bash
mysql -u root -p assurance < database/schema.sql
```

Ou via phpMyAdmin : sélectionner la base `assurance` → **Importer** → choisir `database/schema.sql`

### Étape 4 — Configuration

```bash
cp config.env.example.php config.env.php
```

Éditer `config.env.php` et remplir vos valeurs :

```php
return [
    'db_host'     => 'localhost',
    'db_user'     => 'root',
    'db_password' => '',          // ← votre mot de passe MySQL
    'db_name'     => 'assurance',

    'mail_host'     => 'smtp.gmail.com',
    'mail_port'     => 587,
    'mail_username' => 'votre@gmail.com',
    'mail_password' => 'votre_app_password',  // App password Gmail

    'stripe_secret_key'      => 'sk_test_...',
    'stripe_publishable_key' => 'pk_test_...',

    'groq_api_key' => 'gsk_...',  // console.groq.com (gratuit)

    'github_client_id'     => '...',  // optionnel
    'github_client_secret' => '...',  // optionnel
];
```

### Étape 5 — Dépendances PHP

```bash
cd C:\xampp\htdocs\assurance
composer install
```

### Étape 6 — Dépendances Python

```bash
pip install -r requirements.txt
```

> ⚠️ Si erreur avec `deepface` : `pip install deepface tf-keras`

> **Important — Tesseract OCR** : Le microservice OCR nécessite Tesseract installé sur le système.
> - **Windows** : Télécharger depuis https://github.com/UB-Mannheim/tesseract/wiki
> - **Linux** : `sudo apt install tesseract-ocr tesseract-ocr-fra tesseract-ocr-ara`
> - **Mac** : `brew install tesseract tesseract-lang`
>
> ⚠️ **Fichiers de langue (tessdata)** : Télécharger `ara.traineddata`, `eng.traineddata`, et `fra.traineddata`
> depuis https://github.com/tesseract-ocr/tessdata et les placer dans le dossier `tessdata/` du projet.

### Étape 7 — Lancer les microservices IA

**Windows :**
```bash
# Double-cliquer sur :
Lancer_Tous_Les_Moteurs.bat
```

**Linux / Mac :**
```bash
# Terminal 1 — Face ID
python face_api/face_engine.py

# Terminal 2 — OCR
python ocr_engine.py

# Terminal 3 — CAPTCHA
python puzzle_engine.py
```

### Étape 8 — Ouvrir l'application

```
http://localhost:8081/assurance/welcome/index.html
```

---

## 🔑 Comptes de démonstration
Le mot de passe pour toute compte est:Protex123!

---

## 🎯 Fonctionnalités principales

### FrontOffice (Client)
- Inscription avec OCR CIN + Face ID
- Wizard de souscription 4 étapes
- Déclaration sinistre avec **estimation IA du coût en temps réel**
- Tracker visuel de l'avancement des sinistres
- Téléchargement PDF contrats avec QR code
- Paiements Stripe sécurisés
- Réseau social (posts, amis, SOS GPS)
- Jeux fidélité (roulette, snake, memory)
- Page partenaires avec carte interactive
- Programme de parrainage avec code unique

### BackOffice (Admin / Agent)
- Dashboard KPIs temps réel + graphiques
- Gestion RBAC 4 rôles avec isolation par agence
- Module anti-fraude IA (scoring automatique)
- Agence Virtuelle 3D isométrique (Three.js)
- Calendrier des contrats et RDV
- Export PDF/Excel des rapports
- Leaderboard des agences
- Gestion des partenaires

---

## 🔒 Sécurité

- Mots de passe hashés **bcrypt**
- Protection **CSRF** sur tous les formulaires
- Requêtes **PDO préparées** (anti SQL injection)
- Session sécurisée (`session_regenerate_id`, httponly cookies)
- Protection **XSS** (`htmlspecialchars` partout)
- Anti-brute-force (blocage après 5 tentatives)
- **2FA OTP** par email + **Face ID**
- Rate limiting API

---

## 📁 Structure du projet

```
assurance/
├── controller/          # Contrôleurs PHP (MVC)
├── model/               # Modèles de données
├── view/
│   ├── FrontOffice/     # Interface client
│   └── BackOffice/      # Interface admin/agent
├── helpers/             # SessionGuard, RoleHelper, CsrfHelper...
├── face_api/            # Microservice Flask Face ID
├── ai_contract/         # Microservice IA contrats
├── welcome/             # Landing page publique
├── database/
│   └── schema.sql       # Script SQL complet
├── docs/                # Documentation technique
├── demo/                # Captures d'écran
├── api.php              # Point d'entrée API REST
├── bootstrap.php        # Initialisation app
├── config.php           # Configuration
├── config.env.example.php  # Modèle variables d'environnement
├── requirements.txt     # Dépendances Python
├── composer.json        # Dépendances PHP
└── README.md
```

---

## 🎬 Démo

| Lien | Description |
|------|-------------|
| 📹 **Vidéo démo** | (https://youtu.be/atAO1kBjILM?si=F4auUncQ2CSQ_2LA) |
| 🌐 **Déploiement** | *Non déployé — installation locale requise* |

### Captures d'écran

> Voir le dossier `demo/` pour les captures d'écran complètes.

---

## 👥 Auteurs

| Nom | Rôle | Module |
|-----|------|--------|
| **Mohamed Karim Miledi** | Chef de projet · Module 1 | Identity & Access Engine (User + Role) |
| *ABDERRAHMEN BEN ABDALLAH* | Développeur | Module 2 |
| *MERYEN BOUAZIZI* | Développeur | Module 3 |
| *SABRINE MCHABET* | Développeur | Module 4 |
| *YESSIN BEN HAMZA* | Développeur | Module 5 |
| *SADOK BELRZOUGA* | Développeur | Module 6 |

**Classe :** 2A19 — **Année :** 2025-2026  
**Tuteur :** IBN ELFEKIH Oumeima  
**École :** ESPRIT School of Engineering

---

## 📄 Licence

Projet académique — ESPRIT School of Engineering 2025-2026.  
Tous droits réservés aux auteurs.