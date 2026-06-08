<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

require_once __DIR__ . '/../../controller/ContratController.php';
require_once __DIR__ . '/../../helpers/TunnelHelper.php';

$qrTunnelMethod = TunnelHelper::getResolutionMethod();
$qrTunnelBase   = TunnelHelper::getPublicBaseUrl();
$showQrTunnelAlert = ($qrTunnelMethod === 'localhost_fallback');

$idUser = (int) $_SESSION['user_id'];
$idClient = $idUser;

$controller = new ContratController();
$contrats = $controller->getByClient($idUser);

if ($contrats instanceof PDOStatement) {
    $contrats = $contrats->fetchAll(PDO::FETCH_ASSOC);
}

if (!is_array($contrats)) {
    $contrats = [];
}

$nbContratsClient = count($contrats);

// Cloner et trier les contrats pour la timeline par date d'effet croissante
$contratsTimeline = $contrats;
usort($contratsTimeline, static function ($a, $b): int {
    if (!$a instanceof Contrat || !$b instanceof Contrat) return 0;
    return strtotime((string)$a->getDateDebutContrat()) <=> strtotime((string)$b->getDateDebutContrat());
});
require_once __DIR__ . '/../../controller/CategorieController.php';

$categorieC = new CategorieController();
$categories = $categorieC->listCategories();
if ($categories instanceof PDOStatement) {
    $categories = $categories->fetchAll(PDO::FETCH_ASSOC);
}
if (!is_array($categories)) {
    $categories = [];
}

require_once __DIR__ . '/../../controller/RecommandationController.php';

$recommandationResult = null;
$recommandationFormData = [];
$recommandationErrors = [];

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'recommandation_contrat'
) {
    $recommandationFormData = $_POST;

    try {
        $recommandationController = new RecommandationController();
        $recommandationResult = $recommandationController->recommander($_POST);

        if (!$recommandationResult) {
            $recommandationErrors[] = 'Aucune formule compatible avec vos besoins n’a été trouvée.';
        }
    } catch (Exception $e) {
        $recommandationErrors[] = 'Erreur lors de la recommandation : ' . $e->getMessage();
    }
}



