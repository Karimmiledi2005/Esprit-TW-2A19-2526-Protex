<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
	$reclamationC = new ReclamationController();
	$row = $reclamationC->showReclamation($id);
	$userId = (int)($_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 0);
	if ($row && (int)$row['id_user'] !== $userId) {
		header('Location: reclamationList.php?error=acces_refuse');
		exit();
	}
	$reclamationC->deleteReclamation($id);
}

header('Location: reclamationList.php');
exit();
?>

