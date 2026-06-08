<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once dirname(__DIR__, 2) . '/config.php';

$client_id = $_SESSION['user_id'] ?? $_SESSION['id_client'] ?? null;
if (!$client_id) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

if (!empty($_GET['track_offre'])) {
    $track_id = (int)$_GET['track_offre'];
    try {
        $db = config::getConnexion();
        $stmt = $db->prepare("INSERT INTO recommendation_click (id_client, id_offre, clicked_at) VALUES (?, ?, NOW())");
        $stmt->execute([$client_id, $track_id]);
    } catch (Exception $e) {}
}

try {
    $db = config::getConnexion();

    // User profile
    $user_stmt = $db->prepare("SELECT nom, prenom, date_naissance, adresse FROM user WHERE id_user = ?");
    $user_stmt->execute([$client_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    $age = null;
    if ($user && $user['date_naissance']) {
        $birth = new DateTime($user['date_naissance']);
        $age = (int) $birth->diff(new DateTime())->y;
    }

    // Claims history by type
    $claims_stmt = $db->prepare("
        SELECT LOWER(s.type) as stype, COUNT(*) as cnt
        FROM sinistre s
        JOIN contrat c ON c.id_contrat = s.id_contrat
        WHERE c.id_user = ?
        GROUP BY LOWER(s.type)
    ");
    $claims_stmt->execute([$client_id]);
    $claims_map = [];
    while ($row = $claims_stmt->fetch(PDO::FETCH_ASSOC)) {
        $claims_map[$row['stype']] = (int)$row['cnt'];
    }

    // Existing contract types from contrat.type_contrat
    $existing_stmt = $db->prepare("
        SELECT DISTINCT LOWER(type_contrat) FROM contrat
        WHERE id_user = ? AND statut_contrat IN ('actif')
    ");
    $existing_stmt->execute([$client_id]);
    $existing_types = $existing_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Total contracts count
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM contrat WHERE id_user = ? AND statut_contrat IN ('actif')");
    $count_stmt->execute([$client_id]);
    $total_contrats = (int)$count_stmt->fetchColumn();

    // Build query
    $placeholders = count($existing_types) > 0
        ? implode(',', array_fill(0, count($existing_types), '?'))
        : "'__none__'";
    $params = $existing_types;

    $query = "
        SELECT 
            o.*,
            COALESCE((SELECT AVG(note) FROM avis_offre WHERE id_offre = o.id_offre), 0) as note_moyenne,
            CASE 
                WHEN LOWER(o.type_offre) NOT IN ($placeholders) THEN 3
                ELSE 1
            END as priority_score
        FROM offre o
        WHERE o.statut = 'active'
        ORDER BY priority_score DESC, note_moyenne DESC
        LIMIT 5
    ";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $offres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Human-readable reasons with personalization
    $reasons = [
        'auto'       => 'Complétez votre protection avec une assurance auto fiable.',
        'sante'      => 'Protégez votre santé et celle de votre famille.',
        'habitation' => 'Sécurisez votre domicile avec notre formule habitation.',
        'vie'        => 'Préparez l\'avenir avec notre assurance vie premium.',
    ];

    foreach ($offres as &$o) {
        $t = strtolower($o['type_offre'] ?? '');
        $badge = 'Recommandé';
        $raison = '';

        $claim_keywords = [
            'auto'       => ['accident auto', 'accident', 'vol', 'bris de glace', 'incendie'],
            'habitation' => ['dégât des eaux', 'incendie', 'catastrophe', 'cambriolage'],
            'sante'      => ['hospitalisation', 'accident corporel', 'maladie'],
            'vie'        => ['décès', 'invalidité'],
        ];
        $matched_claims = 0;
        if (isset($claim_keywords[$t])) {
            foreach ($claim_keywords[$t] as $kw) {
                if (isset($claims_map[$kw])) {
                    $matched_claims += $claims_map[$kw];
                }
            }
        }

        if (in_array($t, $existing_types)) {
            $note = round((float)$o['note_moyenne'], 1);
            $raison = "Vous avez déjà un contrat $t · Note $note/5 ⭐";
            $badge = 'Déjà client';
        } elseif ($matched_claims > 0) {
            $raison = "Suite à vos sinistres récents, cette couverture est fortement recommandée.";
            $badge = '🔍 Priorité';
        } elseif ($age !== null && $t === 'vie' && $age >= 40) {
            $raison = "À partir de $age ans, préparer l'avenir devient essentiel.";
            $badge = '💡 Conseil';
        } elseif ($age !== null && $t === 'sante' && $age >= 50) {
            $raison = "Avec l'âge, une bonne couverture santé est primordiale.";
            $badge = '💡 Conseil';
        } elseif ($total_contrats === 0) {
            $raison = "Première assurance ? Idéal pour débuter.";
            $badge = '🎯 Starter';
        } else {
            $raison = $reasons[$t] ?? 'Offre recommandée pour vous.';
        }

        $o['raison'] = $raison;
        $o['badge'] = $badge;
    }

    // Re-rank: prioritize claims-matched offers
    usort($offres, function($a, $b) {
        $a_claims = strpos($a['raison'], 'sinistres') !== false ? 1 : 0;
        $b_claims = strpos($b['raison'], 'sinistres') !== false ? 1 : 0;
        if ($a_claims !== $b_claims) return $b_claims - $a_claims;
        return $b['priority_score'] - $a['priority_score'];
    });

    echo json_encode(['success' => true, 'offres' => array_slice($offres, 0, 3)]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
