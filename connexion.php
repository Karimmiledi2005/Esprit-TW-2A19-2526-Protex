<?php
/**
 * connexion.php — Redirige vers config.php (source unique de vérité)
 * Maintenu pour compatibilité ascendante avec les fichiers qui l'incluent.
 */
if (!class_exists('config') && file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}