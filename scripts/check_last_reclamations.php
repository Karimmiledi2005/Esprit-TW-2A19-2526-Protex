<?php
require_once __DIR__ . '/../config.php';
$db = config::getConnexion();
$stmt = $db->query('SELECT id, objet, object_type, object_ref, id_user, date_depot, ref_contrat FROM reclamation ORDER BY id DESC LIMIT 5');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
