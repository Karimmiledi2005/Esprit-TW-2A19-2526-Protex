<?php
/**
 * helpers/SidebarUser.php — Avatar et initiales (BackOffice + FrontOffice)
 */
class SidebarUser
{
    public static function fetchUserById(int $id): ?array
    {
        if ($id <= 0 || !class_exists('config')) {
            return null;
        }
        try {
            $db = config::getConnexion();
            $stmt = $db->prepare(
                "SELECT u.nom, u.prenom, u.email, u.role, u.avatar, u.avatar_url, anc.nom_agence
                 FROM user u
                 LEFT JOIN client c ON u.id_user = c.id_user
                 LEFT JOIN admin a ON u.id_user = a.id_user
                 LEFT JOIN agent ag ON u.id_user = ag.id_user
                 LEFT JOIN agence anc ON (
                     c.id_agence = anc.id_agence
                     OR a.id_agence = anc.id_agence
                     OR ag.id_agence = anc.id_agence
                 )
                 WHERE u.id_user = ? LIMIT 1"
            );
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    public static function clientRoleLabel(string $role): string
    {
        return match ($role) {
            'superadmin' => 'Super Admin',
            'admin'      => 'Administrateur',
            'agent'      => 'Agent',
            default      => 'Client',
        };
    }
    public static function initials(?string $prenom, ?string $nom): string
    {
        $prenom = trim((string) $prenom);
        $nom    = trim((string) $nom);
        if ($prenom !== '' || $nom !== '') {
            $init = mb_strtoupper(mb_substr($nom, 0, 1) . mb_substr($prenom, 0, 1));
            return $init !== '' ? $init : 'AD';
        }
        return 'AD';
    }

    public static function resolveAvatarUrl(?string $avatar, ?string $avatarUrl): string
    {
        $avatarUrl = trim((string) $avatarUrl);
        if ($avatarUrl !== '' && preg_match('#^https?://#i', $avatarUrl)) {
            return $avatarUrl;
        }

        $avatar = trim((string) $avatar);
        if ($avatar === '' || in_array($avatar, ['default.png', 'default'], true)) {
            return '';
        }

        $base = defined('BASE_URL') ? BASE_URL : '';

        if (preg_match('#^https?://#i', $avatar)) {
            return $avatar;
        }
        if (str_contains($avatar, '/')) {
            return $base . '/' . ltrim($avatar, '/');
        }

        return $base . '/uploads/avatars/' . rawurlencode($avatar);
    }

    public static function roleLabel(string $role, ?string $nomAgence = null): string
    {
        $label = $role !== '' ? ucfirst($role) : 'Admin';
        if ($nomAgence !== null && trim($nomAgence) !== '') {
            $label .= ' (' . trim($nomAgence) . ')';
        }
        return $label;
    }

    public static function renderAvatarInner(string $initials, string $imageUrl): string
    {
        $init = htmlspecialchars($initials, ENT_QUOTES, 'UTF-8');
        if ($imageUrl === '') {
            return $init;
        }
        $src = htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8');
        return '<img src="' . $src . '" alt="" width="42" height="42"'
            . ' style="width:100%;height:100%;object-fit:cover;border-radius:50%;"'
            . ' onerror="this.remove();this.parentElement.textContent=\'' . $init . '\';">';
    }
}
