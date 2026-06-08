<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
require_once dirname(__DIR__, 2) . '/controller/ReclamationController.php';
SessionGuard::requireClient();

$reclamationC = new ReclamationController();
$error = '';

// Récupérer l'email depuis la session
$userId    = (int)($_SESSION['user_id'] ?? $_SESSION['id_user'] ?? 0);
$userEmail = '';

if ($userId > 0) {
    try {
        $db   = config::getConnexion();
        $stmt = $db->prepare("SELECT email FROM user WHERE id_user = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) { $userEmail = $user['email']; }
    } catch (Exception $e) {
        error_log('addReclamation email fetch: ' . $e->getMessage());
    }
}

function clean($v) { return trim((string)$v); }

function validateReclamationInput(array $data): array {
    $errors = [];
    $objet       = clean($data['objet']       ?? '');
    $type        = clean($data['type']        ?? '');
    $priorite    = clean($data['priorite']    ?? '');
    $description = clean($data['description'] ?? '');
    $email       = clean($data['email']       ?? '');

    if ($objet === '') {
        $errors[] = "L'objet est obligatoire.";
    } elseif (!preg_match("/^[a-zA-ZÀ-ÿ0-9\s\-\.\'#\(\)\[\]]+$/u", $objet)) {
        $errors[] = "L'objet contient des caractères non autorisés.";
    } elseif (mb_strlen($objet) < 3 || mb_strlen($objet) > 100) {
        $errors[] = "L'objet doit contenir entre 3 et 100 caractères.";
    }

    if (!in_array($type, ['Santé','Auto','Habitation','Autre'], true)) {
        $errors[] = "Type invalide.";
    }
    if (!in_array($priorite, ['Normale','Urgente','Faible'], true)) {
        $errors[] = "Priorité invalide.";
    }
    if ($description === '') {
        $errors[] = "Description obligatoire.";
    } elseif (mb_strlen($description) < 10) {
        $errors[] = "Description trop courte (min. 10 caractères).";
    }
    if ($email === '') {
        $errors[] = "L'adresse email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email est invalide.";
    } elseif (mb_strlen($email) > 150) {
        $errors[] = "L'adresse email est trop longue (max. 150 caractères).";
    }
    return $errors;
}

// ── TRAITEMENT POST ──────────────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    try {
        $errors = validateReclamationInput($_POST);

        if (empty($errors)) {
            $desc     = clean($_POST['description']);
            $priorite = clean($_POST['priorite']);
            if ($priorite === 'Normale') {
                $priorite = ReclamationController::detecterPriorite($desc);
            }

            // Polymorphic object link
            $objectType = clean($_POST['object_type'] ?? 'general');
            $objectRef  = clean($_POST['object_ref']  ?? '');
            if ($objectRef === '' && !empty($_POST['ref_contrat'])) {
                $objectRef  = clean($_POST['ref_contrat']);
                $objectType = 'contrat';
            }
            $validTypes = ['contrat','devis','sinistre','paiement','poste','general'];
            if (!in_array($objectType, $validTypes, true)) { $objectType = 'general'; }

            $reclamation = new Reclamation(
                null,
                clean($_POST['objet']),
                clean($_POST['type']),
                $objectRef,           // refContrat backward-compat
                $priorite,
                'open',
                new DateTime(),
                'REC-' . date('YmdHis'),
                $desc,
                clean($_POST['email']),
                $objectType,
                $objectRef
            );

            $reclamationC->addReclamation($reclamation, $userId);
            header('Location: reclamationList.php');
            exit();
        } else {
            $error = implode('<br>', $errors);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Nouvelle réclamation — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/reclamation.css">
    <script src="assets/js/reclamation-validation.js"></script>
    <script src="assets/js/reclamation.js"></script>
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
                <div class="page-title-main">Nouvelle réclamation</div>
                <div class="page-breadcrumb">
                    <i class="bi bi-house"></i>
                    <a href="client.php">Accueil</a>
                    <i class="bi bi-chevron-right"></i>
                    <a href="reclamationList.php">Réclamations</a>
                    <i class="bi bi-chevron-right"></i>
                    <span>Ajouter</span>
                </div>
            </div>
        </div>

        <div class="form-page-card">

            <?php if (!empty($error)) { ?>
                <div class="alert-error">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?php echo $error; ?>
                </div>
            <?php } ?>

            <form method="POST" onsubmit="return validateReclamationForm()">
                <input type="hidden" name="action" value="add">

                <!-- EMAIL -->
                <div class="form-group">
                    <label class="form-label"><i class="bi bi-envelope"></i> EMAIL *</label>
                    <input type="email" class="form-control" id="fEmail" name="email"
                           placeholder="Ex : client@exemple.com"
                           value="<?php echo h($_POST['email'] ?? $userEmail); ?>"
                           readonly>
                    <span class="field-error" id="email_error"></span>
                </div>

                <!-- OBJET -->
                <div class="form-group">
                    <label class="form-label"><i class="bi bi-pencil-square"></i> OBJET *</label>
                    <input type="text" class="form-control" id="fObjet" name="objet"
                           placeholder="Ex : Remboursement refusé"
                           value="<?php echo h($_POST['objet'] ?? ''); ?>">
                    <span class="field-error" id="objet_error"></span>
                    <div class="char-counter" id="charCountObjet"></div>
                </div>

                <!-- MODULE CONCERNÉ -->
                <div class="form-group">
                    <label class="form-label"><i class="bi bi-layers"></i> MODULE CONCERNÉ</label>
                    <select class="form-control" id="fObjectType" name="object_type"
                            onchange="loadModuleObjects(this.value)">
                        <option value="general"  <?php echo (($_POST['object_type'] ?? '') === 'general')  ? 'selected' : ''; ?>>Général (sans référence)</option>
                        <option value="contrat"  <?php echo (($_POST['object_type'] ?? 'contrat') === 'contrat')  ? 'selected' : ''; ?>>Contrat</option>
                        <option value="devis"    <?php echo (($_POST['object_type'] ?? '') === 'devis')    ? 'selected' : ''; ?>>Devis</option>
                        <option value="sinistre" <?php echo (($_POST['object_type'] ?? '') === 'sinistre') ? 'selected' : ''; ?>>Sinistre</option>
                        <option value="paiement" <?php echo (($_POST['object_type'] ?? '') === 'paiement') ? 'selected' : ''; ?>>Paiement</option>
                        <option value="poste"    <?php echo (($_POST['object_type'] ?? '') === 'poste')    ? 'selected' : ''; ?>>Poste social</option>
                    </select>
                </div>

                <!-- RÉFÉRENCE OBJET (chargé dynamiquement) -->
                <div class="form-group" id="objectRefGroup" style="display:none;">
                    <label class="form-label" id="objectRefLabel"><i class="bi bi-link-45deg"></i> RÉFÉRENCE *</label>
                    <select class="form-control" id="fObjectRef" name="object_ref">
                        <option value="">-- Chargement... --</option>
                    </select>
                    <span class="field-error" id="object_ref_error"></span>
                    <div id="loadingObjects" style="display:none;color:#666;font-size:12px;margin-top:5px;">
                        <i class="bi bi-hourglass-split"></i> Chargement...
                    </div>
                </div>

                <!-- Backward-compat hidden field -->
                <input type="hidden" id="fRefContrat" name="ref_contrat" value="">

                <!-- TYPE + PRIORITÉ -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="bi bi-tag"></i> TYPE *</label>
                        <select class="form-control" id="fType" name="type">
                            <option value="Santé"      <?php echo (($_POST['type'] ?? '') === 'Santé')      ? 'selected' : ''; ?>>Santé</option>
                            <option value="Auto"       <?php echo (($_POST['type'] ?? '') === 'Auto')       ? 'selected' : ''; ?>>Auto</option>
                            <option value="Habitation" <?php echo (($_POST['type'] ?? '') === 'Habitation') ? 'selected' : ''; ?>>Habitation</option>
                            <option value="Autre"      <?php echo (($_POST['type'] ?? '') === 'Autre')      ? 'selected' : ''; ?>>Autre</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="bi bi-flag"></i> PRIORITÉ</label>
                        <select class="form-control" id="fPriorite" name="priorite">
                            <option value="Normale" <?php echo (($_POST['priorite'] ?? '') === 'Normale') ? 'selected' : ''; ?>>Normale</option>
                            <option value="Urgente" <?php echo (($_POST['priorite'] ?? '') === 'Urgente') ? 'selected' : ''; ?>>Urgente</option>
                            <option value="Faible"  <?php echo (($_POST['priorite'] ?? '') === 'Faible')  ? 'selected' : ''; ?>>Faible</option>
                        </select>
                    </div>
                </div>

                <!-- DESCRIPTION -->
                <div class="form-group">
                    <label class="form-label"><i class="bi bi-chat-dots"></i> DESCRIPTION *</label>
                    <textarea class="form-control" id="fDesc" name="description"
                              placeholder="Décrivez votre réclamation en détail..."><?php echo h($_POST['description'] ?? ''); ?></textarea>

                    <div class="voice-input-box">
                        <button type="button" class="btn-voice" id="btnVoiceDesc">
                            <i class="bi bi-mic"></i>
                            <span id="voiceBtnText">Dicter la description</span>
                        </button>
                        <button type="button" class="btn-voice" id="btnAIAssist"
                                style="background:#7c3aed;border-color:#7c3aed;margin-left:8px;">
                            <i class="bi bi-stars"></i>
                            <span>Aide IA</span>
                        </button>
                        <span class="voice-status" id="voiceStatus">Cliquez puis parlez : le vocal sera converti en texte.</span>
                    </div>

                    <span class="field-error" id="desc_error"></span>
                    <div class="char-counter" id="charCountDesc"></div>
                </div>

                <div class="form-actions">
                    <a href="reclamationList.php" class="btn-cancel">
                        <i class="bi bi-arrow-left"></i> Annuler
                    </a>
                    <button type="submit" class="btn-submit">
                        <i class="bi bi-send"></i> Envoyer
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<!-- ══ Module object loader ══════════════════════════════════════════════ -->
<script>
const OBJECT_TYPE_LABELS = {
    contrat:  'Contrat',
    devis:    'Devis',
    sinistre: 'Sinistre',
    paiement: 'Paiement',
    poste:    'Poste social',
    general:  'Général'
};

document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('fObjectType');
    if (typeSelect) loadModuleObjects(typeSelect.value);
});

