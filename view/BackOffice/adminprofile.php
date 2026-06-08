<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Espace Client — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Protex CSS -->
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/validation.css">
    <link rel="stylesheet" href="assets/css/animations.css">

    <!-- Toast style inline (petit composant) -->
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
        .toast-notif.show { opacity: 1; transform: translateY(0); }
        .toast-success i  { color: var(--success); font-size: 18px; }
        .toast-warning i  { color: var(--gold); font-size: 18px; }
        .toast-danger  i  { color: var(--danger); font-size: 18px; }
    </style>
    <style>
        .toast-notif {
            position: fixed; bottom: 24px; right: 24px;
            background: var(--navy-mid); border: 1px solid var(--border);
            border-radius: 12px; padding: 14px 20px;
            display: flex; align-items: center; gap: 10px;
            font-size: 14px; color: var(--text-primary);
            z-index: 9999; opacity: 0; transform: translateY(10px);
            transition: all 0.3s ease; box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }
        .toast-notif.show { opacity: 1; transform: translateY(0); }
        .toast-success i { color: var(--success); font-size: 18px; }
        .toast-warning i { color: var(--gold); font-size: 18px; }
        .toast-danger  i { color: var(--danger); font-size: 18px; }

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
            color: #fff;
        }
        .card-body { padding: 24px; }
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: var(--radius-md);
            font-family: var(--font-body); font-size: 13px; font-weight: 500;
            cursor: pointer; transition: var(--transition); text-decoration: none; border: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: #fff;
        }
        .btn-primary:hover { box-shadow: 0 4px 14px rgba(0,180,216,0.4); }
        .btn-outline {
            background: transparent; border: 1px solid var(--glass-border);
            color: var(--text-secondary);
        }
        .btn-outline:hover { border-color: var(--accent); color: var(--accent); }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .btn-block { width: 100%; justify-content: center; }
    </style>
</head>
<body>

<!-- Background animé (glassmorphism) -->
<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<class="layout">

