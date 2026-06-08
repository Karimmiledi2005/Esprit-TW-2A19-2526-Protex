<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();

$reponseC     = new ReponseController();
$reclamationC = new ReclamationController();

if (!function_exists('h')) {
    function h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

function formatDateFr($date) {
    if (empty($date)) return 'é';
    $ts = strtotime($date);
    if (!$ts) return $date;
    $months = [1=>'Janvier',2=>'Février',3=>'Mars',4=>'Avril',5=>'Mai',6=>'Juin',
               7=>'Juillet',8=>'Aoét',9=>'Septembre',10=>'Octobre',11=>'Novembre',12=>'Décembre'];
    return date('d', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: reclamationList.php');
    exit();
}

// Récupére la réclamation (dédiée) et fusionne la réponse si présente
$row = $reclamationC->showReclamation($id);
if (!$row) {
    header('Location: reclamationList.php');
    exit();
}
$userId = (int)($_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 0);
if ((int)$row['id_user'] !== $userId) {
    header('Location: reclamationList.php?error=acces_refuse');
    exit();
}

$reponse = null;
$allRows = $reponseC->listAllReclamations();
foreach ($allRows as $r) {
    if ((int)$r['id'] === $id) { $reponse = $r; break; }
}
if ($reponse) {
    $row = array_merge($row, [
        'rep_id' => $reponse['rep_id'] ?? null,
        'reponse_contenu' => $reponse['reponse_contenu'] ?? null,
        'rep_date' => $reponse['rep_date'] ?? null,
        'rep_statut' => $reponse['rep_statut'] ?? null,
    ]);
}

$statut      = $row['statut'] ?? 'open';
$rep_statut  = $row['rep_statut'] ?? '';

$badgeClass = ['closed'=>'badge-success','rejected'=>'badge-danger','pending'=>'badge-info'];
$badgeLabel = ['closed'=>'Résolue','rejected'=>'Rejetée','pending'=>'En attente','open'=>'En cours'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Détail réclamation é Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="assets/css/reclamation.css">

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
    <!-- ======== NAVBAR ======== -->
    <?php require_once __DIR__.'/assets/includes/navbar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title-main">Détail de la réclamation</div>
                <div class="page-breadcrumb">
                    <i class="bi bi-house"></i>
                    <a href="client.php">Accueil</a>
                    <i class="bi bi-chevron-right"></i>
                    <a href="reclamationList.php">Réclamations</a>
                    <i class="bi bi-chevron-right"></i>
                    <span>Détails</span>
                </div>
            </div>
            <a href="reclamationList.php" class="btn-new" style="background:transparent;border:1px solid var(--glass-border);color:white">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>

        <div class="rec-card <?php echo $statut; ?>" style="padding:24px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;">
                <div>
                    <h2 style="margin:0;font-size:22px;color:white;"><?php echo h($row['objet'] ?? ''); ?></h2>
                    <div style="font-size:13px;color:var(--text-secondary);margin-top:5px;">
                        Contrat : <?php echo h($row['ref_contrat'] ?? 'é'); ?>
                    </div>
                </div>
                <span class="badge <?php echo $badgeClass[$statut] ?? 'badge-warning'; ?>">
                    <?php echo $badgeLabel[$statut] ?? 'En cours'; ?>
                </span>
            </div>

            <div class="rec-body" style="margin-bottom:20px;">
                <div class="rec-meta-item"> <label>Type</label> <span><?php echo h($row['type'] ?? 'é'); ?></span> </div>
                <div class="rec-meta-item"> <label>Priorité</label> <span><?php echo h($row['priorite'] ?? 'é'); ?></span> </div>
                <div class="rec-meta-item"> <label>Déposée le</label> <span><?php echo h(formatDateFr($row['date_depot'] ?? '')); ?></span> </div>
            </div>

            <div style="border-top:1px solid rgba(255,255,255,0.1);padding-top:20px;">
                <label style="font-size:11px;color:var(--text-secondary);text-transform:uppercase;font-weight:700;">Description</label>
                <div style="color:white;font-size:14px;line-height:1.6;margin-top:8px;">
                    <?php echo nl2br(h($row['description'] ?? '')); ?>
                </div>
            </div>
        </div>

        <?php if (!empty($row['rep_id'])): ?>
        <div class="rec-card closed" style="margin-top:20px;padding:24px;border-left:4px solid var(--success);">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:15px;">
                <i class="bi bi-chat-left-text" style="color:var(--success);font-size:20px;"></i>
                <h3 style="margin:0;font-size:18px;color:white;">Réponse de l'administration</h3>
                <span style="font-size:12px;color:var(--text-secondary);margin-left:auto;">
                    <?php echo h(formatDateFr($row['rep_date'] ?? '')); ?>
                </span>
            </div>
            <div style="background:rgba(255,255,255,0.05);padding:15px;border-radius:8px;color:white;font-size:14px;line-height:1.7;">
                <?php echo nl2br(h($row['reponse_contenu'] ?? '')); ?>
            </div>
        </div>
        <?php else: ?>
        <div class="rec-card" style="margin-top:20px;padding:30px;text-align:center;">
             <i class="bi bi-hourglass-split" style="font-size:32px;color:var(--accent);margin-bottom:10px;display:block;"></i>
             <div style="color:var(--text-secondary);">En attente de traitement par nos services...</div>
        </div>
        <?php endif; ?>
    </main>
</div>

<script>
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



