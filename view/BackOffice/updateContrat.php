<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
SessionGuard::requireRoles(['superadmin', 'admin']);

require_once __DIR__ . '/../../controller/ContratController.php';
require_once __DIR__ . '/../../model/Contrat.php';

$id = (int)($_GET['id'] ?? 0);
$contratC = new ContratController();
$contratData = $contratC->getById($id);
$formules = $contratC->getAllFormules();

if (!$contratData) die('Contrat introuvable.');

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function labelDetail($key) {
    $labels = [
        'telephone' => 'N° de téléphone',
        'date_naissance' => 'Date de naissance',
        'nationalite' => 'Nationalité',
        'situation_professionnelle' => 'Situation professionnelle',
        'adresse_personnelle' => 'Adresse personnelle principale',
        'situation_matrimoniale' => 'Situation matrimoniale',
        'revenu_annuel' => 'Niveau de revenu annuel brut en Dinars',
        'immatriculation' => 'Immatriculation du véhicule',
        'marque' => 'Marque du véhicule',
        'usage_vehicule' => 'Usage du véhicule',
        'kilometrage' => 'Kilométrage du véhicule',
        'puissance' => 'Puissance',
        'date_circulation' => 'Date de circulation',
        'valeur_venale' => 'Valeur vénale',
        'financement' => 'Financement',
        'identite' => 'Identité',
        'email' => 'Email',
        'type_logement' => 'Type de logement',
        'statut_occupation' => 'Statut d’occupation',
        'adresse_logement' => 'Adresse du logement',
        'surface' => 'Surface (m²)',
        'nombre_pieces' => 'Nombre de pièces',
        'valeur_bien' => 'Valeur du bien',
        'type_construction' => 'Type de construction',
        'systeme_securite' => 'Système de sécurité',
        'age' => 'Âge',
        'profession' => 'Profession',
        'antecedents' => 'Antécédents médicaux',
        'nombre_personnes' => 'Nombre de personnes à assurer',
        'objet_protege' => 'Objet protégé',
        'valeur_objet' => 'Valeur objet',
        'description' => 'Description'
    ];
    return $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
}

function detailValue($value) {
    if (is_array($value)) {
        $flat = [];
        array_walk_recursive($value, function($a) use (&$flat) { 
            if (is_scalar($a) || $a === null) $flat[] = (string)$a; 
        });
        return implode(', ', $flat);
    }
    return is_scalar($value) || $value === null ? (string)$value : '';
}

function isGarantieKey($key): bool {
    return in_array((string)$key, [
        'garanties',
        'garanties_optionnelles',
        'garanties_choisies',
        'garanties_selectionnees'
    ], true);
}

function normalizeGaranties($value): array {
    if (is_array($value)) {
        $flat = [];
        array_walk_recursive($value, function($a) use (&$flat) { 
            if (is_scalar($a) || $a === null) $flat[] = trim((string)$a); 
        });
        return array_values(array_filter($flat, function($v) { return $v !== ''; }));
    }

    $text = is_scalar($value) || $value === null ? trim((string)$value) : '';
    if ($text === '') return [];

    $parts = preg_split('/\s*[,;|]\s*/', $text);
    return array_values(array_filter(array_map('trim', $parts), function($v) { return $v !== ''; }));
}