function loadModuleObjects(type) {
    const group   = document.getElementById('objectRefGroup');
    const label   = document.getElementById('objectRefLabel');
    const select  = document.getElementById('fObjectRef');
    const loading = document.getElementById('loadingObjects');
    const hidden  = document.getElementById('fRefContrat');

    if (type === 'general') {
        group.style.display = 'none';
        select.removeAttribute('required');
        hidden.value = '';
        return;
    }

    label.innerHTML = '<i class="bi bi-link-45deg"></i> ' + (OBJECT_TYPE_LABELS[type] || 'Référence') + ' *';
    group.style.display = 'block';
    select.setAttribute('required', 'required');
    loading.style.display = 'inline';

    fetch('api_get_user_objects.php?type=' + encodeURIComponent(type))
        .then(r => r.json())
        .then(data => {
            loading.style.display = 'none';
            select.innerHTML = '<option value="">-- Sélectionnez --</option>';

            if (data.success && data.items && data.items.length > 0) {
                data.items.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.label;
                    select.appendChild(opt);
                });
            } else {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = '-- Aucun ' + (OBJECT_TYPE_LABELS[type] || type) + ' disponible --';
                opt.disabled = true;
                select.appendChild(opt);
            }
        })
        .catch(err => {
            loading.style.display = 'none';
            console.error('Erreur chargement objets:', err);
        });

    // Keep backward-compat hidden field in sync for contrat
    select.onchange = function() {
        hidden.value = (type === 'contrat') ? this.value : '';
    };
}
</script>

