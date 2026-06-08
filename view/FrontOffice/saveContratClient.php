<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();

require_once __DIR__ . '/../../controller/ContratController.php';
require_once __DIR__ . '/../../model/Contrat.php';

// Protex utilise la table `user` et la session créée au login : $_SESSION['user_id'].
// Aucun ID fixe, aucun GET/POST : le contrat appartient toujours au compte connecté.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

function normalizeType(string $type): string
{
    $type = strtolower(trim($type));
    $type = str_replace(['é', 'è', 'ê', 'à'], ['e', 'e', 'e', 'a'], $type);
    return $type;
}

function backToType(string $type): string
{
    return match (normalizeType($type)) {
        'auto' => 'contrat_auto.php',
        'habitation' => 'contrat_habitation.php',
        'sante' => 'contrat_sante.php',
        'protection' => 'contrat_protection.php',
        default => 'contrat.php',
    };
}

function redirectWith(string $url, string $msg): void
{
    header('Location: ' . $url . (str_contains($url, '?') ? '&' : '?') . $msg);
    exit();
}

function postValue(array $post, array $keys): string
{
    foreach ($keys as $key) {
        if (isset($post[$key]) && !is_array($post[$key])) {
            return trim((string)$post[$key]);
        }
    }
    return '';
}

function isValidDateFormat(string $date): bool
{
    if ($date === '') {
        return false;
    }

    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function collectDetails(array $post): string
{
    $exclude = [
        'type_contrat', 'id_categorie', 'id_formule', 'formule', 'formule_habitation',
        'date_debut_contrat', 'date_fin_contrat', 'date_debut', 'date_fin',
        'prime_contrat', 'franchise_contrat', 'statut_contrat'
    ];

    $details = [];

    foreach ($post as $key => $value) {
        if (in_array($key, $exclude, true)) {
            continue;
        }

        if (is_array($value)) {
            $clean = array_values(array_filter(array_map('trim', $value), fn($v) => $v !== ''));
            if (!empty($clean)) {
                $details[$key] = $clean;
            }
        } else {
            $clean = trim((string)$value);
            if ($clean !== '') {
                $details[$key] = $clean;
            }
        }
    }

    return json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contrat.php');
    exit();
}

$controller = new ContratController();

$idUser = (int)$_SESSION['user_id'];
$type = trim($_POST['type_contrat'] ?? '');
$idCategorie = (int)($_POST['id_categorie'] ?? 0);
$idFormule = (int)($_POST['id_formule'] ?? 0);
$formuleName = trim($_POST['formule'] ?? '');
$return = backToType($type);

$errors = [];

// Validation générale du contrat
if ($type === '') {
    $errors[] = 'Le type du contrat est obligatoire.';
}

if ($idCategorie <= 0) {
    $errors[] = 'La catégorie est obligatoire.';
}

if ($idFormule <= 0 && $formuleName === '') {
    $errors[] = 'La formule est obligatoire.';
}

// Validation des informations client
$nom = postValue($_POST, ['nom', 'nom_client', 'client_nom']);
$prenom = postValue($_POST, ['prenom', 'prenom_client', 'client_prenom']);
$email = postValue($_POST, ['email', 'email_client', 'client_email']);
$telephone = preg_replace('/\D+/', '', postValue($_POST, ['telephone', 'tel', 'phone', 'telephone_client', 'client_telephone']));
$adresse = postValue($_POST, ['adresse', 'adresse_client', 'client_adresse']);
$dateNaissance = postValue($_POST, ['date_naissance', 'date_naissance_client', 'naissance']);
$nationalite = postValue($_POST, ['nationalite', 'nationalite_client']);
$nationaliteAutre = postValue($_POST, ['nationalite_autre', 'autre_nationalite']);

if ($nom === '' || !preg_match('/^[a-zA-ZÀ-ÿ\s\'-]{2,50}$/u', $nom)) {
    $errors[] = 'Le nom est invalide.';
}

if ($prenom === '' || !preg_match('/^[a-zA-ZÀ-ÿ\s\'-]{2,50}$/u', $prenom)) {
    $errors[] = 'Le prénom est invalide.';
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "L'adresse email est invalide.";
}

if ($telephone === '' || !preg_match('/^[0-9]{8}$/', $telephone)) {
    $errors[] = 'Le téléphone doit contenir exactement 8 chiffres.';
}

if ($adresse === '' || strlen($adresse) < 5) {
    $errors[] = "L'adresse est invalide.";
}

if (!isValidDateFormat($dateNaissance)) {
    $errors[] = 'La date de naissance est obligatoire ou invalide.';
} else {
    $birthDate = new DateTime($dateNaissance);
    $today = new DateTime(date('Y-m-d'));

    if ($birthDate > $today) {
        $errors[] = 'La date de naissance ne peut pas être dans le futur.';
    }
}

if ($nationalite === '') {
    $errors[] = 'La nationalité est obligatoire.';
}

if (normalizeType($nationalite) === 'autre' && $nationaliteAutre === '') {
    $errors[] = 'Veuillez préciser la nationalité.';
}

// Validation spécifique assurance Auto
if (normalizeType($type) === 'auto') {
    $immatriculation = postValue($_POST, [
        'immatriculation',
        'matricule',
        'numero_immatriculation',
        'immatriculation_vehicule'
    ]);

    $datePremierUsage = postValue($_POST, [
        'date_premier_usage',
        'date_1er_usage',
        'date_premiere_utilisation',
        'date_premiere_mise_circulation',
        'date_mise_circulation',
        'date_circulation'
    ]);

    if ($immatriculation === '') {
        $errors[] = "L'immatriculation est obligatoire pour l'assurance auto.";
    } elseif (!preg_match('/^[a-zA-Z0-9\s\-\/]{3,30}$/u', $immatriculation)) {
        $errors[] = "L'immatriculation est invalide.";
    }

    if (!isValidDateFormat($datePremierUsage)) {
        $errors[] = 'La date de premier usage du véhicule est obligatoire ou invalide.';
    } else {
        $usageDate = new DateTime($datePremierUsage);
        $today = new DateTime(date('Y-m-d'));

        if ($usageDate > $today) {
            $errors[] = 'La date de premier usage ne peut pas être dans le futur.';
        }
    }
}

// Si les validations de base échouent, on retourne avant de chercher la formule
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    redirectWith($return, 'error=validation');
}

