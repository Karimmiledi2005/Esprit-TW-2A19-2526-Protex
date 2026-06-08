<?php

require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
require_once dirname(__DIR__, 2) . '/config.php';
SessionGuard::requireLogin();
header('Content-Type: application/json; charset=utf-8');

$uid = SessionGuard::userId();
$userEmail = SessionGuard::email();

try {
    $db = config::getConnexion();

    // 1. Contrats du client
    $stmtContrats = $db->prepare("
        SELECT c.id_contrat, c.statut_contrat AS statut, c.date_debut_contrat AS date_debut,
               c.date_fin_contrat AS date_fin, c.prime_contrat AS montant_total,
               c.type_contrat, cat.nom_categorie
        FROM contrat c
        LEFT JOIN categorie cat ON c.id_categorie = cat.id_categorie
        WHERE c.id_user = :uid
        ORDER BY c.date_debut_contrat DESC
    ");
    $stmtContrats->execute([':uid' => $uid]);
    $contrats = $stmtContrats->fetchAll(PDO::FETCH_ASSOC);

    // 2. Devis du client (via email)
    $stmtDevis = $db->prepare("
        SELECT d.id_devis, d.statut, d.date_demande, d.montant_estime,
               o.nom_offre, o.type_offre
        FROM devis d
        LEFT JOIN offre o ON d.id_offre = o.id_offre
        WHERE d.email = :email
        ORDER BY d.date_demande DESC
        LIMIT 10
    ");
    $stmtDevis->execute([':email' => $userEmail]);
    $devis = $stmtDevis->fetchAll(PDO::FETCH_ASSOC);

    // 3. Paiements du client (via email dans devis)
    $stmtPaie = $db->prepare("
        SELECT p.id_paiement, p.montant, p.statut, p.date_paiement, o.nom_offre
        FROM paiement p
        LEFT JOIN offre o ON p.id_offre = o.id_offre
        LEFT JOIN devis d ON p.id_devis = d.id_devis
        WHERE d.email = :email
        ORDER BY p.date_paiement DESC
        LIMIT 5
    ");
    $stmtPaie->execute([':email' => $userEmail]);
    $paiements = $stmtPaie->fetchAll(PDO::FETCH_ASSOC);

    // 4. Sinistres du client
    $stmtSin = $db->prepare("
        SELECT s.id_sinistre, s.type, s.statut, s.date_declaration,
               s.description, c.id_contrat
        FROM sinistre s
        LEFT JOIN contrat c ON s.id_contrat = c.id_contrat
        WHERE s.id_user = :uid
        ORDER BY s.date_declaration DESC
        LIMIT 5
    ");
    $stmtSin->execute([':uid' => $uid]);
    $sinistres = $stmtSin->fetchAll(PDO::FETCH_ASSOC);

    // 5. Reclamations du client
    $stmtRec = $db->prepare("
        SELECT r.id, r.objet, r.statut, r.date_depot AS date_creation
        FROM reclamation r
        WHERE r.id_user = :uid
        ORDER BY r.date_depot DESC
        LIMIT 5
    ");
    $stmtRec->execute([':uid' => $uid]);
    $reclamations = $stmtRec->fetchAll(PDO::FETCH_ASSOC);

    // 6. Recommandations (random offres)
    $stmtRecomm = $db->query("SELECT id_offre, nom_offre, type_offre, description, prix_mensuel AS prime_de_base FROM offre ORDER BY RAND() LIMIT 3");
    $recommandations = $stmtRecomm->fetchAll(PDO::FETCH_ASSOC);

    // 7. KPIs resume
    $kpi = [
        'contrats_actifs'   => count(array_filter($contrats, fn($c) => in_array($c['statut'], ['actif', 'active']))),
        'contrats_total'    => count($contrats),
        'devis_en_attente'  => count(array_filter($devis, fn($d) => in_array($d['statut'], ['en_attente', 'pending']))),
        'devis_total'       => count($devis),
        'sinistres_ouverts' => count(array_filter($sinistres, fn($s) => in_array($s['statut'], ['en_attente', 'pending']))),
        'montant_total'     => array_sum(array_column(
            array_filter($paiements, fn($p) => in_array($p['statut'], ['valide', 'success'])),
            'montant'
        )),
        'nom'               => SessionGuard::fullName(),
        'role'              => SessionGuard::role(),
    ];

    echo json_encode([
        'success'     => true,
        'kpi'         => $kpi,
        'contrats'    => $contrats,
        'devis'       => $devis,
        'paiements'   => $paiements,
        'sinistres'   => $sinistres,
        'reclamations'=> $reclamations,
        'recommandations' => $recommandations,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log('api_client_dashboard error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
}