<!-- ===== SIDEBAR ===== -->
    <?php require_once __DIR__.'/assets/includes/sidebar.php'; ?>

    <!-- ==================== MAIN ==================== -->
    <main class="main">

        <!-- TOPBAR -->
        <div class="topbar">
            <div>
                <div class="topbar-title">Mon Profil</div>
                <div class="topbar-sub" id="topbarDate"></div>
            </div>
            <div class="topbar-actions">
                <a href="#" class="topbar-btn" title="Notifications">
                    <i class="bi bi-bell"></i>
                    <span class="notif-dot"></span>
                </a>
                <a href="#" class="topbar-btn" title="Aide">
                    <i class="bi bi-question-circle"></i>
                </a>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="content">

            <!-- Page header -->
            <div class="page-header-bar">
                <div>
                    <div class="page-title">Mon Profil</div>
                    <div class="page-breadcrumb">
                        <i class="bi bi-house"></i>
                        <a href="admin.php">Accueil</a>
                        <i class="bi bi-chevron-right" style="font-size:10px"></i>
                        <span>Mon profil</span>
                    </div>
                </div>
            </div>

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
                        <div class="card-title"><i class="bi bi-person-circle" style="color:var(--accent);margin-right:8px"></i>Profil</div>
                    </div>
                    <div class="card-body">

                        <!-- Avatar upload -->
                        <div class="profile-avatar">
                            <div id="avatarPreview" class="avatar-placeholder">
                                <i class="bi bi-person"></i>
                            </div>
                            <div class="file-input-wrap">
                                <label for="avatarInput" class="btn btn-outline btn-sm">
                                    <i class="bi bi-upload"></i> Changer la photo
                                </label>
                                <input type="file" id="avatarInput" accept="image/*">
                                <span class="file-name">Aucun fichier sélectionné</span>
                            </div>
                        </div>

                        <!-- Formulaire -->
                        <div class="profile-info">
                            <div class="info-item">
                                <label>Nom</label>
                                <input type="text" id="nom" placeholder="—" data-rule="nom">
                            </div>
                            <div class="info-item">
                                <label>Prénom</label>
                                <input type="text" id="prenom" placeholder="—" data-rule="prenom">
                            </div>
                            <div class="info-item">
                                <label>Email</label>
                                <input type="email" id="email" placeholder="—" data-rule="email">
                            </div>
                            <div class="info-item">
                                <label>Téléphone</label>
                                <input type="tel" id="phone" placeholder="+216 XX XXX XXX" data-rule="telephone">
                            </div>
                            <div class="info-item">
                                <label>CIN</label>
                                <input type="text" id="cin" placeholder="—" readonly style="opacity:.6;cursor:not-allowed">
                            </div>
                            <div class="info-item">
                                <label>Rôle</label>
                                <input type="text" id="role" placeholder="—" readonly style="opacity:.6;cursor:not-allowed">
                            </div>
                            <div class="info-item">
                                <label>Agence</label>
                                <input type="text" id="nom_agence" placeholder="—" readonly style="opacity:.6;cursor:not-allowed">
                            </div>
                        </div>

                        <button class="btn-save-profile" id="saveProfile">
                            <i class="bi bi-save"></i> Sauvegarder les modifications
                        </button>
                    </div>
                </div>

                <!-- CARTE SÉCURITÉ -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="bi bi-shield-lock" style="color:var(--accent);margin-right:8px"></i>Sécurité</div>
                    </div>
                    <div class="card-body">

                        <!-- Mot de passe -->
                        <div class="info-item" style="margin-bottom:4px">
                            <label>Ancien mot de passe</label>
                            <input type="password" id="currentPassword" placeholder="Saisissez d'abord l'ancien mot de passe" autocomplete="new-password">
                        </div>

                        <div class="info-item" style="margin-bottom:4px">
                            <label>Nouveau mot de passe</label>
                            <div class="input-group">
                                <input type="password" id="password" placeholder="••••••••" oninput="checkStrength(this.value)" disabled style="opacity: 0.5; cursor: not-allowed;">
                                <button type="button" class="btn btn-outline btn-sm" id="togglePwdBtn" disabled style="opacity: 0.5; cursor: not-allowed;">
                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                            <div class="pwd-strength">
                                <div class="pwd-strength-fill" id="strengthFill"></div>
                            </div>
                            <div class="pwd-strength-label" id="strengthLabel">En attente de l'ancien mot de passe...</div>
                        </div>

                        <div class="info-item" style="margin-bottom:20px">
                            <label>Confirmer le mot de passe</label>
                            <input type="password" id="passwordConfirm" placeholder="••••••••" disabled style="opacity: 0.5; cursor: not-allowed;">
                        </div>

                        <button class="btn-save-profile" id="savePwd" style="margin-bottom:24px">
                            <i class="bi bi-key"></i> Changer le mot de passe
                        </button>

                        <!-- Séparateur -->
                        <div style="border-top:1px solid var(--glass-border); padding-top:20px; margin-bottom:16px">
                            <div class="section-title" style="font-size:13px; margin-bottom:12px">Options de sécurité</div>
                        </div>

                        <!-- Toggles sécurité -->
                        <div class="toggle-row">
                            <div>
                                <div class="toggle-label">Double authentification (2FA)</div>
                                <div class="toggle-desc">SMS envoyé à +216 20 *** ***</div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="toggle2fa" checked>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div class="toggle-row">
                            <div>
                                <div class="toggle-label">Alertes de connexion</div>
                                <div class="toggle-desc">Notification par email à chaque connexion</div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="toggleAlerts" checked>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <!-- Séparateur -->
                        <div style="border-top:1px solid var(--glass-border); padding-top:20px; margin-top:8px">
                            <div class="section-title" style="font-size:13px; margin-bottom:12px">Sessions actives</div>
                        </div>

                        <!-- Sessions -->
                        <div class="session-item">
                            <div class="session-icon"><i class="bi bi-laptop"></i></div>
                            <div class="session-info">
                                <div class="session-name">Chrome — Windows 11</div>
                                <div class="session-meta">Tunis · Maintenant</div>
                            </div>
                            <span class="session-current">Actuel</span>
                        </div>

                        <div class="session-item">
                            <div class="session-icon"><i class="bi bi-phone"></i></div>
                            <div class="session-info">
                                <div class="session-name">Safari — iPhone</div>
                                <div class="session-meta">Tunis · Il y a 2h</div>
                            </div>
                            <button class="btn btn-outline btn-sm" style="color:var(--danger);border-color:var(--danger)" onclick="window.showToast && showToast('Session déconnectée','warning')">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>

                        <div class="info-item" style="margin-top:16px">
                            <label>Dernière connexion</label>
                            <span style="font-size:14px;color:var(--text-primary)">7 Avril 2026 · 10:45</span>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </main>

</div>

