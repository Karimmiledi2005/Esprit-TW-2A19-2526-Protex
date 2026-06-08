<?php
require_once __DIR__ . '/../../bootstrap.php';
header('Content-Type: application/json');

if (isset($_SESSION['user_id']) || isset($_SESSION['id_user'])) {
    echo json_encode([
        "logged"       => true,
        "id_user"      => $_SESSION['id_user'] ?? $_SESSION['user_id'],
        "nom"          => $_SESSION['nom'],
        "role"         => $_SESSION['role'],
        "id_agence"    => $_SESSION['id_agence'] ?? null,
    ]);
} else {
    echo json_encode(["logged"=>false]);
}

