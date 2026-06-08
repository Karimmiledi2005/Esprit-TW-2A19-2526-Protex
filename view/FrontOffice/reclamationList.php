<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();

require_once dirname(__DIR__, 2) . '/controller/ReclamationController.php';
require_once dirname(__DIR__, 2) . '/controller/ReponseController.php';

$reclamationC = new ReclamationController();
$reponseC     = new ReponseController();

// AJOUT via POST (Modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    try {
        $objet       = trim($_POST['objet']       ?? '');
        $type        = trim($_POST['type']        ?? '');
        $priorite    = trim($_POST['priorite']    ?? 'Normale');
        $description = trim($_POST['description'] ?? '');
        $email       = trim($_POST['email']       ?? '');

        // Polymorphic
        $objectType = trim($_POST['object_type'] ?? 'general');
        $objectRef  = trim($_POST['object_ref']  ?? '');
        if ($objectRef === '' && !empty($_POST['ref_contrat'])) {
            $objectRef  = trim($_POST['ref_contrat']);
            $objectType = 'contrat';
        }
        $validTypes = ['contrat','devis','sinistre','paiement','poste','general'];
        if (!in_array($objectType, $validTypes, true)) { $objectType = 'general'; }

        if ($objet && $description && $email) {
            $reclamation = new Reclamation(
                null,
                $objet,
                $type,
                $objectRef,
                $priorite,
                'open',
                new DateTime(),
                'REC-' . date('YmdHis'),
                $description,
                $email,
                $objectType,
                $objectRef
            );

            $userId = (int)($_SESSION['user_id'] ?? $_SESSION['id_user'] ?? 0);
            $reclamationC->addReclamation($reclamation, $userId);
            echo "success";
            exit;
        }
    } catch (Exception $e) {
        error_log("Erreur ajout reclamation: " . $e->getMessage());
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo "error";
            exit;
        }
    }
}

// MODIFICATION via POST (Modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    try {
        $id          = (int)($_POST['id'] ?? 0);
        $objet       = trim($_POST['objet'] ?? '');
        $type        = trim($_POST['type'] ?? '');
        $priorite    = trim($_POST['priorite'] ?? 'Normale');
        $description = trim($_POST['description'] ?? '');

        if ($id > 0 && $objet && $description) {
            $old = $reclamationC->showReclamation($id);
            if ($old) {
                $updated = new Reclamation(
                    $id,
                    $objet,
                    $type,
                    $old['ref_contrat'],
                    $priorite,
                    $old['statut'],
                    new DateTime($old['date_depot']),
                    $old['rec_ref'],
                    $description,
                    $old['email'] ?? ''
                );
                $reclamationC->updateReclamation($updated, $id);
                echo "success";
                exit;
            }
        }
    } catch (Exception $e) {
        error_log("Erreur modification reclamation: " . $e->getMessage());
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo "error";
            exit;
        }
    }
}

// JSON API MODE
if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    $searchObjet = trim($_GET['search_objet'] ?? '');
    if ($searchObjet !== '') {
        $list = $reponseC->searchAllReclamationsByObjet($searchObjet);
    } else {
        $list = $reponseC->listAllReclamations();
    }
    echo json_encode(['success' => true, 'data' => $list ?? []]);
    exit;
}

// SUPPRESSION via GET
if (isset($_GET['delete'])) {
    $reclamationC->deleteReclamation($_GET['delete']);
    header('Location: reclamationList.php');
    exit();
}

// Recherche par objet ou liste complète
$searchObjet = trim($_GET['search_objet'] ?? '');
if ($searchObjet !== '') {
    $list = $reponseC->searchAllReclamationsByObjet($searchObjet);
} else {
    $list = $reponseC->listAllReclamations();
}
 
$total         = 0;
$openCount     = 0;
$closedCount   = 0;
$rejectedCount = 0;
$rows          = [];
 
foreach ($list as $row) {
    $rows[] = $row;
    $total++;
    if (($row['statut'] ?? '') === 'open')     $openCount++;
    if (($row['statut'] ?? '') === 'closed')   $closedCount++;
    if (($row['statut'] ?? '') === 'rejected') $rejectedCount++;
}
 
