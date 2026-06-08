<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
require_once __DIR__ . '/../../bootstrap.php';
header('Content-Type: application/json');

echo json_encode([
    'role'                 => RoleHelper::getRole(),
    'userId'               => RoleHelper::getUserId(),
    'userName'             => trim(($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? 'Utilisateur')),
    'canDeleteSinistre'    => RoleHelper::canDeleteSinistre(),
    'canModifySinistre'    => RoleHelper::canModifySinistre(),
    'canAssignSinistre'    => RoleHelper::canAssignSinistre(),
    'canSeeFraudScore'     => RoleHelper::canSeeFraudScore(),
    'canExportSinistres'   => RoleHelper::canExportSinistres(),
    'canDeleteTraitement'  => RoleHelper::canDeleteTraitement(),
    'canValiderTraitement' => RoleHelper::canValiderTraitement(),
    'canCreateTraitement'  => RoleHelper::canCreateTraitement(),
    'canSeeStatsAgence'    => RoleHelper::canSeeStatsAgence(),
    'canSeeStatsGlobales'  => RoleHelper::canSeeStatsGlobales(),
    // Permissions Reclamation
    'canRepondre'          => RoleHelper::canRepondreReclamation(),
    'canRejeter'           => RoleHelper::canRejeterReclamation(),
    'canModifier'          => RoleHelper::canModifierReponse(),
    'canSupprimer'         => RoleHelper::canSupprimerReponse(),
    'csrfToken'            => CsrfHelper::getToken()
]);
