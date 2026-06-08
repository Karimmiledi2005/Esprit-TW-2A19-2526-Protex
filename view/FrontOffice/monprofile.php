<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();
// FIX 3 — Empêche un client de voir le profil d'un autre client
$sessionUserId = (int)($_SESSION['user_id'] ?? $_SESSION['id_user'] ?? 0);
$requestedId   = isset($_GET['id_user']) ? (int)$_GET['id_user'] : $sessionUserId;
if ($requestedId !== 0 && $requestedId !== $sessionUserId) {
    http_response_code(403);
    die('<p style="font-family:sans-serif;text-align:center;padding:40px;color:#e63946;">Accès refusé : vous ne pouvez consulter que votre propre profil.</p>');
}
?><!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>Mon Profil é Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
    <link rel="stylesheet" href="assets/css/validation.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
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
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }

        .toast-notif.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast-success i {
            color: var(--success);
            font-size: 18px;
        }

        .toast-warning i {
            color: var(--gold);
            font-size: 18px;
        }

        .toast-danger i {
            color: var(--danger);
            font-size: 18px;
        }

        /* Barre de force mot de passe */
        .pwd-strength {
            height: 4px;
            border-radius: 4px;
            background: var(--glass-border);
            margin-top: 8px;
            overflow: hidden;
        }

        .pwd-strength-fill {
            height: 100%;
            border-radius: 4px;
            width: 0%;
            transition: width 0.3s ease, background 0.3s ease;
        }

        .pwd-strength-label {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        .card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            overflow: hidden;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-family: var(--font-display);
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .card-body {
            padding: 24px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: var(--radius-md);
            font-family: var(--font-body);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: #fff;
        }

        .btn-primary:hover {
            box-shadow: 0 4px 14px rgba(0, 180, 216, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--glass-border);
            color: var(--text-secondary);
        }

        .btn-outline:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-block {
            width: 100%;
            justify-content: center;
        }

        /* 3D Avatar Effect */
        .profile-avatar {
            perspective: 1000px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .avatar-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--glass-bg);
            border: 2px solid var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: var(--accent);
            transition: transform 0.5s cubic-bezier(0.23, 1, 0.32, 1);
            transform-style: preserve-3d;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .profile-avatar:hover .avatar-placeholder {
            transform: rotateY(15deg) rotateX(10deg) scale(1.05);
            box-shadow: -10px 15px 40px rgba(0, 180, 216, 0.2);
        }

        .contact-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        /* Local Profile Avatar (Main Card) */
        .profile-avatar-3d-main {
            width: 120px;
            height: 120px;
            position: relative;
            margin: 0 auto 20px;
            perspective: 1000px;
        }

        .profile-avatar-ring-main {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 2px dashed var(--ptx-orange);
            animation: ptx-ring-pulse-main 4s infinite ease-in-out;
        }

        .profile-avatar-inner-main {
            width: 100px;
            height: 100px;
            position: absolute;
            top: 10px;
            left: 10px;
            background: linear-gradient(135deg, var(--ptx-navy), #0a0f1e);
            border-radius: 50%;
            box-shadow: 0 0 30px rgba(255, 107, 26, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            animation: ptx-avatar-float-main 6s infinite ease-in-out;
            transform-style: preserve-3d;
        }

        .profile-avatar-inner-main img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .profile-avatar-3d-main:hover .profile-avatar-inner-main img {
            transform: scale(1.1) rotateY(10deg);
        }

        @keyframes ptx-avatar-float-main {
            0%, 100% { transform: translateY(0) rotateX(0); }
            50% { transform: translateY(-10px) rotateX(5deg); }
        }

        @keyframes ptx-ring-pulse-main {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 1; box-shadow: 0 0 20px var(--ptx-orange); }
        }
    </style>

    <!-- FrontOffice unifie - surcharge théme camarades dark-navy -->
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/light-theme.css"></head>

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
            <div class="page-header">
                <div>
                    <div class="page-title-main">Mon Profil</div>
                    <div class="page-breadcrumb">
                        <i class="bi bi-house"></i>
                        <a href="client.php" style="color:inherit;text-decoration:none;">Accueil</a>
                        <i class="bi bi-chevron-right" style="font-size:10px"></i>
                        <span>Mon profil</span>
                        &nbsp;é&nbsp; <span id="now"></span>
                    </div>
                </div>
            </div>

            <div class="content">

                <!-- ===== SECTION INFOS PERSONNELLES ===== -->
                <div class="section-header" style="margin-bottom:20px">
                    <div>
                        <div class="section-title">Informations personnelles</div>
                        <div class="section-sub">Gérez vos données personnelles</div>
                    </div>
                </div>

                <div class="grid-2">

                    <!-- CARTE PROFIL -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title"><i class="bi bi-person-circle"
                                    style="color:var(--accent);margin-right:8px"></i>Profil</div>
                        </div>
                        <div class="card-body">

                            <!-- Avatar upload -->
                            <div style="position:relative; width:120px; height:120px; margin:0 auto 24px;">
                                <!-- Preview for static image/upload -->
                                <div id="avatarPreview"
                                    style="width: 100%; height: 100%; border-radius: 50%; background: var(--glass-bg); border: 2px solid var(--accent); display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                </div>
                            </div>
                            <div id="ptx-badges"
                                style="text-align:center; margin-bottom:15px; display:flex; justify-content:center; gap:5px; flex-wrap:wrap;">
                                <!-- Injected by JS -->
                            </div>

                            <!-- Score de complétion profil -->
                            <div style="margin:0 0 20px; text-align:center;">
                                <div
                                    style="font-size:11px; text-transform:uppercase; color:var(--text-secondary); margin-bottom:12px; letter-spacing:1px;">
                                    Complétion du profil</div>
                                <div style="position:relative; width:90px; height:90px; margin:0 auto 8px;">
                                    <svg width="90" height="90" style="transform:rotate(-90deg)">
                                        <circle cx="45" cy="45" r="38" fill="none" stroke="rgba(255,255,255,0.08)"
                                            stroke-width="7" />
                                        <circle id="profileProgressCircle" cx="45" cy="45" r="38" fill="none"
                                            stroke="var(--accent)" stroke-width="7" stroke-dasharray="239"
                                            stroke-dashoffset="239"
                                            style="transition:stroke-dashoffset 1.5s ease; stroke-linecap:round;" />
                                    </svg>
                                    <div
                                        style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); text-align:center;">
                                        <div id="profileCompletionPct"
                                            style="font-size:18px; font-weight:900; color:var(--text-primary);">0%</div>
                                    </div>
                                </div>
                                <div id="completionTip" style="font-size:11px; color:var(--text-secondary);"></div>
                            </div>

                            <!-- Badges gamifiés -->
                            <div
                                style="margin-bottom:20px; border-top:1px solid rgba(255,255,255,0.06); padding-top:16px;">
                                <div
                                    style="font-size:11px; text-transform:uppercase; color:var(--text-secondary); margin-bottom:12px; letter-spacing:1px;">
                                    &#127942; Mes badges</div>
                                <div id="badgesContainer"
                                    style="display:flex; flex-wrap:wrap; gap:6px; justify-content:center;"></div>
                                <div
                                    style="margin-top:10px; height:4px; background:rgba(255,255,255,0.06); border-radius:2px; overflow:hidden;">
                                    <div id="pointsBar"
                                        style="height:100%; background:linear-gradient(90deg, var(--accent), #ffd700); border-radius:2px; width:0%; transition:width 1.5s ease;">
                                    </div>
                                </div>
                                <div style="display:flex; justify-content:space-between; margin-top:4px;">
                                    <div id="pointsLabel" style="font-size:11px; color:var(--text-secondary);">0 points
                                    </div>
                                    <div id="nextBadgeLabel" style="font-size:11px; color:var(--text-secondary);"></div>
                                </div>
                            </div>

                            <div class="file-input-wrap" style="text-align: center; margin-bottom: 24px;">
                                <label for="avatarInput" class="btn btn-outline btn-sm">
                                    <i class="bi bi-upload"></i> Changer la photo
                                </label>
                                <input type="file" id="avatarInput" accept="image/*" style="display:none;">
                            </div>
                            <!-- Formulaire -->
                            <div class="profile-info">
                                <div class="info-item">
                                    <label>Nom</label>
                                    <div id="field-nom" contenteditable="false" data-field="nom"
                                        onclick="makeInlineEditable(this)"
                                        style="padding: 10px 12px; border-radius: var(--radius-md); border: 1px solid var(--glass-border); background: var(--glass-bg); color: var(--text-primary); font-size: 14px; transition: var(--transition); cursor: pointer; min-height: 20px; width: 100%; box-sizing: border-box;"
                                        title="Cliquez pour modifier"></div>
                                    <div id="save-nom" style="display:none; font-size:11px; color:#2ed573;"></div>
                                </div>
                                <div class="info-item">
                                    <label>Prénom</label>
                                    <div id="field-prenom" contenteditable="false" data-field="prenom"
                                        onclick="makeInlineEditable(this)"
                                        style="padding: 10px 12px; border-radius: var(--radius-md); border: 1px solid var(--glass-border); background: var(--glass-bg); color: var(--text-primary); font-size: 14px; transition: var(--transition); cursor: pointer; min-height: 20px; width: 100%; box-sizing: border-box;"
                                        title="Cliquez pour modifier"></div>
                                    <div id="save-prenom" style="display:none; font-size:11px; color:#2ed573;"></div>
                                </div>
                                <div class="info-item">
                                    <label>Email</label>
                                    <input type="email" id="email" placeholder="Email">
                                </div>
                                <div class="info-item">
                                    <label>Téléphone</label>
                                    <div id="field-telephone" contenteditable="false" data-field="telephone"
                                        onclick="makeInlineEditable(this)"
                                        style="padding: 10px 12px; border-radius: var(--radius-md); border: 1px solid var(--glass-border); background: var(--glass-bg); color: var(--text-primary); font-size: 14px; transition: var(--transition); cursor: pointer; min-height: 20px; width: 100%; box-sizing: border-box;"
                                        title="Cliquez pour modifier"></div>
                                    <div id="save-telephone" style="display:none; font-size:11px; color:#2ed573;"></div>
                                </div>
                                <div class="info-item">
                                    <label>CIN</label>
                                    <input type="text" id="cin" readonly>
                                </div>
                                <div class="info-item">
                                    <label>Numéro client</label>
                                    <input type="text" id="numero_client" readonly
                                        style="color:var(--accent);font-weight:600;">
                                </div>
                                <div class="info-item">
                                    <label>Agence</label>
                                    <input type="text" id="nom_agence" readonly>
                                </div>
                                <div class="info-item">
                                    <label>Date de naissance</label>
                                    <input type="text" id="date_naissance" readonly>
                                </div>
                                <div class="info-item">
                                    <label>Adresse</label>
                                    <div id="field-adresse" contenteditable="false" data-field="adresse"
                                        onclick="makeInlineEditable(this)"
                                        style="padding: 10px 12px; border-radius: var(--radius-md); border: 1px solid var(--glass-border); background: var(--glass-bg); color: var(--text-primary); font-size: 14px; transition: var(--transition); cursor: pointer; min-height: 20px; width: 100%; box-sizing: border-box;"
                                        title="Cliquez pour modifier"></div>
                                    <div id="save-adresse" style="display:none; font-size:11px; color:#2ed573;"></div>
                                </div>
                            </div>

                            <button class="btn-save-profile" id="saveProfile">
                                <i class="bi bi-save"></i> Sauvegarder les modifications
                            </button>
                        </div>
                    </div>

                    <!-- CARTE SéCURITé -->
                    <div class="card" id="securite">
                        <div class="card-header">
                            <div class="card-title"><i class="bi bi-shield-lock"
                                    style="color:var(--accent);margin-right:8px"></i>Sécurité</div>
                        </div>
                        <div class="card-body">

                            <!-- Mot de passe -->
                            <div class="info-item" style="margin-bottom:4px">
                                <label>Ancien mot de passe</label>
                                <!-- autocomplete="new-password" évite que le navigateur ne remplisse automatiquement le champ avec le mot de passe enregistré -->
                                <input type="password" id="currentPassword"
                                    placeholder="Saisissez d'abord l'ancien mot de passe" autocomplete="new-password">
                            </div>

                            <div class="info-item" style="margin-bottom:4px">
                                <label>Nouveau mot de passe</label>
                                <div class="input-group">
                                    <input type="password" id="new-password" placeholder="********"
                                        oninput="checkStrength(this.value)" disabled
                                        style="opacity: 0.5; cursor: not-allowed;">
                                    <button type="button" class="btn btn-outline btn-sm" id="togglePwdBtn" disabled
                                        style="opacity: 0.5; cursor: not-allowed;">
                                        <i class="bi bi-eye" id="eyeIcon"></i>
                                    </button>
                                </div>
                                <div class="pwd-strength">
                                    <div class="pwd-strength-fill" id="strengthFill"></div>
                                </div>
                                <div class="pwd-strength-label" id="strengthLabel">En attente de l'ancien mot de
                                    passe...</div>
                            </div>

                            <div class="info-item" style="margin-bottom:20px">
                                <label>Confirmer le mot de passe</label>
                                <input type="password" id="confirm-new-password" placeholder="********"
                                    disabled style="opacity: 0.5; cursor: not-allowed;">
                            </div>

                            <button class="btn-save-profile" id="savePwd" style="margin-bottom:24px">
                                <i class="bi bi-key"></i> Changer le mot de passe
                            </button>

                            <!-- Séparateur -->
                            <div style="border-top:1px solid var(--glass-border); padding-top:20px; margin-bottom:16px">
                                <div class="section-title" style="font-size:13px; margin-bottom:12px">Options de
                                    sécurité</div>
                            </div>

                            <!-- Toggles sécurité -->
                            <div class="toggle-row">
                                <div>
                                    <div class="toggle-label">Double authentification (2FA)</div>
                                    <div class="toggle-desc">SMS envoyé é +216 20 *** ***</div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="toggle2fa" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="toggle-row">
                                <div>
                                    <div class="toggle-label">Alertes de connexion</div>
                                    <div class="toggle-desc">Notification par email é chaque connexion</div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="toggleAlerts" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <!-- Séparateur -->
                            <div style="border-top:1px solid var(--glass-border); padding-top:20px; margin-top:8px">
                                <div class="section-title" style="font-size:13px; margin-bottom:12px">Sessions actives
                                </div>
                            </div>

                            <!-- Sessions -->
                            <div class="session-item">
                                <div class="session-icon"><i class="bi bi-laptop"></i></div>
                                <div class="session-info">
                                    <div class="session-name">Chrome é Windows 11</div>
                                    <div class="session-meta">Tunis é Maintenant</div>
                                </div>
                                <span class="session-current">Actuel</span>
                            </div>

                            <div class="session-item">
                                <div class="session-icon"><i class="bi bi-phone"></i></div>
                                <div class="session-info">
                                    <div class="session-name">Safari é iPhone</div>
                                    <div class="session-meta">Tunis é Il y a 2h</div>
                                </div>
                                <button class="btn btn-outline btn-sm"
                                    style="color:var(--danger);border-color:var(--danger)"
                                    onclick="window.showToast && showToast('Session déconnectée','warning')">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>

                            <div class="info-item" style="margin-top:16px">
                                <label>Derniére connexion</label>
                                <span style="font-size:14px;color:var(--text-primary)"
                                    data-field="last-login">---</span>
                            </div>

                        </div>
                    </div>

                    <!-- CARTE FACE ID -->
                    <div class="card" style="margin-top:20px;">
                        <div class="card-header">
                            <div class="card-title"><i class="bi bi-person-bounding-box"
                                    style="color:var(--accent);margin-right:8px"></i>Face ID (IA Locale)</div>
                        </div>
                        <div class="card-body">
                            <div class="info-item" style="margin-bottom:16px">
                                <p style="font-size:13px; color:var(--text-secondary)">Connectez-vous rapidement et en
                                    toute sécurité gréce é la reconnaissance faciale propulsée par Intelligence
                                    Artificielle locale.</p>
                            </div>
                            <div id="faceIdStatus"
                                style="margin-bottom: 20px; font-size: 14px; font-weight: 500; color: var(--text-primary);">
                                Statut : <span id="faceIdState" style="color:var(--warning)">Inconnu</span>
                            </div>
                            <button class="btn btn-primary btn-block" id="setupFaceIdBtn">
                                <i class="bi bi-camera"></i> Configurer mon Face ID
                            </button>
                            <button class="btn btn-block" id="unregisterFaceIdBtn"
                                style="margin-top:8px; background:rgba(255,71,87,0.1); color:#ff4757; border:1px solid rgba(255,71,87,0.3); display:none;">
                                <i class="bi bi-trash"></i> Désactiver Face ID
                            </button>
                        </div>
                    </div>


                    <!-- CARTE PARRAINAGE -->
                    <div class="ref-card">
                        <div style="display:flex; align-items:center; gap:12px; margin-bottom:15px;">
                            <div
                                style="width:45px; height:45px; border-radius:12px; background:var(--ptx-orange); display:flex; align-items:center; justify-content:center; color:#fff; font-size:22px; box-shadow: 0 4px 15px rgba(255, 107, 26, 0.3);">
                                <i class="bi bi-gift-fill"></i>
                            </div>
                            <div>
                                <div class="ref-title">Parrainage</div>
                                <div class="ref-subtitle">Gagnez des points en invitant vos amis</div>
                            </div>
                        </div>
                        <div style="font-size:15px; color:var(--text-primary); margin-bottom:15px; font-weight:500;">
                            Votre score : <span id="refPoints" class="ref-points-val">0 pts</span>
                        </div>
                        <div style="font-size:12px; color:rgba(255,255,255,0.9); margin-bottom:8px; font-weight:600;">
                            VOTRE LIEN UNIQUE :</div>
                        <div class="ref-link-box">
                            <span id="refLink" style="letter-spacing:1px; font-weight:700;">PRTX-LOADING...</span>
                            <i class="bi bi-clipboard-fill" style="cursor:pointer; font-size:18px;" onclick="copyRef()"
                                title="Copier le code"></i>
                        </div>
                    </div>

                    <!-- ===== CARTE HISTORIQUE DES CONNEXIONS (U5) ===== -->
                    <div class="card" style="margin-top:20px;">
                        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
                            <div class="card-title"><i class="bi bi-clock-history" style="color:var(--accent);margin-right:8px;"></i>Historique des connexions</div>
                            <button id="btnLogoutAll" class="btn btn-outline btn-sm" style="color:#ff4757;border-color:rgba(255,71,87,0.5);font-size:11px;">
                                <i class="bi bi-box-arrow-left"></i> Déconnecter tout
                            </button>
                        </div>
                        <div class="card-body" style="padding:0;">
                            <div id="loginHistoryList" style="max-height:320px;overflow-y:auto;">
                                <div style="padding:20px;text-align:center;font-size:12px;color:var(--text-secondary);"><i class="bi bi-arrow-repeat spin"></i> Chargement...</div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== CARTE PRÉFÉRENCES NOTIFICATIONS (U7) ===== -->
                    <div class="card" style="margin-top:20px; background:linear-gradient(135deg,rgba(0,180,216,0.06),rgba(0,180,216,0.02));">
                        <div class="card-header">
                            <div class="card-title"><i class="bi bi-bell-fill" style="color:var(--accent);margin-right:8px;"></i>Préférences de notifications</div>
                        </div>
                        <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;gap:16px;">
                            <p style="font-size:13px;color:var(--text-secondary);margin:0;">Gérez vos canaux de notification : email, SMS et alertes in-app par catégorie.</p>
                            <a href="parametres_notifications.php" class="btn btn-primary btn-sm" style="white-space:nowrap;">
                                <i class="bi bi-sliders"></i> Configurer
                            </a>
                        </div>
                    </div>

                    <!-- CARTE DANGER ZONE -->
                    <div class="card"
                        style="margin-top:20px; border:1px solid rgba(255,71,87,0.3); background:rgba(255,71,87,0.05);">
                        <div class="card-header" style="border-bottom:1px solid rgba(255,71,87,0.2);">
                            <div class="card-title" style="color:#ff4757;"><i class="bi bi-exclamation-triangle"
                                    style="margin-right:8px"></i>Zone de danger</div>
                        </div>
                        <div class="card-body">
                            <p style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">Une fois votre
                                compte désactivé, vous ne pourrez plus accéder à vos contrats et services Protex. Cette
                                action est réversible par un administrateur.</p>
                            <button class="btn"
                                style="background:#ff4757; color:#fff; width:100%; justify-content:center;"
                                id="btn-deactivate">
                                Désactiver mon compte
                            </button>
                        </div>
                    </div>

                </div>



            </div>
        </main>
    </div>

    </div>
    </div>

    <!-- Chat Modal enrichi -->
    <div class="chat-modal" id="chatModal">
        <div class="chat-header"
            style="display:flex; align-items:center; gap:12px; padding:14px 18px; background:rgba(0,0,0,0.3); border-bottom:1px solid rgba(255,255,255,0.08);">
            <div style="position:relative;">
                <div id="chatAvatar"
                    style="width:36px; height:36px; border-radius:50%; background:rgba(255,255,255,0.1); overflow:hidden;">
                </div>
                <div id="chatOnlineDot"
                    style="position:absolute; bottom:0; right:0; width:10px; height:10px; background:#2ed573; border:2px solid #0a0f1e; border-radius:50%; display:none;">
                </div>
            </div>
            <div style="flex:1;">
                <div id="chatName" style="color:var(--text-primary); font-weight:700; font-size:14px;">Chat</div>
                <div id="chatSubtitle" style="font-size:11px; color:var(--text-secondary);">Hors ligne</div>
            </div>
            <i class="bi bi-x-lg" style="cursor:pointer; color:var(--text-secondary); font-size:18px;"
                onclick="closeChat()"></i>
        </div>
        <div class="chat-messages" id="chatMessages"></div>
        <!-- Barre emojis rapides -->
        <div id="emojiBar"
            style="padding:6px 12px; display:flex; gap:6px; border-top:1px solid rgba(255,255,255,0.05); background:rgba(0,0,0,0.2);">
            <button onclick="insertEmoji('??')"
                style="background:none; border:none; font-size:18px; cursor:pointer; padding:4px; border-radius:6px; transition:0.2s;"
                onmouseover="this.style.background='rgba(255,255,255,0.1)'"
                onmouseout="this.style.background='none'">??</button>
            <button onclick="insertEmoji('??')"
                style="background:none; border:none; font-size:18px; cursor:pointer; padding:4px; border-radius:6px; transition:0.2s;"
                onmouseover="this.style.background='rgba(255,255,255,0.1)'"
                onmouseout="this.style.background='none'">??</button>
            <button onclick="insertEmoji('??')"
                style="background:none; border:none; font-size:18px; cursor:pointer; padding:4px; border-radius:6px; transition:0.2s;"
                onmouseover="this.style.background='rgba(255,255,255,0.1)'"
                onmouseout="this.style.background='none'">??</button>
            <button onclick="insertEmoji('??')"
                style="background:none; border:none; font-size:18px; cursor:pointer; padding:4px; border-radius:6px; transition:0.2s;"
                onmouseover="this.style.background='rgba(255,255,255,0.1)'"
                onmouseout="this.style.background='none'">??</button>
            <button onclick="insertEmoji('??')"
                style="background:none; border:none; font-size:18px; cursor:pointer; padding:4px; border-radius:6px; transition:0.2s;"
                onmouseover="this.style.background='rgba(255,255,255,0.1)'"
                onmouseout="this.style.background='none'">??</button>
            <button onclick="insertEmoji('?')"
                style="background:none; border:none; font-size:18px; cursor:pointer; padding:4px; border-radius:6px; transition:0.2s;"
                onmouseover="this.style.background='rgba(255,255,255,0.1)'"
                onmouseout="this.style.background='none'">?</button>
        </div>
        <div class="chat-input-area">
            <input type="text" class="chat-input" id="chatInput" placeholder="Votre message..."
                oninput="handleTyping()">
            <button class="btn btn-primary btn-sm" onclick="sendChat()"><i class="bi bi-send"></i></button>
        </div>
    </div>

    <!-- Modale SOS améliorée -->
    <div id="sosModal"
        style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(0,0,0,0.9); backdrop-filter:blur(10px); align-items:center; justify-content:center;">
        <div
            style="background:linear-gradient(135deg,#1a0505,#2d0a0a); border:2px solid #ff4757; border-radius:24px; padding:40px 32px; max-width:440px; width:90%; text-align:center; box-shadow:0 0 80px rgba(255,71,87,0.3);">
            <div style="font-size:64px; margin-bottom:16px; animation:sos-pulse 1s infinite;">??</div>
            <div style="font-size:24px; font-weight:900; color:#ff4757; margin-bottom:8px; letter-spacing:1px;">ALERTE
                SOS SINISTRE</div>
            <div style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">Vos contacts de confiance
                seront alertés immédiatement avec votre position GPS.</div>

            <div id="sosTrustedList"
                style="background:rgba(255,71,87,0.08); border:1px solid rgba(255,71,87,0.2); border-radius:12px; padding:12px; margin-bottom:16px; text-align:left;">
            </div>

            <div id="sosCountdownZone" style="margin-bottom:20px;">
                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:8px;">Envoi automatique dans :
                </div>
                <div style="font-size:48px; font-weight:900; color:#ff4757;" id="sosCountdownNum">5</div>
                <button onclick="cancelSOSCountdown()"
                    style="background:none; border:1px solid rgba(255,255,255,0.2); color:var(--text-secondary); border-radius:8px; padding:6px 16px; font-size:12px; cursor:pointer; margin-top:8px;">?
                    Annuler le compte é rebours</button>
            </div>

            <div id="sosGPSStatus" style="font-size:12px; color:var(--text-secondary); margin-bottom:20px;">??
                Récupération de la position...</div>

            <div style="display:flex; gap:12px;">
                <button id="sosConfirmBtn" onclick="confirmSOS()"
                    style="flex:2; background:#ff4757; color:#fff; border:none; border-radius:14px; padding:16px; font-weight:900; font-size:16px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:10px; letter-spacing:1px;">
                    <i class="bi bi-exclamation-octagon-fill"></i> ENVOYER MAINTENANT
                </button>
                <button onclick="cancelSOS()"
                    style="flex:1; background:rgba(255,255,255,0.08); color:rgba(255,255,255,0.7); border:1px solid rgba(255,255,255,0.15); border-radius:14px; padding:16px; font-weight:600; cursor:pointer;">Annuler</button>
            </div>
        </div>
    </div>

    <button class="sos-floating" onclick="triggerSOS()">
        <i class="bi bi-exclamation-octagon-fill"></i> SOS
    </button>

    <!-- Modale Configuration Face ID -->
    <div id="faceIdSetupModal" class="setup-modal">
        <div class="setup-card">
            <button class="close-setup-btn" id="closeSetupFaceIdBtn">&times;</button>
            <div id="setupScannerContainer" class="setup-scanner">
                <video id="setupFaceVideo" autoplay playsinline></video>
                <div id="setupProgressRing" class="setup-progress-ring"></div>
            </div>
            <div class="setup-title">SCAN FACIAL</div>
            <div id="setupFaceStatus" class="setup-status">Prét pour l'analyse...</div>
            <div class="setup-progress-bar-container">
                <div id="setupProgressBar" class="setup-progress-bar"></div>
            </div>
        </div>
    </div>

    <style>
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.92);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .setup-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(10, 15, 30, 0.85);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .setup-modal.show {
            display: flex;
            opacity: 1;
        }

        .setup-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 35px 30px;
            width: 320px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            position: relative;
            transform: translateY(20px);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .setup-modal.show .setup-card {
            transform: translateY(0);
        }

        .setup-scanner {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            margin: 0 auto 20px auto;
            position: relative;
            overflow: hidden;
            border: 3px solid rgba(0, 180, 216, 0.2);
            box-shadow: 0 0 30px rgba(0, 180, 216, 0.1);
            transition: all 0.3s;
        }

        .setup-scanner video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
        }

        .setup-progress-ring {
            position: absolute;
            top: -3px;
            left: -3px;
            right: -3px;
            bottom: -3px;
            border-radius: 50%;
            border: 3px solid transparent;
            border-top-color: #00b4d8;
            border-right-color: #00b4d8;
            animation: spin 1.5s linear infinite;
            z-index: 5;
            display: none;
        }

        @keyframes spin {
            100% {
                transform: rotate(360deg);
            }
        }

        .setup-title {
            font-family: 'Space Mono', monospace;
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .setup-status {
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            color: #a0aabf;
        }

        .setup-progress-bar-container {
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            margin-top: 15px;
            overflow: hidden;
        }

        .setup-progress-bar {
            height: 100%;
            width: 0%;
            background: #00b4d8;
            transition: width 0.35s ease;
            box-shadow: 0 0 10px #00b4d8;
        }

        /* ===== ADVANCED SOCIAL CSS ===== */
        .sos-btn-pulse {
            background: #ff4757;
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            width: 100%;
            margin-top: 15px;
            box-shadow: 0 0 0 0 rgba(255, 71, 87, 0.7);
            animation: sos-pulse 2s infinite;
        }

        @keyframes sos-pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(255, 71, 87, 0.7);
            }

            70% {
                transform: scale(1.02);
                box-shadow: 0 0 0 15px rgba(255, 71, 87, 0);
            }

            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(255, 71, 87, 0);
            }
        }

        .chat-modal {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 350px;
            height: 450px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            z-index: 10000;
            display: none;
            flex-direction: column;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .chat-header {
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: rgba(0, 0, 0, 0.1);
        }

        .msg {
            padding: 10px 14px;
            border-radius: 14px;
            max-width: 80%;
            font-size: 13px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .msg.sent {
            align-self: flex-end;
            background: var(--ptx-orange);
            color: #fff;
            font-weight: 500;
        }

        .msg.received {
            align-self: flex-start;
            background: rgba(26, 58, 122, 0.08);
            color: var(--text-primary);
            border: 1px solid var(--glass-border);
        }

        .chat-input-area {
            padding: 15px;
            display: flex;
            gap: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .chat-input {
            flex: 1;
            background: rgba(26, 58, 122, 0.05);
            border: 1px solid rgba(26, 58, 122, 0.1);
            border-radius: 8px;
            color: var(--text-primary);
            padding: 8px 12px;
        }

        .chat-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            background: rgba(255, 107, 26, 0.25);
            color: #fff;
            border: 1px solid rgba(255, 107, 26, 0.4);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .ref-card {
            background: linear-gradient(135deg, rgba(10, 20, 40, 0.8), rgba(26, 58, 122, 0.6));
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 24px;
            margin-top: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }

        .ref-title {
            color: #fff;
            font-weight: 700;
            font-size: 18px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }

        .ref-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
        }

        .ref-link-box {
            background: rgba(0, 0, 0, 0.4);
            border: 1px dashed var(--accent);
            padding: 12px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: 'Space Mono', monospace;
            font-size: 14px;
            color: var(--accent);
            margin-top: 15px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .ref-points-val {
            font-size: 20px;
            font-weight: 800;
            color: var(--ptx-orange);
            text-shadow: 0 0 10px rgba(255, 107, 26, 0.4);
        }


        .close-setup-btn {
            position: absolute;
            top: 15px;
            right: 20px;
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.5);
            font-size: 28px;
            cursor: pointer;
            transition: 0.3s;
            line-height: 1;
        }

        .close-setup-btn:hover {
            color: #ff4757;
            transform: scale(1.1);
        }


        [contenteditable] {
            color: var(--text-primary) !important;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border) !important;
            border-radius: var(--radius-md) !important;
            padding: 10px 12px !important;
            transition: var(--transition) !important;
        }

        [contenteditable]:hover {
            border-color: rgba(255, 107, 26, 0.4) !important;
        }

        [contenteditable]:focus {
            border-color: var(--accent) !important;
            box-shadow: 0 0 8px var(--accent-glow) !important;
            outline: none !important;
            background: #ffffff !important;
        }

        [contenteditable]:empty::before {
            content: attr(title);
            color: var(--text-secondary);
            font-style: italic;
            font-size: 13px;
        }

        .info-item input {
            color: var(--text-primary);
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            width: 100%;
        }

        .info-item input[readonly] {
            color: var(--text-secondary);
            opacity: 0.8;
            cursor: not-allowed;
        }

        .info-item label {
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 600;
        }
    </style>

    <!-- JS global -->
    <!-- legacy script removed -->

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // --- NOUVELLES FONCTIONS : INLINE EDIT & GAMIFICATION ---
            window.makeInlineEditable = function (el) {
                if (el.contentEditable === "true") return;
                const field = el.dataset.field;
                const originalValue = el.innerText;
                el.contentEditable = "true";
                el.style.border = "1px solid var(--accent)";
                el.style.background = "rgba(255,255,255,0.05)";
                el.focus();

                const save = () => {
                    const newValue = el.innerText.trim();
                    el.contentEditable = "false";
                    el.style.border = "1px solid transparent";
                    el.style.background = "transparent";
                    if (newValue === originalValue) return;

                    fetch('update_user.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ field: field, value: newValue })
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                const msg = document.getElementById('save-' + field);
                                if (msg) {
                                    msg.textContent = "? Sauvegardé";
                                    msg.style.display = "block";
                                    setTimeout(() => msg.style.display = "none", 2000);
                                }
                                updateCompletionScore();
                            } else {
                                el.innerText = originalValue;
                                showToast(data.message || "Erreur", "danger");
                            }
                        });
                };

                el.onblur = save;
                el.onkeydown = (e) => { if (e.key === 'Enter') { e.preventDefault(); el.blur(); } };
            };

            // ── U6 : Score de complétion profil étendu (5 critères × 20%) ──
            let _completionAlreadyAwarded = false;

            window.updateCompletionScore = function () {
                let filled = 0;
                const total = 5;

                // 1. Champs éditables (nom, prenom, telephone, adresse)
                ['nom', 'prenom', 'telephone', 'adresse'].forEach(f => {
                    const el = document.getElementById('field-' + f);
                    if (el && el.innerText.trim().length > 2) filled++;
                });
                // 5. CIN renseigné
                const cinEl = document.getElementById('cin');
                if (cinEl && cinEl.value && cinEl.value.trim().length > 3) filled++;

                const pct = Math.round((filled / total) * 100);
                const circle = document.getElementById('profileProgressCircle');
                if (circle) {
                    const offset = 239 - (239 * pct / 100);
                    circle.style.strokeDashoffset = offset;
                    // Couleur dynamique
                    circle.style.stroke = pct >= 100 ? '#2ed573' : (pct >= 60 ? 'var(--accent)' : '#ffa502');
                }
                const pctEl = document.getElementById('profileCompletionPct');
                if (pctEl) pctEl.textContent = pct + '%';

                const tip = document.getElementById('completionTip');
                if (tip) {
                    if (pct < 100) {
                        const missing = [];
                        const nEl = document.getElementById('field-nom');
                        const prEl = document.getElementById('field-prenom');
                        const tEl = document.getElementById('field-telephone');
                        const aEl = document.getElementById('field-adresse');
                        if (!nEl || nEl.innerText.trim().length <= 2) missing.push('Nom');
                        if (!prEl || prEl.innerText.trim().length <= 2) missing.push('Prénom');
                        if (!tEl || tEl.innerText.trim().length <= 2) missing.push('Téléphone');
                        if (!aEl || aEl.innerText.trim().length <= 2) missing.push('Adresse');
                        if (!cinEl || cinEl.value.trim().length <= 3) missing.push('CIN');
                        tip.textContent = 'Manquant : ' + missing.join(', ');
                    } else {
                        tip.innerHTML = '🎉 Profil 100% complété !';
                        // Déclencher confetti + attribuer points (une seule fois par session)
                        if (!_completionAlreadyAwarded) {
                            _completionAlreadyAwarded = true;
                            launchConfetti();
                            fetch('../../api.php?action=award_completion_points')
                                .then(r => r.json())
                                .then(res => {
                                    if (res.success) showToast('🏆 ' + res.message, 'success');
                                })
                                .catch(() => {});
                        }
                    }
                }
            };

            window.calculateBadges = function (data) {
                const container = document.getElementById('badgesContainer');
                if (!container) return;

                const pts = parseInt(data.points_parrainage || 0);
                const badges = [
                    { name: 'Nouveau', icon: '??', min: 0, color: '#a0aabf' },
                    { name: 'Actif', icon: '??', min: 50, color: '#ff6b1a' },
                    { name: 'Ambassadeur', icon: '?', min: 100, color: '#ffd700' },
                    { name: 'Protecteur', icon: '???', min: 250, color: '#2ed573' },
                    { name: 'Légende', icon: '??', min: 500, color: '#00b4d8' }
                ];

                let html = '';
                let currentBadge = badges[0];
                let nextBadge = badges[1];

                badges.forEach((b, i) => {
                    const active = pts >= b.min;
                    if (active) {
                        currentBadge = b;
                        nextBadge = badges[i + 1] || null;
                    }
                    html += `
                        <div title="${b.name} (${b.min} pts)" style="width:34px; height:34px; border-radius:50%; background:${active ? b.color + '22' : 'rgba(255,255,255,0.03)'}; border:1px solid ${active ? b.color + '44' : 'rgba(255,255,255,0.05)'}; display:flex; align-items:center; justify-content:center; font-size:16px; filter:${active ? 'none' : 'grayscale(1) opacity(0.3)'}; transition:0.3s;">
                            ${b.icon}
                        </div>`;
                });
                container.innerHTML = html;

                // Update Points Bar
                const pointsLabel = document.getElementById('pointsLabel');
                if (pointsLabel) pointsLabel.textContent = pts + ' points';

                const pointsBar = document.getElementById('pointsBar');
                if (pointsBar && nextBadge) {
                    const range = nextBadge.min - currentBadge.min;
                    const progress = pts - currentBadge.min;
                    const barPct = Math.min(100, Math.round((progress / range) * 100));
                    pointsBar.style.width = barPct + '%';

                    const nextLabel = document.getElementById('nextBadgeLabel');
                    if (nextLabel) nextLabel.textContent = `Prochain : ${nextBadge.name} (${nextBadge.min} pts)`;
                }
            };

            // ===== DATE BREADCRUMB =====
            const now = new Date();
            const dateStr = now.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
            const el = document.getElementById('currentDate');
            if (el) el.textContent = dateStr;

            // ===== TOGGLE MOT DE PASSE =====
            const togglePwdBtn = document.getElementById('togglePwdBtn');
            const pwd = document.getElementById('new-password');
            const eyeIcon = document.getElementById('eyeIcon');

            if (togglePwdBtn && pwd) {
                togglePwdBtn.addEventListener('click', function () {
                    if (pwd.type === 'password') {
                        pwd.type = 'text';
                        eyeIcon.className = 'bi bi-eye-slash';
                    } else {
                        pwd.type = 'password';
                        eyeIcon.className = 'bi bi-eye';
                    }
                });
            }

            // ===== FORCE MOT DE PASSE =====
            window.checkStrength = function (val) {
                const fill = document.getElementById('strengthFill');
                const label = document.getElementById('strengthLabel');
                if (!fill || !label) return;

                let score = 0;
                if (val.length >= 8) score++;
                if (/[A-Z]/.test(val)) score++;
                if (/[0-9]/.test(val)) score++;
                if (/[^A-Za-z0-9]/.test(val)) score++;

                const configs = [
                    { w: '0%', c: '', t: 'Entrez un nouveau mot de passe' },
                    { w: '25%', c: 'var(--danger)', t: 'Faible' },
                    { w: '50%', c: 'var(--gold)', t: 'Moyen' },
                    { w: '75%', c: 'var(--warning)', t: 'Bon' },
                    { w: '100%', c: 'var(--success)', t: 'Excellent' },
                ];
                const cfg = val.length === 0 ? configs[0] : configs[score] || configs[1];
                fill.style.width = cfg.w;
                fill.style.background = cfg.c;
                label.textContent = cfg.t;
                label.style.color = cfg.c || 'var(--text-secondary)';
            };

            // ===== UPLOAD AVATAR =====
            const avatarInput = document.getElementById('avatarInput');
            const avatarPreview = document.getElementById('avatarPreview');
            const fileNameDisplay = document.querySelector('.file-name');

            if (avatarInput && avatarPreview) {
                avatarInput.addEventListener('change', function (e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    if (file.size > 2 * 1024 * 1024) {
                        showToast('Fichier trop volumineux. Maximum 2MB', 'danger');
                        avatarInput.value = '';
                        return;
                    }

                    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!allowedTypes.includes(file.type)) {
                        showToast('Format non autorisé. Utilisez JPG, PNG, GIF ou WebP', 'danger');
                        avatarInput.value = '';
                        return;
                    }

                    // Prévisualisation
                    const reader = new FileReader();
                    reader.onload = function (ev) {
                        avatarPreview.innerHTML = `<img src="${ev.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">`;
                    };
                    reader.readAsDataURL(file);

                    if (fileNameDisplay) fileNameDisplay.textContent = file.name;

                    // Upload vers le serveur
                    const formData = new FormData();
                    formData.append('avatar', file);

                    fetch('upload_avatar.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showToast('Avatar mis à jour', 'success');
                                loadProfile();
                                if (window.ProtexUser) {
                                    window.ProtexUser.refresh();
                                }
                            } else {
                                showToast(data.message || 'Erreur lors de l\'upload', 'danger');
                                loadProfile();
                            }
                        })
                        .catch(() => {
                            showToast('Erreur réseau', 'danger');
                            loadProfile();
                        });
                });
            }

            // ===== CHARGEMENT DU PROFIL DEPUIS LA DB =====
            function loadProfile() {
                fetch('get_user.php')
                    .then(res => {
                        if (res.status === 401) {
                            window.location.href = 'login.html';
                            return null;
                        }
                        return res.json();
                    })
                    .then(data => {
                        if (!data || data.error) return;
                        
                        // Extract user data from the new JSON format
                        if (data.success && data.user) {
                            data = data.user;
                        }

                        const setField = (id, val) => {
                            const el = document.getElementById('field-' + id);
                            if (el) el.innerText = val || 'Non renseigné';
                        };
                        setField('nom', data.nom);
                        setField('prenom', data.prenom);
                        setField('telephone', data.telephone);
                        setField('adresse', data.adresse);

                        const set = (id, val) => { const el = document.getElementById(id); if (el && val !== null && val !== undefined) el.value = val; };
                        set('email', data.email);
                        set('cin', data.cin);
                        set('numero_client', data.numero_client);
                        set('nom_agence', data.nom_agence || 'Non renseigné');
                        set('date_naissance', data.date_naissance_formatted || data.date_naissance);

                        updateCompletionScore();

                        // Parrainage & Badges
                        const refPoints = document.getElementById('refPoints');
                        if (refPoints) refPoints.textContent = (data.points_parrainage || 0) + ' pts';
                        const refLink = document.getElementById('refLink');
                        if (refLink) refLink.textContent = data.referral_code || 'PRTX-NEW';

                        calculateBadges(data);

                        // Avatar - priorité Google > Upload > Initiales
                        const avatarPreview = document.getElementById('avatarPreview');
                        const initiales = ((data.nom || '').charAt(0) + (data.prenom || '').charAt(0)).toUpperCase();

                        let avatarSrc = '';
                        if (data.avatar_url) {
                            avatarSrc = data.avatar_url; // Google
                        } else if (data.avatar && data.avatar !== 'default.png') {
                            avatarSrc = data.avatar.includes('/') ? data.avatar : '../../uploads/avatars/' + data.avatar;
                        }

                        if (avatarPreview) {
                            if (avatarSrc) {
                                avatarPreview.innerHTML = `<img src="${avatarSrc}" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"><span style="font-size:32px;font-weight:600;color:var(--accent);display:none;">${initiales}</span>`;
                            } else {
                                avatarPreview.innerHTML = `<span style="font-size:32px;font-weight:600;color:var(--accent);">${initiales}</span>`;
                            }
                        }

                        if (window.ProtexUser) window.ProtexUser.applyUser(data);

                        // Derniére connexion
                        const lcEl = document.querySelector('[data-field="last-login"]');
                        if (lcEl && data.date_creation_formatted) lcEl.textContent = data.date_creation_formatted;
                    })
                    .catch(err => console.error('Erreur chargement profil:', err));
            }
            loadProfile();

            function getAvatarUrl(url) {
                if (!url || url === 'default.png') return 'logo.png';
                if (url.startsWith('http')) return url;
                // Si l'url contient déjé un chemin (ex: uploads/...), on le garde, sinon on préfixe
                if (url.includes('/')) return url;
                return '../uploads/avatars/' + url;
            }



            // ===== SAUVEGARDE PROFIL =====
            const saveProfileBtn = document.getElementById('saveProfile');
            if (saveProfileBtn) {
                saveProfileBtn.addEventListener('click', function () {
                    const nom = document.getElementById('field-nom').innerText.trim();
                    const prenom = document.getElementById('field-prenom').innerText.trim();
                    const email = document.getElementById('email').value.trim();
                    const phone = document.getElementById('field-telephone').innerText.trim();
                    const adresse = document.getElementById('field-adresse').innerText.trim();
                    const date_naissance = document.getElementById('date_naissance').value.trim();

                    // Validations
                    if (!nom || !prenom) {
                        showToast('Nom et prénom obligatoires', 'danger'); return;
                    }
                    const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                    if (!emailOk) {
                        showToast("Email invalide. Format: nom@exemple.com", 'danger'); return;
                    }

                    if (phone) {
                        const phoneOk = /^(\+216\s?)?[2-9]\d{7}$/.test(phone.replace(/\s/g, ''));
                        if (!phoneOk) {
                            showToast('Numéro invalide. Format: 20 123 456 ou +216 20 123 456', 'danger'); return;
                        }
                    }

                    const btn = saveProfileBtn;
                    const orig = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Enregistrement...';
                    btn.disabled = true;

                    // ? VRAI fetch vers le backend PHP
                    fetch('update_user.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            nom: nom, 
                            prenom: prenom, 
                            email: email, 
                            telephone: phone, 
                            adresse: adresse, 
                            date_naissance: date_naissance 
                        })
                    })
                        .then(res => res.json())
                        .then(data => {
                            btn.innerHTML = orig;
                            btn.disabled = false;
                            if (data.success) {
                                showToast(data.message || 'Profil mis é jour avec succés', 'success');
                                // Recharger le profil sans recharger toute la page
                                loadProfile();
                            } else {
                                showToast(data.message || 'Erreur lors de la mise é jour', 'danger');
                            }
                        })
                        .catch(() => {
                            btn.innerHTML = orig;
                            btn.disabled = false;
                            showToast('Erreur réseau, réessayez', 'danger');
                        });
                });
            }



            // ===== GESTION CHAMP ANCIEN MOT DE PASSE =====
            const currentPwdInput = document.getElementById('currentPassword');
            const newPwdInput = document.getElementById('new-password');
            const confirmPwdInput = document.getElementById('confirm-new-password');
            const pwdToggleBtn = document.getElementById('togglePwdBtn');
            const forcePwd = new URLSearchParams(window.location.search).get('force_pwd') === '1';

            function enablePwdFields(enable) {
                newPwdInput.disabled = !enable;
                confirmPwdInput.disabled = !enable;
                pwdToggleBtn.disabled = !enable;
                var opacity = enable ? '1' : '0.5';
                var cursor = enable ? 'text' : 'not-allowed';
                newPwdInput.style.opacity = opacity;
                newPwdInput.style.cursor = cursor;
                confirmPwdInput.style.opacity = opacity;
                confirmPwdInput.style.cursor = cursor;
                pwdToggleBtn.style.opacity = opacity;
                pwdToggleBtn.style.cursor = enable ? 'pointer' : 'not-allowed';
            }

            if (currentPwdInput && newPwdInput && confirmPwdInput) {
                if (forcePwd) {
                    enablePwdFields(true);
                    document.getElementById('strengthLabel').textContent = "Entrez un nouveau mot de passe (connexion par lien magique)";
                    currentPwdInput.style.display = 'none';
                    currentPwdInput.parentElement.style.display = 'none';
                } else {
                    currentPwdInput.addEventListener('input', function () {
                        var hasValue = this.value.trim().length > 0;
                        enablePwdFields(hasValue);
                        if (!hasValue) {
                            document.getElementById('strengthLabel').textContent = "En attente de l'ancien mot de passe...";
                            document.getElementById('strengthFill').style.width = '0%';
                            newPwdInput.value = '';
                            confirmPwdInput.value = '';
                        } else if (newPwdInput.value.length === 0) {
                            document.getElementById('strengthLabel').textContent = "Entrez un nouveau mot de passe";
                        }
                    });
                }
            }

            // ===== SAUVEGARDE MOT DE PASSE =====
            const savePwdBtn = document.getElementById('savePwd');
            if (savePwdBtn) {
                savePwdBtn.addEventListener('click', function () {
                    var body = {};
                    var p1 = document.getElementById('new-password').value;
                    var p2 = document.getElementById('confirm-new-password').value;

                    if (!forcePwd) {
                        var ancienMdp = document.getElementById('currentPassword').value;
                        if (!ancienMdp) {
                            showToast('Veuillez saisir votre ancien mot de passe', 'warning'); return;
                        }
                        body.ancien_mdp = ancienMdp;
                    }
                    if (p1.length < 8) {
                        showToast('Mot de passe trop court. 8 caractéres min avec majuscule, minuscule, chiffre et caractére spécial', 'warning'); return;
                    }
                    if (p1 !== p2) {
                        showToast('Les mots de passe ne correspondent pas. Doit étre identique au mot de passe', 'danger'); return;
                    }

                    body.nouveau_mdp = p1;

                    var orig = savePwdBtn.innerHTML;
                    savePwdBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Enregistrement...';
                    savePwdBtn.disabled = true;

                    fetch('change_password.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(body)
                    })
                        .then(function(response){return response.json();})
                        .then(function(data){
                            if (data.success) {
                                document.getElementById('currentPassword').value = '';
                                document.getElementById('new-password').value = '';
                                document.getElementById('confirm-new-password').value = '';
                                checkStrength('');
                                showToast('Mot de passe changé avec succés', 'success');
                            } else {
                                showToast(data.message || 'Erreur lors du changement', 'danger');
                            }
                        })
                        .catch(function(error){
                            console.error('Erreur:', error);
                            showToast('Erreur de connexion', 'danger');
                        })
                        .finally(function(){
                            savePwdBtn.innerHTML = orig;
                            savePwdBtn.disabled = false;
                        });
                });
            }

            // ===== DéSACTIVATION DU COMPTE =====
            const btnDeactivate = document.getElementById('btn-deactivate');
            if (btnDeactivate) {
                btnDeactivate.addEventListener('click', function () {
                    if (confirm("? étes-vous sér de vouloir désactiver votre compte ? Cette action est irréversible sans l'intervention d'un administrateur.")) {
                        fetch('update_user.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ action: 'deactivate' })
                        })
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) {
                                    alert("Votre compte a été désactivé. Vous allez étre redirigé.");
                                    window.location.href = 'login.html';
                                } else {
                                    showToast(data.message || "Erreur lors de la désactivation", "danger");
                                    btnDeactivate.disabled = false;
                                    btnDeactivate.innerText = 'Désactiver mon compte';
                                }
                            })
                            .catch(() => {
                                showToast('Erreur réseau', 'danger');
                                btnDeactivate.disabled = false;
                                btnDeactivate.innerText = 'Désactiver mon compte';
                            });
                    }
                });
            }

            // ===== RéSEAU SOCIAL (AMIS) =====
            const contactsList = document.getElementById('ptx-contacts-list');



            window.filterNetwork = function (type) {
                // UI Tabs
                ['all', 'online', 'trusted', 'pending', 'sos'].forEach(t => {
                    const tab = document.getElementById('netTab-' + t);
                    if (tab) {
                        tab.style.background = (t === type) ? 'var(--accent)' : 'rgba(255,255,255,0.05)';
                        tab.style.color = (t === type) ? '#fff' : 'var(--text-secondary)';
                    }
                });

                // Panel Toggle
                const contactList = document.getElementById('ptx-contacts-list');
                const sosPanel = document.getElementById('sos-panel');
                
                if (type === 'sos') {
                    contactList.style.display = 'none';
                    sosPanel.style.display = 'block';
                    loadSOSHistory(); // Refresh history
                    updateSOSTrustedPreview(); // Update trusted contacts
                } else {
                    contactList.style.display = 'block';
                    sosPanel.style.display = 'none';
                    
                    // Logic
                    document.querySelectorAll('#ptx-contacts-list .contact-item').forEach(item => {
                        if (type === 'all') item.style.display = 'flex';
                        else if (type === 'online') item.style.display = (item.dataset.online === '1') ? 'flex' : 'none';
                        else if (type === 'trusted') item.style.display = (item.dataset.trusted === '1') ? 'flex' : 'none';
                        else if (type === 'pending') item.style.display = (item.dataset.pending === '1') ? 'flex' : 'none';
                    });
                }
            };

            // Nouvelle fonction pour l'aperu des contacts de confiance dans le panneau SOS
            window.updateSOSTrustedPreview = function() {
                const preview = document.getElementById('sosTrustedPreview');
                if (!preview) return;
                
                const trusted = document.querySelectorAll('#ptx-contacts-list .contact-item[data-trusted="1"]');
                if (trusted.length === 0) {
                    preview.innerHTML = '<div style="font-size:10px; color:var(--text-secondary); opacity:0.6;">Aucun contact de confiance défini. Marquez vos amis avec ? pour les ajouter ici.</div>';
                    return;
                }
                
                let html = '';
                trusted.forEach(t => {
                    const name = t.dataset.name;
                    const img = t.querySelector('img').src;
                    html += `
                        <div style="flex:0 0 50px; text-align:center;">
                            <img src="${img}" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid var(--gold);">
                            <div style="font-size:9px; color:var(--text-primary); margin-top:4px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${name.split(' ')[0]}</div>
                        </div>
                    `;
                });
                preview.innerHTML = html;
            };

            function loadNetwork() {
                if (!contactsList) return;
                fetch('friends.php?action=list')
                    .then(r => r.json())
                    .then(data => {
                        if (!data.success) return;
                        let html = '';

                        const invBadge = document.getElementById('invitationBadge');
                        if (invBadge) {
                            if (data.pending && data.pending.length > 0) {
                                invBadge.textContent = data.pending.length;
                                invBadge.style.display = 'flex';
                            } else {
                                invBadge.style.display = 'none';
                            }
                        }

                        const netCount = document.getElementById('networkCount');
                        if (netCount) {
                            netCount.textContent = `(${data.friends ? data.friends.length : 0} contacts)`;
                        }

                        // Invitations
                        if (data.pending && data.pending.length > 0) {
                            data.pending.forEach(u => {
                                html += `
                                <div class="contact-item" data-pending="1" style="display:flex; align-items:center; gap:12px; padding:12px 16px; border-bottom:1px solid rgba(255,255,255,0.05)">
                                    <img src="${getAvatarUrl(u.avatar_url)}" style="width:32px; height:32px; border-radius:50%; object-fit:cover;">
                                    <div style="flex:1">
                                        <div style="font-size:13px; font-weight:600; color:#fff">${u.prenom} ${u.nom}</div>
                                        <div style="font-size:10px; color:var(--accent);">Invitation en attente</div>
                                    </div>
                                    <div style="display:flex; gap:5px;">
                                        <button class="btn btn-primary btn-sm" onclick="handleFriend(${u.id_user}, 'accept')" style="padding:4px 8px; font-size:11px"><i class="bi bi-check-lg"></i></button>
                                        <button class="btn btn-outline btn-sm" onclick="handleFriend(${u.id_user}, 'reject')" style="padding:4px 8px; font-size:11px; border-color:var(--danger); color:var(--danger)"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                </div>`;
                            });
                        }

                        // Amis
                        if (data.friends && data.friends.length > 0) {
                            data.friends.forEach(u => {
                                const online = u.is_online == 1;
                                const trustColor = u.is_trusted == 1 ? 'var(--gold)' : 'var(--text-secondary)';
                                const roleColor = u.role === 'agent' ? '#FF6B1A' : '#00b4d8';
                                html += `
                                <div class="contact-item" data-userid="${u.id_user}" data-name="${u.prenom} ${u.nom}" data-trusted="${u.is_trusted}" data-online="${online ? '1' : '0'}" style="display:flex; align-items:center; gap:12px; padding:14px 16px; border-bottom:1px solid rgba(255,255,255,0.08); transition: 0.3s; background: rgba(0,0,0,0.15); border-radius: 12px; margin: 0 10px 8px 10px;">
                                    <div style="position:relative">
                                        <img src="${getAvatarUrl(u.avatar_url)}" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border: 2px solid rgba(255,255,255,0.1);">
                                        ${online ? '<div style="position:absolute; bottom:0; right:0; width:12px; height:12px; background:#2ed573; border:2px solid #0a0f1e; border-radius:50%;"></div>' : ''}
                                    </div>
                                    <div style="flex:1">
                                        <div style="font-size:14px; font-weight:700; color:var(--text-primary);">${u.prenom} ${u.nom}</div>
                                        <div style="display:flex; align-items:center; gap:6px; margin-top:2px;">
                                            <span style="font-size:9px; background:${roleColor}; color:#fff; padding:1px 6px; border-radius:10px; text-transform:uppercase; font-weight:700;">${u.role}</span>
                                            <span style="font-size:10px; color:${online ? '#2ed573' : 'var(--text-secondary)'};">${online ? 'En ligne' : 'Hors ligne'}</span>
                                        </div>
                                    </div>
                                    <div style="display:flex; gap:12px; align-items:center;">
                                        <div class="unread-badge" style="display:none; background:var(--danger); color:#fff; font-size:10px; font-weight:bold; padding:2px 6px; border-radius:10px;">0</div>
                                        <i class="bi bi-star-fill" style="cursor:pointer; color:${trustColor}; font-size:18px;" onclick="toggleTrust(${u.id_user})"></i>
                                        <i class="bi bi-chat-dots-fill" style="cursor:pointer; color:var(--accent); font-size:18px;" onclick="openChat(${u.id_user}, '${u.prenom}', '${getAvatarUrl(u.avatar_url)}', ${online})"></i>
                                    </div>
                                </div>`;
                            });
                        }

                        // Suggestions
                        if (data.suggestions && data.suggestions.length > 0) {
                            html += `
                            <div style="padding:15px 16px 5px; font-size:11px; text-transform:uppercase; color:var(--text-secondary); font-weight:700;">
                                Suggestions (Méme Agence)
                            </div>`;
                            data.suggestions.forEach(u => {
                                html += `
                                <div class="contact-item" data-suggestion="1" style="display:flex; align-items:center; gap:12px; padding:12px 16px; border-bottom:1px solid rgba(255,255,255,0.05)">
                                    <img src="${getAvatarUrl(u.avatar_url)}" style="width:32px; height:32px; border-radius:50%; object-fit:cover;">
                                    <div style="flex:1">
                                        <div style="font-size:13px; font-weight:600; color:#fff">${u.prenom} ${u.nom}</div>
                                        <div style="font-size:10px; color:var(--text-secondary);">Client</div>
                                    </div>
                                    <button class="btn btn-outline btn-sm" onclick="handleFriend(${u.id_user}, 'add')" style="padding:4px 10px; font-size:11px; border-radius:12px; color:var(--accent); border-color:var(--accent);">
                                        <i class="bi bi-person-plus"></i> Ajouter
                                    </button>
                                </div>`;
                            });
                        }

                        if (!data.pending || data.pending.length === 0) {
                            if ((!data.friends || data.friends.length === 0) && (!data.suggestions || data.suggestions.length === 0)) {
                                html = '<div style="padding:40px 20px; text-align:center; font-size:12px; color:rgba(255,255,255,0.2)">Votre réseau est vide. Invitez des amis pour activer les fonctions de sécurité.</div>';
                            }
                        }
                        
                        contactsList.innerHTML = html;
                        updateSOSTrustedPreview();
                    });
            }

            window.handleFriend = function (id, action) {
                fetch('friends.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: action, friend_id: id })
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message, 'success');
                            loadNetwork();
                        } else {
                            showToast(data.message, 'warning');
                        }
                    });
            };

            window.toggleTrust = function (id) {
                fetch('sos.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'toggle_trust', friend_id: id })
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message, 'success');
                            loadNetwork();
                            // Refresh SOS preview after a short delay to ensure DOM is updated by loadNetwork
                            setTimeout(updateSOSTrustedPreview, 500);
                        } else {
                            showToast(data.message, 'warning');
                        }
                    });
            };

            // --- SOS LOGIC WITH COUNTDOWN ---
            let sosCountdownTimer = null;
            let sosLocation = { lat: null, lng: null };

            window.triggerSOS = function () {
                if (window.explodeAvatar) explodeAvatar();

                const trusted = document.querySelectorAll('#ptx-contacts-list [data-trusted="1"]');
                const listEl = document.getElementById('sosTrustedList');
                if (trusted.length === 0) {
                    listEl.innerHTML = '<div style="color:#ff4757; font-size:12px; font-weight:700;">?? Aucun contact de confiance !</div>';
                } else {
                    let names = [];
                    trusted.forEach(t => names.push(t.dataset.name));
                    listEl.innerHTML = `<div style="color:rgba(255,255,255,0.8); font-size:12px;"><strong>Alertes pour :</strong> ${names.join(', ')}</div>`;
                }

                document.getElementById('sosModal').style.display = 'flex';

                // GPS
                document.getElementById('sosGPSStatus').innerHTML = '? Récupération de la position GPS...';
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        pos => {
                            sosLocation = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                            document.getElementById('sosGPSStatus').innerHTML = '? Position récupérée';
                        },
                        () => { document.getElementById('sosGPSStatus').innerHTML = '?? GPS non disponible'; }
                    );
                }

                // Countdown
                let count = 5;
                const countEl = document.getElementById('sosCountdownNum');
                countEl.textContent = count;
                document.getElementById('sosCountdownZone').style.display = 'block';

                if (sosCountdownTimer) clearInterval(sosCountdownTimer);
                sosCountdownTimer = setInterval(() => {
                    count--;
                    countEl.textContent = count;
                    if (count <= 0) {
                        clearInterval(sosCountdownTimer);
                        confirmSOS();
                    }
                }, 1000);
            };

            window.cancelSOSCountdown = function () {
                clearInterval(sosCountdownTimer);
                document.getElementById('sosCountdownZone').style.display = 'none';
            };

            window.cancelSOS = function () {
                clearInterval(sosCountdownTimer);
                document.getElementById('sosModal').style.display = 'none';
            };

            window.confirmSOS = function () {
                const btn = document.getElementById('sosConfirmBtn');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner"></span> Envoi...';

                fetch('sos.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'trigger',
                        lat: sosLocation.lat,
                        lng: sosLocation.lng
                    })
                })
                    .then(r => r.json())
                    .then(data => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-exclamation-octagon-fill"></i> ENVOYER MAINTENANT';
                        if (data.success) {
                            showToast('?? ' + data.message, 'success');
                            cancelSOS();
                            loadSOSHistory();
                        } else {
                            showToast('? ' + (data.message || 'Erreur SOS'), 'danger');
                        }
                    })
                    .catch(() => {
                        btn.disabled = false;
                        showToast('? Erreur réseau SOS', 'danger');
                    });
            };

            function loadSOSHistory() {
                fetch('sos.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'history' }) })
                    .then(r => r.json()).then(data => {
                        if (!data.success || !data.history.length) return;
                        const list = document.getElementById('sosHistoryList');
                        list.innerHTML = data.history.map(h => {
                            const date = new Date(h.created_at).toLocaleString('fr-FR');
                            const loc = h.lat ? `?? ${parseFloat(h.lat).toFixed(4)}, ${parseFloat(h.lng).toFixed(4)}` : '?? Position non disponible';
                            const resolved = h.statut === 'resolu' ? '? Résolu' : '? En cours';
                            return `<div style="padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.05);">
                            <div style="display:flex; justify-content:space-between;">
                                <div style="color:#ff4757; font-weight:600;">?? ${date}</div>
                                <div style="font-size:10px; color:${h.statut === 'resolu' ? '#2ed573' : '#ffa502'}">${resolved}</div>
                            </div>
                            <div style="color:rgba(255,255,255,0.4)">${loc}</div>
                        </div>`;
                        }).join('');
                    });
            }
            loadSOSHistory();

            // --- CHAT LOGIC ---
            let activeChatId = null;
            let chatInterval = null;

            window.openChat = function (id, name, avatar, isOnline) {
                activeChatId = id;
                document.getElementById('chatName').textContent = name;
                document.getElementById('chatAvatar').innerHTML = `<img src="${avatar}" style="width:100%; height:100%; object-fit:cover;">`;
                document.getElementById('chatOnlineDot').style.display = isOnline ? 'block' : 'none';
                document.getElementById('chatSubtitle').textContent = isOnline ? 'En ligne' : 'Hors ligne';
                document.getElementById('chatSubtitle').style.color = isOnline ? '#2ed573' : 'rgba(255,255,255,0.4)';

                document.getElementById('chatModal').style.display = 'flex';
                loadMessages();
                if (chatInterval) clearInterval(chatInterval);
                chatInterval = setInterval(loadMessages, 3000);
            };

            window.closeChat = function () {
                document.getElementById('chatModal').style.display = 'none';
                if (chatInterval) clearInterval(chatInterval);
                activeChatId = null;
            };

            window.insertEmoji = function (emoji) {
                const input = document.getElementById('chatInput');
                input.value += emoji;
                input.focus();
            };

            function loadMessages() {
                if (!activeChatId) return;
                fetch(`chat.php?action=fetch&friend_id=${activeChatId}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            const box = document.getElementById('chatMessages');
                            let html = '';
                            data.messages.forEach(m => {
                                const cls = m.sender_id == activeChatId ? 'received' : 'sent';
                                const time = new Date(m.created_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                                html += `
                            <div class="msg ${cls}" style="display:flex; flex-direction:column; align-items:${cls === 'sent' ? 'flex-end' : 'flex-start'}; margin-bottom:12px;">
                                <div style="max-width:75%; background:${cls === 'sent' ? 'var(--accent)' : 'rgba(255,255,255,0.1)'}; 
                                padding:10px 14px; border-radius:${cls === 'sent' ? '16px 16px 4px 16px' : '16px 16px 16px 4px'}; color:#fff; font-size:13px;">
                                    ${m.content}
                                </div>
                                <div style="font-size:10px; color:rgba(255,255,255,0.3); margin-top:3px;">${time} ${m.is_read == 1 ? '??' : '?'}</div>
                            </div>`;
                            });
                            box.innerHTML = html;
                            box.scrollTop = box.scrollHeight;
                        }
                    });
            }

            window.sendChat = function () {
                const input = document.getElementById('chatInput');
                const content = input.value.trim();
                if (!content || !activeChatId) return;
                fetch('chat.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'send', friend_id: activeChatId, content: content })
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            input.value = '';
                            loadMessages();
                        }
                    });
            };

            window.copyRef = function () {
                const code = document.getElementById('refLink').textContent;
                navigator.clipboard.writeText(code).then(() => {
                    showToast('Lien de parrainage copié !', 'success');
                });
            };

            const chatInput = document.getElementById('chatInput');
            if (chatInput) {
                chatInput.addEventListener('keypress', function (e) {
                    if (e.key === 'Enter') sendChat();
                });
            }

            loadNetwork();

            // ===== TOGGLES SéCURITé =====
            document.getElementById('toggle2fa') && document.getElementById('toggle2fa').addEventListener('change', function () {
                showToast(this.checked ? '2FA activé' : '2FA désactivé', this.checked ? 'success' : 'warning');
            });
            document.getElementById('toggleAlerts') && document.getElementById('toggleAlerts').addEventListener('change', function () {
                showToast(this.checked ? 'Alertes activées' : 'Alertes désactivées', this.checked ? 'success' : 'warning');
            });

            // ===== FACE ID SETUP =====
            const setupFaceIdBtn = document.getElementById('setupFaceIdBtn');
            const setupModal = document.getElementById('faceIdSetupModal');
            const setupVideo = document.getElementById('setupFaceVideo');
            const setupStatus = document.getElementById('setupFaceStatus');
            const setupProgressBar = document.getElementById('setupProgressBar');
            const setupProgressRing = document.getElementById('setupProgressRing');
            const closeSetupBtn = document.getElementById('closeSetupFaceIdBtn');
            const scannerContainer = document.getElementById('setupScannerContainer');

            let setupStreamRef = null;

            const stopSetupCamera = () => {
                if (setupStreamRef) {
                    setupStreamRef.getTracks().forEach(t => t.stop());
                    setupStreamRef = null;
                }
                if (setupModal) {
                    setupModal.classList.remove('show');
                    setTimeout(() => { setupModal.style.display = 'none'; }, 300);
                }
                if (setupFaceIdBtn) setupFaceIdBtn.disabled = false;
            };

            if (closeSetupBtn) closeSetupBtn.onclick = stopSetupCamera;

            const unregisterFaceIdBtn = document.getElementById('unregisterFaceIdBtn');
            fetch('get_user.php').then(r => r.json()).then(data => {
                if (data && data.success && data.user) data = data.user;
                if (data && data.face_encoding === 'configured') {
                    document.getElementById('faceIdState').textContent = 'Configuré';
                    document.getElementById('faceIdState').style.color = 'var(--success)';
                    if (setupFaceIdBtn) setupFaceIdBtn.innerHTML = '<i class="bi bi-camera"></i> Reconfigurer Face ID';
                    if (unregisterFaceIdBtn) unregisterFaceIdBtn.style.display = 'flex';
                } else {
                    document.getElementById('faceIdState').textContent = 'Non configuré';
                    document.getElementById('faceIdState').style.color = 'var(--warning)';
                    if (unregisterFaceIdBtn) unregisterFaceIdBtn.style.display = 'none';
                }
            });

            if (unregisterFaceIdBtn) {
                unregisterFaceIdBtn.addEventListener('click', function () {
                    if (!confirm('Êtes-vous sûr de vouloir désactiver Face ID ? Les données de reconnaissance faciale seront supprimées.')) return;
                    unregisterFaceIdBtn.disabled = true;
                    unregisterFaceIdBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Suppression...';
                    fetch('unregister_face.php', { method: 'POST' })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                showToast('Face ID désactivé', 'success');
                                document.getElementById('faceIdState').textContent = 'Non configuré';
                                document.getElementById('faceIdState').style.color = 'var(--warning)';
                                if (setupFaceIdBtn) setupFaceIdBtn.innerHTML = '<i class="bi bi-camera"></i> Configurer mon Face ID';
                                if (unregisterFaceIdBtn) unregisterFaceIdBtn.style.display = 'none';
                            } else {
                                showToast(res.message || 'Erreur', 'danger');
                            }
                        })
                        .catch(() => showToast('Erreur réseau', 'danger'))
                        .finally(() => {
                            unregisterFaceIdBtn.disabled = false;
                            unregisterFaceIdBtn.innerHTML = '<i class="bi bi-trash"></i> Désactiver Face ID';
                        });
                });
            }

            if (setupFaceIdBtn) {
                setupFaceIdBtn.addEventListener('click', async function () {
                    if (setupFaceIdBtn.disabled) return;
                    setupFaceIdBtn.disabled = true;

                    setupModal.style.display = 'flex';
                    setTimeout(() => { setupModal.classList.add('show'); }, 10);

                    setupStatus.textContent = 'Initialisation de la caméra...';
                    setupStatus.style.color = '#a0aabf';
                    setupProgressBar.style.width = '0%';
                    setupProgressBar.style.background = '#00b4d8';
                    setupProgressRing.style.display = 'none';
                    scannerContainer.style.borderColor = 'rgba(0, 180, 216, 0.2)';

                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                        setupStreamRef = stream;
                        setupVideo.srcObject = stream;

                        let captures = [];
                        let maxCaptures = 20;

                        setTimeout(() => {
                            if (!setupStreamRef) return;
                            setupStatus.textContent = "Regardez l'objectif...";
                            setupProgressRing.style.display = 'block';
                            scannerContainer.style.borderColor = '#00b4d8';

                            let interval = setInterval(() => {
                                if (!setupStreamRef) { clearInterval(interval); return; }

                                const canvas = document.createElement('canvas');
                                canvas.width = setupVideo.videoWidth;
                                canvas.height = setupVideo.videoHeight;
                                canvas.getContext('2d').drawImage(setupVideo, 0, 0);
                                captures.push(canvas.toDataURL('image/jpeg', 0.8));

                                let percent = Math.round((captures.length / maxCaptures) * 100);
                                setupProgressBar.style.width = percent + '%';
                                setupStatus.textContent = `Analyse des traits faciaux... ${percent}%`;

                                if (captures.length >= maxCaptures) {
                                    clearInterval(interval);
                                    setupStatus.textContent = "? Capture terminée !";
                                    setupProgressBar.style.width = "100%";
                                    setupProgressBar.style.background = "#2ed573";
                                    
                                    setTimeout(() => {
                                        setupStatus.textContent = "Analyse et entraénement...";
                                        fetch('register_face.php', {
                                            method: 'POST',
                                            headers: {'Content-Type': 'application/json'},
                                            body: JSON.stringify({images: captures})
                                        })
                                        .then(r => r.json())
                                        .then(res => {
                                            if (res.success) {
                                                setupStatus.textContent = "? Face ID configuré !";
                                                scannerContainer.style.borderColor = '#2ed573';
                                                document.getElementById('faceIdState').textContent = 'Configuré';
                                                document.getElementById('faceIdState').style.color = 'var(--success)';
                                                setupFaceIdBtn.innerHTML = '<i class="bi bi-camera"></i> Reconfigurer Face ID';
                                                setTimeout(() => { stopSetupCamera(); }, 2500);
                                            } else {
                                                setupStatus.textContent = "? " + (res.message || "Erreur");
                                                setupStatus.style.color = '#ff4757';
                                                setupProgressBar.style.background = '#ff4757';
                                                scannerContainer.style.borderColor = '#ff4757';
                                                setTimeout(() => { stopSetupCamera(); }, 3000);
                                            }
                                        })
                                        .catch(() => {
                                            if (!setupStreamRef) return;
                                            setupStatus.textContent = "Erreur réseau";
                                            setupStatus.style.color = '#ff4757';
                                            setTimeout(() => { stopSetupCamera(); }, 2500);
                                        });
                                    }, 1000);
                                }
                            }, 350);
                        }, 1000);
                    } catch (e) {
                        setupStatus.textContent = 'Accés caméra refusé';
                        setupStatus.style.color = '#ff4757';
                        setTimeout(() => { stopSetupCamera(); }, 2500);
                    }
                });
            }

            // ── U5 : Historique des connexions ──
            function parseBrowser(ua) {
                if (!ua) return { icon: 'bi-globe', name: 'Navigateur inconnu' };
                if (/Edg\//.test(ua))     return { icon: 'bi-browser-edge',    name: 'Edge' };
                if (/OPR\//.test(ua))     return { icon: 'bi-browser-opera',   name: 'Opera' };
                if (/Chrome\//.test(ua))  return { icon: 'bi-browser-chrome',  name: 'Chrome' };
                if (/Firefox\//.test(ua)) return { icon: 'bi-browser-firefox', name: 'Firefox' };
                if (/Safari\//.test(ua))  return { icon: 'bi-browser-safari',  name: 'Safari' };
                return { icon: 'bi-globe', name: 'Autre' };
            }
            function parseOS(ua) {
                if (!ua) return '';
                if (/Windows NT 11/.test(ua) || /Windows NT 10/.test(ua)) return 'Windows';
                if (/Windows/.test(ua)) return 'Windows';
                if (/Mac OS X/.test(ua))   return 'macOS';
                if (/Android/.test(ua))    return 'Android';
                if (/iPhone|iPad/.test(ua)) return 'iOS';
                if (/Linux/.test(ua))      return 'Linux';
                return '';
            }
            function maskIp(ip) {
                if (!ip) return '—';
                const parts = ip.split('.');
                if (parts.length === 4) return parts[0] + '.' + parts[1] + '.xx.xx';
                // IPv6 : masquer les 2 derniers groupes
                return ip.replace(/:[^:]+:[^:]+$/, ':xxxx:xxxx');
            }
            function loadLoginHistory() {
                const container = document.getElementById('loginHistoryList');
                if (!container) return;
                fetch('../../api.php?action=get_login_history')
                    .then(r => r.json())
                    .then(data => {
                        if (!data.success || !data.history.length) {
                            container.innerHTML = '<div style="padding:20px;text-align:center;font-size:12px;color:var(--text-secondary);">Aucune connexion enregistrée.</div>';
                            return;
                        }
                        let html = '';
                        data.history.forEach((row, i) => {
                            const br   = parseBrowser(row.user_agent);
                            const os   = parseOS(row.user_agent);
                            const ip   = maskIp(row.ip);
                            const date = new Date(row.created_at).toLocaleString('fr-FR', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
                            const isFirst = i === 0;
                            html += `
                            <div style="display:flex;align-items:center;gap:14px;padding:12px 18px;border-bottom:1px solid rgba(255,255,255,0.05);${isFirst ? 'background:rgba(0,180,216,0.04);' : ''}">
                                <div style="width:36px;height:36px;border-radius:10px;background:rgba(0,180,216,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="bi ${br.icon}" style="font-size:18px;color:var(--accent);"></i>
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-size:13px;font-weight:600;color:var(--text-primary);">${br.name}${os ? ' &mdash; ' + os : ''} ${isFirst ? '<span style="font-size:10px;background:var(--accent);color:#fff;padding:1px 7px;border-radius:20px;margin-left:6px;">Actuel</span>' : ''}</div>
                                    <div style="font-size:11px;color:var(--text-secondary);margin-top:2px;">${ip} &bull; ${date}${row.ville ? ' &bull; ' + row.ville : ''}</div>
                                </div>
                            </div>`;
                        });
                        container.innerHTML = html;
                    })
                    .catch(() => {
                        container.innerHTML = '<div style="padding:20px;text-align:center;font-size:12px;color:var(--text-secondary);">Erreur de chargement.</div>';
                    });
            }
            loadLoginHistory();

            // Déconnecter toutes les sessions
            const btnLogoutAll = document.getElementById('btnLogoutAll');
            if (btnLogoutAll) {
                btnLogoutAll.addEventListener('click', function () {
                    if (!confirm('Déconnecter toutes les sessions actives ? Vous serez redirigé vers la page de connexion.')) return;
                    btnLogoutAll.disabled = true;
                    btnLogoutAll.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Déconnexion...';
                    fetch('../../api.php?action=logout_all_sessions')
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) window.location.href = 'login.html';
                            else { showToast('Erreur lors de la déconnexion', 'danger'); btnLogoutAll.disabled = false; btnLogoutAll.innerHTML = '<i class="bi bi-box-arrow-left"></i> Déconnecter tout'; }
                        })
                        .catch(() => { btnLogoutAll.disabled = false; });
                });
            }

        });
    </script>

    <!-- ── U6 : Canvas confetti (overlay) ── -->
    <canvas id="confettiCanvas" style="position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:99999;display:none;"></canvas>
    <script>
    // Lightweight confetti engine
    (function() {
        const canvas  = document.getElementById('confettiCanvas');
        const ctx     = canvas ? canvas.getContext('2d') : null;
        let particles = [];
        let animFrame = null;
        const COLORS  = ['#00b4d8','#2ed573','#ffd700','#ff6b1a','#ff4757','#a29bfe'];

        window.launchConfetti = function() {
            if (!canvas || !ctx) return;
            canvas.width  = window.innerWidth;
            canvas.height = window.innerHeight;
            canvas.style.display = 'block';
            particles = [];
            for (let i = 0; i < 180; i++) {
                particles.push({
                    x:  Math.random() * canvas.width,
                    y:  Math.random() * canvas.height - canvas.height,
                    w:  Math.random() * 10 + 5,
                    h:  Math.random() * 5  + 3,
                    color: COLORS[Math.floor(Math.random() * COLORS.length)],
                    vy: Math.random() * 3 + 2,
                    vx: (Math.random() - 0.5) * 2,
                    rot: Math.random() * 360,
                    rotV: (Math.random() - 0.5) * 6,
                    opacity: 1
                });
            }
            if (animFrame) cancelAnimationFrame(animFrame);
            let elapsed = 0;
            function draw() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                let alive = false;
                particles.forEach(p => {
                    p.y   += p.vy;
                    p.x   += p.vx;
                    p.rot += p.rotV;
                    if (p.y > canvas.height * 0.7) p.opacity -= 0.015;
                    if (p.opacity <= 0) return;
                    alive = true;
                    ctx.save();
                    ctx.globalAlpha = Math.max(0, p.opacity);
                    ctx.translate(p.x + p.w / 2, p.y + p.h / 2);
                    ctx.rotate(p.rot * Math.PI / 180);
                    ctx.fillStyle = p.color;
                    ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                    ctx.restore();
                });
                if (alive) {
                    animFrame = requestAnimationFrame(draw);
                } else {
                    canvas.style.display = 'none';
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                }
            }
            draw();
            // Auto-stop après 5 s
            setTimeout(() => { if (animFrame) cancelAnimationFrame(animFrame); canvas.style.display = 'none'; }, 5000);
        };
    })();
    </script>

    <script src="user/js/validation.js"></script>
    <script src="assets/js/main.js"></script>
</body>

</html>