$formule = null;

if ($idFormule > 0) {
    $formule = $controller->getFormuleById($idFormule);
} else {
    $formule = $controller->getFormuleByNameAndCategorie($formuleName, $idCategorie);
}

if (!$formule || (int)$formule['id_categorie'] !== $idCategorie) {
    $_SESSION['errors'] = ['La formule sélectionnée est introuvable ou ne correspond pas à la catégorie.'];
    redirectWith($return, 'error=formule');
}

// Le client ne remplit pas ces champs : ils sont générés automatiquement.
$dateDebut = date('Y-m-d');
$dateFin = date('Y-m-d', strtotime('+1 year'));
$numero = $controller->generateNumero();
$prime = (float)($formule['prix_formule'] ?? 0);
$franchise = (float)($formule['franchise_formule'] ?? 0);
$formuleContrat = (string)($formule['nom_formule'] ?? $formuleName);
$detailsContrat = collectDetails($_POST);

$contrat = new Contrat(
    $numero,
    $type,
    $idUser,
    $idCategorie,
    $prime,
    $franchise,
    $dateDebut,
    $dateFin,
    'en attente',
    (int)$formule['id_formule'],
    $formuleContrat,
    $detailsContrat
);

try {
    $ok = $controller->addContrat($contrat);

    if (!$ok) {
        $_SESSION['errors'] = ['Le contrat n’a pas été enregistré dans la base. Vérifiez la table contrat et la colonne id_user.'];
        redirectWith($return, 'error=insert');
    }

    header('Location: contrat.php?success=demande');
    exit();
} catch (Throwable $e) {
    error_log('saveContratClient.php INSERT: ' . $e->getMessage());
    $_SESSION['errors'] = ['Erreur lors de l’enregistrement. Veuillez réessayer plus tard.'];
    redirectWith($return, 'error=sql');
}