<!-- Scripts profil admin -->
<script>
    // ===== TOPBAR DATE =====
    const now = new Date();
    const topbarDate = document.getElementById('topbarDate');
    if (topbarDate) {
        topbarDate.textContent = now.toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
    }

    // ===== TOAST =====
    function showToast(msg, type = 'success') {
        let t = document.getElementById('toastNotif');
        if (!t) {
            t = document.createElement('div'); t.id = 'toastNotif';
            t.className = 'toast-notif'; document.body.appendChild(t);
        }
        const icons = { success:'bi-check-circle-fill', warning:'bi-exclamation-triangle-fill', danger:'bi-x-circle-fill' };
        t.className = 'toast-notif toast-' + type;
        t.innerHTML = `<i class="bi ${icons[type]||icons.success}"></i><span>${msg}</span>`;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3500);
    }

    // ===== AVATAR PREVIEW ET UPLOAD =====
    const avatarInput   = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    const fileNameDisplay = document.querySelector('.file-name');

    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function(e) {
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
            reader.onload = function(ev) {
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

    // ===== CHARGEMENT PROFIL =====
    function loadProfile() {
        const inputs = ['nom', 'prenom', 'email', 'phone', 'cin', 'role'];
        inputs.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.opacity = '0.5';
        });
        
        fetch('get_admin.php')
            .then(res => {
                if (res.status === 401) { window.location.href = '../FrontOffice/login.html'; return null; }
                return res.json();
            })
            .then(data => {
                if (!data || data.error) return;
                const set = (id, val) => { const el = document.getElementById(id); if (el && val != null) el.value = val; };
                set('nom',    data.nom);
                set('prenom', data.prenom);
                set('email',  data.email);
                set('phone',  data.telephone);
                set('cin',    data.cin);
                set('role',   data.role ? data.role.charAt(0).toUpperCase() + data.role.slice(1) : '');
                set('nom_agence', data.nom_agence || 'Non renseigné');

                // Fade in animation
                inputs.forEach((id, i) => {
                    const el = document.getElementById(id);
                    if (el) {
                        setTimeout(() => {
                            el.style.opacity = '1';
                            el.style.transition = 'opacity 0.3s ease';
                        }, i * 50);
                    }
                });

                // Avatar - afficher l'avatar ou les initiales
                const avatarPreview = document.getElementById('avatarPreview');
                const initiales = ((data.nom||'').charAt(0) + (data.prenom||'').charAt(0)).toUpperCase();
                const avatarValue = data.avatar || '';
                const isValidImage = avatarValue && (avatarValue.includes('/') || avatarValue.match(/\.(jpg|jpeg|png|gif|webp)$/i));
                const avatarPath = isValidImage ? (avatarValue.includes('/') ? avatarValue : '../../uploads/avatars/' + avatarValue) : '';
                
                if (avatarPath) {
                    avatarPreview.innerHTML = `<img src="${avatarPath}" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"><span style="font-size:32px;font-weight:600;color:var(--accent);display:none;">${initiales}</span>`;
                } else {
                    avatarPreview.innerHTML = `<span style="font-size:32px;font-weight:600;color:var(--accent);">${initiales}</span>`;
                }

                if (window.ProtexSidebar) window.ProtexSidebar.applyUser(data);

                // Dernière connexion
                const lcEl = document.querySelector('[data-field="last-login"]');
                if (lcEl && data.date_creation_formatted) lcEl.textContent = data.date_creation_formatted;
            })
            .catch(err => console.error('Erreur chargement profil admin:', err));
    }
    loadProfile();

    // ===== SAUVEGARDE PROFIL =====
    const adminRules = {
        nom: {
            validate(v) {
                if (!v) return 'Le nom est obligatoire';
                if (v.length < 2) return 'Le nom doit contenir au moins 2 lettres';
                if (v.length > 50) return 'Le nom ne doit pas dépasser 50 caractères';
                if (/[0-9]/.test(v)) return 'Le nom ne doit pas contenir de chiffres';
                if (!/^[a-zA-ZÀ-ÿ\s'\-]+$/.test(v)) return 'Le nom ne doit contenir que des lettres';
                return null;
            }
        },
        prenom: {
            validate(v) {
                if (!v) return 'Le prénom est obligatoire';
                if (v.length < 2) return 'Le prénom doit contenir au moins 2 lettres';
                if (v.length > 50) return 'Le prénom ne doit pas dépasser 50 caractères';
                if (/[0-9]/.test(v)) return 'Le prénom ne doit pas contenir de chiffres';
                if (!/^[a-zA-ZÀ-ÿ\s'\-]+$/.test(v)) return 'Le prénom ne doit contenir que des lettres';
                return null;
            }
        },
        email: {
            validate(v) {
                if (!v) return "L'email est obligatoire";
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) return 'Email invalide';
                return null;
            }
        },
        telephone: {
            validate(v) {
                if (!v) return null;
                const clean = v.replace(/\s/g, '');
                if (!/^(\+216)?[2-9]\d{7}$/.test(clean)) return 'Numéro tunisien invalide';
                return null;
            }
        }
    };

    function adminShowError(input, message) {
        if (!input) return;
        adminClearError(input);
        input.classList.add('input-error');
        const err = document.createElement('span');
        err.className = 'field-error';
        err.textContent = message;
        if (input.nextSibling) {
            input.parentNode.insertBefore(err, input.nextSibling);
        } else {
            input.parentNode.appendChild(err);
        }
    }

    function adminClearError(input) {
        if (!input) return;
        input.classList.remove('input-error', 'input-valid');
        const existing = input.parentNode.querySelector('.field-error');
        if (existing) existing.remove();
    }

    function adminValidateField(input, ruleName) {
        const rule = adminRules[ruleName];
        if (!rule) return true;
        const err = rule.validate(input.value.trim());
        if (err) { adminShowError(input, err); return false; }
        adminClearError(input);
        input.classList.add('input-valid');
        return true;
    }

    const nomInput = document.getElementById('nom');
    const prenomInput = document.getElementById('prenom');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');

    if (nomInput) {
        nomInput.addEventListener('blur', () => adminValidateField(nomInput, 'nom'));
        nomInput.addEventListener('input', () => {
            if (nomInput.classList.contains('input-error') || nomInput.classList.contains('input-valid')) {
                adminValidateField(nomInput, 'nom');
            }
        });
    }
    if (prenomInput) {
        prenomInput.addEventListener('blur', () => adminValidateField(prenomInput, 'prenom'));
        prenomInput.addEventListener('input', () => {
            if (prenomInput.classList.contains('input-error') || prenomInput.classList.contains('input-valid')) {
                adminValidateField(prenomInput, 'prenom');
            }
        });
    }
    if (emailInput) {
        emailInput.addEventListener('blur', () => adminValidateField(emailInput, 'email'));
        emailInput.addEventListener('input', () => {
            if (emailInput.classList.contains('input-error') || emailInput.classList.contains('input-valid')) {
                adminValidateField(emailInput, 'email');
            }
        });
    }
    if (phoneInput) {
        phoneInput.addEventListener('blur', () => adminValidateField(phoneInput, 'telephone'));
        phoneInput.addEventListener('input', () => {
            if (phoneInput.classList.contains('input-error') || phoneInput.classList.contains('input-valid')) {
                adminValidateField(phoneInput, 'telephone');
            }
        });
    }

    const saveProfileBtn = document.getElementById('saveProfile');
    if (saveProfileBtn) {
        saveProfileBtn.addEventListener('click', function () {
            let valid = true;

            if (!adminValidateField(nomInput, 'nom')) valid = false;
            if (!adminValidateField(prenomInput, 'prenom')) valid = false;
            if (!adminValidateField(emailInput, 'email')) valid = false;
            if (phoneInput && phoneInput.value && !adminValidateField(phoneInput, 'telephone')) valid = false;

            if (!valid) {
                showToast('Veuillez corriger les erreurs', 'danger');
                return;
            }

            const nom = nomInput.value.trim();
            const prenom = prenomInput.value.trim();
            const email = emailInput.value.trim();
            const phone = phoneInput ? phoneInput.value.trim() : '';

            const orig = saveProfileBtn.innerHTML;
            saveProfileBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Enregistrement...';
            saveProfileBtn.disabled = true;

            fetch('update_admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ nom, prenom, email, telephone: phone })
            })
            .then(res => res.json())
            .then(data => {
                saveProfileBtn.innerHTML = orig;
                saveProfileBtn.disabled = false;
                if (data.success) { showToast(data.message || 'Profil mis à jour avec succès', 'success'); loadProfile(); }
                else              { showToast(data.message || 'Erreur lors de la mise à jour', 'danger'); }
            })
            .catch(() => {
                saveProfileBtn.innerHTML = orig;
                saveProfileBtn.disabled = false;
                showToast('Erreur réseau, réessayez', 'danger');
            });
        });
    }

    // ===== MOT DE PASSE =====
    function checkStrength(val) {
        const fill  = document.getElementById('strengthFill');
        const label = document.getElementById('strengthLabel');
        if (!fill || !label) return;
        const cfg = val.length === 0 ? { w:'0%',   c:'',                t:'Entrez un nouveau mot de passe' }
                  : val.length < 6   ? { w:'25%',  c:'var(--danger)',   t:'Trop court' }
                  : val.length < 10  ? { w:'55%',  c:'var(--gold)',     t:'Moyen' }
                  :                    { w:'100%',  c:'var(--success)',  t:'Fort' };
        fill.style.width = cfg.w; fill.style.background = cfg.c;
        label.textContent = cfg.t; label.style.color = cfg.c || 'var(--text-secondary)';
    }

    document.getElementById('password')?.addEventListener('input', e => checkStrength(e.target.value));

    document.getElementById('togglePwdBtn')?.addEventListener('click', function () {
        const inp = document.getElementById('password');
        const icon = document.getElementById('eyeIcon');
        if (!inp) return;
        inp.type = inp.type === 'password' ? 'text' : 'password';
        icon.className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
    });

    const savePwdBtn = document.getElementById('savePwd');
    
    // ===== GESTION CHAMP ANCIEN MOT DE PASSE =====
    const currentPwdInput = document.getElementById('currentPassword');
    const newPwdInput = document.getElementById('password');
    const confirmPwdInput = document.getElementById('passwordConfirm');
    const pwdToggleBtn = document.getElementById('togglePwdBtn');
    
    if (currentPwdInput && newPwdInput && confirmPwdInput) {
        currentPwdInput.addEventListener('input', function() {
            const hasValue = this.value.trim().length > 0;
            
            // Activer/Désactiver les champs
            newPwdInput.disabled = !hasValue;
            confirmPwdInput.disabled = !hasValue;
            pwdToggleBtn.disabled = !hasValue;
            
            // Gérer le style
            const opacity = hasValue ? '1' : '0.5';
            const cursor = hasValue ? 'text' : 'not-allowed';
            const btnCursor = hasValue ? 'pointer' : 'not-allowed';
            
            newPwdInput.style.opacity = opacity;
            newPwdInput.style.cursor = cursor;
            
            confirmPwdInput.style.opacity = opacity;
            confirmPwdInput.style.cursor = cursor;
            
            pwdToggleBtn.style.opacity = opacity;
            pwdToggleBtn.style.cursor = btnCursor;
            
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

    if (savePwdBtn) {
        savePwdBtn.addEventListener('click', function () {
            const ancienMdp = document.getElementById('currentPassword')?.value || '';
            const p1 = document.getElementById('password')?.value || '';
            const p2 = document.getElementById('passwordConfirm')?.value || '';

            if (!ancienMdp) {
                showToast('Veuillez saisir votre ancien mot de passe', 'warning'); return;
            }
            if (p1.length < 8) {
                showToast('Mot de passe trop court (min 8 caractères)', 'warning'); return;
            }
            if (p1 !== p2) {
                document.getElementById('passwordConfirm').style.borderColor = 'var(--danger)';
                showToast('Les mots de passe ne correspondent pas', 'danger'); return;
            }
            document.getElementById('passwordConfirm').style.borderColor = '';

            const orig = savePwdBtn.innerHTML;
            savePwdBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Enregistrement...';
            savePwdBtn.disabled = true;

            // Envoyer la requête au serveur
            fetch('../FrontOffice/change_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ancien_mdp: ancienMdp,
                    nouveau_mdp: p1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Vider les champs
                    document.getElementById('currentPassword').value = '';
                    document.getElementById('password').value = '';
                    document.getElementById('passwordConfirm').value = '';
                    checkStrength('');
                    showToast('Mot de passe changé avec succès', 'success');
                } else {
                    showToast(data.message || 'Erreur lors du changement', 'danger');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showToast('Erreur de connexion', 'danger');
            })
            .finally(() => {
                savePwdBtn.innerHTML = orig;
                savePwdBtn.disabled = false;
            });
        });
    }

    // Activer le lien du sidebar selon la page actuelle
    document.querySelectorAll('.nav-item').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === 'adminprofile.html') {
            link.classList.add('active');
        }
    });
</script>



