<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// User pre-fill logic
require_once dirname(__DIR__, 2) . '/helpers/RoleHelper.php';
$userId = RoleHelper::getUserId();

// Fallback session
$userNom    = $_SESSION['nom']    ?? $_SESSION['user_nom']    ?? '';
$userPrenom = $_SESSION['prenom'] ?? $_SESSION['user_prenom'] ?? '';
$userEmail  = $_SESSION['email']  ?? $_SESSION['user_email']  ?? '';
$userTel    = $_SESSION['telephone'] ?? $_SESSION['tel']      ?? '';

if ($userId > 0) {
    require_once dirname(__DIR__, 2) . '/connexion.php';
    try {
        $db = config::getConnexion();
        $stmt = $db->prepare("SELECT nom, prenom, email, telephone FROM user WHERE id_user = ?");
        $stmt->execute([$userId]);
        $uData = $stmt->fetch();
        if ($uData) {
            if (!empty($uData['nom']))       $userNom    = $uData['nom'];
            if (!empty($uData['prenom']))    $userPrenom = $uData['prenom'];
            if (!empty($uData['email']))     $userEmail  = $uData['email'];
            if (!empty($uData['telephone'])) $userTel    = $uData['telephone'];
        }
    } catch (Exception $e) {
        error_log("devis.php error: " . $e->getMessage());
    }
}