function detailSelectOptions($key): ?array {
    $key = (string)$key;
    $options = [
        'identite' => ['Monsieur', 'Madame'],
        'civilite' => ['Monsieur', 'Madame'],
        'nationalite' => ['Tunisienne', 'Tunisien', 'Française', 'Français', 'Algérienne', 'Algérien', 'Marocaine', 'Marocain', 'Autre'],
        'situation_professionnelle' => ['Salarié', 'Étudiant', 'Indépendant', 'Retraité', 'Sans emploi'],
        'profession' => ['Salarié', 'Étudiant', 'Indépendant', 'Retraité', 'Sans emploi'],
        'situation_matrimoniale' => ['Célibataire', 'Marié(e)', 'Divorcé(e)', 'Veuf/Veuve'],
        'revenu_annuel' => ['Moins de 10 000 DT', '10 000 – 20 000 DT', '20 000 – 40 000 DT', 'Plus de 40 000 DT'],
        'estimation_km' => ['Moins de 10 000 km', '10 000 – 20 000 km', '20 000 – 30 000 km', 'Plus de 30 000 km'],
        'kilometrage' => ['Moins de 10 000 km', '10 000 – 20 000 km', '20 000 – 30 000 km', 'Plus de 30 000 km'],
        'conducteurs' => ['Moi uniquement', 'Conjoint', 'Conducteur + conjoint', 'Plusieurs conducteurs'],
        'stationnement' => ['Garage privé', 'Parking collectif', 'Dans la rue', 'Parking sécurisé'],
        'utilisation' => ['Usage personnel', 'Usage professionnel', 'Usage occasionnel', 'Mixte'],
        'trajets_prevus' => ['Ville uniquement', 'Route', 'Ville + route', 'Longs trajets'],
        'financement' => ['Comptant', 'Crédit', 'Leasing'],
        'type_logement' => ['Appartement', 'Maison', 'Villa', 'Studio'],
        'statut_occupation' => ['Propriétaire', 'Locataire', 'Hébergé(e)'],
        'type_construction' => ['Béton', 'Traditionnelle', 'Mixte'],
        'systeme_securite' => ['Oui', 'Non'],
        'antecedents' => ['Oui', 'Non'],
        'couvrir_famille' => ['oui', 'non'],
        'type_protection' => ['Protection juridique', 'Protection financière', 'Assistance', 'Protection complète'],
        'niveau_couverture' => ['Basique', 'Avancé', 'Premium'],
        'duree_contrat' => ['1 an', '2 ans', '3 ans'],
    ];

    return $options[$key] ?? null;
}

$details = [];
if (!empty($contratData['details_contrat'])) {
    $decoded = json_decode($contratData['details_contrat'], true);
    if (is_array($decoded)) $details = $decoded;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client = (int)($_POST['id_client'] ?? 0);
    $idFormule = (int)($_POST['id_formule'] ?? 0);
    $dateDebut = trim($_POST['date_debut_contrat'] ?? '');
    $dateFin = trim($_POST['date_fin_contrat'] ?? '');
    $statut = trim($_POST['statut_contrat'] ?? 'en attente');
    $postedDetails = $_POST['details'] ?? [];

    $cleanDetails = [];
    if (is_array($postedDetails)) {
        foreach ($postedDetails as $key => $value) {
            $safeKey = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$key);
            if ($safeKey === '') continue;
            if (is_array($value)) {
                $flat = [];
                array_walk_recursive($value, function($a) use (&$flat) { 
                    if (is_scalar($a) || $a === null) $flat[] = trim((string)$a); 
                });
                $cleanDetails[$safeKey] = array_values($flat);
            } else {
                $cleanDetails[$safeKey] = is_scalar($value) || $value === null ? trim((string)$value) : '';
            }
        }
    }

    $formule = $contratC->getFormuleById($idFormule);

    if ($client <= 0 || !$formule) {
        $error = 'Veuillez choisir un client et une formule valide.';
    } elseif ($dateDebut === '') {
        $error = 'La date début est obligatoire.';
    } elseif ($dateFin === '') {
        $error = 'La date fin est obligatoire.';
    } elseif ($dateFin <= $dateDebut) {
        $error = 'La date de fin doit être supérieure à la date de début.';
    } else {
        $detailsJson = json_encode($cleanDetails, JSON_UNESCAPED_UNICODE);

        $contrat = new Contrat(
            $contratData['numero_contrat'],
            $formule['nom_categorie'] ?? $contratData['type_contrat'],
            $client,
            (int)$formule['id_categorie'],
            (float)$formule['prix_formule'],
            (float)$formule['franchise_formule'],
            $dateDebut,
            $dateFin,
            $statut,
            (int)$formule['id_formule'],
            $formule['nom_formule'],
            $detailsJson
        );

        if ($contratC->updateContrat($id, $contrat)) {
            header('Location: showContrat.php?id=' . $id . '&success=1');
            exit();
        }
        $error = 'Erreur lors de la modification du contrat.';
    }

    $details = $cleanDetails;
    $contratData['id_client'] = $client;
    $contratData['id_formule'] = $idFormule;
    $contratData['date_debut_contrat'] = $dateDebut;
    $contratData['date_fin_contrat'] = $dateFin;
    $contratData['statut_contrat'] = $statut;
}


