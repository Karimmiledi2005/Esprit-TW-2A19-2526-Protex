<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
require_once dirname(__DIR__, 2) . '/config.php';

header('Content-Type: application/json; charset=utf-8');

$userId = SessionGuard::userId();
if ($userId <= 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$action = $_GET['action'] ?? '';
$db = config::getConnexion();

if ($action === 'upcoming_alerts') {
    // Contrats with echeance in the next 7 days without validated payment this month
    $stmt = $db->prepare("
        SELECT c.id_contrat, c.numero_contrat, c.prime_contrat, c.date_fin_contrat,
               DATEDIFF(c.date_fin_contrat, NOW()) as jours_restants
        FROM contrat c
        WHERE c.id_user = ?
          AND c.statut_contrat = 'actif'
          AND c.date_fin_contrat BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
          AND c.id_contrat NOT IN (
              SELECT p.id_offre FROM paiement p 
              WHERE p.statut = 'valide'
                AND MONTH(p.date_paiement) = MONTH(NOW())
                AND YEAR(p.date_paiement) = YEAR(NOW())
          )
        ORDER BY c.date_fin_contrat ASC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $alerts = array_map(function($r) {
        return [
            'numero_contrat'   => $r['numero_contrat'],
            'date_echeance'    => date('d/m/Y', strtotime($r['date_fin_contrat'])),
            'prime'            => number_format((float)$r['prime_contrat'], 3, ',', ' ') . ' TND',
            'jours_restants'   => max(0, (int)$r['jours_restants'])
        ];
    }, $rows);

    echo json_encode(['success' => true, 'alerts' => $alerts]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Action inconnue']);
