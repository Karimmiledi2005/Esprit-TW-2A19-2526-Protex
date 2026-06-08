<?php
/**
 * helpers/RoleHelper.php
 * FIX P-12 : Lit $_{SESSION['role'] ET $_SESSION['user_role'] (compatibilité des deux systèmes)
 * FIX P-13 : Lit $_SESSION['id_user'] ET $_SESSION['user_id'] (même pivôt)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
class RoleHelper {

    public static function getRole(): string {
        // Priorité : 'role' (camarades) ; fallback : 'user_role' (mon dossier)
        return $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'client';
    }

    public static function getAgenceId(): ?int {
        if (isset($_SESSION['agence_id'])) return (int)$_SESSION['agence_id'];
        if (isset($_SESSION['id_agence'])) return (int)$_SESSION['id_agence'];
        
        // Fallback si la session est incomplète (évite de forcer un logout)
        $uid = self::getUserId();
        if ($uid > 0) {
            try {
                $db = config::getConnexion();
                $role = self::getRole();
                $table = ($role === 'admin') ? 'admin' : (($role === 'agent') ? 'agent' : 'client');
                $stmt = $db->prepare("SELECT id_agence FROM $table WHERE id_user = ?");
                $stmt->execute([$uid]);
                $id = $stmt->fetchColumn();
                if ($id) {
                    $_SESSION['id_agence'] = (int)$id;
                    return (int)$id;
                }
            } catch (Exception $e) { error_log("RoleHelper::getAgenceId fallback error: " . $e->getMessage()); }
        }
        return null;
    }

    public static function getUserId(): int {
        // Accepte les deux clés — $_SESSION['user_id'] écrite par Client_Con.php ET AuthController.php
        return (int)($_SESSION['user_id'] ?? $_SESSION['id_user'] ?? 0);
    }

    public static function isSuperAdmin(): bool {
        return self::getRole() === 'superadmin';
    }

    public static function isAdminAgence(): bool {
        return self::getRole() === 'admin';
    }

    public static function isAgent(): bool {
        return self::getRole() === 'agent';
    }

    public static function isClient(): bool {
        return self::getRole() === 'client';
    }

    // SINISTRE
    public static function canDeleteSinistre(): bool {
        return self::isSuperAdmin() || self::isAdminAgence();
    }

    public static function canModifySinistre(): bool {
        return self::isSuperAdmin() || self::isAdminAgence();
    }

    public static function canAssignSinistre(): bool {
        return self::isSuperAdmin() || self::isAdminAgence();
    }

    public static function canSeeFraudScore(): bool {
        return in_array(self::getRole(), ['superadmin', 'admin', 'agent']);
    }

    public static function canSeeFraudScoreGlobal(): bool {
        return self::isSuperAdmin();
    }

    public static function canExportSinistres(): bool {
        return self::isSuperAdmin() || self::isAdminAgence();
    }

    // TRAITEMENT
    public static function canDeleteTraitement(): bool {
        return self::isSuperAdmin() || self::isAdminAgence();
    }

    public static function canValiderTraitement(): bool {
        return self::isSuperAdmin() || self::isAdminAgence();
    }

    public static function canOverrideDecision(): bool {
        return self::isSuperAdmin();
    }

    public static function canModifyTraitement(int $idAgentTraitement, bool $estValide = false): bool {
        if (self::isSuperAdmin() || self::isAdminAgence()) return true;
        return self::isAgent() && $idAgentTraitement === self::getUserId() && !$estValide;
    }

    public static function canCreateTraitement(): bool {
        return self::isSuperAdmin() || self::isAdminAgence() || self::isAgent();
    }

    public static function canSeeStatsAgence(): bool {
        return self::isSuperAdmin() || self::isAdminAgence();
    }

    public static function canSeeStatsGlobales(): bool {
        return self::isSuperAdmin();
    }

    // RECLAMATION & REPONSE
    public static function canRepondreReclamation(): bool {
        return in_array(self::getRole(), ['superadmin', 'admin', 'agent']);
    }

    public static function canRejeterReclamation(): bool {
        return self::isSuperAdmin() || self::isAdminAgence();
    }

    public static function canModifierReponse(): bool {
        return self::isSuperAdmin() || self::isAdminAgence();
    }

    public static function canSupprimerReponse(): bool {
        return self::isSuperAdmin() || self::isAdminAgence();
    }

    public static function canVoirToutesAgences(): bool {
        return self::isSuperAdmin();
    }

    // UTILISATEURS
    public static function canManageUsers(): bool {
        return self::isSuperAdmin() || self::isAdminAgence() || self::isAgent();
    }

    // DEVIS
    public static function canManageDevis(): bool {
        return self::isSuperAdmin() || self::isAdminAgence() || self::isAgent();
    }

    public static function canConvertDevis(): bool {
        return self::isSuperAdmin() || self::isAdminAgence() || self::isAgent();
    }

    // CONTRATS
    public static function canManageContrats(): bool {
        return self::isSuperAdmin() || self::isAdminAgence() || self::isAgent();
    }

    // OFFRES / CATEGORIES / FORMULES / GARANTIES
    public static function canManageOffres(): bool {
        return self::isSuperAdmin() || self::isAdminAgence() || self::isAgent();
    }

    // PAIEMENTS
    public static function canManagePaiements(): bool {
        return self::isSuperAdmin() || self::isAdminAgence() || self::isAgent();
    }

    // AGENCES & POSTES
    public static function canManageAgences(): bool {
        return self::isSuperAdmin() || self::isAdminAgence() || self::isAgent();
    }

    public static function canManagePostes(): bool {
        return self::isSuperAdmin() || self::isAdminAgence() || self::isAgent();
    }

    // Guard : bloque avec 403 JSON si rôle non autorisé
    public static function requireRole(array $rolesAutorises): void {
        if (!in_array(self::getRole(), $rolesAutorises)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
            exit;
        }
    }

    // Guard : redirige si non connecté
    public static function requireAuth(string $loginUrl = null): void {
        if (!self::getUserId()) {
            if ($loginUrl === null) {
                $loginUrl = (defined('BASE_URL') ? BASE_URL : '') . '/view/FrontOffice/login.php';
            }
            header('Location: ' . $loginUrl);
            exit;
        }
    }
}