<!-- ══ MODAL IA ASSIST ════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalAIAssist"
     style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:16px;padding:24px;max-width:520px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
      <h3 style="margin:0;font-size:18px;"><i class="bi bi-stars" style="color:#7c3aed;"></i> Assistant IA</h3>
      <button onclick="closeAIModal()" style="border:none;background:none;font-size:24px;cursor:pointer;color:#999;">&times;</button>
    </div>
    <p style="font-size:13px;color:#666;margin-bottom:16px;">Décrivez brièvement votre situation, l'IA rédigera une description professionnelle.</p>
    <textarea id="aiSituation" class="form-control" rows="3"
              placeholder="Ex : Mon remboursement dentaire a été refusé..."
              style="width:100%;padding:12px;border:1px solid #ddd;border-radius:8px;margin-bottom:12px;"></textarea>
    <div style="display:flex;gap:8px;justify-content:flex-end;">
      <button onclick="closeAIModal()" style="padding:8px 16px;border-radius:8px;border:1px solid #ddd;background:#f8f9fa;">Annuler</button>
      <button id="btnAIGenerate" onclick="generateAIDescription()"
              style="padding:8px 16px;border-radius:8px;background:#7c3aed;color:white;border:none;">
        <i class="bi bi-stars"></i> Générer
      </button>
    </div>
    <div id="aiLoading" style="display:none;text-align:center;padding:16px;color:#7c3aed;">
      <i class="bi bi-hourglass-split"></i> Génération en cours...
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('btnAIAssist').addEventListener('click', function() {
        document.getElementById('modalAIAssist').style.display = 'flex';
    });
});

function closeAIModal() {
    document.getElementById('modalAIAssist').style.display = 'none';
}

function generateAIDescription() {
    var situation = document.getElementById('aiSituation').value.trim();
    if (!situation) { alert('Veuillez décrire votre situation.'); return; }

    var btn = document.getElementById('btnAIGenerate');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Génération...';
    document.getElementById('aiLoading').style.display = 'block';

    fetch('/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=ai_assist_reclamation&situation=' + encodeURIComponent(situation)
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-stars"></i> Générer';
        document.getElementById('aiLoading').style.display = 'none';
        if (data.success) {
            document.getElementById('fDesc').value = data.text;
            closeAIModal();
        } else {
            alert(data.error || 'Erreur lors de la génération.');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-stars"></i> Générer';
        document.getElementById('aiLoading').style.display = 'none';
        alert('Erreur réseau.');
    });
}
</script>
</body>
</html>
