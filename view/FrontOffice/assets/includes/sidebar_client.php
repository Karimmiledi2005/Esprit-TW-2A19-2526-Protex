<?php

require_once dirname(__DIR__, 4) . '/helpers/SessionGuard.php';
require_once dirname(__DIR__, 4) . '/helpers/SidebarUser.php';

if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 4) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}

$uid      = SessionGuard::userId();
$navUser  = $uid > 0 ? SidebarUser::fetchUserById($uid) : null;
$prenom   = $navUser['prenom'] ?? ($_SESSION['user_prenom'] ?? $_SESSION['prenom'] ?? '');
$nom      = $navUser['nom']    ?? ($_SESSION['user_nom']    ?? $_SESSION['nom']    ?? '');
$fullName = trim($prenom . ' ' . $nom);
if ($fullName === '') {
    $fullName = SessionGuard::fullName();
}
$clientInit = SidebarUser::initials($prenom, $nom);
if ($clientInit === 'AD') {
    $clientInit = 'CL';
}
$clientAvatarUrl = SidebarUser::resolveAvatarUrl(
    $navUser['avatar'] ?? null,
    $navUser['avatar_url'] ?? null
);
$clientAvatarHtml = SidebarUser::renderAvatarInner($clientInit, $clientAvatarUrl);

$uri = strtolower($_SERVER['REQUEST_URI'] ?? '');
function sidebarClientActive(string ...$pages): string {
    global $uri;
    foreach ($pages as $p) {
        if (str_contains($uri, $p)) {
            return 'active';
        }
    }
    return '';
}
?>
<aside class="sidebar animate-fadeInLeft">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="logo-icon"><i class="bi bi-shield-fill-check"></i></div>
            <span class="logo-text">Protex</span>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar" id="sidebarUserAvatar" data-sidebar-avatar><?= $clientAvatarHtml ?></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="user-role">Client</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li class="nav-item <?= sidebarClientActive('client.php') ?>">
                <a href="client.php"><i class="bi bi-grid-1x2"></i><span>Tableau de bord</span></a>
            </li>
            <li class="nav-item <?= sidebarClientActive('contrat') ?>">
                <a href="contrat.php"><i class="bi bi-file-earmark-text"></i><span>Mes contrats</span></a>
            </li>
            <li class="nav-item <?= sidebarClientActive('devis') ?>">
                <a href="mes_devis.php"><i class="bi bi-file-earmark-medical"></i><span>Mes devis</span></a>
            </li>
            <li class="nav-item <?= sidebarClientActive('sinistre') ?>">
                <a href="mes-sinistres.php"><i class="bi bi-exclamation-triangle"></i><span>Mes sinistres</span></a>
            </li>
            <li class="nav-item <?= sidebarClientActive('reclamation') ?>">
                <a href="reclamationList.php"><i class="bi bi-chat-square-text"></i><span>Réclamations</span></a>
            </li>
            <li class="nav-item <?= sidebarClientActive('monprofile') ?>">
                <a href="monprofile.php"><i class="bi bi-person-circle"></i><span>Mon profil</span></a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/controller/AuthController.php?action=logout"
           class="logout-btn">
            <i class="bi bi-box-arrow-left"></i><span>Déconnexion</span>
        </a>
    </div>
</aside>
<script>window.BASE_URL = '<?= defined('BASE_URL') ? BASE_URL : '' ?>';</script>
<script src="<?= (defined('BASE_URL') ? BASE_URL : '') ?>/view/BackOffice/assets/js/sidebar-user.js"></script>
