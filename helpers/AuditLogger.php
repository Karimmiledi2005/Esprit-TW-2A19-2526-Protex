<?php
/**
  * helpers/AuditLogger.php — Logger d'audit pour Protex
  */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/SessionGuard.php';

class AuditLogger
{
    /**
     * Enregistre une action d'audit dans la base de données.
     *
     * @param string $action Nom de l'action (ex: suppression_user, toggle_statut)
     * @param string $cible Cible de l'action (ex: user, contrat, etc.)
     * @param string $details Détails complémentaires (ex: ID de la cible)
     * @return void
     */
    public static function log(string $action, string $cible, string $details = ''): void
    {
        try {
            $db = config::getConnexion();
            $idUser = SessionGuard::userId(); // 0 si non connecté (ou CLI)
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            $stmt = $db->prepare("
                INSERT INTO audit_log (id_user, action, cible, details, ip, created_at)
                VALUES (:id_user, :action, :cible, :details, :ip, NOW())
            ");
            
            $stmt->execute([
                ':id_user' => $idUser > 0 ? $idUser : null,
                ':action'  => $action,
                ':cible'   => $cible,
                ':details' => $details,
                ':ip'      => $ip,
            ]);
        } catch (Exception $e) {
            error_log('[AuditLogger] Error logging audit action: ' . $e->getMessage());
        }
    }
}
