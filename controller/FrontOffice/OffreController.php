<?php
/* =============================================
   OffreController.php — FrontOffice
   Protex Assurance
   ============================================= */

require_once __DIR__ . '/../../model/OffreModel.php';

class OffreController {

    private OffreModel $model;

    public function __construct() {
        $this->model = new OffreModel();
    }

    /* =============================================
       index() — Afficher les offres au client
       ============================================= */
    public function index(): void {

        /* Récupérer le filtre type */
        $filtre = $_GET['type'] ?? 'tous';

        $types_valides = ['auto', 'sante', 'habitation', 'vie'];

        /* Récupérer les offres selon filtre */
        if ($filtre === 'tous') {
            $offres = $this->model->getActives();
        } elseif (in_array($filtre, $types_valides)) {
            $offres = $this->model->getByType($filtre);
        } else {
            $offres = $this->model->getActives();
            $filtre = 'tous';
        }

        /* Passer les données à la vue */
        require_once __DIR__ . '/../../view/FrontOffice/offres.php';
    }
}

/* =============================================
   Point d'entrée — appel du controller
   ============================================= */
$controller = new OffreController();
$controller->index();