if (!function_exists('h')) {
    function h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
 
function badgeClass($statut) {
    switch ($statut) {
        case 'closed':   return 'badge-success';
        case 'pending':  return 'badge-info';
        case 'rejected': return 'badge-danger';
        default:         return 'badge-warning';
    }
}
 
function badgeLabel($statut) {
    switch ($statut) {
        case 'closed':   return 'Résolue';
        case 'pending':  return 'En attente';
        case 'rejected': return 'Rejetée';
        default:         return 'En cours';
    }
}
 
function cardClass($statut) {
    $allowed = ['open', 'closed', 'pending', 'rejected'];
    return in_array($statut, $allowed, true) ? $statut : 'open';
}
 
function iconWrapClass($type) {
    switch ($type) {
        case 'Santé':      return 'icon-sante';
        case 'Auto':       return 'icon-auto';
        case 'Habitation': return 'icon-habitat';
        default:           return 'icon-autre';
    }
}
 
function iconBiClass($type) {
    switch ($type) {
        case 'Santé':      return 'bi-heart-pulse';
        case 'Auto':       return 'bi-car-front';
        case 'Habitation': return 'bi-house';
        default:           return 'bi-three-dots';
    }
}
 
function formatDateFr($date) {
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    if ($timestamp === false) return $date;
    $months = [
        1=>'Janvier',  2=>'Février',   3=>'Mars',      4=>'Avril',
        5=>'Mai',      6=>'Juin',      7=>'Juillet',   8=>'Août',
        9=>'Septembre',10=>'Octobre',  11=>'Novembre', 12=>'Décembre'
    ];
    return date('d', $timestamp) . ' ' . $months[(int)date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Réclamations — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/reclamation.css">
    <script src="assets/js/reclamation-validation.js"></script>
    
    <style>
        /* Modal styles (same as mes-sinistres.html) */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(26,58,122,0.30);
            backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
            justify-content: center; align-items: center; z-index: 9999;
        }
        .modal-overlay.open { display: flex; }

        .modal-box {
            width: 460px; max-width: 94vw; padding: 28px; border-radius: 18px;
            background: #ffffff;
            border: 1px solid rgba(26,58,122,0.15);
            box-shadow: 0 20px 60px rgba(26,58,122,0.18);
            color: #15233C; position: relative;
            max-height: 90vh; overflow-y: auto;
            animation: glassPop 0.25s ease;
        }
        @keyframes glassPop { from { transform: scale(0.92); opacity:0; } to { transform: scale(1); opacity:1; } }

        .modal-box h3 { color: #15233C; font-size: 18px; font-weight: 700; margin-bottom: 20px; }

        .modal-box label {
            display: block; font-size: 13px; font-weight: 500;
            color: #15233C; margin-bottom: 6px;
        }

        .form-control {
            width: 100%; padding: 11px 14px; margin-top: 0; margin-bottom: 14px;
            border-radius: 10px; border: 1px solid rgba(26,58,122,0.12);
            background: #fbfdff; color: #15233C; font-size: 14px;
            outline: none; transition: all 0.2s; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
            box-sizing: border-box;
        }
        .form-control:focus {
            border-color: #2b5baf; background: #ffffff;
            box-shadow: 0 0 0 4px rgba(43,91,175,0.08);
        }
        .form-control[readonly] { background: #f4f7fb; color: #6b778c; cursor: not-allowed; }

        .btn-submit {
            height: 44px; border-radius: 10px; border: none;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: #ffffff; font-size: 15px; font-weight: 600; cursor: pointer;
            width: 100%; transition: all 0.2s;
            box-shadow: 0 8px 20px rgba(30, 60, 114, 0.25);
            display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 12px 25px rgba(30, 60, 114, 0.35); }

        select.form-control {
            appearance: none; cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%2315233C' viewBox='0 0 24 24'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 16px center;
            background-size: 14px; padding-right: 40px;
        }

        textarea.form-control { resize: none; min-height: 100px; }

        .close-btn {
            position: absolute; top: 20px; right: 20px; background: none; border: none;
            font-size: 22px; color: #8e9bb0; cursor: pointer; transition: color 0.2s;
            display: flex; align-items: center; justify-content: center;
        }
        .close-btn:hover { color: #d32f2f; }

        /* Animated fields */
        .animated-field { opacity: 0; transform: translateY(10px); animation: fadeUp 0.5s ease forwards; }
        .animated-field:nth-child(1) { animation-delay: 0.05s; }
        .animated-field:nth-child(2) { animation-delay: 0.12s; }
        .animated-field:nth-child(3) { animation-delay: 0.19s; }
        .animated-field:nth-child(4) { animation-delay: 0.26s; }
        .animated-field:nth-child(5) { animation-delay: 0.33s; }
        @keyframes fadeUp { to { opacity:1; transform:translateY(0); } }

        .field-error {
            font-size: 12px; color: #ef4444; margin-top: 4px;
        }

        .form-row {
            display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 14px;
        }

        /* ===== TOAST ===== */
        .toast-notif {
            position: fixed; bottom: 24px; right: 24px;
            background: #fff; border: 1px solid rgba(26,58,122,0.15);
            border-radius: 12px; padding: 14px 20px;
            display: flex; align-items: center; gap: 10px;
            font-size: 14px; color: #15233C;
            z-index: 9999; opacity: 0; transform: translateY(10px);
            transition: all 0.3s ease; box-shadow: 0 8px 24px rgba(26,58,122,0.12);
        }
        .toast-notif.show { opacity: 1; transform: translateY(0); }
        .toast-notif i { font-size: 18px; }
        .toast-success i { color: #1A3A7A; }
        .toast-warning i { color: #FF6B1A; }
        .toast-danger  i { color: var(--danger); }
    </style>

    <!-- FrontOffice unifie - surcharge thème camarades dark-navy -->
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
 
    <!-- ======== MAIN CONTENT ======== -->
    <main class="main">
 
        <div class="page-header">
            <div>
                <div class="page-title-main"> Mes réclamations </div>
                <div class="page-breadcrumb">
                    <i class="bi bi-house"></i>
                    <a href="client.php">Accueil</a>
                    <i class="bi bi-chevron-right"></i>
                    <span>Réclamations</span>
                    &nbsp;·&nbsp;
                    <span id="currentDate"></span>
                </div>
            </div>
            <div style="display:flex;gap:10px;">
                <button class="btn-new" onclick="openReclamationModal()"> <i class="bi bi-plus-lg"></i> Nouvelle réclamation </button>
            </div>
        </div>
 
        <div class="stats-row">
            <div class="stat-pill sp-blue">
                <div class="stat-pill-icon"><i class="bi bi-chat-dots"></i></div>
                <div>
                    <div class="stat-pill-val"><?php echo $total; ?></div>
                    <div class="stat-pill-lbl">Total réclamations</div>
                </div>
            </div>
            <div class="stat-pill sp-warn">
                <div class="stat-pill-icon"><i class="bi bi-clock"></i></div>
                <div>
                    <div class="stat-pill-val"><?php echo $openCount; ?></div>
                    <div class="stat-pill-lbl">En cours</div>
                </div>
            </div>
            <div class="stat-pill sp-green">
                <div class="stat-pill-icon"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="stat-pill-val"><?php echo $closedCount; ?></div>
                    <div class="stat-pill-lbl">Résolues</div>
                </div>
            </div>
        </div>
 
        <form method="GET" action="reclamationList.php" class="filters-bar">
            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="searchInput" class="search-input" name="search_objet" placeholder="Rechercher par objet..." value="<?php echo h($searchObjet); ?>">
                <button type="submit" class="btn-search-objet"> <i class="bi bi-arrow-right-circle-fill"></i> </button>
            </div>
            <select class="filter-select" id="filterStatut">
                <option value="">Tous les statuts</option>
                <option value="open">En cours</option>
                <option value="closed">Résolue</option>
                <option value="rejected">Rejetée</option>
            </select>
            <select class="filter-select" id="filterType">
                <option value="">Tous les types</option>
                <option value="Santé">Santé</option>
                <option value="Auto">Auto</option>
                <option value="Habitation">Habitation</option>
                <option value="Autre">Autre</option>
            </select>
        </form>
 
        <div class="reclamations-list" id="reclamationsList">
           <?php if (!empty($rows)) { ?>
                <?php foreach ($rows as $reclamation) { ?>
                    <div class="rec-card <?php echo cardClass($reclamation['statut'] ?? 'open'); ?>" data-statut="<?php echo h($reclamation['statut'] ?? 'open'); ?>" data-type="<?php echo h($reclamation['type'] ?? ''); ?>">
                        <div class="rec-header">
                            <div class="rec-title-group">
                                <div class="rec-icon <?php echo iconWrapClass($reclamation['type'] ?? 'Autre'); ?>">
                                    <i class="bi <?php echo iconBiClass($reclamation['type'] ?? 'Autre'); ?>"></i>
                                </div>
                                <div>
                                    <div class="rec-name"><?php echo h($reclamation['objet'] ?? ''); ?></div>
                                    <div class="rec-ref">
                                        <?php echo h($reclamation['rec_ref'] ?? ''); ?>
                                        <?php if (!empty($reclamation['object_type']) && $reclamation['object_type'] !== 'general') { ?>
                                        &nbsp;·&nbsp;
                                        <?php
                                            $labels = ['contrat'=>'Contrat','devis'=>'Devis','sinistre'=>'Sinistre','paiement'=>'Paiement','poste'=>'Poste'];
                                            echo h($labels[$reclamation['object_type']] ?? ucfirst($reclamation['object_type']));
                                        ?> : <?php echo h($reclamation['object_ref'] ?? $reclamation['ref_contrat'] ?? '—'); ?>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            <div class="rec-actions">
                                <button type="button" class="btn-action edit"
                                        data-id="<?php echo (int)$reclamation['id']; ?>"
                                        data-objet="<?php echo h($reclamation['objet'] ?? ''); ?>"
                                        data-type="<?php echo h($reclamation['type'] ?? ''); ?>"
                                        data-priorite="<?php echo h($reclamation['priorite'] ?? ''); ?>"
                                        data-description="<?php echo h($reclamation['description'] ?? ''); ?>"
                                        onclick="openUpdateModal(this)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn-action view"
                                        data-objet="<?php echo h($reclamation['objet'] ?? ''); ?>"
                                        data-contrat="<?php echo h($reclamation['ref_contrat'] ?? '—'); ?>"
                                        data-statut="<?php echo h($reclamation['statut'] ?? 'open'); ?>"
                                        data-type="<?php echo h($reclamation['type'] ?? '—'); ?>"
                                        data-priorite="<?php echo h($reclamation['priorite'] ?? '—'); ?>"
                                        data-date="<?php echo h(formatDateFr($reclamation['date_depot'] ?? '')); ?>"
                                        data-description="<?php echo h($reclamation['description'] ?? ''); ?>"
                                        data-reponse="<?php echo h($reclamation['reponse_contenu'] ?? ''); ?>"
                                        data-repdate="<?php echo h(formatDateFr($reclamation['rep_date'] ?? '')); ?>"
                                        onclick="openViewModal(this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a href="reclamationList.php?delete=<?php echo (int)$reclamation['id']; ?>" class="btn-action del" onclick="return confirm('Supprimer ?')"><i class="bi bi-trash3"></i></a>
                            </div>
                        </div>
                        <div class="rec-body">
                            <div class="rec-meta-item"> <label>Type</label> <span><?php echo h($reclamation['type'] ?? '—'); ?></span> </div>
                            <div class="rec-meta-item"> <label>Priorité</label> <span><?php echo h($reclamation['priorite'] ?? '—'); ?></span> </div>
                            <div class="rec-meta-item"> <label>Statut</label> <span class="badge <?php echo badgeClass($reclamation['statut'] ?? 'open'); ?>"><?php echo badgeLabel($reclamation['statut'] ?? 'open'); ?></span> </div>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="empty-state"> <i class="bi bi-inbox"></i> <p>Aucune réclamation trouvée</p> </div>
            <?php } ?>
        </div>

        <!-- ===== MODAL NOUVELLE RÉCLAMATION ===== -->
        <div id="reclamationModal" class="modal-overlay">
            <div class="modal-box">
                <h3><i class="bi bi-plus-circle"></i> Nouvelle réclamation</h3>
                <button class="close-btn" onclick="closeReclamationModal()"><i class="bi bi-x"></i></button>

                <form id="reclamationModalForm" onsubmit="handleReclamationSubmit(event)">
                    <!-- Email (auto-rempli, readonly) -->
                    <div class="animated-field">
                        <label><i class="bi bi-envelope"></i> EMAIL</label>
                        <input type="email" id="fEmail" name="email" class="form-control" readonly>
                        <span id="email_error" class="field-error"></span>
                    </div>

                    <!-- Objet -->
                    <div class="animated-field">
                        <label><i class="bi bi-pencil-square"></i> OBJET *</label>
                        <input type="text" id="fObjet" name="objet" class="form-control" required placeholder="Ex : Remboursement refusé">
                        <span id="objet_error" class="field-error"></span>
                        <div class="char-counter" id="charCountObjet"></div>
                    </div>

                    <!-- Module concerné -->
                    <div class="animated-field">
                        <label><i class="bi bi-layers"></i> MODULE CONCERNÉ</label>
                        <select id="fObjectType" name="object_type" class="form-control"
                                onchange="loadModalObjects(this.value)">
                            <option value="general">Général (sans référence)</option>
                            <option value="contrat" selected>Contrat</option>
                            <option value="devis">Devis</option>
                            <option value="sinistre">Sinistre</option>
                            <option value="paiement">Paiement</option>
                            <option value="poste">Poste social</option>
                        </select>
                    </div>

                    <!-- Référence dynamique -->
                    <div class="animated-field" id="modalObjectRefGroup">
                        <label id="modalObjectRefLabel"><i class="bi bi-link-45deg"></i> RÉFÉRENCE CONTRAT *</label>
                        <select id="fRefContrat" name="object_ref" class="form-control" required>
                            <option value="">-- Sélectionnez --</option>
                        </select>
                        <div id="modalLoadingContracts" style="display:none;color:#666;font-size:12px;margin-top:-10px;margin-bottom:10px;"><i class="bi bi-hourglass-split"></i> Chargement...</div>
                        <span id="ref_contrat_error" class="field-error"></span>
                        <!-- Hidden backward-compat -->
                        <input type="hidden" id="fRefContratHidden" name="ref_contrat" value="">
                    </div>

                    <!-- Type & Priorité -->
                    <div class="animated-field form-row">
                        <div>
                            <label><i class="bi bi-tag"></i> TYPE *</label>
                            <select id="fType" name="type" class="form-control" required>
                                <option value="Santé">Santé</option>
                                <option value="Auto">Auto</option>
                                <option value="Habitation">Habitation</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                        <div>
                            <label><i class="bi bi-flag"></i> PRIORITÉ</label>
                            <select id="fPriorite" name="priorite" class="form-control">
                                <option value="Normale">Normale</option>
                                <option value="Urgente">Urgente</option>
                                <option value="Faible">Faible</option>
                            </select>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="animated-field">
                        <label><i class="bi bi-chat-dots"></i> DESCRIPTION *</label>
                        <textarea id="fDesc" name="description" class="form-control" required placeholder="Décrivez votre réclamation en détail..." rows="3"></textarea>
                        
                        <div class="voice-input-box" style="margin-top:8px;">
                            <button type="button" class="btn-voice" id="btnVoiceDesc">
                                <i class="bi bi-mic"></i>
                                <span id="voiceBtnText">Dicter</span>
                            </button>
                            <span class="voice-status" id="voiceStatus" style="font-size:11px;opacity:0.7;">Vocal vers texte disponible</span>
                        </div>

                        <span id="desc_error" class="field-error"></span>
                        <div class="char-counter" id="charCountDesc"></div>
                    </div>

                    <!-- Actions -->
                    <div class="animated-field" style="display:flex;gap:12px;margin-top:20px;margin-bottom:0;">
                        <button type="button" onclick="closeReclamationModal()" class="btn-submit" style="background:#ccc;color:#15233C;">
                            <i class="bi bi-arrow-left"></i> Annuler
                        </button>
                        <button type="submit" class="btn-submit">
                            <i class="bi bi-send"></i> Envoyer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<!-- ===== MODAL MODIFICATION RÉCLAMATION ===== -->
<div id="updateReclamationModal" class="modal-overlay">
    <div class="modal-box">
        <h3><i class="bi bi-pencil-square"></i> Modifier réclamation</h3>
        <button class="close-btn" onclick="closeUpdateModal()"><i class="bi bi-x"></i></button>

        <form id="updateReclamationModalForm" onsubmit="handleUpdateSubmit(event)">
            <input type="hidden" id="uId" name="id">
            
            <div class="animated-field">
                <label><i class="bi bi-pencil-square"></i> OBJET *</label>
                <input type="text" id="uObjet" name="objet" class="form-control" required>
            </div>

            <div class="animated-field form-row">
                <div>
                    <label><i class="bi bi-tag"></i> TYPE *</label>
                    <select id="uType" name="type" class="form-control" required>
                        <option value="Santé">Santé</option>
                        <option value="Auto">Auto</option>
                        <option value="Habitation">Habitation</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
                <div>
                    <label><i class="bi bi-flag"></i> PRIORITÉ</label>
                    <select id="uPriorite" name="priorite" class="form-control">
                        <option value="Normale">Normale</option>
                        <option value="Urgente">Urgente</option>
                        <option value="Faible">Faible</option>
                    </select>
                </div>
            </div>

            <div class="animated-field">
                <label><i class="bi bi-chat-dots"></i> DESCRIPTION *</label>
                <textarea id="uDesc" name="description" class="form-control" required rows="3"></textarea>
            </div>

            <div class="animated-field" style="margin-top:20px;">
                <button type="submit" class="btn-submit" style="background:#FF6B1A;box-shadow:0 8px 20px rgba(255,107,26,0.25);">
                    <i class="bi bi-pencil-square"></i> Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL VOIR RÉCLAMATION ===== -->
<div id="viewReclamationModal" class="modal-overlay">
    <div class="modal-box" style="width: 600px; padding: 0; overflow: hidden; background: #fff;">
        <!-- Header du modal -->
        <div style="background: linear-gradient(135deg, #1A3A7A 0%, #2b5baf 100%); padding: 24px; position: relative;">
            <button class="close-btn" onclick="closeViewModal()" style="color: rgba(255,255,255,0.8); background: rgba(255,255,255,0.1); border:none; top: 16px; right: 16px;"><i class="bi bi-x"></i></button>
            
            <div style="display:flex; justify-content:space-between; align-items:flex-start; padding-right: 70px;">
                <div>
                    <h2 id="vObjet" style="margin:0; font-size:20px; color:white; font-weight:700;"></h2>
                    <div id="vContrat" style="font-size:12px; color:rgba(255,255,255,0.8); margin-top:6px;"></div>
                </div>
                <span id="vStatutBadge" class="badge"></span>
            </div>
        </div>

        <div style="padding: 24px; max-height: 70vh; overflow-y: auto;">
            <!-- Meta info -->
            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div>
                    <div style="font-size:11px; color:#8892a3; text-transform:uppercase; font-weight:700;">Type</div>
                    <div id="vType" style="font-size:14px; color:#15233C; font-weight:600; margin-top:4px;"></div>
                </div>
                <div>
                    <div style="font-size:11px; color:#8892a3; text-transform:uppercase; font-weight:700;">Priorité</div>
                    <div id="vPriorite" style="font-size:14px; color:#15233C; font-weight:600; margin-top:4px;"></div>
                </div>
                <div>
                    <div style="font-size:11px; color:#8892a3; text-transform:uppercase; font-weight:700;">Déposée le</div>
                    <div id="vDate" style="font-size:14px; color:#15233C; font-weight:600; margin-top:4px;"></div>
                </div>
            </div>

            <!-- Description -->
            <div style="margin-bottom: 24px;">
                <div style="font-size:11px; color:#8892a3; text-transform:uppercase; font-weight:700; margin-bottom: 8px;">Description</div>
                <div id="vDesc" style="font-size:14px; color:#15233C; line-height:1.6; background:#f8fbff; padding:16px; border-radius:12px; border:1px solid #d9ebf5;"></div>
            </div>

            <!-- Réponse -->
            <div id="vReponseBox" style="display:none; background:#ecfdf5; border:1px solid #a7f3d0; padding:20px; border-radius:12px;">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                    <i class="bi bi-chat-left-text" style="color:#059669; font-size:18px;"></i>
                    <h3 style="margin:0; font-size:15px; color:#064e3b; font-weight:700;">Réponse de l'administration</h3>
                    <span id="vRepDate" style="font-size:11px; color:#047857; margin-left:auto;"></span>
                </div>
                <div id="vRepContenu" style="font-size:13px; color:#064e3b; line-height:1.6;"></div>
            </div>

            <!-- En attente -->
            <div id="vWaitBox" style="display:none; text-align:center; padding:24px; background:#fffcf2; border:1px solid #fde68a; border-radius:12px;">
                <i class="bi bi-hourglass-split" style="font-size:24px; color:#d97706; margin-bottom:10px; display:block;"></i>
                <div style="color:#b45309; font-size:14px; font-weight:500;">En attente de traitement par nos services...</div>
            </div>
        </div>
    </div>
</div>
 
<div class="toast-notif" id="toastNotif">
    <i class="bi bi-check-circle"></i>
    <span id="toastMsg"></span>
</div>

<script>
    // ── Modal functions ──────────────────────────────────────────────────────
    function openReclamationModal() {
        const modal = document.getElementById('reclamationModal');
        modal.classList.add('open');
        // Load the default module type (contrat) on open
        const typeSelect = document.getElementById('fObjectType');
        if (typeSelect) loadModalObjects(typeSelect.value);
        loadUserEmailForModal();
    }

    function closeReclamationModal() {
        const modal = document.getElementById('reclamationModal');
        modal.classList.remove('open');
        document.getElementById('reclamationModalForm').reset();
        clearModalErrors();
    }

    function clearModalErrors() {
        if (window.ReclamationValidation) {
            // Utiliser la fonction de reset si elle existait, sinon on vide à la main
            ['email_error','objet_error','ref_contrat_error','desc_error'].forEach(id => {
                const el = document.getElementById(id);
                if (el) { el.textContent = ''; el.style.display = 'none'; }
            });
            ['fEmail','fObjet','fRefContrat','fDesc'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.remove('is-invalid');
            });
        }
    }

    function loadUserEmailForModal() {
        const emailField = document.getElementById('fEmail');
        fetch('api_get_user_email.php')
            .then(r => r.json())
            .then(d => {
                if (d.success && d.email) {
                    emailField.value = d.email;
                    if (window.ReclamationValidation) ReclamationValidation.validateEmail(true);
                }
            })
            .catch(e => console.error('Erreur email:', e));
    }

    const MODAL_TYPE_LABELS = {
        contrat: 'Référence Contrat', devis: 'Référence Devis',
        sinistre: 'Référence Sinistre', paiement: 'Référence Paiement',
        poste: 'Référence Poste social', general: 'Référence'
    };

    function loadModalObjects(type) {
        const group   = document.getElementById('modalObjectRefGroup');
        const label   = document.getElementById('modalObjectRefLabel');
        const select  = document.getElementById('fRefContrat');
        const loading = document.getElementById('modalLoadingContracts');
        const hidden  = document.getElementById('fRefContratHidden');

        if (type === 'general') {
            group.style.display = 'none';
            select.removeAttribute('required');
            hidden.value = '';
            return;
        }

        label.innerHTML = '<i class="bi bi-link-45deg"></i> ' + (MODAL_TYPE_LABELS[type] || 'Référence') + ' *';
        group.style.display = 'block';
        select.setAttribute('required', 'required');
        loading.style.display = 'inline';

        fetch('api_get_user_objects.php?type=' + encodeURIComponent(type))
            .then(r => r.json())
            .then(d => {
                loading.style.display = 'none';
                select.innerHTML = '<option value="">-- Sélectionnez --</option>';
                if (d.success && d.items && d.items.length > 0) {
                    d.items.forEach(item => {
                        const opt = document.createElement('option');
                        opt.value = item.id;
                        opt.textContent = item.label;
                        select.appendChild(opt);
                    });
                } else {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = '-- Aucun élément disponible --';
                    opt.disabled = true;
                    select.appendChild(opt);
                }
                select.onchange = () => { hidden.value = type === 'contrat' ? select.value : ''; };
            })
            .catch(e => { loading.style.display = 'none'; console.error(e); });
    }

    function handleReclamationSubmit(e) {
        e.preventDefault();
        
        // Validation obligatoire
        if (!ReclamationValidation.validateForm()) {
            return false;
        }

        const objet       = document.getElementById('fObjet').value.trim();
        const objectType  = document.getElementById('fObjectType') ? document.getElementById('fObjectType').value : 'general';
        const objectRef   = document.getElementById('fRefContrat').value.trim();
        const type        = document.getElementById('fType').value;
        const priorite    = document.getElementById('fPriorite').value;
        const description = document.getElementById('fDesc').value.trim();
        const email       = document.getElementById('fEmail').value.trim();

        const submitBtn = e.target.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Envoi...';

        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('objet', objet);
        formData.append('object_type', objectType);
        formData.append('object_ref', objectRef);
        formData.append('ref_contrat', objectType === 'contrat' ? objectRef : ''); // backward compat
        formData.append('type', type);
        formData.append('priorite', priorite);
        formData.append('description', description);
        formData.append('email', email);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(r => r.text())
        .then(html => {
            showToast('Réclamation ajoutée avec succès!', 'success');
            closeReclamationModal();
            setTimeout(() => { window.location.reload(); }, 1500);
        })
        .catch(e => {
            console.error('Erreur:', e);
            showToast('Erreur lors de l\'ajout. Veuillez réessayer.', 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-send"></i> Envoyer';
        });

        return false;
    }

    function showToast(msg, type='success') {
        const icons = { success:'bi-check-circle', warning:'bi-exclamation-circle', danger:'bi-x-circle', info:'bi-info-circle' };
        const el = document.getElementById('toastNotif');
        el.querySelector('i').className=`bi ${icons[type]||icons.success}`;
        document.getElementById('toastMsg').textContent=msg;
        el.className=`toast-notif toast-${type} show`;
        setTimeout(()=>el.classList.remove('show'), 3000);
    }

    // Close modal on overlay click
    document.getElementById('reclamationModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeReclamationModal();
        }
    });

    // ── Update Modal Functions ──
    function openUpdateModal(btn) {
        document.getElementById('uId').value = btn.getAttribute('data-id');
        document.getElementById('uObjet').value = btn.getAttribute('data-objet');
        document.getElementById('uType').value = btn.getAttribute('data-type');
        document.getElementById('uPriorite').value = btn.getAttribute('data-priorite');
        document.getElementById('uDesc').value = btn.getAttribute('data-description');
        document.getElementById('updateReclamationModal').classList.add('open');
    }

    function closeUpdateModal() {
        document.getElementById('updateReclamationModal').classList.remove('open');
        document.getElementById('updateReclamationModalForm').reset();
    }

    function handleUpdateSubmit(e) {
        e.preventDefault();
        const submitBtn = e.target.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Mise à jour...';

        const formData = new FormData(e.target);
        formData.append('action', 'update');

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(r => r.text())
        .then(html => {
            showToast('Réclamation modifiée avec succès!', 'success');
            closeUpdateModal();
            setTimeout(() => { window.location.reload(); }, 1500);
        })
        .catch(e => {
            console.error('Erreur:', e);
            showToast('Erreur lors de la modification.', 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-pencil-square"></i> Mettre à jour';
        });
    }

    document.getElementById('updateReclamationModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeUpdateModal();
        }
    });

    // ── View Modal Functions ──
    function openViewModal(btn) {
        const ds = btn.dataset;
        document.getElementById('vObjet').textContent = ds.objet;
        document.getElementById('vContrat').textContent = 'Contrat : ' + ds.contrat;
        
        const badgeMap = {'closed': {c:'badge-success', l:'Résolue'}, 'rejected': {c:'badge-danger', l:'Rejetée'}, 'pending': {c:'badge-info', l:'En attente'}, 'open': {c:'badge-warning', l:'En cours'}};
        const st = badgeMap[ds.statut] || badgeMap['open'];
        const badge = document.getElementById('vStatutBadge');
        badge.className = 'badge ' + st.c;
        badge.textContent = st.l;

        document.getElementById('vType').textContent = ds.type;
        document.getElementById('vPriorite').textContent = ds.priorite;
        document.getElementById('vDate').textContent = ds.date;
        document.getElementById('vDesc').innerHTML = ds.description.replace(/\n/g, '<br>');

        const repBox = document.getElementById('vReponseBox');
        const waitBox = document.getElementById('vWaitBox');

        if (ds.reponse && ds.reponse.trim() !== '') {
            repBox.style.display = 'block';
            waitBox.style.display = 'none';
            document.getElementById('vRepDate').textContent = ds.repdate;
            document.getElementById('vRepContenu').innerHTML = ds.reponse.replace(/\n/g, '<br>');
        } else {
            repBox.style.display = 'none';
            waitBox.style.display = 'block';
        }

        document.getElementById('viewReclamationModal').classList.add('open');
    }

    function closeViewModal() {
        document.getElementById('viewReclamationModal').classList.remove('open');
    }

    document.getElementById('viewReclamationModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeViewModal();
        }
    });

    // Boton IA Flottant
    const aiBtn = document.createElement('div');
    aiBtn.innerHTML = `
        <div id="btnOpenChat" style="position:fixed;bottom:30px;right:30px;width:60px;height:60px;background:linear-gradient(135deg,#ff7a1a,#ef6b0a);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:28px;cursor:pointer;box-shadow:0 10px 25px rgba(239,107,10,0.4);z-index:7999;transition:all 0.3s ease;">
            <i class="bi bi-stars"></i>
        </div>
    `;
    document.body.appendChild(aiBtn);
    
    document.getElementById('btnOpenChat').addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.1) rotate(15deg)';
    });
    document.getElementById('btnOpenChat').addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1) rotate(0deg)';
    });
</script>
<script src="assets/js/chatbot-assurance.js"></script>
</body>
</html>


