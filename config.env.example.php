<?php
/**
 * config.env.example.php - MODELE de configuration Protex
 * =========================================================
 * Ce fichier est versionne sur GitHub (pas de donnees sensibles).
 *
 * POUR INSTALLER LE PROJET :
 *   cp config.env.example.php config.env.php
 *   Puis remplir les valeurs dans config.env.php
 *
 * config.env.php est dans .gitignore → jamais commité
 */

return [

    // - BASE DE DONNEES ------------------------------------------------
    'db_host'     => 'localhost',
    'db_user'     => 'root',
    'db_password' => '',
    'db_name'     => 'assurance',
    'db_port'     => 3306,

    // - EMAIL SMTP -----------------------------------------------------
    'mail_host'      => 'smtp.gmail.com',
    'mail_port'      => 587,
    'mail_username'  => 'votre.email@gmail.com',
    'mail_password'  => 'xxxx_xxxx_xxxx_xxxx',   // App Password 16 car.
    'mail_from'      => 'votre.email@gmail.com',
    'mail_from_name' => 'Protex Assurance',

    // - STRIPE PAIEMENT (mode TEST) ------------------------------------
    'stripe_secret_key'      => 'sk_test_REMPLACER_PAR_VOTRE_CLE',
    'stripe_publishable_key' => 'pk_test_REMPLACER_PAR_VOTRE_CLE',

    // - INFOBIP SMS ----------------------------------------------------
    'infobip_api_key'  => 'VOTRE_CLE_INFOBIP',
    'infobip_base_url' => 'VOTRE_BASE_URL.api.infobip.com',
    'infobip_sender'   => 'Protex',

    // - GROQ IA --------------------------------------------------------
    'groq_api_key' => 'gsk_REMPLACER_PAR_VOTRE_CLE',

    // - CLAUDE API (optionnel) -----------------------------------------
    'claude_api_key' => '',

    // - GEMINI API (optionnel) -----------------------------------------
    'gemini_api_key' => '',

    // - GITHUB OAUTH (optionnel) ---------------------------------------
    'github_client_id'     => 'VOTRE_GITHUB_CLIENT_ID',
    'github_client_secret' => 'VOTRE_GITHUB_CLIENT_SECRET',

    // - TUNNEL PUBLIC (Ngrok) ------------------------------------------
    'ngrok_url' => 'https://votre-tunnel.ngrok-free.app',

];