if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 3) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
$__base = defined('BASE_URL') ? BASE_URL : '';
?>
<script>const BASE_URL_PHP = '<?= $__base ?>';</script>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Demande de devis — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
    <style>
        :root {
            --devis-shadow: 0 18px 45px rgba(31, 63, 134, 0.10);
            --devis-soft-shadow: 0 10px 28px rgba(31, 63, 134, 0.06);
            --devis-ring: 0 0 0 3px rgba(255, 107, 26, 0.12);
            --devis-bg: #f6f8fc;
            --devis-card: rgba(255,255,255,0.96);
            --devis-card-soft: rgba(255,255,255,0.84);
            --devis-border: rgba(31, 63, 134, 0.10);
            --devis-text: #1f2f4a;
            --devis-muted: #6f7d95;
            --devis-blue: #1f3f86;
            --devis-blue-dark: #18336c;
            --devis-orange: #ff6b1a;
            --devis-orange-dark: #ef5d10;
            --devis-green: #16a36f;
            --devis-danger: #d94b4b;
        }

        body {
            background:
                radial-gradient(circle at top left, rgba(255, 107, 26, 0.08), transparent 24%),
                radial-gradient(circle at bottom right, rgba(66, 133, 244, 0.09), transparent 28%),
                linear-gradient(180deg, #f8fafc 0%, #eef3f9 100%);
        }

        .hero-banner {
            position: relative;
            overflow: hidden;
            padding: 34px;
            border-radius: 30px;
            background:
                radial-gradient(circle at 78% 18%, rgba(255,255,255,0.10), transparent 22%),
                radial-gradient(circle at 50% 110%, rgba(255,255,255,0.07), transparent 18%),
                linear-gradient(135deg, var(--devis-blue), var(--devis-blue-dark));
            border: 1px solid rgba(255,255,255,0.10);
            box-shadow: 0 24px 55px rgba(29, 53, 105, 0.16);
            margin-bottom: 28px;
        }

        .hero-banner::before {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            right: -40px;
            top: -38px;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
            pointer-events: none;
        }

        .hero-banner::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            left: 44%;
            bottom: -130px;
            border-radius: 50%;
            background: rgba(255,255,255,0.07);
            pointer-events: none;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.3fr .9fr;
            gap: 24px;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-family: var(--font-display);
            font-size: clamp(34px, 3.4vw, 58px);
            line-height: 1.02;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 16px;
            letter-spacing: -0.03em;
            max-width: 760px;
        }

        .hero-title .accent {
            color: #ffb07e;
        }

        .hero-sub {
            color: rgba(255,255,255,0.84);
            max-width: 780px;
            font-size: 15px;
            line-height: 1.95;
            margin-bottom: 22px;
        }

        .hero-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 16px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.14);
            background: rgba(255,255,255,0.08);
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
        }

        .hero-badge i {
            color: #73ff97;
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: 16px;
        }

        .hero-stat-card {
            padding: 22px 20px;
            border-radius: 24px;
            border: 1px solid rgba(255,255,255,0.10);
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(6px);
        }

        .hero-stat-value {
            font-size: 34px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 8px;
        }

        .hero-stat-label {
            color: rgba(255,255,255,0.74);
            font-size: 12px;
            line-height: 1.7;
            font-weight: 600;
        }

        .client-shell {
            display: grid;
            grid-template-columns: 1.15fr .85fr;
            gap: 22px;
            align-items: start;
        }

        .devis-card {
            background: var(--devis-card);
            border: 1px solid var(--devis-border);
            border-radius: 28px;
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            overflow: hidden;
            box-shadow: var(--devis-shadow);
        }

        .devis-card-header {
            padding: 24px 28px;
            border-bottom: 1px solid rgba(31, 63, 134, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            background: linear-gradient(180deg, rgba(255,255,255,0.82), rgba(255,255,255,0.68));
        }

        .devis-card-title {
            font-family: var(--font-display);
            font-size: 22px;
            font-weight: 800;
            color: var(--devis-text);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .devis-card-title i {
            color: var(--devis-orange);
        }

        .devis-card-sub {
            font-size: 13px;
            color: var(--devis-muted);
            margin-top: 6px;
            line-height: 1.7;
        }

        .step-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .step-pill {
            border: 1px solid rgba(31,63,134,0.12);
            background: #ffffff;
            color: var(--devis-muted);
            border-radius: 999px;
            padding: 11px 14px;
            font-size: 12px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all .25s ease;
            box-shadow: 0 6px 16px rgba(31, 63, 134, 0.06);
        }

        .step-pill.active {
            color: var(--devis-orange);
            background: rgba(255, 107, 26, 0.10);
            border-color: rgba(255, 107, 26, 0.22);
            box-shadow: none;
        }

        .step-pill.done {
            color: var(--devis-green);
            background: rgba(22, 163, 111, 0.10);
            border-color: rgba(22, 163, 111, 0.18);
            box-shadow: none;
        }

        .devis-card-body {
            padding: 28px;
        }

        .section-divider {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin: 8px 0 18px;
        }

        .section-divider .left {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            color: var(--devis-text);
            font-size: 16px;
        }

        .section-divider .left i {
            color: var(--devis-orange);
        }

        .section-divider .right {
            font-size: 12px;
            color: var(--devis-muted);
            font-weight: 600;
        }

        .devis-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: 16px;
            margin-bottom: 18px;
        }

        .devis-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0,1fr));
            gap: 16px;
            margin-bottom: 18px;
        }

        .devis-full {
            grid-column: 1 / -1;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .field label {
            color: var(--devis-text);
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .field label i {
            color: var(--devis-orange);
        }

        .field small {
            color: var(--devis-muted);
            font-size: 11px;
            line-height: 1.6;
        }

        .field input,
        .field textarea,
        .field select {
            width: 100%;
            padding: 15px 16px;
            border-radius: 18px;
            border: 1px solid rgba(31, 63, 134, 0.10);
            background: rgba(255,255,255,0.98);
            color: var(--devis-text);
            outline: none;
            font-size: 14px;
            transition: border-color .22s ease, box-shadow .22s ease, background .22s ease;
            box-shadow: 0 10px 24px rgba(31, 63, 134, 0.04);
        }

        .field input::placeholder,
        .field textarea::placeholder {
            color: #98a3b6;
        }

        .field input:focus,
        .field textarea:focus,
        .field select:focus {
            border-color: rgba(255, 107, 26, 0.35);
            box-shadow: var(--devis-ring);
            background: #ffffff;
        }

        .field select option {
            color: #111;
        }

        .field textarea {
            resize: vertical;
            min-height: 120px;
        }

        .field-error {
            color: var(--devis-danger);
            font-size: 12px;
            display: none;
            font-weight: 600;
        }

        .field-error.show {
            display: block;
        }

        .field input.error,
        .field textarea.error,
        .field select.error {
            border-color: rgba(217, 75, 75, 0.45);
            box-shadow: 0 0 0 3px rgba(217, 75, 75, 0.12);
        }

        .type-selector {
            display: grid;
            grid-template-columns: repeat(3, minmax(0,1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .type-card {
            position: relative;
            border-radius: 24px;
            padding: 22px;
            cursor: pointer;
            border: 1px solid rgba(31,63,134,0.10);
            background: rgba(255,255,255,0.98);
            transition: all .25s ease;
            overflow: hidden;
            box-shadow: 0 12px 26px rgba(31, 63, 134, 0.05);
        }

        .type-card:hover {
            transform: translateY(-2px);
            border-color: rgba(255,107,26,0.18);
        }

        .type-card.active {
            background: linear-gradient(135deg, rgba(255,107,26,0.08), rgba(255,255,255,0.96));
            border-color: rgba(255,107,26,0.22);
            box-shadow: 0 16px 32px rgba(255, 107, 26, 0.10);
        }

        .type-card-icon {
            width: 54px;
            height: 54px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            font-size: 22px;
            color: #fff;
            background: linear-gradient(135deg, var(--devis-orange), #ff9f66);
            margin-bottom: 14px;
            box-shadow: 0 14px 28px rgba(255, 107, 26, 0.16);
        }

        .type-card-title {
            color: var(--devis-text);
            font-size: 17px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .type-card-text {
            color: var(--devis-muted);
            font-size: 12px;
            line-height: 1.7;
        }

        .type-card input[type="radio"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            pointer-events: none;
        }

        .sub-form {
            display: none;
            margin-top: 6px;
            animation: fadeSlide .25s ease;
        }

        .sub-form.active {
            display: block;
        }

        @keyframes fadeSlide {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .info-banner {
            margin-bottom: 18px;
            padding: 18px;
            border-radius: 22px;
            border: 1px solid rgba(31,63,134,0.08);
            background: linear-gradient(135deg, rgba(255,255,255,0.92), rgba(246,248,252,0.92));
            display: flex;
            align-items: flex-start;
            gap: 12px;
            box-shadow: 0 12px 28px rgba(31, 63, 134, 0.04);
        }

        .info-banner i {
            font-size: 18px;
            color: var(--devis-orange);
            margin-top: 2px;
        }

        .info-banner strong {
            color: var(--devis-text);
            display: block;
            margin-bottom: 4px;
        }

        .info-banner span {
            color: var(--devis-muted);
            font-size: 13px;
            line-height: 1.75;
        }

        .summary-panel {
            position: sticky;
            top: 95px;
        }

        .summary-box {
            padding: 24px;
            background: linear-gradient(180deg, rgba(241,248,255,0.82), rgba(255,255,255,0.88));
        }

        .summary-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .summary-head-icon {
            width: 56px;
            height: 56px;
            border-radius: 20px;
            display: grid;
            place-items: center;
            font-size: 22px;
            color: #fff;
            background: linear-gradient(135deg, var(--devis-orange), #ff9b5f);
            box-shadow: 0 16px 28px rgba(255,107,26,0.18);
        }

        .summary-head-title {
            color: var(--devis-text);
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .summary-head-sub {
            color: var(--devis-muted);
            font-size: 12px;
        }

        .summary-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }

        .summary-item {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 16px;
            border: 1px solid rgba(31,63,134,0.07);
            background: rgba(255,255,255,0.78);
        }

        .summary-item .label {
            color: var(--devis-muted);
            font-size: 12px;
            line-height: 1.5;
            font-weight: 600;
        }

        .summary-item .value {
            color: var(--devis-text);
            font-size: 13px;
            font-weight: 800;
            text-align: right;
            line-height: 1.5;
        }

        .summary-note {
            padding: 16px;
            border-radius: 18px;
            border: 1px solid rgba(31,63,134,0.07);
            background: rgba(255,255,255,0.75);
            color: var(--devis-muted);
            font-size: 13px;
            line-height: 1.8;
            margin-bottom: 18px;
        }

        .summary-note strong {
            color: var(--devis-text);
        }

        .offer-chips {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .offer-chip {
            padding: 10px 12px;
            border-radius: 999px;
            background: #ffffff;
            border: 1px solid rgba(31,63,134,0.08);
            color: var(--devis-text);
            font-size: 12px;
            font-weight: 700;
        }

        .cta-stack {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn-client-primary,
        .btn-client-outline,
        .btn-client-soft {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 18px;
            border-radius: 16px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all .25s ease;
            font-size: 14px;
            font-weight: 800;
        }

        .btn-client-primary {
            background: linear-gradient(135deg, var(--devis-orange), var(--devis-orange-dark));
            color: #fff;
            box-shadow: 0 10px 24px rgba(255, 107, 26, 0.18);
        }

        .btn-client-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 30px rgba(255, 107, 26, 0.22);
        }

        .btn-client-outline {
            background: #ffffff;
            color: var(--devis-text);
            border: 1px solid rgba(31,63,134,0.10);
            box-shadow: 0 8px 18px rgba(31, 63, 134, 0.05);
        }

        .btn-client-outline:hover {
            border-color: rgba(255,107,26,0.24);
            color: var(--devis-orange);
            background: #ffffff;
        }

        .btn-client-soft {
            background: rgba(255,255,255,0.82);
            color: var(--devis-muted);
            border: 1px solid rgba(31,63,134,0.08);
        }

        .btn-client-soft:hover {
            color: var(--devis-text);
            background: #ffffff;
        }

        .faq-mini {
            margin-top: 22px;
            display: grid;
            gap: 12px;
        }

        .faq-item {
            border-radius: 18px;
            border: 1px solid rgba(31,63,134,0.07);
            background: rgba(255,255,255,0.75);
            overflow: hidden;
        }

        .faq-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            background: transparent;
            border: none;
            padding: 15px 16px;
            color: var(--devis-text);
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
        }

        .faq-content {
            display: none;
            padding: 0 16px 16px;
            color: var(--devis-muted);
            font-size: 13px;
            line-height: 1.75;
        }

        .faq-item.open .faq-content {
            display: block;
        }

        .timeline {
            margin-top: 20px;
            display: grid;
            gap: 14px;
        }

        .timeline-item {
            display: grid;
            grid-template-columns: 34px 1fr;
            gap: 12px;
            align-items: start;
        }

        .timeline-icon {
            width: 34px;
            height: 34px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 14px;
            color: #fff;
            background: linear-gradient(135deg, var(--devis-orange), #ff9b5f);
            border: 1px solid rgba(255,107,26,0.12);
        }

        .timeline-title {
            color: var(--devis-text);
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .timeline-text {
            color: var(--devis-muted);
            font-size: 12px;
            line-height: 1.65;
        }

        .captcha-box {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .captcha-display {
            min-width: 130px;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px dashed rgba(31,63,134,0.14);
            background: rgba(255,255,255,0.80);
            color: var(--devis-text);
            font-weight: 800;
            letter-spacing: .04em;
            text-align: center;
        }

        .success-modal {
            position: fixed;
            inset: 0;
            background: rgba(4, 10, 25, 0.48);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 9999;
        }

        .success-modal.open {
            display: flex;
        }

        .success-card {
            width: min(560px, 100%);
            background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(248,250,252,0.96));
            border: 1px solid rgba(31,63,134,0.08);
            border-radius: 28px;
            padding: 28px;
            box-shadow: var(--devis-shadow);
            text-align: center;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            border-radius: 24px;
            margin: 0 auto 18px;
            display: grid;
            place-items: center;
            font-size: 36px;
            color: #fff;
            background: linear-gradient(135deg, rgba(0, 200, 130, 0.95), rgba(0, 160, 110, 0.82));
            box-shadow: 0 18px 36px rgba(0, 200, 130, 0.16);
        }

        .success-title {
            font-family: var(--font-display);
            color: var(--devis-text);
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .success-text {
            color: var(--devis-muted);
            line-height: 1.8;
            font-size: 14px;
            margin-bottom: 22px;
        }

        .success-ref {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            border-radius: 999px;
            background: #ffffff;
            border: 1px solid rgba(31,63,134,0.08);
            color: var(--devis-text);
            font-weight: 800;
            margin-bottom: 18px;
        }

        .success-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .section-note {
            margin-top: 8px;
            color: var(--devis-muted);
            font-size: 12px;
            line-height: 1.7;
        }

        .mini-kpis {
            display: grid;
            grid-template-columns: repeat(3, minmax(0,1fr));
            gap: 14px;
            margin-top: 22px;
        }

        .mini-kpi {
            padding: 16px;
            border-radius: 20px;
            border: 1px solid rgba(31,63,134,0.08);
            background: rgba(255,255,255,0.86);
            box-shadow: 0 10px 22px rgba(31, 63, 134, 0.04);
        }

        .mini-kpi .value {
            color: var(--devis-text);
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .mini-kpi .label {
            color: var(--devis-muted);
            font-size: 12px;
            line-height: 1.7;
        }

        .offer-radio-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: 14px;
            margin-top: 12px;
        }

        .offer-radio {
            position: relative;
            border-radius: 22px;
            border: 1px solid rgba(31,63,134,0.08);
            background: rgba(255,255,255,0.97);
            padding: 18px;
            cursor: pointer;
            transition: all .22s ease;
            box-shadow: 0 12px 22px rgba(31, 63, 134, 0.04);
        }

        .offer-radio:hover {
            border-color: rgba(255,107,26,0.18);
            transform: translateY(-1px);
        }

        .offer-radio.active {
            border-color: rgba(255,107,26,0.22);
            background: linear-gradient(135deg, rgba(255,107,26,0.08), rgba(255,255,255,0.98));
            box-shadow: 0 16px 30px rgba(255, 107, 26, 0.08);
        }

        .offer-radio input[type="radio"] {
            position: absolute;
            opacity: 0;
            inset: 0;
        }

        .offer-radio-title {
            color: var(--devis-text);
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .offer-radio-meta {
            color: var(--devis-muted);
            font-size: 12px;
            line-height: 1.7;
            margin-bottom: 10px;
        }

        .offer-radio-price {
            color: var(--devis-text);
            font-size: 13px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 999px;
            background: rgba(255,255,255,0.94);
            border: 1px solid rgba(31,63,134,0.08);
        }

        .footer-help {
            margin-top: 24px;
            padding: 18px;
            border-radius: 22px;
            border: 1px dashed rgba(31,63,134,0.12);
            background: rgba(255,255,255,0.78);
            display: flex;
            align-items: flex-start;
            gap: 12px;
            color: var(--devis-muted);
            font-size: 13px;
            line-height: 1.8;
        }

        .footer-help i {
            color: var(--devis-orange);
            font-size: 18px;
            margin-top: 2px;
        }

        .toast-notif {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #ffffff;
            border: 1px solid rgba(31,63,134,0.10);
            border-radius: 14px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--devis-text);
            z-index: 9999;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
            box-shadow: 0 12px 28px rgba(31, 63, 134, 0.10);
        }

        .toast-notif.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast-success i { color: var(--devis-green); font-size: 18px; }
        .toast-warning i { color: #ff9c2b; font-size: 18px; }
        .toast-danger i { color: var(--devis-danger); font-size: 18px; }

        .spin {
            animation: spin .8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 1180px) {
            .client-shell,
            .hero-grid {
                grid-template-columns: 1fr;
            }

            .summary-panel {
                position: static;
            }
        }

        @media (max-width: 980px) {
            .type-selector,
            .offer-radio-list,
            .devis-grid-3,
            .mini-kpis,
            .hero-stats {
                grid-template-columns: 1fr;
            }

            .devis-grid-2 {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .hero-banner,
            .devis-card-body,
            .devis-card-header,
            .summary-box {
                padding: 18px;
            }
        }
    </style>
</head>
<body>

<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">
<?php require_once __DIR__.'/assets/includes/navbar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title-main">Demande de devis</div>
                <div class="page-breadcrumb">
                    <i class="bi bi-house"></i>
                    <a href="client.php" style="color:inherit;text-decoration:none;">Accueil</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <span>Demande de devis</span>
                    &nbsp;·&nbsp; <span id="now"></span>
                </div>
            </div>
        </div>

        <div class="content">
            <section class="hero-banner">
                <div class="hero-grid">
                    <div>
                        <div class="hero-title">Obtenez un <span class="accent">devis personnalisé</span> en quelques étapes</div>
                        <div class="hero-sub">
                            Sélectionnez votre type d’assurance, renseignez vos informations et recevez une estimation adaptée à votre situation. Notre équipe analyse votre demande puis vous répond avec une proposition claire, rapide et sur mesure.
                        </div>
                        <div class="hero-badges">
                            <span class="hero-badge"><i class="bi bi-check2-circle"></i> Demande 100% en ligne</span>
                            <span class="hero-badge"><i class="bi bi-lightning-charge"></i> Réponse rapide</span>
                            <span class="hero-badge"><i class="bi bi-shield-check"></i> Données sécurisées</span>
                        </div>
                    </div>
                    <div class="hero-stats">
                        <div class="hero-stat-card">
                            <div class="hero-stat-value">24h</div>
                            <div class="hero-stat-label">Délai moyen de réponse</div>
                        </div>
                        <div class="hero-stat-card">
                            <div class="hero-stat-value">3</div>
                            <div class="hero-stat-label">Types de devis disponibles</div>
                        </div>
                        <div class="hero-stat-card">
                            <div class="hero-stat-value">+2.5k</div>
                            <div class="hero-stat-label">Demandes traitées en ligne</div>
                        </div>
                        <div class="hero-stat-card">
                            <div class="hero-stat-value">98%</div>
                            <div class="hero-stat-label">Clients satisfaits</div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="client-shell">
                <section class="devis-card">
                    <div class="devis-card-header">
                        <div>
                            <div class="devis-card-title"><i class="bi bi-ui-checks-grid"></i> Formulaire de devis</div>
                            <div class="devis-card-sub">Complétez le formulaire selon le type d’assurance souhaité. Les champs changent automatiquement selon votre choix.</div>
                        </div>
                        <div class="step-pills" id="stepPills">
                            <span class="step-pill active" data-step="1"><i class="bi bi-1-circle"></i> Type</span>
                            <span class="step-pill" data-step="2"><i class="bi bi-2-circle"></i> Infos</span>
                            <span class="step-pill" data-step="3"><i class="bi bi-3-circle"></i> Détails</span>
                            <span class="step-pill" data-step="4"><i class="bi bi-4-circle"></i> Validation</span>
                        </div>
                    </div>

                    <div class="devis-card-body">
                        <div class="info-banner">
                            <i class="bi bi-info-circle"></i>
                            <div>
                                <strong>Avant de commencer</strong>
                                <span>Choisissez d’abord le type de devis. Les champs spécifiques s’afficheront automatiquement pour l’assurance auto, habitation ou santé. Les informations demandées servent uniquement à produire une estimation adaptée.</span>
                            </div>
                        </div>

                        <div class="section-divider">
                            <div class="left"><i class="bi bi-diagram-3"></i> 1. Choisissez votre type de devis</div>
                            <div class="right">Une seule sélection à la fois</div>
                        </div>

                        <form id="devisForm" novalidate>
                            <div class="type-selector">
                                <label class="type-card active" data-type="auto">
                                    <input type="radio" name="type_assurance" value="auto" checked>
                                    <div class="type-card-icon"><i class="bi bi-car-front"></i></div>
                                    <div class="type-card-title">Assurance auto</div>
                                    <div class="type-card-text">Demandez un devis lié à votre véhicule : marque, modèle, valeur, usage et année.</div>
                                </label>

                                <label class="type-card" data-type="habitation">
                                    <input type="radio" name="type_assurance" value="habitation">
                                    <div class="type-card-icon"><i class="bi bi-house-door"></i></div>
                                    <div class="type-card-title">Assurance habitation</div>
                                    <div class="type-card-text">Obtenez une estimation selon votre bien, sa superficie, son adresse et sa valeur.</div>
                                </label>

                                <label class="type-card" data-type="sante">
                                    <input type="radio" name="type_assurance" value="sante">
                                    <div class="type-card-icon"><i class="bi bi-heart-pulse"></i></div>
                                    <div class="type-card-title">Assurance santé</div>
                                    <div class="type-card-text">Renseignez votre profil santé, le nombre de bénéficiaires et le niveau de couverture souhaité.</div>
                                </label>
                            </div>

                            <div class="section-divider">
                                <div class="left"><i class="bi bi-person-vcard"></i> 2. Vos informations personnelles</div>
                                <div class="right">Ces informations sont obligatoires</div>
                            </div>

                            <div class="devis-grid-2">
                                <div class="field">
                                    <label for="nom"><i class="bi bi-person"></i> Nom *</label>
                                    <input type="text" id="nom" name="nom" placeholder="Ben Salah" value="<?= htmlspecialchars($userNom ?? '') ?>">
                                    <div class="field-error" id="err-nom">Le nom est obligatoire.</div>
                                </div>
                                <div class="field">
                                    <label for="prenom"><i class="bi bi-person"></i> Prénom *</label>
                                    <input type="text" id="prenom" name="prenom" placeholder="Ali" value="<?= htmlspecialchars($userPrenom ?? '') ?>">
                                    <div class="field-error" id="err-prenom">Le prénom est obligatoire.</div>
                                </div>
                            </div>

                            <div class="devis-grid-2">
                                <div class="field">
                                    <label for="email"><i class="bi bi-envelope"></i> Email *</label>
                                    <input type="email" id="email" name="email" placeholder="ali@gmail.com" value="<?= htmlspecialchars($userEmail ?? '') ?>">
                                    <div class="field-error" id="err-email">Veuillez saisir un email valide.</div>
                                </div>
                                <div class="field">
                                    <label for="telephone"><i class="bi bi-telephone"></i> Téléphone *</label>
                                    <input type="tel" id="telephone" name="telephone" placeholder="+216 22 111 111" value="<?= htmlspecialchars($userTel ?? '') ?>">
                                    <div class="field-error" id="err-telephone">Le téléphone est obligatoire.</div>
                                </div>
                            </div>

                            <div class="devis-grid-2">
                                <div class="field">
                                    <label for="entreprise"><i class="bi bi-building"></i> Entreprise</label>
                                    <input type="text" id="entreprise" name="entreprise" placeholder="Nom entreprise (optionnel)">
                                    <small>Utile si votre demande concerne une couverture professionnelle.</small>
                                </div>
                                <div class="field">
                                    <label for="fonction"><i class="bi bi-briefcase"></i> Fonction</label>
                                    <input type="text" id="fonction" name="fonction" placeholder="Votre fonction (optionnel)">
                                    <small>Ce champ est facultatif mais peut aider à personnaliser la réponse.</small>
                                </div>
                            </div>

                            <div class="section-divider">
                                <div class="left"><i class="bi bi-stars"></i> 3. Choisissez l’offre concernée</div>
                                <div class="right">Les offres changent selon le type choisi</div>
                            </div>

                            <div class="offer-radio-list" id="offerList"></div>
                            <div class="field-error" id="err-offre" style="margin-top:8px;">Veuillez sélectionner une offre.</div>

                            <div class="section-divider" style="margin-top:22px;">
                                <div class="left"><i class="bi bi-sliders2"></i> 4. Détails spécifiques à votre demande</div>
                                <div class="right">Section dynamique</div>
                            </div>

                            <div class="sub-form active" id="form-auto">
                                <div class="info-banner">
                                    <i class="bi bi-car-front"></i>
                                    <div>
                                        <strong>Informations véhicule</strong>
                                        <span>Plus les informations du véhicule sont précises, plus l’estimation fournie sera cohérente avec votre profil de conduite et la valeur réelle du véhicule.</span>
                                    </div>
                                </div>

                                <div class="devis-grid-3">
                                    <div class="field">
                                        <label for="auto_marque">Marque *</label>
                                        <input type="text" id="auto_marque" placeholder="Peugeot">
                                        <div class="field-error" id="err-auto_marque">La marque est obligatoire.</div>
                                    </div>
                                    <div class="field">
                                        <label for="auto_modele">Modèle *</label>
                                        <input type="text" id="auto_modele" placeholder="208">
                                        <div class="field-error" id="err-auto_modele">Le modèle est obligatoire.</div>
                                    </div>
                                    <div class="field">
                                        <label for="auto_annee">Année *</label>
                                        <input type="number" id="auto_annee" placeholder="2021">
                                        <div class="field-error" id="err-auto_annee">L’année est obligatoire.</div>
                                    </div>
                                </div>

                                <div class="devis-grid-3">
                                    <div class="field">
                                        <label for="auto_immat">Immatriculation</label>
                                        <input type="text" id="auto_immat" placeholder="123 TUN 456">
                                    </div>
                                    <div class="field">
                                        <label for="auto_pf">Puissance fiscale</label>
                                        <input type="number" id="auto_pf" placeholder="5">
                                    </div>
                                    <div class="field">
                                        <label for="auto_carburant">Carburant</label>
                                        <select id="auto_carburant">
                                            <option value="Essence">Essence</option>
                                            <option value="Diesel">Diesel</option>
                                            <option value="Hybride">Hybride</option>
                                            <option value="Électrique">Électrique</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="devis-grid-2">
                                    <div class="field">
                                        <label for="auto_valeur">Valeur estimée du véhicule</label>
                                        <input type="number" id="auto_valeur" placeholder="45000">
                                    </div>
                                    <div class="field">
                                        <label for="auto_usage">Usage du véhicule</label>
                                        <select id="auto_usage">
                                            <option value="Personnel">Personnel</option>
                                            <option value="Professionnel">Professionnel</option>
                                            <option value="Mixte">Mixte</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="sub-form" id="form-habitation">
                                <div class="info-banner">
                                    <i class="bi bi-house-door"></i>
                                    <div>
                                        <strong>Informations habitation</strong>
                                        <span>Décrivez votre bien afin de calculer une proposition adaptée à sa localisation, sa taille, sa valeur et votre statut d’occupation.</span>
                                    </div>
                                </div>

                                <div class="devis-grid-2">
                                    <div class="field">
                                        <label for="hab_type">Type d’habitation *</label>
                                        <select id="hab_type">
                                            <option value="Appartement">Appartement</option>
                                            <option value="Maison">Maison</option>
                                            <option value="Villa">Villa</option>
                                            <option value="Studio">Studio</option>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label for="hab_occupation">Statut d’occupation</label>
                                        <select id="hab_occupation">
                                            <option value="Propriétaire">Propriétaire</option>
                                            <option value="Locataire">Locataire</option>
                                            <option value="Occupant">Occupant</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="devis-grid-2">
                                    <div class="field devis-full">
                                        <label for="hab_adresse">Adresse du bien *</label>
                                        <input type="text" id="hab_adresse" placeholder="Résidence, rue, ville, gouvernorat">
                                        <div class="field-error" id="err-hab_adresse">L’adresse est obligatoire.</div>
                                    </div>
                                </div>

                                <div class="devis-grid-3">
                                    <div class="field">
                                        <label for="hab_superficie">Superficie (m²)</label>
                                        <input type="number" id="hab_superficie" placeholder="120">
                                    </div>
                                    <div class="field">
                                        <label for="hab_pieces">Nombre de pièces</label>
                                        <input type="number" id="hab_pieces" placeholder="4">
                                    </div>
                                    <div class="field">
                                        <label for="hab_valeur">Valeur estimée du bien</label>
                                        <input type="number" id="hab_valeur" placeholder="180000">
                                    </div>
                                </div>
                            </div>

                            <div class="sub-form" id="form-sante">
                                <div class="info-banner">
                                    <i class="bi bi-heart-pulse"></i>
                                    <div>
                                        <strong>Informations santé</strong>
                                        <span>Déclarez votre situation avec transparence afin d’obtenir une estimation cohérente avec le niveau de couverture souhaité et la composition de votre foyer.</span>
                                    </div>
                                </div>

                                <div class="devis-grid-3">
                                    <div class="field">
                                        <label for="sante_age">Âge *</label>
                                        <input type="number" id="sante_age" placeholder="35">
                                        <div class="field-error" id="err-sante_age">L’âge est obligatoire.</div>
                                    </div>
                                    <div class="field">
                                        <label for="sante_situation">Situation familiale</label>
                                        <select id="sante_situation">
                                            <option value="Célibataire">Célibataire</option>
                                            <option value="Marié(e)">Marié(e)</option>
                                            <option value="Divorcé(e)">Divorcé(e)</option>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label for="sante_beneficiaires">Nombre de bénéficiaires</label>
                                        <input type="number" id="sante_beneficiaires" value="1" min="1">
                                    </div>
                                </div>

                                <div class="devis-grid-2">
                                    <div class="field">
                                        <label for="sante_profession">Profession</label>
                                        <input type="text" id="sante_profession" placeholder="Ingénieur, commerçant, médecin...">
                                    </div>
                                    <div class="field">
                                        <label for="sante_couverture">Couverture souhaitée</label>
                                        <input type="text" id="sante_couverture" placeholder="Hospitalisation, consultations, dentaire...">
                                    </div>
                                </div>

                                <div class="devis-grid-2">
                                    <div class="field devis-full">
                                        <label for="sante_antecedents">Antécédents médicaux</label>
                                        <textarea id="sante_antecedents" placeholder="Indiquez toute information utile pour mieux orienter l’analyse de votre demande."></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="section-divider" style="margin-top:22px;">
                                <div class="left"><i class="bi bi-chat-left-text"></i> 5. Votre message</div>
                                <div class="right">Expliquez votre besoin</div>
                            </div>

                            <div class="devis-grid-2">
                                <div class="field devis-full">
                                    <label for="objet">Objet de la demande *</label>
                                    <input type="text" id="objet" placeholder="Exemple : Demande de devis pour assurance auto familiale">
                                    <div class="field-error" id="err-objet">L’objet est obligatoire.</div>
                                </div>
                            </div>

                            <div class="devis-grid-2">
                                <div class="field devis-full">
                                    <label for="message">Message *</label>
                                    <textarea id="message" placeholder="Décrivez brièvement votre besoin, vos attentes, les garanties souhaitées ou tout détail important pour obtenir une meilleure estimation."></textarea>
                                    <div class="field-error" id="err-message">Le message est obligatoire.</div>
                                </div>
                            </div>

                            <div class="section-divider" style="margin-top:22px;">
                                <div class="left"><i class="bi bi-shield-lock"></i> 6. Vérification</div>
                                <div class="right">Anti-spam</div>
                            </div>

                            <div class="devis-grid-2">
                                <div class="field">
                                    <label for="captcha_input">Combien font <span id="captchaQuestion">3 + 7</span> ? *</label>
                                    <div class="captcha-box">
                                        <div class="captcha-display" id="captchaDisplay">3 + 7</div>
                                        <button type="button" class="btn-client-soft" id="regenCaptcha">
                                            <i class="bi bi-arrow-clockwise"></i> Régénérer
                                        </button>
                                    </div>
                                    <input type="number" id="captcha_input" placeholder="Votre réponse" style="margin-top:10px;">
                                    <div class="field-error" id="err-captcha">La vérification est incorrecte.</div>
                                </div>
                                <div class="field">
                                    <label><i class="bi bi-check2-square"></i> Confirmation *</label>
                                    <div style="padding:14px 16px;border-radius:16px;border:1px solid rgba(31,63,134,0.08);background:rgba(255,255,255,0.82);display:flex;gap:10px;align-items:flex-start;">
                                        <input type="checkbox" id="consent" style="margin-top:2px;width:18px;height:18px;">
                                        <div style="color:var(--devis-muted);font-size:13px;line-height:1.7;">
                                            J’accepte que mes informations soient utilisées pour l’étude de ma demande de devis et la prise de contact par l’équipe Protex.
                                        </div>
                                    </div>
                                    <div class="field-error" id="err-consent">Vous devez accepter avant l’envoi.</div>
                                </div>
                            </div>

                            <div class="mini-kpis">
                                <div class="mini-kpi">
                                    <div class="value">Auto</div>
                                    <div class="label">Formulaire spécifique véhicule avec marque, modèle, année et valeur.</div>
                                </div>
                                <div class="mini-kpi">
                                    <div class="value">Habitation</div>
                                    <div class="label">Évaluation basée sur la surface, l’adresse et la valeur du bien.</div>
                                </div>
                                <div class="mini-kpi">
                                    <div class="value">Santé</div>
                                    <div class="label">Estimation ajustée selon l’âge, la situation familiale et la couverture.</div>
                                </div>
                            </div>

                            <div class="footer-help">
                                <i class="bi bi-info-circle"></i>
                                <div>
                                    Les champs marqués d’un astérisque sont obligatoires. Après l’envoi, votre demande sera enregistrée puis analysée. Vous pourrez ensuite recevoir une réponse avec le montant estimé ou une demande d’informations complémentaires.
                                </div>
                            </div>
                        </form>
                    </div>
                </section>

                <aside class="devis-card summary-panel">
                    <div class="summary-box">
                        <div class="summary-head">
                            <div class="summary-head-icon"><i class="bi bi-clipboard2-check"></i></div>
                            <div>
                                <div class="summary-head-title">Résumé de votre demande</div>
                                <div class="summary-head-sub">Mis à jour en temps réel pendant la saisie</div>
                            </div>
                        </div>

                        <div class="summary-list" id="summaryList">
                            <div class="summary-item">
                                <div class="label">Type sélectionné</div>
                                <div class="value" id="sumType">Auto</div>
                            </div>
                            <div class="summary-item">
                                <div class="label">Offre choisie</div>
                                <div class="value" id="sumOffre">Auto Protect Plus</div>
                            </div>
                            <div class="summary-item">
                                <div class="label">Client</div>
                                <div class="value" id="sumClient">—</div>
                            </div>
                            <div class="summary-item">
                                <div class="label">Contact</div>
                                <div class="value" id="sumContact">—</div>
                            </div>
                            <div class="summary-item">
                                <div class="label">Objet</div>
                                <div class="value" id="sumObjet">—</div>
                            </div>
                        </div>

                        <div class="summary-note">
                            <strong>Conseil Protex :</strong>
                            plus votre formulaire est détaillé, plus notre retour sera précis. Pensez à vérifier votre email et votre numéro avant l’envoi.
                        </div>

                        <div class="offer-chips" id="sumOfferChips">
                            <span class="offer-chip"><i class="bi bi-stars"></i> Estimation personnalisée</span>
                            <span class="offer-chip"><i class="bi bi-clock-history"></i> Analyse rapide</span>
                        </div>

                        <div class="cta-stack" style="margin-top:20px;">
                            <button class="btn-client-primary" id="submitBtn">
                                <i class="bi bi-send"></i> Envoyer ma demande de devis
                            </button>
                            <button type="button" class="btn-client-outline" id="previewBtn">
                                <i class="bi bi-eye"></i> Prévisualiser les données
                            </button>
                            <button type="button" class="btn-client-soft" id="resetBtn">
                                <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser le formulaire
                            </button>
                        </div>

                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-icon"><i class="bi bi-1-circle"></i></div>
                                <div>
                                    <div class="timeline-title">Vous envoyez votre demande</div>
                                    <div class="timeline-text">Le formulaire est vérifié puis enregistré dans le système.</div>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-icon"><i class="bi bi-2-circle"></i></div>
                                <div>
                                    <div class="timeline-title">Protex analyse votre dossier</div>
                                    <div class="timeline-text">Un agent examine les données du devis selon le type d’assurance sélectionné.</div>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-icon"><i class="bi bi-3-circle"></i></div>
                                <div>
                                    <div class="timeline-title">Vous recevez une réponse</div>
                                    <div class="timeline-text">Un montant estimé et une réponse personnalisée vous sont communiqués.</div>
                                </div>
                            </div>
                        </div>

                        <div class="faq-mini">
                            <div class="faq-item open">
                                <button class="faq-btn" type="button">
                                    <span>Un devis engage-t-il le client ?</span>
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                                <div class="faq-content">Non. Le devis est une estimation. Il permet de connaître un tarif avant toute souscription définitive.</div>
                            </div>
                            <div class="faq-item">
                                <button class="faq-btn" type="button">
                                    <span>En combien de temps vais-je recevoir une réponse ?</span>
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                                <div class="faq-content">Le délai moyen est de 24 à 48 heures selon la complexité de la demande et le type d’assurance choisi.</div>
                            </div>
                            <div class="faq-item">
                                <button class="faq-btn" type="button">
                                    <span>Puis-je demander plusieurs devis ?</span>
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                                <div class="faq-content">Oui. Vous pouvez envoyer plusieurs demandes si vous souhaitez comparer différents profils ou plusieurs types d’assurance.</div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>
</div>

<div class="success-modal" id="successModal">
    <div class="success-card">
        <div class="success-icon"><i class="bi bi-check2-circle"></i></div>
        <div class="success-title">Demande envoyée</div>
        <div class="success-text">
            Votre demande de devis a bien été enregistrée. Notre équipe va l’analyser puis vous recontacter avec une estimation adaptée à votre profil.
        </div>
        <div class="success-ref" id="successRef">
            <i class="bi bi-upc-scan"></i> Référence : DEV-2026-0001
        </div>
        <div class="success-actions">
            <button class="btn-client-primary" id="closeSuccess">
                <i class="bi bi-check2"></i> Continuer
            </button>
            <a href="client.php" class="btn-client-outline">
                <i class="bi bi-house"></i> Retour à l’accueil
            </a>
        </div>
    </div>
</div>

<script src="assets/js/main.js"></script>
<script>
    const offersByType = {
        auto: [
            { id: 1, title: 'Auto Protect Plus', desc: 'Couverture essentielle pour votre véhicule avec assistance standard et gestion simplifiée.', price: 'À partir de 850 DT/an' },
            { id: 2, title: 'Auto Premium Pro', desc: 'Protection renforcée pour les conducteurs recherchant plus de garanties et une meilleure prise en charge.', price: 'À partir de 1100 DT/an' }
        ],
        habitation: [
            { id: 3, title: 'Habitation Secure', desc: 'Protection habitation de base contre les principaux risques du quotidien.', price: 'À partir de 620 DT/an' },
            { id: 4, title: 'Maison Premium', desc: 'Formule complète pour biens de grande valeur avec couverture étendue.', price: 'À partir de 940 DT/an' }
        ],
        sante: [
            { id: 5, title: 'Santé Essentielle', desc: 'Couverture santé pensée pour les besoins courants et un budget maîtrisé.', price: 'À partir de 720 DT/an' },
            { id: 6, title: 'Santé Famille Plus', desc: 'Protection santé plus large avec prise en charge familiale renforcée.', price: 'À partir de 1350 DT/an' }
        ]
    };

    const typeLabels = {
        auto: 'Auto',
        habitation: 'Habitation',
        sante: 'Santé'
    };

    let captchaA = 3;
    let captchaB = 7;
    let currentType = 'auto';
    let selectedOfferId = 1;
    let devisCounter = 1;

    document.addEventListener('DOMContentLoaded', function () {
        initDate();
        initFaq();
        initTypeSelector();
        renderOffers();
        bindInputs();
        refreshSummary();
        generateCaptcha();

        document.getElementById('regenCaptcha').addEventListener('click', generateCaptcha);
        document.getElementById('previewBtn').addEventListener('click', previewData);
        document.getElementById('resetBtn').addEventListener('click', resetForm);
        document.getElementById('submitBtn').addEventListener('click', submitForm);
        document.getElementById('closeSuccess').addEventListener('click', function () {
            document.getElementById('successModal').classList.remove('open');
        });
    });

    function initDate() {
        const now = new Date();
        const dateStr = now.toLocaleDateString('fr-FR', { day:'numeric', month:'long', year:'numeric' });
        const el = document.getElementById('now');
        if (el) el.textContent = dateStr;
    }

    function initFaq() {
        document.querySelectorAll('.faq-item').forEach(item => {
            const btn = item.querySelector('.faq-btn');
            btn.addEventListener('click', function () {
                item.classList.toggle('open');
            });
        });
    }

    function initTypeSelector() {
        document.querySelectorAll('.type-card').forEach(card => {
            card.addEventListener('click', function () {
                document.querySelectorAll('.type-card').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                currentType = this.dataset.type;
                this.querySelector('input').checked = true;
                switchFormByType();
                renderOffers();
                refreshSummary();
                setStepState(2);
            });
        });
    }

    function switchFormByType() {
        document.querySelectorAll('.sub-form').forEach(form => form.classList.remove('active'));
        const activeForm = document.getElementById(`form-${currentType}`);
        if (activeForm) activeForm.classList.add('active');
        clearSpecificErrors();
    }

    function renderOffers() {
        const offerList = document.getElementById('offerList');
        const items = offersByType[currentType] || [];
        if (!items.length) {
            offerList.innerHTML = '<div class="section-note">Aucune offre disponible pour ce type.</div>';
            return;
        }

        if (!items.some(item => item.id === selectedOfferId)) {
            selectedOfferId = items[0].id;
        }

        offerList.innerHTML = items.map(item => `
            <label class="offer-radio ${item.id === selectedOfferId ? 'active' : ''}" data-offer="${item.id}">
                <input type="radio" name="offer_choice" value="${item.id}" ${item.id === selectedOfferId ? 'checked' : ''}>
                <div class="offer-radio-title">${item.title}</div>
                <div class="offer-radio-meta">${item.desc}</div>
                <div class="offer-radio-price"><i class="bi bi-cash-coin"></i> ${item.price}</div>
            </label>
        `).join('');

        document.querySelectorAll('.offer-radio').forEach(card => {
            card.addEventListener('click', function () {
                document.querySelectorAll('.offer-radio').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                selectedOfferId = Number(this.dataset.offer);
                this.querySelector('input').checked = true;
                document.getElementById('err-offre').classList.remove('show');
                refreshSummary();
                setStepState(3);
            });
        });
    }

    function bindInputs() {
        const ids = [
            'nom','prenom','email','telephone','entreprise','fonction','objet','message',
            'auto_marque','auto_modele','auto_annee','auto_immat','auto_pf','auto_valeur',
            'hab_adresse','hab_superficie','hab_pieces','hab_valeur',
            'sante_age','sante_beneficiaires','sante_profession','sante_couverture','sante_antecedents',
            'captcha_input','consent'
        ];

        ids.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            const evt = el.type === 'checkbox' ? 'change' : 'input';
            el.addEventListener(evt, function () {
                if (this.classList.contains('error')) this.classList.remove('error');
                const err = document.getElementById(`err-${id}`);
                if (err) err.classList.remove('show');
                refreshSummary();
                updateStepCompletion();
            });
        });

        ['auto_carburant','auto_usage','hab_type','hab_occupation','sante_situation'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('change', refreshSummary);
        });
    }

    function refreshSummary() {
        document.getElementById('sumType').textContent = typeLabels[currentType];
        document.getElementById('sumOffre').textContent = getSelectedOffer() ? getSelectedOffer().title : '—';

        const nom = document.getElementById('nom').value.trim();
        const prenom = document.getElementById('prenom').value.trim();
        const email = document.getElementById('email').value.trim();
        const tel = document.getElementById('telephone').value.trim();
        const objet = document.getElementById('objet').value.trim();

        document.getElementById('sumClient').textContent = (prenom || nom) ? `${prenom} ${nom}`.trim() : '—';
        document.getElementById('sumContact').textContent = (email || tel) ? [email, tel].filter(Boolean).join(' · ') : '—';
        document.getElementById('sumObjet').textContent = objet || '—';

        const chips = document.getElementById('sumOfferChips');
        const offer = getSelectedOffer();
        chips.innerHTML = '';
        if (offer) {
            chips.innerHTML += `<span class="offer-chip"><i class="bi bi-stars"></i> ${offer.title}</span>`;
            chips.innerHTML += `<span class="offer-chip"><i class="bi bi-cash-coin"></i> ${offer.price}</span>`;
        }
        chips.innerHTML += `<span class="offer-chip"><i class="bi bi-${iconByType(currentType)}"></i> ${typeLabels[currentType]}</span>`;
    }

    function getSelectedOffer() {
        return (offersByType[currentType] || []).find(item => item.id === selectedOfferId) || null;
    }

    function iconByType(type) {
        if (type === 'auto') return 'car-front';
        if (type === 'habitation') return 'house-door';
        return 'heart-pulse';
    }

    function generateCaptcha() {
        captchaA = Math.floor(Math.random() * 10) + 1;
        captchaB = Math.floor(Math.random() * 10) + 1;
        document.getElementById('captchaQuestion').textContent = `${captchaA} + ${captchaB}`;
        document.getElementById('captchaDisplay').textContent = `${captchaA} + ${captchaB}`;
    }

    function setStepState(stepNumber) {
        document.querySelectorAll('.step-pill').forEach((pill, index) => {
            const step = index + 1;
            pill.classList.remove('active');
            if (step < stepNumber) pill.classList.add('done');
            else pill.classList.remove('done');
            if (step === stepNumber) pill.classList.add('active');
        });
    }

    function updateStepCompletion() {
        const basicOk = document.getElementById('nom').value.trim() && document.getElementById('prenom').value.trim();
        const contactOk = validateEmail(document.getElementById('email').value.trim()) && document.getElementById('telephone').value.trim();
        const offerOk = !!getSelectedOffer();
        const detailsOk = checkSpecificRequired(false);

        if (basicOk && contactOk && offerOk && detailsOk) setStepState(4);
        else if (basicOk && contactOk && offerOk) setStepState(3);
        else if (basicOk && contactOk) setStepState(2);
        else setStepState(1);
    }

    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function showError(inputId, show) {
        const input = document.getElementById(inputId);
        const error = document.getElementById(`err-${inputId}`);
        if (input) input.classList.toggle('error', show);
        if (error) error.classList.toggle('show', show);
    }

    function clearSpecificErrors() {
        ['auto_marque','auto_modele','auto_annee','hab_adresse','sante_age'].forEach(id => showError(id, false));
    }

    function checkSpecificRequired(showErrors = true) {
        let ok = true;

        if (currentType === 'auto') {
            const marque = document.getElementById('auto_marque').value.trim();
            const modele = document.getElementById('auto_modele').value.trim();
            const annee = document.getElementById('auto_annee').value.trim();
            if (!marque) { if (showErrors) showError('auto_marque', true); ok = false; } else if (showErrors) showError('auto_marque', false);
            if (!modele) { if (showErrors) showError('auto_modele', true); ok = false; } else if (showErrors) showError('auto_modele', false);
            if (!annee) { if (showErrors) showError('auto_annee', true); ok = false; } else if (showErrors) showError('auto_annee', false);
        }

        if (currentType === 'habitation') {
            const adresse = document.getElementById('hab_adresse').value.trim();
            if (!adresse) { if (showErrors) showError('hab_adresse', true); ok = false; } else if (showErrors) showError('hab_adresse', false);
        }

        if (currentType === 'sante') {
            const age = document.getElementById('sante_age').value.trim();
            if (!age) { if (showErrors) showError('sante_age', true); ok = false; } else if (showErrors) showError('sante_age', false);
        }

        return ok;
    }

    function validateForm() {
        let valid = true;
        const nom = document.getElementById('nom').value.trim();
        const prenom = document.getElementById('prenom').value.trim();
        const email = document.getElementById('email').value.trim();
        const telephone = document.getElementById('telephone').value.trim();
        const objet = document.getElementById('objet').value.trim();
        const message = document.getElementById('message').value.trim();
        const captchaInput = document.getElementById('captcha_input').value.trim();
        const consent = document.getElementById('consent').checked;

        if (!nom) { showError('nom', true); valid = false; } else showError('nom', false);
        if (!prenom) { showError('prenom', true); valid = false; } else showError('prenom', false);
        if (!validateEmail(email)) { showError('email', true); valid = false; } else showError('email', false);
        if (!telephone) { showError('telephone', true); valid = false; } else showError('telephone', false);
        if (!objet) { showError('objet', true); valid = false; } else showError('objet', false);
        if (!message) { showError('message', true); valid = false; } else showError('message', false);

        if (!getSelectedOffer()) {
            document.getElementById('err-offre').classList.add('show');
            valid = false;
        } else {
            document.getElementById('err-offre').classList.remove('show');
        }

        if (!checkSpecificRequired(true)) valid = false;

        if (Number(captchaInput) !== captchaA + captchaB) {
            document.getElementById('captcha_input').classList.add('error');
            document.getElementById('err-captcha').classList.add('show');
            valid = false;
        } else {
            document.getElementById('captcha_input').classList.remove('error');
            document.getElementById('err-captcha').classList.remove('show');
        }

        if (!consent) {
            document.getElementById('err-consent').classList.add('show');
            valid = false;
        } else {
            document.getElementById('err-consent').classList.remove('show');
        }

        if (!valid) showToast('Veuillez corriger les champs en erreur avant l’envoi.', 'warning');
        return valid;
    }

    function previewData() {
        refreshSummary();
        showToast('Le résumé a été mis à jour avec vos données actuelles.', 'success');
    }

    function submitForm() {
        if (!validateForm()) return;

        const btn = document.getElementById('submitBtn');
        btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Envoi en cours...';
        btn.disabled = true;

        const payload = {
            id_offre:       selectedOfferId,
            nom:            document.getElementById('nom').value.trim(),
            prenom:         document.getElementById('prenom').value.trim(),
            email:          document.getElementById('email').value.trim(),
            telephone:      document.getElementById('telephone').value.trim(),
            type_assurance: currentType,
            // Auto
            marque:            document.getElementById('auto_marque')?.value.trim(),
            modele:            document.getElementById('auto_modele')?.value.trim(),
            annee:             document.getElementById('auto_annee')?.value,
            immatriculation:   document.getElementById('auto_immat')?.value.trim(),
            puissance: document.getElementById('auto_pf')?.value,
            carburant:         document.getElementById('auto_carburant')?.value,
            valeur_vehicule:   document.getElementById('auto_valeur')?.value,
            usage_vehicule:    document.getElementById('auto_usage')?.value,
            // Habitation
            type_habitation:   document.getElementById('hab_type')?.value,
            adresse:           document.getElementById('hab_adresse')?.value.trim(),
            superficie:        document.getElementById('hab_superficie')?.value,
            nombre_pieces:     document.getElementById('hab_pieces')?.value,
            valeur_bien:       document.getElementById('hab_valeur')?.value,
            statut_occupation: document.getElementById('hab_occupation')?.value,
            // Santé
            age:                   document.getElementById('sante_age')?.value,
            situation_familiale:   document.getElementById('sante_situation')?.value,
            nombre_beneficiaires:  document.getElementById('sante_beneficiaires')?.value,
            antecedents_medicaux:  document.getElementById('sante_antecedents')?.value.trim(),
            couverture_souhaitee:  document.getElementById('sante_couverture')?.value.trim(),
            profession:            document.getElementById('sante_profession')?.value.trim(),
        };

        fetch(' + BASE_URL_PHP + '/api.php?action=devis_ajouter', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('successRef').innerHTML =
                    `<i class="bi bi-upc-scan"></i> Référence : ${data.reference}`;
                document.getElementById('successModal').classList.add('open');
                showToast('Demande envoyée avec succès.', 'success');
            } else {
                showToast('Erreur : ' + (data.error || 'Inconnue'), 'danger');
            }
        })
        .catch(() => showToast('Erreur réseau.', 'danger'))
        .finally(() => {
            btn.innerHTML = '<i class="bi bi-send"></i> Envoyer ma demande de devis';
            btn.disabled = false;
        });
    }

    function resetForm() {
        document.getElementById('devisForm').reset();
        currentType = 'auto';
        selectedOfferId = 1;

        document.querySelectorAll('.type-card').forEach(card => {
            card.classList.toggle('active', card.dataset.type === 'auto');
            card.querySelector('input').checked = card.dataset.type === 'auto';
        });

        switchFormByType();
        renderOffers();
        generateCaptcha();
        document.querySelectorAll('.field-error').forEach(el => el.classList.remove('show'));
        document.querySelectorAll('.field input, .field textarea, .field select').forEach(el => el.classList.remove('error'));
        document.getElementById('captcha_input').classList.remove('error');
        refreshSummary();
        setStepState(1);
        showToast('Le formulaire a été réinitialisé.', 'warning');
    }

    function showToast(message, type = 'success') {
        const icons = { success: 'check-circle', warning: 'exclamation-triangle', danger: 'x-circle' };
        const toast = document.createElement('div');
        toast.className = `toast-notif toast-${type}`;
        toast.innerHTML = `<i class="bi bi-${icons[type]}"></i><span>${message}</span>`;
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 40);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 2800);
    }
</script>
</body>
</html>


