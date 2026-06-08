# Architecture

## Vue d'ensemble

Protex suit une architecture MVC native PHP avec des microservices Flask specialises et une base MySQL centralisee.

```
 +------------------+
 |    Navigateur     |
 |   Bootstrap 5     |
 |   Vanilla JS      |
 +--------+---------+
          | HTTP
          v
 +---------------------------------------+
 |           PHP MVC (natif)              |
 |  +-----------+  +---------------+      |        +-----------+
 |  |Controllers |  |    Models     |      |------->|  MySQL 8  |
 |  |    x32     |  |     x18       |      |        |(assurance)|
 |  +-----------+  +---------------+      |        +-----------+
 |  +-----------+  +---------------+      |
 |  |  Helpers  |  |   Services    |      |
 |  |    x10    |  |     x3        |      |
 |  +-----------+  +---------------+      |
 +-------+--------+--------+--------+----+
         |        |        |        |
         v        v        v        v
   +--------+ +--------+ +--------+ +--------+
   |  Flask  | |  Flask | |  Flask | |  IA    |
   | Face ID | |  OCR   | |CAPTCHA | |Contrats|
   |  :5000  | | :5007  | | :5006  | |(script)|
   +--------+ +--------+ +--------+ +--------+

   +------------------------------------------+
   |          Services Externes                |
   | Stripe | Infobip | Groq/LLaMA | Geminii  |
   | GitHub OAuth | PHPMailer | PeerJS        |
   +------------------------------------------+
```

## Structure des repertoires

```
assurance/
|-- controller/        # 32 controleurs MVC
|-- model/             # 18 modeles metier
|-- view/
|   |-- FrontOffice/   # Interface client
|   |-- BackOffice/    # Interface admin
|-- helpers/           # Utilitaires (CSRF, RBAC, Session, RateLimit)
|-- service/           # Services Email, SMS, WhatsApp
|-- face_api/          # Microservice Face ID (Flask, port 5000)
|-- ai_contract/       # Generateur de contrats IA
|-- migrations/        # Scripts SQL par module
|-- scripts/           # Scripts utilitaire et cron
|-- tests/             # Tests PHPUnit et Python
|-- docs/              # Documentation technique
|-- api.php            # API REST centralisee (~85 endpoints)
|-- bootstrap.php      # Initialisation globale
|-- config.php         # Configuration centralisee PDO
```

## Flux de donnees

1. Le navigateur charge `index.php` -> redirige vers `welcome/index.html`
2. Les appels API transitent par `api.php` avec session + CSRF + rate limiting
3. Les controleurs MVC servent les pages (BackOffice/FrontOffice) avec vues PHP
4. Les microservices Flask sont appeles via HTTP depuis les controleurs PHP
5. Les services Stripe, Infobip, Groq sont appeles via leurs SDK PHP

## Principes d'architecture

- **Pas de framework** : PHP natif MVC, pas de Laravel/Symfony
- **API monolithique** : `api.php` centralise tous les endpoints REST
- **PDO singleton** : `config::getConnexion()` instance unique
- **RBAC** : `RoleHelper` verifie les permissions (25+ methodes)
- **Securite** : 8 couches (bcrypt, PDO, CSRF, OTP, session, rate limit, XSS, RBAC)
