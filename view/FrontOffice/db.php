<?php
$host = '127.0.0.1';
$dbname = 'assurance';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    error_log('front db.php connection error: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur connexion BD'
    ]);
    exit;
}

function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function getJsonInput(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function addNotification(PDO $pdo, int $idUser, string $message, string $type = 'info'): void {
    $stmt = $pdo->prepare("INSERT INTO notification (id_user, message, type) VALUES (?, ?, ?)");
    $stmt->execute([$idUser, $message, $type]);
}

function syncPostStats(PDO $pdo, int $idPoste): void {
    $stmtLikes = $pdo->prepare("SELECT COUNT(*) FROM like_post WHERE id_poste = ?");
    $stmtLikes->execute([$idPoste]);
    $likes = (int)$stmtLikes->fetchColumn();

    $stmtComments = $pdo->prepare("SELECT COUNT(*) FROM commentaire WHERE id_poste = ?");
    $stmtComments->execute([$idPoste]);
    $comments = (int)$stmtComments->fetchColumn();

    $stmtUpdate = $pdo->prepare("
        UPDATE poste
        SET nb_likes = ?, nb_commentaires = ?
        WHERE id_poste = ?
    ");
    $stmtUpdate->execute([$likes, $comments, $idPoste]);
}

