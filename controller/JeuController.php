<?php
/**
 * controller/JeuController.php — Gestion des jeux (Snake + Memory)
 */

include_once __DIR__ . '/../config.php';
include_once __DIR__ . '/../model/JeuSnake.php';
include_once __DIR__ . '/../model/JeuMemory.php';

require_once __DIR__ . '/../bootstrap.php';

class JeuController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = config::getConnexion();
    }

    public function saveScore(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Méthode non autorisée']);
            return;
        }
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            echo json_encode(['error' => 'Non connecté']);
            return;
        }
        $score = (int)($_POST['score'] ?? 0);
        $vitesse = trim($_POST['vitesse'] ?? 'Normal');
        $dureeSec = (int)($_POST['duree'] ?? 0);
        $serpentsManges = (int)($_POST['serpents'] ?? 0);
        if ($score <= 0) {
            echo json_encode(['error' => 'Score invalide']);
            return;
        }
        try {
            $id = JeuSnake::save($this->db, $userId, $score, $vitesse, $dureeSec, $serpentsManges);
            echo json_encode(['success' => true, 'id' => $id]);
        } catch (Exception $e) {
            error_log('JeuController::saveScore error: ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
        }
    }

    public function saveMemory(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Méthode non autorisée']);
            return;
        }
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            echo json_encode(['error' => 'Non connecté']);
            return;
        }
        $temps = (int)($_POST['temps'] ?? 0);
        $coups = (int)($_POST['coups'] ?? 0);
        $difficulte = trim($_POST['difficulte'] ?? 'Facile');
        $paires = (int)($_POST['paires'] ?? 6);
        if ($temps <= 0) {
            echo json_encode(['error' => 'Temps invalide']);
            return;
        }
        try {
            $id = JeuMemory::save($this->db, $userId, $temps, $coups, $difficulte, $paires);
            echo json_encode(['success' => true, 'id' => $id]);
        } catch (Exception $e) {
            error_log('JeuController::saveMemory error: ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
        }
    }

    public function getBest(): void
    {
        header('Content-Type: application/json');
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            echo json_encode(['error' => 'Non connecté']);
            return;
        }
        try {
            $bests = JeuSnake::getBestScore($this->db, $userId);
            $stats = JeuSnake::getUserStats($this->db, $userId);
            echo json_encode(['success' => true, 'bests' => $bests, 'stats' => $stats]);
        } catch (Exception $e) {
            error_log('JeuController::getBest error: ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
        }
    }

    public function getLeaderboard(): void
    {
        header('Content-Type: application/json');
        $vitesse = $_GET['vitesse'] ?? null;
        try {
            $board = JeuSnake::getLeaderboard($this->db, $vitesse);
            echo json_encode(['success' => true, 'leaderboard' => $board]);
        } catch (Exception $e) {
            error_log('JeuController::getLeaderboard error: ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
        }
    }
}

$controller = new JeuController();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'save_score':     $controller->saveScore();     break;
    case 'save_memory':    $controller->saveMemory();    break;
    case 'get_best':       $controller->getBest();       break;
    case 'leaderboard':    $controller->getLeaderboard(); break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Action inconnue']);
        break;
}
