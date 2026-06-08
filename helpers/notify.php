<?php
function notify(PDO $db, int $idUser, string $message, string $type = 'info', ?string $lien = null): void {
    if ($idUser <= 0) return;
    $stmt = $db->prepare("INSERT INTO notification (id_user, message, type, lien) VALUES (?, ?, ?, ?)");
    $stmt->execute([$idUser, $message, $type, $lien]);
}
