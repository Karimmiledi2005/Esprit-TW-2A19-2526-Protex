<?php
header('Content-Type: application/json');
require_once __DIR__ . '/connexion.php';
$db = config::getConnexion();
echo json_encode([
    'clients'  => (int)$db->query("SELECT COUNT(*) FROM user WHERE role='client'")->fetchColumn(),
    'contrats' => (int)$db->query("SELECT COUNT(*) FROM contrat WHERE statut_contrat='actif'")->fetchColumn(),
    'agences'  => (int)$db->query("SELECT COUNT(*) FROM agence WHERE statut='active'")->fetchColumn(),
    'annees'   => 25,
]);