// ===== Données pour recommandation intelligente =====
$formulesReco = [];
try {
    if (class_exists('config')) {
        $dbReco = config::getConnexion();

        // Requête compatible avec le schéma habituel : formule + categorie
        $sqlReco = "
            SELECT
                f.id_formule,
                f.nom_formule,
                f.prix_formule,
                f.franchise_formule,
                f.id_categorie,
                c.nom_categorie
            FROM formule f
            LEFT JOIN categorie c ON c.id_categorie = f.id_categorie
            ORDER BY c.nom_categorie ASC, f.prix_formule ASC
        ";

        $stmtReco = $dbReco->query($sqlReco);
        if ($stmtReco) {
            $formulesReco = $stmtReco->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Throwable $e) {
    $formulesReco = [];
}

// ===== Helpers =====
function statusClass(?string $statut): string
{
    $s = strtolower(trim((string)$statut));

    return match ($s) {
        'actif', 'active' => 'active',
        'en attente', 'pending' => 'waiting',
        'expiré', 'expire', 'résilié', 'resilie', 'inactive' => 'expired',
        'refusé', 'refuse' => 'refused',
        default => 'waiting',
    };
}

function typeIcon(?string $type): array
{
    $t = strtolower(trim((string)$type));

    return match ($t) {
        'auto' => ['icon' => 'bi-car-front-fill', 'class' => 'auto'],
        'habitation' => ['icon' => 'bi-house-door-fill', 'class' => 'habitation'],
        'sante', 'santé' => ['icon' => 'bi-heart-pulse-fill', 'class' => 'sante'],
        'protection' => ['icon' => 'bi-shield-check', 'class' => 'protection'],
        default => ['icon' => 'bi-file-earmark-text', 'class' => 'default'],
    };
}

function formatDateFr(?string $date): string
{
    if (!$date) return '-';

    $timestamp = strtotime($date);
    if ($timestamp === false) return htmlspecialchars($date);

    return date('d/m/Y', $timestamp);
}


function normalizeCategoryName(?string $name): string
{
    $name = strtolower(trim((string)$name));
    return str_replace(['é', 'è', 'ê', 'à', 'ù', 'ô', 'ï', 'î', 'â'], ['e', 'e', 'e', 'a', 'u', 'o', 'i', 'i', 'a'], $name);
}

function categoryConfig(?string $name): array
{
    $normalized = normalizeCategoryName($name);

    return match ($normalized) {
        'auto' => [
            'href' => 'contrat_auto.php',
            'icon' => 'bi-car-front-fill',
            'class' => 'auto',
            'default_description' => 'Assurance automobile et mobilité.',
        ],
        'habitation' => [
            'href' => 'contrat_habitation.php',
            'icon' => 'bi-house-door-fill',
            'class' => 'habitation',
            'default_description' => 'Protection du logement et du patrimoine.',
        ],
        'sante' => [
            'href' => 'contrat_sante.php',
            'icon' => 'bi-heart-pulse-fill',
            'class' => 'sante',
            'default_description' => 'Couverture santé et assistance médicale.',
        ],
        'protection' => [
            'href' => 'contrat_protection.php',
            'icon' => 'bi-shield-check',
            'class' => 'protection',
            'default_description' => 'Prévoyance, sécurité et assistance.',
        ],
        default => [
            'href' => '#',
            'icon' => 'bi-grid-1x2',
            'class' => 'default',
            'default_description' => 'Découvrez cette catégorie d’assurance.',
        ],
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Contrats — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Style contrats spécifique (base) -->
    <link rel="stylesheet" href="user/assets_contrats/css/variables.css">
    <link rel="stylesheet" href="user/assets_contrats/css/base.css">
    <link rel="stylesheet" href="user/assets_contrats/css/layout.css">
    <link rel="stylesheet" href="user/assets_contrats/css/client.css">
    <link rel="stylesheet" href="user/assets_contrats/css/contrat.css">

    <!-- Style dashboard User : override navbar/avatar comme client.html -->
    <link rel="stylesheet" href="user/css/variables.css">
    <link rel="stylesheet" href="user/css/base.css">
    <link rel="stylesheet" href="user/css/layout.css">
    <link rel="stylesheet" href="user/css/client.css">
    <link rel="stylesheet" href="user/css/animations.css">

<script src="user/assets_contrats/js/main.js"></script>
    <style>
        .toast-notif {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: var(--navy-mid);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--text-primary);
            z-index: 9999;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }

        .toast-notif.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast-success i { color: var(--success); font-size: 18px; }
        .toast-warning i { color: var(--gold); font-size: 18px; }
        .toast-danger i  { color: var(--danger); font-size: 18px; }

        .empty-contracts {
            padding: 26px;
            border: 1px dashed var(--border);
            border-radius: 18px;
            background: rgba(255,255,255,0.04);
            text-align: center;
            color: var(--text-secondary);
        }

        .contracts-tools {
            display: flex;
            gap: 14px;
            align-items: center;
            margin: 18px 0 24px;
            flex-wrap: wrap;
        }

        .contracts-search {
            flex: 1;
            min-width: 280px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 18px;
            border: 1px solid rgba(20, 39, 56, 0.12);
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 12px 30px rgba(10, 25, 49, 0.06);
        }

        .contracts-search i {
            color: #EE5828;
            font-size: 18px;
        }

        .contracts-search input {
            border: none;
            outline: none;
            width: 100%;
            font-weight: 700;
            color: #142738;
            background: transparent;
        }

        .contracts-search input::placeholder {
            color: rgba(20, 39, 56, 0.55);
        }

        .contracts-tools select,
        .contracts-tools button {
            padding: 14px 18px;
            border-radius: 16px;
            border: 1px solid rgba(20, 39, 56, 0.12);
            background: #ffffff;
            font-weight: 800;
            color: #142738;
            cursor: pointer;
            box-shadow: 0 12px 30px rgba(10, 25, 49, 0.06);
        }

        .contracts-tools button {
            color: #EE5828;
        }

        .contracts-feedback {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 14px 0 18px;
            padding: 14px 18px;
            border-radius: 18px;
            font-weight: 800;
            border: 1px solid rgba(20, 39, 56, 0.10);
        }

        .contracts-feedback.success {
            background: #ecfdf3;
            color: #15803d;
        }

        .contracts-feedback.error {
            background: #fff1f2;
            color: #be123c;
        }

        .contracts-empty-filter {
            display: none;
            padding: 24px;
            margin-top: 14px;
            border: 1px dashed rgba(238, 88, 40, 0.35);
            border-radius: 20px;
            background: rgba(238, 88, 40, 0.06);
            text-align: center;
            color: #142738;
            font-weight: 700;
        }



        /* ===== GÉNÉRATION INTELLIGENTE SELON LES BESOINS ===== */
        .ai-reco-card{
            width:100%;
            margin:28px 0 34px;
            padding:32px 36px;
            border-radius:26px;
            background:linear-gradient(135deg,#071a33,#244c84);
            box-shadow:0 18px 45px rgba(10,25,49,0.20);
            color:#fff;
        }
        .ai-reco-card-content{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:30px;
            flex-wrap:wrap;
        }
        .ai-reco-card h2{
            color:#fff !important;
            font-size:34px;
            margin:0 0 12px;
            font-weight:800;
            text-shadow:0 2px 12px rgba(0,0,0,0.35);
        }
        .ai-reco-card p{
            color:#fff !important;
            opacity:1 !important;
            font-size:16px;
            line-height:1.75;
            max-width:760px;
            margin:0;
        }
        .ai-reco-mini{
            display:flex;
            align-items:center;
            gap:10px;
            flex-wrap:wrap;
            margin-top:18px;
        }
        .ai-reco-mini span{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:8px 12px;
            border-radius:999px;
            background:rgba(255,255,255,0.10);
            border:1px solid rgba(255,255,255,0.14);
            color:#fff;
            font-size:12px;
            font-weight:800;
        }
        .ai-open-btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:280px;
            min-height:64px;
            padding:16px 30px;
            border:none;
            background:#FF6B1A;
            color:#fff;
            border-radius:18px;
            font-weight:800;
            font-size:15px;
            cursor:pointer;
            box-shadow:0 14px 28px rgba(255,107,26,0.30);
            transition:0.25s ease;
        }
        .ai-open-btn:hover{ transform:translateY(-2px); background:#ff7f35; }



        /* ===== AJOUT ATTRACTIF + MARGES CONTRAT ===== */
        .main{
            padding-left:38px !important;
            padding-right:38px !important;
            padding-bottom:60px !important;
        }
        .page-header{
            margin-left:0 !important;
            margin-right:0 !important;
        }
        .ai-reco-card{
            margin:28px 0 30px !important;
        }
        .contracts-intro{
            margin:34px 0 22px !important;
        }
        .contracts-intro h2{
            margin:0 0 10px !important;
        }
        .categories-grid{
            margin-top:0 !important;
        }
        .client-attract-strip{
            display:grid;
            grid-template-columns:repeat(3,minmax(0,1fr));
            gap:18px;
            margin:0 0 38px;
        }
        .client-attract-card{
            position:relative;
            overflow:hidden;
            display:flex;
            align-items:center;
            gap:16px;
            padding:20px 22px;
            border-radius:24px;
            background:rgba(255,255,255,0.86);
            border:1px solid rgba(20,39,56,0.10);
            box-shadow:0 18px 45px rgba(10,25,49,0.08);
        }
        .client-attract-card::after{
            content:"";
            position:absolute;
            right:-30px;
            top:-30px;
            width:95px;
            height:95px;
            border-radius:50%;
            background:rgba(255,107,26,0.08);
        }
        .client-attract-icon{
            width:52px;
            height:52px;
            flex:0 0 52px;
            border-radius:18px;
            display:flex;
            align-items:center;
            justify-content:center;
            color:#fff;
            font-size:22px;
            background:linear-gradient(135deg,#EE5828,#ff8a45);
            box-shadow:0 12px 28px rgba(238,88,40,0.22);
        }
        .client-attract-card:nth-child(2) .client-attract-icon{
            background:linear-gradient(135deg,#1f6feb,#4cc9f0);
            box-shadow:0 12px 28px rgba(31,111,235,0.18);
        }
        .client-attract-card:nth-child(3) .client-attract-icon{
            background:linear-gradient(135deg,#16a34a,#39d98a);
            box-shadow:0 12px 28px rgba(22,163,74,0.18);
        }
        .client-attract-card h3{
            margin:0 0 5px;
            color:#0A1931;
            font-size:16px;
            font-weight:900;
        }
        .client-attract-card p{
            margin:0;
            color:#718096;
            font-size:13px;
            line-height:1.55;
            font-weight:650;
        }
        @media (max-width:900px){
            .main{ padding-left:18px !important; padding-right:18px !important; }
            .client-attract-strip{ grid-template-columns:1fr; }
            .ai-open-btn{ width:100%; min-width:0; }
        }

        .ai-modal-overlay{
            display:none;
            position:fixed;
            inset:0;
            background:rgba(8,17,34,0.66);
            z-index:99999;
            padding:22px;
            align-items:center;
            justify-content:center;
            backdrop-filter:blur(7px);
        }
        .ai-modal-overlay.show{ display:flex; }
        .ai-modal{
            width:min(1160px,100%);
            max-height:92vh;
            overflow:auto;
            border-radius:28px;
            background:#f8fbff;
            box-shadow:0 30px 90px rgba(0,0,0,0.35);
            border:1px solid rgba(255,255,255,0.6);
        }
        .ai-modal-header{
            padding:30px 34px;
            background:linear-gradient(135deg,#071a33,#244c84);
            color:#fff;
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:20px;
        }
        .ai-modal-header h2{
            margin:0 0 10px;
            font-size:38px;
            color:#fff !important;
        }
        .ai-modal-header p{
            margin:0;
            color:#fff !important;
            opacity:1 !important;
            font-size:17px;
            line-height:1.6;
        }
        .ai-close-btn{
            border:none;
            width:56px;
            height:56px;
            border-radius:18px;
            background:rgba(255,255,255,0.14);
            color:#fff;
            font-size:30px;
            cursor:pointer;
            font-weight:800;
        }
        .ai-modal-body{ padding:30px 34px 34px; }
        .ai-form-grid{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:20px 26px;
        }
        .ai-form-group{
            display:flex;
            flex-direction:column;
            gap:9px;
        }
        .ai-form-group.full{ grid-column:1/-1; }
        .ai-form-group label{
            color:#142738;
            font-weight:800;
            font-size:15px;
        }
        .ai-form-group input,
        .ai-form-group select,
        .ai-form-group textarea,
        .specific-field select{
            width:100%;
            border:1px solid rgba(20,39,56,0.14);
            border-radius:18px;
            background:#fff;
            color:#142738;
            min-height:58px;
            padding:0 16px;
            font-size:16px;
            outline:none;
            box-shadow:0 12px 30px rgba(10,25,49,0.05);
        }
        .ai-form-group textarea{
            min-height:110px;
            padding:16px;
            resize:vertical;
        }
        .ai-error-msg{
            color:#e11d48;
            font-size:13px;
            font-weight:800;
            min-height:17px;
        }
        .ai-invalid{
            border-color:#e11d48 !important;
            box-shadow:0 0 0 4px rgba(225,29,72,0.12) !important;
        }
        .specific-questions{
            display:none;
            grid-column:1/-1;
            background:#fff;
            border:1px solid rgba(20,39,56,0.08);
            border-radius:22px;
            padding:22px;
            box-shadow:0 12px 30px rgba(10,25,49,0.05);
        }
        .specific-questions.active{ display:block; }
        .specific-questions h3{
            margin:0 0 18px;
            color:#142738;
            font-size:22px;
            font-weight:900;
            display:flex;
            gap:10px;
            align-items:center;
        }
        .specific-questions h3 i{ color:#FF6B1A; }
        .specific-grid{
            display:grid;
            grid-template-columns:repeat(3,minmax(0,1fr));
            gap:16px;
        }
        .specific-field{
            display:flex;
            flex-direction:column;
            gap:8px;
        }
        .specific-field label{
            color:#142738;
            font-weight:800;
            font-size:14px;
        }
        .ai-modal-actions{
            margin-top:26px;
            display:flex;
            justify-content:flex-end;
            gap:14px;
            flex-wrap:wrap;
        }
        .ai-cancel-btn,
        .ai-submit-btn{
            border:none;
            min-height:58px;
            padding:0 26px;
            border-radius:18px;
            font-weight:900;
            font-size:15px;
            cursor:pointer;
        }
        .ai-cancel-btn{
            background:#fff;
            color:#142738;
            border:1px solid rgba(20,39,56,0.12);
        }
        .ai-submit-btn{
            background:linear-gradient(135deg,#FF6B1A,#ff8848);
            color:#fff;
            box-shadow:0 14px 28px rgba(255,107,26,0.25);
        }
        .ai-result-box{
            display:none;
            margin-top:26px;
            background:#fff;
            border:1px solid rgba(20,39,56,0.08);
            border-radius:24px;
            padding:24px;
            box-shadow:0 15px 35px rgba(10,25,49,0.08);
        }
        .ai-result-box.show{ display:block; }
        .ai-result-top{
            display:flex;
            justify-content:space-between;
            gap:18px;
            flex-wrap:wrap;
            margin-bottom:18px;
        }
        .ai-result-box h3{
            color:#142738;
            font-size:26px;
            margin:0 0 8px;
        }
        .ai-result-box p{ color:#64748b; margin:0; line-height:1.6; }
        .ai-score{
            background:rgba(255,107,26,0.12);
            color:#EE5828;
            border-radius:999px;
            padding:10px 14px;
            font-weight:900;
            height:fit-content;
        }
        .ai-result-meta{
            display:grid;
            grid-template-columns:repeat(3,minmax(0,1fr));
            gap:14px;
            margin:18px 0;
        }
        .ai-meta{
            background:#f7f9fc;
            border-radius:16px;
            padding:14px;
            border:1px solid rgba(20,39,56,0.06);
        }
        .ai-meta span{ display:block; color:#64748b; font-size:12px; margin-bottom:5px; }
        .ai-meta strong{ color:#142738; font-size:17px; }
        .ai-reasons{ color:#334155; line-height:1.8; font-weight:700; }
        @media(max-width:900px){
            .ai-form-grid,.specific-grid,.ai-result-meta{ grid-template-columns:1fr; }
            .ai-open-btn{ width:100%; }
            .ai-modal-header h2{ font-size:30px; }
        }

        @media (max-width: 900px) {
            .contracts-tools {
                flex-direction: column;
                align-items: stretch;
            }

            .contracts-search {
                min-width: 100%;
            }
        }


        /* ===== STYLE ATTRACTIF — GENERATION INTELLIGENTE ===== */
        .ai-reco-card{
            position:relative !important;
            overflow:hidden !important;
            padding:38px 42px !important;
            border-radius:32px !important;
            background:
                radial-gradient(circle at 82% 20%, rgba(255,255,255,0.22), transparent 26%),
                radial-gradient(circle at 12% 120%, rgba(255,107,26,0.38), transparent 34%),
                linear-gradient(135deg,#071A33 0%, #102B50 46%, #244C84 100%) !important;
            border:1px solid rgba(255,255,255,0.16) !important;
            box-shadow:0 26px 70px rgba(7,26,51,0.26) !important;
            isolation:isolate;
        }
        .ai-reco-card::before{
            content:"";
            position:absolute;
            inset:-2px;
            background:linear-gradient(120deg, transparent, rgba(255,255,255,0.18), transparent);
            transform:translateX(-100%);
            animation:aiCardShine 5.5s ease-in-out infinite;
            z-index:-1;
        }
        .ai-reco-card::after{
            content:"IA";
            position:absolute;
            right:36px;
            top:22px;
            width:78px;
            height:78px;
            border-radius:26px;
            display:grid;
            place-items:center;
            font-size:24px;
            font-weight:950;
            letter-spacing:.5px;
            color:rgba(255,255,255,0.92);
            background:rgba(255,255,255,0.10);
            border:1px solid rgba(255,255,255,0.15);
            backdrop-filter:blur(10px);
            box-shadow:inset 0 1px 0 rgba(255,255,255,0.18), 0 18px 34px rgba(0,0,0,0.12);
        }
        @keyframes aiCardShine{
            0%,55%{ transform:translateX(-120%); opacity:0; }
            65%{ opacity:1; }
            100%{ transform:translateX(120%); opacity:0; }
        }
        .ai-reco-card-content{
            position:relative;
            z-index:2;
            align-items:center !important;
        }
        .ai-reco-card h2{
            font-size:clamp(30px, 3vw, 44px) !important;
            line-height:1.05 !important;
            letter-spacing:-.8px;
            max-width:760px;
        }
        .ai-reco-card p{
            max-width:780px !important;
            font-size:16.5px !important;
            color:rgba(255,255,255,0.88) !important;
        }
        .ai-reco-mini span{
            min-height:38px;
            padding:9px 14px !important;
            background:rgba(255,255,255,0.12) !important;
            box-shadow:inset 0 1px 0 rgba(255,255,255,0.12);
        }
        .ai-reco-flow{
            display:flex;
            align-items:center;
            gap:10px;
            margin-top:18px;
            flex-wrap:wrap;
        }
        .ai-reco-flow div{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:10px 13px;
            border-radius:15px;
            background:rgba(255,255,255,0.09);
            border:1px solid rgba(255,255,255,0.12);
            color:#fff;
            font-size:13px;
            font-weight:850;
        }
        .ai-reco-flow > i{
            color:#ffb180;
            font-size:22px;
        }
        .ai-open-btn{
            position:relative !important;
            overflow:hidden !important;
            min-width:310px !important;
            min-height:68px !important;
            gap:12px !important;
            border-radius:22px !important;
            background:linear-gradient(135deg,#FF6B1A,#ff8a45) !important;
            box-shadow:0 18px 36px rgba(255,107,26,0.34), inset 0 1px 0 rgba(255,255,255,0.22) !important;
        }
        .ai-open-btn::after{
            content:"";
            position:absolute;
            inset:0;
            background:linear-gradient(90deg, transparent, rgba(255,255,255,.24), transparent);
            transform:translateX(-120%);
            transition:.5s ease;
        }
        .ai-open-btn:hover::after{ transform:translateX(120%); }
        .ai-btn-icon{
            width:38px;
            height:38px;
            border-radius:14px;
            display:grid;
            place-items:center;
            background:rgba(255,255,255,0.16);
            border:1px solid rgba(255,255,255,0.18);
            flex:0 0 38px;
        }
        .client-attract-strip{
            margin-top:-8px !important;
        }
        .client-attract-card{
            border-radius:26px !important;
            transition:.25s ease;
        }
        .client-attract-card:hover{
            transform:translateY(-4px);
            box-shadow:0 24px 55px rgba(10,25,49,0.12) !important;
        }
        @media(max-width:900px){
            .ai-reco-card{ padding:28px 24px !important; }
            .ai-reco-card::after{ display:none; }
            .ai-reco-flow{ display:none; }
        }

        /* ===== TIMELINE VIEW ===== */
        .timeline {
            position: relative;
            padding: 2rem 0;
            margin: 0 auto;
            max-width: 800px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            width: 4px;
            height: 100%;
            background: #FF6B1A;
            transform: translateX(-50%);
            border-radius: 2px;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 2.5rem;
            width: 50%;
            padding: 0 2rem;
            box-sizing: border-box;
        }
        .timeline-item:nth-child(odd) {
            left: 0;
            text-align: right;
        }
        .timeline-item:nth-child(even) {
            left: 50%;
            text-align: left;
        }
        .timeline-dot {
            position: absolute;
            top: 20px;
            width: 18px;
            height: 18px;
            background: #0a1931;
            border: 4px solid #FF6B1A;
            border-radius: 50%;
            z-index: 2;
        }
        .timeline-item:nth-child(odd) .timeline-dot {
            right: -9px;
        }
        .timeline-item:nth-child(even) .timeline-dot {
            left: -9px;
        }
        .timeline-content {
            background: rgba(255, 255, 255, 0.04);
            padding: 1.5rem;
            border-radius: 18px;
            box-shadow: 0 12px 30px rgba(10, 25, 49, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: transform 0.3s ease, border-color 0.3s ease;
            text-align: left;
        }
        .timeline-content:hover {
            transform: translateY(-5px);
            border-color: #FF6B1A;
            box-shadow: 0 18px 45px rgba(10, 25, 49, 0.12);
        }
        .timeline-date {
            color: #FF6B1A;
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 14px;
        }
        .timeline-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #fff !important;
        }
        @media (max-width: 768px) {
            .timeline::before { left: 20px; }
            .timeline-item { width: 100%; left: 0 !important; text-align: left !important; padding-left: 50px; padding-right: 0; }
            .timeline-item:nth-child(odd) .timeline-dot,
            .timeline-item:nth-child(even) .timeline-dot { left: 11px; }
        }

    </style>
</head>
<body>

<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">
    <!-- ===== NAVBAR ===== -->
    <?php require_once __DIR__.'/assets/includes/navbar.php'; ?>

    <!-- ===== MAIN ===== -->
    <main class="main">

        <?php if ($showQrTunnelAlert): ?>
        <div class="alert" style="margin:0 0 1.25rem;padding:14px 18px;border-radius:12px;background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.35);color:#fde68a;font-size:14px;display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;">
            <i class="bi bi-qr-code-scan" style="font-size:22px;flex-shrink:0;"></i>
            <div style="flex:1;min-width:200px;">
                <strong>QR code PDF : localhost non scannable sur téléphone</strong><br>
                Utilisez <strong>Ngrok</strong> (tunnel public) puis enregistrez l’URL dans
                <a href="<?= (defined('BASE_URL') ? BASE_URL : '/assurance') ?>/admin/tunnel.php" style="color:#fbbf24;text-decoration:underline;">Configuration tunnel QR</a>.
                Méthode rapide : <code style="background:rgba(0,0,0,.2);padding:2px 6px;border-radius:4px;">ngrok http 80</code>
            </div>
        </div>
        <?php elseif ($qrTunnelMethod === 'local_ip'): ?>
        <div class="alert" style="margin:0 0 1.25rem;padding:12px 16px;border-radius:12px;background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.3);color:#93c5fd;font-size:13px;">
            <i class="bi bi-wifi"></i>
            QR PDF : mode IP locale (<code><?= htmlspecialchars($qrTunnelBase) ?></code>) — fonctionne si le téléphone est sur le <strong>même Wi‑Fi</strong>.
            Pour la 4G / soutenance : <a href="<?= (defined('BASE_URL') ? BASE_URL : '/assurance') ?>/admin/tunnel.php" style="color:#93c5fd;">configurer Ngrok</a>.
        </div>
        <?php endif; ?>

        <div class="page-header">
            <div>
                <div class="page-title-main">Contrats</div>
                <div class="page-breadcrumb">
                    <i class="bi bi-house"></i>
                    <a href="client.php" style="color:inherit;text-decoration:none;">Accueil</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <span>Contrats</span>
                </div>
            </div>
        </div>


        <div class="ai-reco-card">
            <div class="ai-reco-card-content">
                <div>
                    <h2>Générer mon contrat selon mes besoins</h2>
                    <p>Décrivez votre situation, votre budget et vos priorités. L’assistant intelligent prépare une proposition de contrat personnalisée : catégorie, formule, garanties et raisons du choix.</p>
                    <div class="ai-reco-mini">
                        <span><i class="bi bi-ui-checks"></i> Besoins</span>
                        <span><i class="bi bi-cash-coin"></i> Budget</span>
                        <span><i class="bi bi-shield-check"></i> Garanties</span>
                    </div>
                    <div class="ai-reco-flow">
                        <div><i class="bi bi-chat-square-text"></i> Réponses</div>
                        <i class="bi bi-arrow-right-short"></i>
                        <div><i class="bi bi-cpu"></i> Analyse</div>
                        <i class="bi bi-arrow-right-short"></i>
                        <div><i class="bi bi-file-earmark-check"></i> Proposition</div>
                    </div>
                </div>
                <button type="button" class="ai-open-btn" onclick="openRecommendationModal()">
                    <span class="ai-btn-icon"><i class="bi bi-stars"></i></span>
                    <span>Générer mon contrat intelligent</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>



        <div class="client-attract-strip">
            <div class="client-attract-card">
                <div class="client-attract-icon"><i class="bi bi-lightning-charge"></i></div>
                <div>
                    <h3>Demande rapide</h3>
                    <p>Choisissez une catégorie, remplissez le formulaire et suivez votre contrat en ligne.</p>
                </div>
            </div>
            <div class="client-attract-card">
                <div class="client-attract-icon"><i class="bi bi-shield-check"></i></div>
                <div>
                    <h3>Garanties claires</h3>
                    <p>Les garanties incluses et optionnelles sont affichées avant la validation.</p>
                </div>
            </div>
            <div class="client-attract-card">
                <div class="client-attract-icon"><i class="bi bi-graph-up-arrow"></i></div>
                <div>
                    <h3>Suivi personnalisé</h3>
                    <p>Votre espace affiche uniquement vos contrats et leur statut en temps réel.</p>
                </div>
            </div>
        </div>

        <div class="contracts-intro">
            <div>
                <h2>Choisissez une catégorie</h2>
                <p>Sélectionnez le type d’assurance avant de remplir votre contrat.</p>
            </div>
        </div>

        <div class="categories-grid">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $index => $categorie): ?>
                    <?php
                        $config = categoryConfig($categorie['nom_categorie'] ?? '');
                        $descriptionCategorie = trim((string)($categorie['description_categorie'] ?? ''));
                        $descriptionToShow = $descriptionCategorie !== ''
                            ? $descriptionCategorie
                            : $config['default_description'];
                    ?>
                    <a href="<?= htmlspecialchars($config['href']) ?>" class="category-card <?= $index === 0 ? 'active' : '' ?>">
                        <div class="category-icon <?= htmlspecialchars($config['class']) ?>">
                            <i class="bi <?= htmlspecialchars($config['icon']) ?>"></i>
                        </div>
                        <h3><?= htmlspecialchars($categorie['nom_categorie'] ?? 'Catégorie') ?></h3>
                        <p><?= htmlspecialchars($descriptionToShow) ?></p>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-contracts" style="grid-column: 1 / -1;">
                    <h3>Aucune catégorie trouvée</h3>
                    <p>Ajoutez d’abord des catégories dans le back-office.</p>
                </div>
            <?php endif; ?>
        </div>

        <section class="content contracts-page">
            <div class="contracts-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h2>Mes contrats</h2>
                        <p>Consultez et gérez facilement tous vos contrats</p>
                    </div>
                    <div class="d-flex gap-2" style="background: rgba(255,255,255,0.06); padding: 4px; border-radius: 50px; border: 1px solid rgba(255,255,255,0.1);">
                        <button type="button" id="btnListView" class="btn btn-sm rounded-pill fw-bold px-4 py-2 active" style="font-size: 13px; color: white; background: #FF6B1A; border: none; transition: 0.25s;">
                            <i class="bi bi-list-ul"></i> Liste
                        </button>
                        <button type="button" id="btnTimelineView" class="btn btn-sm rounded-pill fw-bold px-4 py-2" style="font-size: 13px; color: rgba(255,255,255,0.6); background: transparent; border: none; transition: 0.25s;">
                            <i class="bi bi-clock-history"></i> Timeline
                        </button>
                    </div>
                </div>
            </div>

            <?php
                $feedbackMessage = '';
                $feedbackClass = 'success';
                $feedbackIcon = 'bi-check-circle';

                // Ancienne redirection : contrat.php?success=renewal&new_id=16
                if (isset($_GET['success']) && $_GET['success'] === 'renewal') {
                    $feedbackMessage = 'Demande de renouvellement créée avec succès. Elle est maintenant en attente de validation.';
                }

                // Nouvelle redirection propre : contrat.php?renewal=pending / approved / rejected
                if (isset($_GET['renewal'])) {
                    if ($_GET['renewal'] === 'pending') {
                        $feedbackMessage = 'Demande de renouvellement créée avec succès. Elle est maintenant en attente de validation.';
                    } elseif ($_GET['renewal'] === 'approved') {
                        $feedbackMessage = 'Votre renouvellement a été validé avec succès par l admin.';
                    } elseif ($_GET['renewal'] === 'rejected') {
                        $feedbackMessage = 'Votre demande de renouvellement a été refusée.';
                        $feedbackClass = 'error';
                        $feedbackIcon = 'bi-x-circle';
                    }
                }

                if (isset($_GET['error']) && $_GET['error'] === 'renouvellement_impossible') {
                    $feedbackMessage = 'Renouvellement impossible pour ce contrat.';
                    $feedbackClass = 'error';
                    $feedbackIcon = 'bi-exclamation-triangle';
                }
            ?>

            <?php if ($feedbackMessage !== ''): ?>
                <div id="renewalFeedback" class="contracts-feedback <?= htmlspecialchars($feedbackClass) ?>">
                    <i class="bi <?= htmlspecialchars($feedbackIcon) ?>"></i>
                    <?= htmlspecialchars($feedbackMessage) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($contrats)): ?>
                <div class="contracts-tools">
                    <div class="contracts-search">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchContrats" placeholder="Rechercher par type, formule, statut, numéro...">
                    </div>

                    <select id="filterStatut">
                        <option value="">Tous les statuts</option>
                        <option value="actif">Actif</option>
                        <option value="en attente">En attente</option>
                        <option value="résilié">Résilié</option>
                        <option value="expiré">Expiré</option>
                        <option value="refusé">Refusé</option>
                    </select>

                    <select id="sortContrats">
                        <option value="default">Tri par défaut</option>
                        <option value="date_desc">Date début récente</option>
                        <option value="date_asc">Date début ancienne</option>
                        <option value="prime_asc">Prime croissante</option>
                        <option value="prime_desc">Prime décroissante</option>
                        <option value="franchise_asc">Franchise croissante</option>
                        <option value="franchise_desc">Franchise décroissante</option>
                    </select>

                    <button type="button" id="resetContratFilters">
                        <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                    </button>
                </div>

                <div class="contracts-empty-filter" id="contractsEmptyFilter">
                    Aucun contrat ne correspond à votre recherche.
                </div>
            <?php endif; ?>

            <div class="contracts-list">
                <?php if (!empty($contrats)): ?>
                    <?php foreach ($contrats as $loopIndex => $contrat): ?>
                        <?php
                            $typeData = typeIcon($contrat->getTypeContrat());
                            $badgeClass = statusClass($contrat->getStatutContrat());
                        ?>

                        <div class="contract-banner"
                             data-original-index="<?= (int)$loopIndex ?>"
                             data-search="<?= htmlspecialchars(strtolower(
                                 $contrat->getNumeroContrat() . ' ' .
                                 $contrat->getTypeContrat() . ' ' .
                                 $contrat->getNomCategorie() . ' ' .
                                 $contrat->getNomFormule() . ' ' .
                                 $contrat->getStatutContrat() . ' ' .
                                 $contrat->getDateDebutContrat() . ' ' .
                                 $contrat->getDateFinContrat()
                             )) ?>"
                             data-statut="<?= htmlspecialchars(strtolower(trim((string)$contrat->getStatutContrat()))) ?>"
                             data-prime="<?= htmlspecialchars((string)((float)$contrat->getPrimeContrat())) ?>"
                             data-franchise="<?= htmlspecialchars((string)((float)$contrat->getFranchiseContrat())) ?>"
                             data-date="<?= htmlspecialchars((string)$contrat->getDateDebutContrat()) ?>">
                            <div class="contract-banner-left">
                                <div class="contract-icon <?= htmlspecialchars($typeData['class']) ?>">
                                    <i class="bi <?= htmlspecialchars($typeData['icon']) ?>"></i>
                                </div>

                                <div>
                                    <h3>Contrat <?= htmlspecialchars($contrat->getTypeContrat()) ?></h3>
                                    <span class="contract-ref">
                                        N° <?= htmlspecialchars($contrat->getNumeroContrat()) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="contract-banner-center">
                                <div class="info-item">
                                    <span class="label">Date début</span>
                                    <strong><?= formatDateFr($contrat->getDateDebutContrat()) ?></strong>
                                </div>

                                <div class="info-item">
                                    <span class="label">Date fin</span>
                                    <strong><?= formatDateFr($contrat->getDateFinContrat()) ?></strong>
                                </div>

                                <div class="info-item">
                                    <span class="label">Prime</span>
                                    <strong><?= htmlspecialchars((string)$contrat->getPrimeContrat()) ?> DT</strong>
                                </div>

                                <div class="info-item">
                                    <span class="label">Franchise</span>
                                    <strong><?= htmlspecialchars((string)$contrat->getFranchiseContrat()) ?> DT</strong>
                                </div>
                            </div>

                            <div class="contract-banner-right">
                                <span class="status-badge <?= htmlspecialchars($badgeClass) ?>">
                                    <?= htmlspecialchars($contrat->getStatutContrat()) ?>
                                </span>

                                <div class="contract-actions">
                                    <a href="contratshow.php?id=<?= urlencode((string)$contrat->getIdContrat()) ?>" class="action-btn">
                                        Voir
                                    </a>
                                    <a href="contrat_update_client.php?id=<?= urlencode((string)$contrat->getIdContrat()) ?>" class="action-btn secondary">
                                        Modifier
                                    </a>
                                    <a href="contratcancel.php?id=<?= urlencode((string)$contrat->getIdContrat()) ?>" class="action-btn secondary" onclick="return confirm('Résilier ce contrat ?')">
                                        Résilier
                                    </a>

                                    <?php if (in_array(strtolower(trim((string)$contrat->getStatutContrat())), ['actif', 'expiré', 'résilié'], true)): ?>
                                        <a href="renouvelerContrat.php?id=<?= urlencode((string)$contrat->getIdContrat()) ?>"
                                           class="action-btn secondary"
                                           onclick="return confirm('Voulez-vous renouveler ce contrat ? Une nouvelle demande sera créée en attente.');">
                                            Renouveler
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-contracts">
                        <h3>Aucun contrat trouvé</h3>
                        <p>Le client n’a pas encore de contrats enregistrés.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="contracts-timeline-container" style="display: none; margin-top: 2rem;">
                <div class="timeline">
                    <?php if (empty($contratsTimeline)): ?>
                        <div class="empty-contracts">
                            <h3>Aucun contrat trouvé</h3>
                            <p>Le client n’a pas encore de contrats enregistrés.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($contratsTimeline as $c): 
                            $statut = strtolower(trim((string)$c->getStatutContrat()));
                            $badgeClass = match($statut) {
                                'actif', 'active' => 'bg-success',
                                'expiré', 'resilie', 'résilié' => 'bg-danger',
                                default => 'bg-warning text-dark'
                            };
                            $couverture = $c->getNomFormule() ?: $c->getFormuleContrat();
                        ?>
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-date"><?= formatDateFr($c->getDateDebutContrat()) ?></div>
                                <h3 class="timeline-title">Contrat <?= htmlspecialchars($c->getTypeContrat() ?? 'Standard') ?></h3>
                                <span class="badge <?= $badgeClass ?> mb-3"><?php $label = match(strtolower(trim($c->getStatutContrat()))) { 'actif'=>'Actif', 'en attente'=>'En attente', 'expiré'=>'Expiré', 'résilié'=>'Résilié', 'refusé'=>'Refusé', default=>$c->getStatutContrat() }; ?><?= $label ?></span>
                                <p class="mb-0" style="color: var(--text-secondary); font-size: 13px;">Couverture: <?= htmlspecialchars($couverture ?? 'Standard') ?></p>
                                <p class="fw-bold mt-2 mb-3" style="color: #FF6B1A; font-size: 15px;"><?= number_format((float)($c->getPrimeContrat() ?? 0), 2, ',', ' ') ?> DT / an</p>
                                <a href="<?= $base ?>/download_pdf.php?id=<?= $c->getIdContrat() ?>" class="btn btn-sm btn-outline-primary rounded-pill" target="_blank" style="border-color:#FF6B1A; color:#FF6B1A; font-weight: bold;">
                                    <i class="bi bi-file-earmark-pdf"></i> Télécharger PDF
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>



        <!-- ===== POPUP GÉNÉRATION INTELLIGENTE SELON LES BESOINS ===== -->
        <div class="ai-modal-overlay" id="recommendationModal">
            <div class="ai-modal">
                <div class="ai-modal-header">
                    <div>
                        <h2>Génération intelligente du contrat</h2>
                        <p>Répondez aux questions. Le système analyse vos besoins et génère une proposition complète avec la formule la plus adaptée.</p>
                    </div>
                    <button type="button" class="ai-close-btn" onclick="closeRecommendationModal()">&times;</button>
                </div>

                <div class="ai-modal-body">
                    <form id="recommendationForm" novalidate>
                        <input type="hidden" name="action" value="recommandation_contrat">
                        <div class="ai-form-grid">
                            <div class="ai-form-group">
                                <label>Catégorie souhaitée</label>
                                <select name="categorie" id="categorieReco">
                                    <option value="">Choisir une catégorie</option>
                                    <?php foreach ($categories as $categorie): ?>
                                        <option value="<?= htmlspecialchars((string)($categorie['nom_categorie'] ?? '')) ?>" data-id="<?= (int)($categorie['id_categorie'] ?? 0) ?>">
                                            <?= htmlspecialchars((string)($categorie['nom_categorie'] ?? 'Catégorie')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="ai-error-msg" data-error-for="categorie"></small>
                            </div>

                            <div class="ai-form-group">
                                <label>Budget mensuel maximum</label>
                                <input type="number" name="budget" placeholder="Ex : 150">
                                <small class="ai-error-msg" data-error-for="budget"></small>
                            </div>

                            <div class="ai-form-group">
                                <label>Priorité principale</label>
                                <select name="objectif">
                                    <option value="">Choisir</option>
                                    <option value="prix_bas">Prix le plus bas</option>
                                    <option value="franchise_faible">Franchise faible</option>
                                    <option value="couverture_max">Couverture maximale</option>
                                    <option value="equilibre">Équilibre prix / garanties</option>
                                </select>
                                <small class="ai-error-msg" data-error-for="objectif"></small>
                            </div>

                            <div class="ai-form-group">
                                <label>Niveau de risque</label>
                                <select name="risque">
                                    <option value="">Choisir</option>
                                    <option value="faible">Faible</option>
                                    <option value="moyen">Moyen</option>
                                    <option value="eleve">Élevé</option>
                                </select>
                                <small class="ai-error-msg" data-error-for="risque"></small>
                            </div>

                            <div class="ai-form-group">
                                <label>Franchise préférée</label>
                                <select name="franchise_pref">
                                    <option value="">Choisir</option>
                                    <option value="basse">La plus basse possible</option>
                                    <option value="moyenne">Moyenne</option>
                                    <option value="peu_importe">Peu importe</option>
                                </select>
                                <small class="ai-error-msg" data-error-for="franchise_pref"></small>
                            </div>

                            <div class="ai-form-group">
                                <label>Durée souhaitée</label>
                                <select name="duree">
                                    <option value="">Choisir</option>
                                    <option value="1">1 mois</option>
                                    <option value="6">6 mois</option>
                                    <option value="12">12 mois</option>
                                </select>
                                <small class="ai-error-msg" data-error-for="duree"></small>
                            </div>

                            <div id="questionsAuto" class="specific-questions">
                                <h3><i class="bi bi-car-front-fill"></i> Questions spécifiques Auto</h3>
                                <div class="specific-grid">
                                    <div class="specific-field"><label>Véhicule neuf ou ancien ?</label><select name="vehicule_age"><option value="">Choisir</option><option value="neuf">Neuf</option><option value="ancien">Ancien</option></select><small class="ai-error-msg" data-error-for="vehicule_age"></small></div>
                                    <div class="specific-field"><label>Stationnement sécurisé ?</label><select name="stationnement"><option value="">Choisir</option><option value="oui">Oui</option><option value="non">Non</option></select><small class="ai-error-msg" data-error-for="stationnement"></small></div>
                                    <div class="specific-field"><label>Conduite quotidienne ?</label><select name="conduite_quotidienne"><option value="">Choisir</option><option value="oui">Oui</option><option value="non">Non</option></select><small class="ai-error-msg" data-error-for="conduite_quotidienne"></small></div>
                                </div>
                            </div>

                            <div id="questionsHabitation" class="specific-questions">
                                <h3><i class="bi bi-house-door-fill"></i> Questions spécifiques Habitation</h3>
                                <div class="specific-grid">
                                    <div class="specific-field"><label>Appartement ou maison ?</label><select name="type_logement"><option value="">Choisir</option><option value="appartement">Appartement</option><option value="maison">Maison</option></select><small class="ai-error-msg" data-error-for="type_logement"></small></div>
                                    <div class="specific-field"><label>Propriétaire ou locataire ?</label><select name="statut_logement"><option value="">Choisir</option><option value="proprietaire">Propriétaire</option><option value="locataire">Locataire</option></select><small class="ai-error-msg" data-error-for="statut_logement"></small></div>
                                    <div class="specific-field"><label>Zone à risque ?</label><select name="zone_risque"><option value="">Choisir</option><option value="oui">Oui</option><option value="non">Non</option></select><small class="ai-error-msg" data-error-for="zone_risque"></small></div>
                                </div>
                            </div>

                            <div id="questionsSante" class="specific-questions">
                                <h3><i class="bi bi-heart-pulse-fill"></i> Questions spécifiques Santé</h3>
                                <div class="specific-grid">
                                    <div class="specific-field"><label>Besoin hospitalisation ?</label><select name="hospitalisation"><option value="">Choisir</option><option value="oui">Oui</option><option value="non">Non</option></select><small class="ai-error-msg" data-error-for="hospitalisation"></small></div>
                                    <div class="specific-field"><label>Consultations fréquentes ?</label><select name="consultations_frequentes"><option value="">Choisir</option><option value="oui">Oui</option><option value="non">Non</option></select><small class="ai-error-msg" data-error-for="consultations_frequentes"></small></div>
                                    <div class="specific-field"><label>Couverture familiale ?</label><select name="couverture_familiale"><option value="">Choisir</option><option value="oui">Oui</option><option value="non">Non</option></select><small class="ai-error-msg" data-error-for="couverture_familiale"></small></div>
                                </div>
                            </div>

                            <div id="questionsProtection" class="specific-questions">
                                <h3><i class="bi bi-shield-check"></i> Questions spécifiques Protection</h3>
                                <div class="specific-grid">
                                    <div class="specific-field"><label>Assistance juridique ?</label><select name="assistance_juridique"><option value="">Choisir</option><option value="oui">Oui</option><option value="non">Non</option></select><small class="ai-error-msg" data-error-for="assistance_juridique"></small></div>
                                    <div class="specific-field"><label>Sécurité voyage ?</label><select name="securite_voyage"><option value="">Choisir</option><option value="oui">Oui</option><option value="non">Non</option></select><small class="ai-error-msg" data-error-for="securite_voyage"></small></div>
                                    <div class="specific-field"><label>Protection personnelle ?</label><select name="protection_personnelle"><option value="">Choisir</option><option value="oui">Oui</option><option value="non">Non</option></select><small class="ai-error-msg" data-error-for="protection_personnelle"></small></div>
                                </div>
                            </div>

                            <div class="ai-form-group full">
                                <label>Besoin précis du client</label>
                                <textarea name="besoin" placeholder="Ex : je veux protéger ma famille, limiter le budget, couvrir les urgences et avoir une bonne assistance..."></textarea>
                            </div>
                        </div>

                        <div class="ai-modal-actions">
                            <button type="button" class="ai-cancel-btn" onclick="closeRecommendationModal()">Annuler</button>
                            <button type="submit" class="ai-submit-btn"><i class="bi bi-stars"></i> Générer ma proposition</button>
                        </div>
                    </form>

                    <div id="aiResultBox" class="ai-result-box"></div>
                </div>
            </div>
        </div>

    </main>
</div>

<script src="assets/js/main.js"></script>
<script>
(function () {
    const searchInput = document.getElementById('searchContrats');
    const filterStatut = document.getElementById('filterStatut');
    const sortContrats = document.getElementById('sortContrats');
    const resetButton = document.getElementById('resetContratFilters');
    const contractsList = document.querySelector('.contracts-list');
    const emptyMessage = document.getElementById('contractsEmptyFilter');

    if (!searchInput || !filterStatut || !sortContrats || !resetButton || !contractsList) {
        return;
    }

    function normalizeText(value) {
        return (value || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function getCards() {
        return Array.from(document.querySelectorAll('.contract-banner'));
    }

    function matchesSearch(card, words) {
        const data = normalizeText(card.dataset.search || '');
        return words.every(word => data.includes(word));
    }

    function sortCards(cards) {
        const sortValue = sortContrats.value;

        cards.sort((a, b) => {
            const primeA = parseFloat(a.dataset.prime || '0');
            const primeB = parseFloat(b.dataset.prime || '0');
            const franchiseA = parseFloat(a.dataset.franchise || '0');
            const franchiseB = parseFloat(b.dataset.franchise || '0');
            const dateA = new Date(a.dataset.date || '1970-01-01');
            const dateB = new Date(b.dataset.date || '1970-01-01');
            const indexA = parseInt(a.dataset.originalIndex || '0', 10);
            const indexB = parseInt(b.dataset.originalIndex || '0', 10);

            if (sortValue === 'prime_asc') return primeA - primeB;
            if (sortValue === 'prime_desc') return primeB - primeA;
            if (sortValue === 'franchise_asc') return franchiseA - franchiseB;
            if (sortValue === 'franchise_desc') return franchiseB - franchiseA;
            if (sortValue === 'date_asc') return dateA - dateB;
            if (sortValue === 'date_desc') return dateB - dateA;

            return indexA - indexB;
        });

        cards.forEach(card => contractsList.appendChild(card));
    }

    function applyContratFilters() {
        const words = normalizeText(searchInput.value).split(/\s+/).filter(Boolean);
        const statutValue = normalizeText(filterStatut.value);
        const cards = getCards();
        let visibleCount = 0;

        sortCards(cards);

        cards.forEach(card => {
            const cardStatut = normalizeText(card.dataset.statut || '');
            const searchOk = words.length === 0 || matchesSearch(card, words);
            const statutOk = statutValue === '' || cardStatut === statutValue;
            const visible = searchOk && statutOk;

            card.style.display = visible ? '' : 'none';
            if (visible) visibleCount++;
        });

        if (emptyMessage) {
            emptyMessage.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    function resetContratFilters() {
        searchInput.value = '';
        filterStatut.value = '';
        sortContrats.value = 'default';
        applyContratFilters();
    }

    searchInput.addEventListener('input', applyContratFilters);
    filterStatut.addEventListener('change', applyContratFilters);
    sortContrats.addEventListener('change', applyContratFilters);
    resetButton.addEventListener('click', resetContratFilters);
})();
</script>

<script>
(function () {
    const feedback = document.getElementById('renewalFeedback');
    if (!feedback) return;

    setTimeout(() => {
        feedback.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        feedback.style.opacity = '0';
        feedback.style.transform = 'translateY(-6px)';

        setTimeout(() => {
            feedback.remove();
        }, 450);

        const url = new URL(window.location.href);
        url.searchParams.delete('success');
        url.searchParams.delete('renewal');
        url.searchParams.delete('new_id');
        url.searchParams.delete('error');

        window.history.replaceState({}, document.title, url.pathname + (url.search ? url.search : ''));
    }, 5000);
})();
</script>


<script>
const recoFormulesFromDb = <?= json_encode($formulesReco, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const recoFallbackFormules = [
    {nom_formule:'Classique', nom_categorie:'Auto', prix_formule:80, franchise_formule:220},
    {nom_formule:'Tierce collision', nom_categorie:'Auto', prix_formule:150, franchise_formule:160},
    {nom_formule:'Tous risques', nom_categorie:'Auto', prix_formule:260, franchise_formule:90},
    {nom_formule:'Économique', nom_categorie:'Habitation', prix_formule:55, franchise_formule:180},
    {nom_formule:'Privilège', nom_categorie:'Habitation', prix_formule:140, franchise_formule:90},
    {nom_formule:'Santé Basic', nom_categorie:'Santé', prix_formule:70, franchise_formule:150},
    {nom_formule:'Santé Confort', nom_categorie:'Santé', prix_formule:130, franchise_formule:100},
    {nom_formule:'Santé Premium', nom_categorie:'Santé', prix_formule:240, franchise_formule:50},
    {nom_formule:'Protection Essentiel', nom_categorie:'Protection', prix_formule:60, franchise_formule:160},
    {nom_formule:'Protection Plus', nom_categorie:'Protection', prix_formule:120, franchise_formule:100},
    {nom_formule:'Protection Premium', nom_categorie:'Protection', prix_formule:210, franchise_formule:50}
];

function openRecommendationModal(){
    const modal = document.getElementById('recommendationModal');
    if(modal) modal.classList.add('show');
}
function closeRecommendationModal(){
    const modal = document.getElementById('recommendationModal');
    if(modal) modal.classList.remove('show');
}
function recoNormalize(value){
    return (value || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim();
}
function recoClearError(field){
    if(!field || !field.name) return;
    field.classList.remove('ai-invalid');
    const err = document.querySelector('.ai-error-msg[data-error-for="'+field.name+'"]');
    if(err) err.textContent = '';
}
function recoSetError(field,msg){
    if(!field || !field.name) return;
    field.classList.add('ai-invalid');
    const err = document.querySelector('.ai-error-msg[data-error-for="'+field.name+'"]');
    if(err) err.textContent = msg;
}
function recoActiveSpecificBox(){
    const cat = recoNormalize(document.getElementById('categorieReco')?.value || '');
    if(cat.includes('auto')) return document.getElementById('questionsAuto');
    if(cat.includes('habitation')) return document.getElementById('questionsHabitation');
    if(cat.includes('sante')) return document.getElementById('questionsSante');
    if(cat.includes('protection')) return document.getElementById('questionsProtection');
    return null;
}
function recoShowSpecificQuestions(){
    document.querySelectorAll('.specific-questions').forEach(box => {
        box.classList.remove('active');
        box.querySelectorAll('select').forEach(recoClearError);
    });
    const active = recoActiveSpecificBox();
    if(active) active.classList.add('active');
}
function recoGetFormValue(form,name){
    return form.querySelector('[name="'+name+'"]')?.value || '';
}
function recoValidate(form){
    let ok = true;
    const fields = ['categorie','budget','objectif','risque','franchise_pref','duree'];
    fields.forEach(name => recoClearError(form.querySelector('[name="'+name+'"]')));
    const categorie = form.querySelector('[name="categorie"]');
    const budget = form.querySelector('[name="budget"]');
    const objectif = form.querySelector('[name="objectif"]');
    const risque = form.querySelector('[name="risque"]');
    const franchise = form.querySelector('[name="franchise_pref"]');
    const duree = form.querySelector('[name="duree"]');
    if(!categorie.value.trim()){ recoSetError(categorie,'Veuillez choisir une catégorie.'); ok=false; }
    if(!budget.value.trim()){ recoSetError(budget,'Veuillez saisir votre budget.'); ok=false; }
    else if(isNaN(Number(budget.value)) || Number(budget.value)<=0){ recoSetError(budget,'Le budget doit être un nombre positif.'); ok=false; }
    if(!objectif.value.trim()){ recoSetError(objectif,'Veuillez choisir une priorité.'); ok=false; }
    if(!risque.value.trim()){ recoSetError(risque,'Veuillez choisir un niveau de risque.'); ok=false; }
    if(!franchise.value.trim()){ recoSetError(franchise,'Veuillez choisir une franchise.'); ok=false; }
    if(!duree.value.trim()){ recoSetError(duree,'Veuillez choisir une durée.'); ok=false; }
    const active = recoActiveSpecificBox();
    if(active){
        active.querySelectorAll('select').forEach(sel => {
            recoClearError(sel);
            if(!sel.value.trim()){ recoSetError(sel,'Veuillez choisir une réponse.'); ok=false; }
        });
    }
    if(!ok){
        const first = form.querySelector('.ai-invalid');
        if(first) first.focus();
    }
    return ok;
}
function recoCategoryOfFormula(f){
    return f.nom_categorie || f.categorie || f.categorie_formule || '';
}
function recoNameOfFormula(f){
    return f.nom_formule || f.nom || 'Formule recommandée';
}
function recoPriceOfFormula(f){
    return Number(f.prix_formule || f.prix || 0);
}
function recoFranchiseOfFormula(f){
    return Number(f.franchise_formule || f.franchise || 0);
}
function recoScoreFormula(f, form){
    const budget = Number(recoGetFormValue(form,'budget'));
    const objectif = recoGetFormValue(form,'objectif');
    const risque = recoGetFormValue(form,'risque');
    const franchisePref = recoGetFormValue(form,'franchise_pref');
    const price = recoPriceOfFormula(f);
    const franchise = recoFranchiseOfFormula(f);
    let score = 50;
    const reasons = [];
    if(price <= budget){ score += 35; reasons.push('La prime respecte le budget indiqué.'); }
    else { score -= Math.min(40, (price-budget)/5); }
    if(objectif === 'prix_bas') { score += Math.max(0, 30 - price/10); reasons.push('La formule favorise le prix le plus bas.'); }
    if(objectif === 'franchise_faible') { score += Math.max(0, 35 - franchise/8); reasons.push('La recommandation tient compte de la franchise souhaitée.'); }
    if(objectif === 'couverture_max') { score += price >= budget*0.55 ? 35 : 10; reasons.push('La formule est orientée couverture maximale.'); }
    if(objectif === 'equilibre') { score += price <= budget && franchise <= 180 ? 30 : 10; reasons.push('Bon équilibre entre prix, franchise et protection.'); }
    if(risque === 'eleve') score += price >= budget*0.55 ? 25 : 5;
    if(risque === 'faible') score += price <= budget*0.75 ? 20 : 5;
    if(franchisePref === 'basse') score += franchise <= 100 ? 25 : -10;
    if(franchisePref === 'moyenne') score += franchise > 80 && franchise <= 180 ? 20 : 5;

    const active = recoActiveSpecificBox();
    if(active){
        active.querySelectorAll('select').forEach(sel => {
            if(sel.value === 'oui' || sel.value === 'neuf' || sel.value === 'maison' || sel.value === 'proprietaire') score += 8;
        });
        reasons.push('Les réponses spécifiques à la catégorie ont été prises en compte.');
    }
    return {score: Math.round(score), reasons};
}
function recoAnalyze(form){
    const selectedCat = recoNormalize(recoGetFormValue(form,'categorie'));
    const source = Array.isArray(recoFormulesFromDb) && recoFormulesFromDb.length ? recoFormulesFromDb : recoFallbackFormules;
    const candidates = source.filter(f => recoNormalize(recoCategoryOfFormula(f)).includes(selectedCat) || selectedCat.includes(recoNormalize(recoCategoryOfFormula(f))));
    const list = candidates.length ? candidates : source;
    let best = null;
    list.forEach(f => {
        const result = recoScoreFormula(f, form);
        if(!best || result.score > best.score) best = {...f, score:result.score, reasons:result.reasons};
    });
    return best;
}
function recoTargetPage(cat){
    const c = recoNormalize(cat);
    if(c.includes('auto')) return 'contrat_auto.php';
    if(c.includes('habitation')) return 'contrat_habitation.php';
    if(c.includes('sante')) return 'contrat_sante.php';
    if(c.includes('protection')) return 'contrat_protection.php';
    return 'contrat.php';
}
function recoShowResult(best){
    const box = document.getElementById('aiResultBox');
    if(!box || !best) return;
    const name = recoNameOfFormula(best);
    const cat = recoCategoryOfFormula(best);
    const price = recoPriceOfFormula(best);
    const franchise = recoFranchiseOfFormula(best);
    const page = recoTargetPage(cat);
    box.innerHTML = `
        <div class="ai-result-top">
            <div>
                <h3>Proposition générée : ${name}</h3>
                <p>Cette proposition est générée selon les besoins, le budget, le risque et les réponses spécifiques du client.</p>
            </div>
            <div class="ai-score">Score besoins : ${best.score}</div>
        </div>
        <div class="ai-result-meta">
            <div class="ai-meta"><span>Catégorie</span><strong>${cat || '-'}</strong></div>
            <div class="ai-meta"><span>Prime</span><strong>${price} DT</strong></div>
            <div class="ai-meta"><span>Franchise</span><strong>${franchise} DT</strong></div>
        </div>
        <ul class="ai-reasons">${(best.reasons || []).map(r => `<li>${r}</li>`).join('')}</ul>
        <div class="ai-modal-actions">
            <a class="ai-submit-btn" style="display:inline-flex;align-items:center;text-decoration:none;gap:8px;" href="${page}?formule=${encodeURIComponent(name)}"><i class="bi bi-file-earmark-plus"></i> Créer le contrat proposé</a>
        </div>
    `;
    box.classList.add('show');
    box.scrollIntoView({behavior:'smooth', block:'nearest'});
}

function recoEscapeHtml(value){
    return (value || '').toString().replace(/[&<>'"]/g, function(ch){
        return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[ch];
    });
}
function recoShowPythonResult(data){
    const box = document.getElementById('aiResultBox');
    if(!box) return;
    const name = recoEscapeHtml(data.formule || 'Formule personnalisée');
    const cat = recoEscapeHtml(data.categorie || '-');
    const page = data.page || recoTargetPage(data.categorie || '');
    const score = Number(data.score || 0);
    const prime = data.prime ?? '-';
    const franchise = data.franchise ?? '-';
    const garanties = Array.isArray(data.garanties) ? data.garanties : [];
    const raisons = Array.isArray(data.raisons) ? data.raisons : [];
    const resume = recoEscapeHtml(data.resume || 'Proposition générée avec le moteur Python local.');

    box.innerHTML = `
        <div class="ai-result-top">
            <div>
                <h3>Proposition générée : ${name}</h3>
                <p>${resume}</p>
            </div>
            <div class="ai-score">Score : ${score}%</div>
        </div>
        <div class="ai-result-meta">
            <div class="ai-meta"><span>Catégorie</span><strong>${cat}</strong></div>
            <div class="ai-meta"><span>Prime estimée</span><strong>${prime} DT</strong></div>
            <div class="ai-meta"><span>Franchise</span><strong>${franchise} DT</strong></div>
        </div>
        <h4 style="margin:18px 0 10px;color:#142738;">Garanties conseillées</h4>
        <ul class="ai-reasons">${garanties.map(g => `<li>${recoEscapeHtml(g)}</li>`).join('')}</ul>
        <h4 style="margin:18px 0 10px;color:#142738;">Pourquoi ce choix ?</h4>
        <ul class="ai-reasons">${raisons.map(r => `<li>${recoEscapeHtml(r)}</li>`).join('')}</ul>
        <div class="ai-modal-actions">
            <a class="ai-submit-btn" style="display:inline-flex;align-items:center;text-decoration:none;gap:8px;" href="${page}?formule=${encodeURIComponent(data.formule || '')}"><i class="bi bi-file-earmark-plus"></i> Créer le contrat proposé</a>
        </div>
    `;
    box.classList.add('show');
    box.scrollIntoView({behavior:'smooth', block:'nearest'});
}

document.addEventListener('DOMContentLoaded', function(){
    const form = document.getElementById('recommendationForm');
    const cat = document.getElementById('categorieReco');
    if(cat) cat.addEventListener('change', recoShowSpecificQuestions);
    recoShowSpecificQuestions();
    if(form){
        form.setAttribute('novalidate','novalidate');
        form.querySelectorAll('input,select,textarea').forEach(field => {
            field.removeAttribute('required');
            field.addEventListener('input', () => recoClearError(field));
            field.addEventListener('change', () => recoClearError(field));
        });
        form.addEventListener('submit', async function(e){
            e.preventDefault();
            if(!recoValidate(form)) return;

            const box = document.getElementById('aiResultBox');
            const submitBtn = form.querySelector('button[type="submit"]');
            if(box){
                box.innerHTML = '<div class="ai-result-top"><div><h3>Analyse Python en cours...</h3><p>Le système analyse vos réponses et prépare une proposition personnalisée.</p></div><div class="ai-score">IA locale</div></div>';
                box.classList.add('show');
            }
            if(submitBtn){
                submitBtn.disabled = true;
                submitBtn.dataset.oldText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Génération...';
            }

            try {
                const response = await fetch('generer_contrat_besoin.php', {
                    method: 'POST',
                    body: new FormData(form)
                });
                const data = await response.json();
                if(!response.ok || !data.success){
                    throw new Error(data.message || 'Erreur pendant la génération.');
                }
                recoShowPythonResult(data);
            } catch (err) {
                if(box){
                    box.innerHTML = `<div class="ai-result-top"><div><h3>Erreur de génération</h3><p>${err.message}</p></div></div>`;
                    box.classList.add('show');
                    box.scrollIntoView({behavior:'smooth', block:'nearest'});
                }
            } finally {
                if(submitBtn){
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = submitBtn.dataset.oldText || '<i class="bi bi-stars"></i> Générer ma proposition';
                }
            }
        });
    }
    const modal = document.getElementById('recommendationModal');
    if(modal){
        modal.addEventListener('click', function(e){ if(e.target === modal) closeRecommendationModal(); });
    }
});
</script>


<!-- JS dashboard User : nécessaire pour dropdown avatar / interactions navbar -->
<script src="user/js/main.js"></script>

<!-- JS spécifique contrats : garde tes scripts contrats dans assets_contrats -->
<script src="user/assets_contrats/js/main.js"></script>

<script>
// Fallback sûr : même si main.js ne se charge pas, l'avatar dropdown fonctionne.
document.addEventListener('DOMContentLoaded', function () {
    const avatarBtn = document.getElementById('avatarBtn');
    const avatarDropdown = document.getElementById('avatarDropdown');

    if (avatarBtn && avatarDropdown) {
        avatarBtn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            avatarDropdown.classList.toggle('open');
            avatarDropdown.classList.toggle('show');
        });

        avatarDropdown.addEventListener('click', function (event) {
            event.stopPropagation();
        });

        document.addEventListener('click', function () {
            avatarDropdown.classList.remove('open', 'show');
        });
    }

});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnListView = document.getElementById('btnListView');
    const btnTimelineView = document.getElementById('btnTimelineView');
    const listContainer = document.querySelector('.contracts-list');
    const timelineContainer = document.querySelector('.contracts-timeline-container');
    const toolsContainer = document.querySelector('.contracts-tools');
    const emptyFilter = document.getElementById('contractsEmptyFilter');

    if (btnListView && btnTimelineView && listContainer && timelineContainer) {
        btnListView.addEventListener('click', function () {
            btnListView.style.background = '#FF6B1A';
            btnListView.style.color = 'white';
            btnTimelineView.style.background = 'transparent';
            btnTimelineView.style.color = 'rgba(255, 255, 255, 0.6)';
            
            listContainer.style.display = '';
            if (toolsContainer) toolsContainer.style.display = '';
            timelineContainer.style.display = 'none';
        });

        btnTimelineView.addEventListener('click', function () {
            btnTimelineView.style.background = '#FF6B1A';
            btnTimelineView.style.color = 'white';
            btnListView.style.background = 'transparent';
            btnListView.style.color = 'rgba(255, 255, 255, 0.6)';
            
            listContainer.style.display = 'none';
            if (toolsContainer) toolsContainer.style.display = 'none';
            if (emptyFilter) emptyFilter.style.display = 'none';
            timelineContainer.style.display = 'block';
        });
    }
});
</script>

</body>
</html>