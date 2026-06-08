<?php
require_once __DIR__ . '/../../bootstrap.php';
RoleHelper::requireRole(['superadmin', 'admin', 'agent']);

// Désactiver le buffer pour éviter les corruptions
if (ob_get_level()) ob_end_clean();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="reclamations_' . date('Ymd_His') . '.csv"');
header('Cache-Control: no-cache');

$ctrl = new ReponseController();
// On récupère tout pour l'export (ou on pourrait appliquer les filtres s'ils étaient passés)
$rows = $ctrl->listAllReclamations(1, 10000);

$out = fopen('php://output', 'w');
// BOM UTF-8 pour Excel
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($out, [
    'ID', 
    'Email', 
    'Objet', 
    'Type', 
    'Priorité', 
    'Statut', 
    'Réf. contrat', 
    'Date dépôt', 
    'Réponse', 
    'Date réponse',
    'Agence',
    'Agent'
], ';');

foreach ($rows as $r) {
    fputcsv($out, [
        $r['id'],
        $r['email'] ?? '',
        $r['objet'] ?? '',
        $r['type'] ?? '',
        $r['priorite'] ?? '',
        $r['statut'] ?? '',
        $r['ref_contrat'] ?? '',
        $r['date_depot'] ?? '',
        $r['reponse_contenu'] ?? '',
        $r['rep_date'] ?? '',
        $r['nom_agence'] ?? 'N/A',
        ($r['agent_nom'] ? $r['agent_prenom'] . ' ' . $r['agent_nom'] : 'N/A')
    ], ';');
}

fclose($out);
exit;
