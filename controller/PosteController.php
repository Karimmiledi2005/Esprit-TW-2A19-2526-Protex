<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/PosteModel.php';

$pdo = config::getConnexion();
$model = new PosteModel($pdo);
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $data = [
                'contenu' => $_POST['contenu'] ?? '',
                'date_publication' => $_POST['date_publication'] ?? '',
                'note' => $_POST['note'] ?? 0,
                'auteur' => $_POST['auteur'] ?? '',
                'nb_likes' => $_POST['nb_likes'] ?? 0,
                'nb_commentaires' => $_POST['nb_commentaires'] ?? 0,
                'id_agence' => $_POST['id_agence'] ?? ''
            ];

            $model->createPoste($data);
            header('Location: ../view/BackOffice/admin-postes.php?success=create');
            exit;

        case 'update':
            if (empty($_POST['id_poste'])) {
                error_log('PosteController: id_poste manquant pour update');
                http_response_code(400);
                echo 'Paramètre manquant';
                exit;
            }

            $data = [
                'id_poste' => $_POST['id_poste'],
                'contenu' => $_POST['contenu'] ?? '',
                'date_publication' => $_POST['date_publication'] ?? '',
                'note' => $_POST['note'] ?? 0,
                'auteur' => $_POST['auteur'] ?? '',
                'nb_likes' => $_POST['nb_likes'] ?? 0,
                'nb_commentaires' => $_POST['nb_commentaires'] ?? 0,
                'id_agence' => $_POST['id_agence'] ?? ''
            ];

            $model->updatePoste($data);
            header('Location: ../view/BackOffice/admin-postes.php?success=update');
            exit;

        case 'delete':
            if (empty($_POST['id_poste'])) {
                error_log('PosteController: id_poste manquant pour delete');
                http_response_code(400);
                echo 'Paramètre manquant';
                exit;
            }

            $model->deletePoste((int)$_POST['id_poste']);
            header('Location: ../view/BackOffice/admin-postes.php?success=delete');
            exit;

        default:
            error_log('PosteController: action invalide => ' . ($action ?? 'NULL'));
            http_response_code(400);
            echo 'Action invalide';
            exit;
    }
} catch (Throwable $e) {
    error_log('PosteController error: ' . $e->getMessage());
    http_response_code(500);
    echo 'Erreur interne serveur';
}
?>