$clientName = trim(($contratData['prenom'] ?? '') . ' ' . ($contratData['nom'] ?? ''));
$clientFallbackId = $contratData['id_user'] ?? $contratData['id_client'] ?? '—';
$clientDisplay = $clientName !== '' ? $clientName : ('ID ' . $clientFallbackId);
$status = strtolower((string)($contratData['statut_contrat'] ?? ''));
$statusClass = match ($status) {
    'actif' => 'status-active',
    'refusé', 'refuse' => 'status-refused',
    'résilié', 'resilie' => 'status-ended',
    default => 'status-pending',
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier contrat — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/admin-users.css">
    <link rel="stylesheet" href="assets/css/validation.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <style>
        body {
            min-height: 100vh;
            overflow-x: hidden;
        }

        .show-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 42px 18px;
            position: relative;
        }

        .show-backdrop {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 15% 10%, rgba(0, 198, 255, 0.18), transparent 30%),
                radial-gradient(circle at 90% 80%, rgba(255, 107, 26, 0.22), transparent 34%),
                rgba(2, 8, 23, 0.72);
            backdrop-filter: blur(13px);
            z-index: 1;
        }

        .show-modal {
            position: relative;
            z-index: 2;
            width: min(920px, 96vw);
            max-height: 92vh;
            overflow: auto;
            background: linear-gradient(180deg, rgba(8, 22, 52, 0.98), rgba(5, 17, 42, 0.98));
            border: 1px solid rgba(80, 132, 255, 0.24);
            border-radius: 24px;
            box-shadow: 0 32px 90px rgba(0, 0, 0, 0.42);
            padding: 0;
            color: #fff;
        }

        .show-modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            padding: 28px 32px 22px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .show-title-wrap {
            display: flex;
            gap: 14px;
            align-items: center;
        }

        .show-icon {
            width: 48px;
            height: 48px;
            border-radius: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ff6b1a, #ff3d3d);
            box-shadow: 0 16px 34px rgba(255, 107, 26, 0.25);
            font-size: 24px;
            flex: 0 0 auto;
        }

        .show-title {
            margin: 0;
            font-size: 26px;
            line-height: 1.15;
            font-weight: 800;
            color: #fff;
        }

        .show-subtitle {
            margin-top: 6px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.65);
            font-weight: 700;
        }

        .show-close {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.06);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 18px;
            transition: .2s ease;
            flex: 0 0 auto;
        }

        .show-close:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-1px);
        }

        .show-modal-body {
            padding: 26px 32px 8px;
        }

        .show-section {
            margin-bottom: 24px;
        }

        .show-section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 17px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 16px;
        }

        .show-section-title i {
            color: #00c6ff;
            font-size: 20px;
        }

        .show-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .show-field {
            min-height: 72px;
            background: rgba(255, 255, 255, 0.055);
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 16px;
            padding: 14px 16px;
        }

        .show-label {
            display: block;
            margin-bottom: 8px;
            font-size: 11px;
            letter-spacing: .5px;
            color: rgba(255, 255, 255, 0.56);
            text-transform: uppercase;
            font-weight: 800;
        }

        .show-value {
            color: #fff;
            font-size: 15px;
            font-weight: 750;
            line-height: 1.35;
            word-break: break-word;
        }

        .show-empty {
            background: rgba(255, 255, 255, 0.045);
            border: 1px dashed rgba(255, 255, 255, 0.14);
            border-radius: 16px;
            padding: 18px;
            color: rgba(255, 255, 255, 0.62);
            font-size: 13px;
        }

        .show-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 9px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 850;
            border: 1px solid transparent;
        }

        .status-active { background: rgba(46, 213, 115, .14); color: #2ed573; border-color: rgba(46, 213, 115, .24); }
        .status-pending { background: rgba(255, 193, 7, .15); color: #ffd166; border-color: rgba(255, 193, 7, .24); }
        .status-refused { background: rgba(255, 71, 87, .14); color: #ff6b7a; border-color: rgba(255, 71, 87, .24); }
        .status-ended { background: rgba(148, 163, 184, .17); color: #cbd5e1; border-color: rgba(148, 163, 184, .24); }

        .show-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 22px 32px 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            flex-wrap: wrap;
        }

        .show-btn {
            height: 44px;
            padding: 0 18px;
            border-radius: 13px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            line-height: 1;
            text-decoration: none;
            font-weight: 800;
            font-size: 14px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            transition: .2s ease;
            cursor: pointer;
            white-space: nowrap;
        }

        .show-btn:hover {
            transform: translateY(-1px);
        }

        .btn-return {
            color: rgba(255, 255, 255, .78);
            background: rgba(255, 255, 255, 0.05);
        }

        .btn-edit, .btn-valid {
            color: #fff;
            background: linear-gradient(135deg, #00c6ff, #0891b2);
            border-color: transparent;
        }

        .btn-refuse {
            color: #ffd166;
            background: rgba(255, 193, 7, .08);
            border-color: rgba(255, 193, 7, .18);
        }

        .btn-end {
            color: #cbd5e1;
            background: rgba(148, 163, 184, .08);
            border-color: rgba(148, 163, 184, .16);
        }

        .btn-delete {
            color: #fff;
            background: linear-gradient(135deg, #ff4757, #b91c1c);
            border-color: transparent;
        }

        .garantie-field {
            grid-column: 1 / -1;
        }

        .garantie-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 4px;
        }

        .garantie-item {
            min-height: 48px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.055);
            border: 1px solid rgba(0, 198, 255, 0.18);
            color: #fff;
            font-weight: 800;
            line-height: 1.25;
        }

        .garantie-item i {
            width: 24px;
            height: 24px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            color: #2ed573;
            background: rgba(46, 213, 115, 0.12);
            border: 1px solid rgba(46, 213, 115, 0.25);
            font-size: 14px;
        }

        @media (max-width: 720px) {
            .show-modal-header,
            .show-modal-body,
            .show-footer {
                padding-left: 20px;
                padding-right: 20px;
            }

            .show-grid {
                grid-template-columns: 1fr;
            }

            .garantie-list {
                grid-template-columns: 1fr;
            }

            .show-footer .show-btn {
                flex: 1 1 100%;
            }
        }
    

        .show-field input,
        .show-field select,
        .show-field textarea {
            width: 100%;
            min-height: 44px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.10);
            background: rgba(255, 255, 255, 0.045);
            color: #fff;
            padding: 12px 14px;
            font-weight: 800;
            font-size: 14px;
            outline: none;
            box-sizing: border-box;
            margin-top: 8px;
        }

        .show-field textarea {
            min-height: 96px;
            resize: vertical;
            line-height: 1.45;
        }

        .show-field select option {
            color: #0f172a;
        }

        .show-field input:focus,
        .show-field select:focus,
        .show-field textarea:focus {
            border-color: rgba(0, 198, 255, 0.55);
            box-shadow: 0 0 0 3px rgba(0, 198, 255, 0.10);
        }

        .show-readonly-note {
            margin-top: 14px;
            color: rgba(203, 213, 225, 0.72);
            font-size: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .field-error,
        .error-message {
            display: block;
            min-height: 14px;
            color: #ff9b9b;
            font-size: 12px;
            font-weight: 700;
            margin-top: 7px;
        }

        .show-alert-error {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 16px;
            background: rgba(239, 68, 68, 0.13);
            border: 1px solid rgba(239, 68, 68, 0.34);
            color: #ffd4d4;
            font-weight: 800;
        }

        .garantie-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #22c55e;
            margin: 0;
            flex: 0 0 auto;
        }

        .garantie-item {
            cursor: pointer;
        }
</style>
</head>
<body>
<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="show-page">
    <div class="show-backdrop"></div>

    <div class="show-modal" role="dialog" aria-modal="true" aria-labelledby="updateContratTitle">
        <div class="show-modal-header">
            <div class="show-title-wrap">
                <div class="show-icon"><i class="bi bi-pencil-square"></i></div>
                <div>
                    <h1 class="show-title" id="updateContratTitle">Modifier le contrat</h1>
                    <div class="show-subtitle">N° <?= h($contratData['numero_contrat'] ?? '—') ?></div>
                </div>
            </div>

            <a class="show-close" href="showContrat.php?id=<?= (int)$id ?>" title="Fermer"><i class="bi bi-x"></i></a>
        </div>

        <form method="POST" novalidate onsubmit="return validateContratBO()">
            <input type="hidden" name="id_client" id="id_client" value="<?= (int)($contratData['id_user'] ?? $contratData['id_client'] ?? 0) ?>">
            <input type="hidden" name="id_formule" id="id_formule" value="<?= (int)($contratData['id_formule'] ?? 0) ?>">
            <input type="hidden" name="date_debut_contrat" id="date_debut" value="<?= h($contratData['date_debut_contrat'] ?? '') ?>">
            <!-- date_fin_contrat est modifiable dans la card "Date fin" plus bas -->

            <div class="show-modal-body">
                <?php if (isset($error)): ?>
                    <div class="show-alert-error"><i class="bi bi-exclamation-triangle"></i> <?= h($error) ?></div>
                <?php endif; ?>

                <div class="show-section">
                    <div class="show-section-title"><i class="bi bi-shield-check"></i> Informations contrat</div>

                    <div class="show-grid">
                        <div class="show-field">
                            <span class="show-label">N° contrat</span>
                            <div class="show-value"><?= h($contratData['numero_contrat'] ?? '—') ?></div>
                        </div>
                        <div class="show-field">
                            <span class="show-label">Statut</span>
                            <select name="statut_contrat" class="detail-input">
                                <option value="en attente" <?= ($status === 'en attente' || $status === 'en_attente') ? 'selected' : '' ?>>En attente</option>
                                <option value="actif" <?= ($status === 'actif') ? 'selected' : '' ?>>Actif</option>
                                <option value="expire" <?= ($status === 'expire' || $status === 'expiré') ? 'selected' : '' ?>>Expiré</option>
                                <option value="resilie" <?= ($status === 'resilie' || $status === 'résilié') ? 'selected' : '' ?>>Résilié</option>
                            </select>
                        </div>
                        <div class="show-field">
                            <span class="show-label">Catégorie</span>
                            <div class="show-value"><?= h($contratData['nom_categorie'] ?? $contratData['type_contrat'] ?? '—') ?></div>
                        </div>
                        <div class="show-field">
                            <span class="show-label">Formule</span>
                            <div class="show-value"><?= h($contratData['nom_formule'] ?? $contratData['formule_contrat'] ?? '—') ?></div>
                        </div>
                        <div class="show-field">
                            <span class="show-label">Prime</span>
                            <div class="show-value"><?= h($contratData['prime_contrat'] ?? '—') ?> DT</div>
                        </div>
                        <div class="show-field">
                            <span class="show-label">Franchise</span>
                            <div class="show-value"><?= h($contratData['franchise_contrat'] ?? '—') ?> DT</div>
                        </div>
                        <div class="show-field">
                            <span class="show-label">Date début</span>
                            <div class="show-value"><?= h($contratData['date_debut_contrat'] ?? '—') ?></div>
                        </div>
                        <div class="show-field">
                            <span class="show-label">Date fin</span>
                            <input
                                type="date"
                                name="date_fin_contrat"
                                id="date_fin"
                                class="detail-input"
                                value="<?= h($contratData['date_fin_contrat'] ?? '') ?>"
                            >
                            <small class="error-message detail-error"></small>
                        </div>
                        <div class="show-field">
                            <span class="show-label">Client</span>
                            <div class="show-value"><?= h($clientDisplay) ?></div>
                        </div>
                        <div class="show-field">
                            <span class="show-label">Email</span>
                            <div class="show-value"><?= h($contratData['email'] ?? '—') ?></div>
                        </div>
                    </div>

                    <div class="show-readonly-note">
                        <i class="bi bi-lock"></i>
                        Ces informations sont liées au contrat et ne sont pas modifiables ici. Modifiez seulement les informations saisies par le client.
                    </div>
                </div>

                <div class="show-section">
                    <div class="show-section-title"><i class="bi bi-list-check"></i> Informations remplies par le client</div>

                    <?php if (empty($details)): ?>
                        <div class="show-empty">Aucune information spécifique enregistrée.</div>
                    <?php else: ?>
                        <div class="show-grid">
                            <?php foreach ($details as $key => $value): ?>
                                <?php if (isGarantieKey($key)): ?>
                                    <?php $garanties = normalizeGaranties($value); ?>
                                    <div class="show-field garantie-field">
                                        <span class="show-label"><?= h(labelDetail($key)) ?></span>

                                        <?php if (empty($garanties)): ?>
                                            <div class="show-value">—</div>
                                        <?php else: ?>
                                            <div class="garantie-list">
                                                <?php foreach ($garanties as $garantie): ?>
                                                    <label class="garantie-item">
                                                        <input type="checkbox" name="details[<?= h($key) ?>][]" value="<?= h($garantie) ?>" checked>
                                                        <span><?= h($garantie) ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <small class="error-message detail-error"></small>
                                    </div>
                                <?php else: ?>
                                    <?php $selectOptions = detailSelectOptions($key); ?>
                                    <div class="show-field <?= is_array($value) || strlen(detailValue($value)) > 70 ? 'garantie-field' : '' ?>">
                                        <span class="show-label"><?= h(labelDetail($key)) ?></span>
                                        <?php if ($selectOptions): ?>
                                            <select class="detail-input" name="details[<?= h($key) ?>]">
                                                <option value="">— Veuillez choisir une option —</option>
                                                <?php foreach ($selectOptions as $option): ?>
                                                    <option value="<?= h($option) ?>" <?= trim(detailValue($value)) === $option ? 'selected' : '' ?>><?= h($option) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php elseif (str_contains((string)$key, 'date')): ?>
                                            <input type="date" class="detail-input" name="details[<?= h($key) ?>]" value="<?= h(detailValue($value)) ?>">
                                        <?php elseif (is_array($value) || strlen(detailValue($value)) > 70): ?>
                                            <textarea class="detail-input" name="details[<?= h($key) ?>]" rows="3"><?= h(detailValue($value)) ?></textarea>
                                        <?php else: ?>
                                            <input type="text" class="detail-input" name="details[<?= h($key) ?>]" value="<?= h(detailValue($value)) ?>">
                                        <?php endif; ?>
                                        <small class="error-message detail-error"></small>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="show-footer">
                <a href="showContrat.php?id=<?= (int)$id ?>" class="show-btn btn-return"><i class="bi bi-arrow-left"></i> Annuler</a>
                <button type="submit" class="show-btn btn-edit"><i class="bi bi-save"></i> Modifier</button>
            </div>
        </form>
    </div>
</div>
<script>
function boTodayISO(){ const d=new Date(); d.setHours(0,0,0,0); return d.toISOString().slice(0,10); }
function boYearsAgo(y){ const d=new Date(); d.setFullYear(d.getFullYear()-y); d.setHours(0,0,0,0); return d.toISOString().slice(0,10); }
const boRules = {
    email: /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/,
    letters: /^[A-Za-zÀ-ÖØ-öø-ÿĀ-ſ\u0600-\u06FF]+(?:[ '\-][A-Za-zÀ-ÖØ-öø-ÿĀ-ſ\u0600-\u06FF]+)*$/u,
    address: /^[A-Za-zÀ-ÖØ-öø-ÿĀ-ſ\u0600-\u06FF0-9\s,.'°º\-\/]+$/u,
    immatTN: /^\d{1,4}\s*TUN\s*\d{1,4}$/i,
    immatAr: /^نت\s*\d{1,6}$/u,
    immatForeign: /^(?=.*[A-Za-z\u0600-\u06FF])(?=.*\d)[A-Za-z0-9\u0600-\u06FF\-\s]{3,15}$/u
};
function boNum(v){ return Number(String(v).replace(',', '.').replace(/\s/g,'')); }
function boErr(id,msg){ const e=document.getElementById(id); if(e) e.textContent=msg||''; }
function boMark(el,msg){ if(!el)return !msg; el.style.borderColor=msg?'#ef4444':'rgba(255, 255, 255, 0.10)'; el.style.boxShadow=msg?'0 0 0 3px rgba(239,68,68,.12)':''; return !msg; }
function validateDetailBO(input){
    const error=input.parentElement.querySelector('.detail-error');
    const label=(input.parentElement.querySelector('.show-label')?.textContent||'Champ').trim();
    const key=(input.name+' '+label).toLowerCase();
    const value=input.value.trim();
    let msg='';
    if(value==='') msg=label+' obligatoire.';
    else if(key.includes('email') && !boRules.email.test(value)) msg='Email invalide.';
    else if((key.includes('telephone')||key.includes('téléphone')) && !/^\d{8}$/.test(value)) msg='Téléphone invalide : exactement 8 chiffres.';
    else if(key.includes('nombre beneficiaires') || key.includes('nombre bénéficiaires') || key.includes('nombre_beneficiaires') || key.includes('nombre personnes') || key.includes('nombre_personnes')){ const n=boNum(value); if(!(Number.isInteger(n)&&n>=1&&n<=20)) msg='Nombre bénéficiaires invalide : entre 1 et 20.'; }
    else if((key.includes('nom')||key.includes('prenom')||key.includes('nationalite')||key.includes('nationalité')) && !(boRules.letters.test(value)&&value.length>=2)) msg=label+' doit contenir seulement des lettres.';
    else if(key.includes('adresse') && !(boRules.address.test(value)&&value.length>=5)) msg='Adresse invalide : lettres, chiffres et ponctuation simple seulement.';
    else if(key.includes('immatriculation')){ const compact=value.replace(/\s+/g,''); if(!(boRules.immatTN.test(value)||boRules.immatAr.test(compact)||boRules.immatForeign.test(value))) msg='Immatriculation invalide. Exemples : 123TUN4567, نت225444, AB-123-CD.'; }
    else if(key.includes('puissance')){ const n=boNum(value); if(!(Number.isFinite(n)&&n>=1&&n<=100)) msg='Puissance invalide : entre 1 et 100 CV.'; }
    else if(key.includes('valeur venale')||key.includes('valeur_venale')){ const n=boNum(value); if(!(Number.isFinite(n)&&n>=1000&&n<=1000000)) msg='Valeur vénale invalide : entre 1 000 et 1 000 000 DT.'; }
    else if(key.includes('date circulation')||key.includes('date_circulation')){ if(!(value<=boTodayISO()&&value>='1980-01-01')) msg='Date circulation invalide : pas future et pas avant 1980.'; }
    else if(key.includes('date naissance')||key.includes('date_naissance')){ if(!(value<=boYearsAgo(18)&&value>=boYearsAgo(100))) msg='Âge invalide : entre 18 et 100 ans.'; }
    if(error) error.textContent=msg;
    return boMark(input,msg);
}
function validateContratBO(){
    let ok=true, first=null;
    document.querySelectorAll('.detail-input').forEach(function(input){ if(!validateDetailBO(input)){ ok=false; first=first||input; } });
    if(!ok && first) first.focus();
    return ok;
}
document.addEventListener('DOMContentLoaded',function(){
    const form=document.querySelector('form[method="POST"]');
    if(form){ form.setAttribute('novalidate','novalidate'); form.querySelectorAll('[required],[min],[max],[pattern]').forEach(el=>{el.removeAttribute('required');el.removeAttribute('min');el.removeAttribute('max');el.removeAttribute('pattern');}); }
    document.querySelectorAll('.detail-input').forEach(function(el){ el.addEventListener('input',()=>validateDetailBO(el)); el.addEventListener('change',()=>validateDetailBO(el)); });
});
</script>
</body>
</html>
