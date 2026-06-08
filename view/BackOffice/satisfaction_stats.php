<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}

$db = config::getConnexion();
$role = RoleHelper::getRole();
$idAg = RoleHelper::getAgenceId();

$where = '';
$params = [];
if (($role === 'admin' || $role === 'agent') && $idAg !== null) {
    $where = ' WHERE c.id_agence = :idAg ';
    $params['idAg'] = $idAg;
}

// Global stats
$stmt = $db->prepare("
    SELECT
        COUNT(s.id) AS total_avis,
        AVG(s.note) AS note_moyenne,
        SUM(CASE WHEN s.note >= 4 THEN 1 ELSE 0 END) AS positif,
        SUM(CASE WHEN s.note = 3 THEN 1 ELSE 0 END) AS neutre,
        SUM(CASE WHEN s.note <= 2 THEN 1 ELSE 0 END) AS negatif
    FROM reclamation_satisfaction s
    JOIN reclamation r ON s.id_reclamation = r.id
    LEFT JOIN client c ON r.id_user = c.id_user
    $where
");
$stmt->execute($params);
$global = $stmt->fetch(PDO::FETCH_ASSOC);

// Monthly trend (last 6 months)
$stmt = $db->prepare("
    SELECT
        DATE_FORMAT(s.created_at, '%Y-%m') AS mois,
        COUNT(*) AS nb,
        ROUND(AVG(s.note), 1) AS moyenne
    FROM reclamation_satisfaction s
    JOIN reclamation r ON s.id_reclamation = r.id
    LEFT JOIN client c ON r.id_user = c.id_user
    $where
    GROUP BY mois
    ORDER BY mois DESC
    LIMIT 6
");
$stmt->execute($params);
$monthly = $stmt->fetchAll(PDO::FETCH_ASSOC);

// By agent
$stmt = $db->prepare("
    SELECT
        u.prenom, u.nom,
        COUNT(s.id) AS nb_avis,
        ROUND(AVG(s.note), 1) AS moyenne,
        COUNT(r.id) AS nb_reclamations
    FROM reclamation_satisfaction s
    JOIN reclamation r ON s.id_reclamation = r.id
    JOIN reponse rp ON rp.reclamation_id = r.id AND rp.id_user IS NOT NULL
    JOIN `user` u ON rp.id_user = u.id_user
    LEFT JOIN client c ON r.id_user = c.id_user
    $where
    GROUP BY u.id_user
    ORDER BY moyenne DESC
");
$stmt->execute($params);
$agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Paginated recent ratings
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;
$stmt = $db->prepare("
    SELECT s.*, r.objet, u.nom, u.prenom
    FROM reclamation_satisfaction s
    JOIN reclamation r ON s.id_reclamation = r.id
    JOIN `user` u ON r.id_user = u.id_user
    LEFT JOIN client c ON r.id_user = c.id_user
    $where
    ORDER BY s.created_at DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtCnt = $db->prepare("SELECT COUNT(*) FROM reclamation_satisfaction s JOIN reclamation r ON s.id_reclamation = r.id LEFT JOIN client c ON r.id_user = c.id_user $where");
$stmtCnt->execute($params);
$totalRows = (int)$stmtCnt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$satisfactionPct = $global && $global['total_avis'] > 0 ? round(($global['positif'] / $global['total_avis']) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Statistiques Satisfaction — Protex Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/variables.css">
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/layout.css">
  <link rel="stylesheet" href="assets/css/admin-users.css">
  <style>
    .stat-card-sat { border-radius:12px; padding:20px; color:white; margin-bottom:16px; }
    .stat-card-sat h3 { font-size:13px; opacity:.9; margin:0; }
    .stat-card-sat .val { font-size:32px; font-weight:700; margin-top:8px; }
    .stat-card-sat .sub { font-size:12px; opacity:.7; margin-top:4px; }
    .bg-sat-excellent { background:linear-gradient(135deg,#22c55e,#16a34a); }
    .bg-sat-good { background:linear-gradient(135deg,#3b82f6,#2563eb); }
    .bg-sat-neutral { background:linear-gradient(135deg,#f59e0b,#d97706); }
    .bg-sat-poor { background:linear-gradient(135deg,#ef4444,#dc2626); }
    .rating-bar { height:8px; border-radius:4px; margin-top:4px; }
    .rating-bar.bg-success { background:#22c55e; }
    .rating-bar.bg-warning { background:#f59e0b; }
    .rating-bar.bg-danger { background:#ef4444; }
    .stars { color:#f59e0b; font-size:14px; }
  </style>
</head>
<body>
<div class="container-fluid p-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-star-fill" style="color:#f59e0b;"></i> Statistiques Satisfaction</h1>
    <a href="reponse.php" class="btn btn-outline"><i class="bi bi-arrow-left"></i> Retour</a>
  </div>

  <!-- KPI row -->
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="stat-card-sat <?php echo $satisfactionPct >= 80 ? 'bg-sat-excellent' : ($satisfactionPct >= 60 ? 'bg-sat-good' : ($satisfactionPct >= 40 ? 'bg-sat-neutral' : 'bg-sat-poor')); ?>">
        <h3>Taux de Satisfaction</h3>
        <div class="val"><?php echo $satisfactionPct; ?>%</div>
        <div class="sub">Avis positifs (4-5★)</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card-sat bg-sat-good">
        <h3>Note Moyenne</h3>
        <div class="val"><?php echo $global ? number_format((float)$global['note_moyenne'], 1) : '—'; ?> / 5</div>
        <div class="sub">Sur <?php echo $global['total_avis'] ?? 0; ?> avis</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card-sat bg-sat-neutral">
        <h3>Répartition</h3>
        <div class="val" style="font-size:18px;">
          <span style="color:#22c55e;"><?php echo $global['positif'] ?? 0; ?> ★★★★</span>
          <span style="color:#f59e0b;"><?php echo $global['neutre'] ?? 0; ?> ★★★</span>
          <span style="color:#ef4444;"><?php echo $global['negatif'] ?? 0; ?> ★★</span>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card-sat bg-sat-excellent">
        <h3>Dernier Avis</h3>
        <div class="val" style="font-size:16px;">
          <?php
          $lastStmt = $db->prepare("SELECT s.note, s.created_at FROM reclamation_satisfaction s JOIN reclamation r ON s.id_reclamation = r.id LEFT JOIN client c ON r.id_user = c.id_user $where ORDER BY s.created_at DESC LIMIT 1");
          $lastStmt->execute($params);
          $last = $lastStmt->fetch();
          if ($last):
            echo str_repeat('⭐', (int)$last['note']) . ' <span style="font-size:12px;">' . date('d/m', strtotime($last['created_at'])) . '</span>';
          else:
            echo '—';
          endif;
          ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Monthly trend -->
  <div class="card mb-4">
    <div class="card-header"><i class="bi bi-graph-up"></i> Tendance Mensuelle</div>
    <div class="card-body">
      <?php if ($monthly): ?>
      <div style="display:flex; gap:16px; flex-wrap:wrap;">
        <?php foreach ($monthly as $m): ?>
        <div style="flex:1; min-width:120px; background:#f8fafc; border-radius:8px; padding:12px; text-align:center;">
          <div style="font-size:11px; color:#94a3b8; text-transform:uppercase;"><?php echo $m['mois']; ?></div>
          <div style="font-size:24px; font-weight:700; color:#1e293b; margin:4px 0;"><?php echo $m['moyenne']; ?></div>
          <div style="font-size:11px; color:#64748b;"><?php echo $m['nb']; ?> avis</div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <p class="text-muted">Aucune donnée mensuelle.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Agent ranking -->
  <div class="card mb-4">
    <div class="card-header"><i class="bi bi-people"></i> Classement des Agents</div>
    <div class="card-body">
      <?php if ($agents): ?>
      <table class="table table-hover">
        <thead><tr><th>Agent</th><th>Note Moy.</th><th>Avis</th><th>Réclamations trait.</th><th>Barre</th></tr></thead>
        <tbody>
          <?php foreach ($agents as $a): 
            $pct = $a['nb_reclamations'] > 0 ? round(($a['nb_avis'] / $a['nb_reclamations']) * 100) : 0;
          ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($a['prenom'] . ' ' . $a['nom']); ?></strong></td>
            <td><span class="stars"><?php echo str_repeat('⭐', max(1, min(5, round((float)$a['moyenne'])))); ?></span> <?php echo $a['moyenne']; ?></td>
            <td><?php echo $a['nb_avis']; ?></td>
            <td><?php echo $a['nb_reclamations']; ?> (<?php echo $pct; ?>%)</td>
            <td>
              <div class="rating-bar" style="width:<?php echo $pct; ?>%; background:<?php echo $a['moyenne'] >= 4 ? '#22c55e' : ($a['moyenne'] >= 3 ? '#f59e0b' : '#ef4444'); ?>;"></div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <p class="text-muted">Aucun agent avec des avis.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent ratings -->
  <div class="card">
    <div class="card-header"><i class="bi bi-list-ul"></i> Derniers Avis</div>
    <div class="card-body">
      <?php if ($recent): ?>
      <table class="table table-hover">
        <thead><tr><th>Client</th><th>Objet</th><th>Note</th><th>Commentaire</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($recent as $s): ?>
          <tr>
            <td><?php echo htmlspecialchars($s['prenom'] . ' ' . $s['nom']); ?></td>
            <td><?php echo htmlspecialchars(substr($s['objet'] ?? '', 0, 40)); ?></td>
            <td><span class="stars"><?php echo str_repeat('⭐', (int)$s['note']); ?></span></td>
            <td><?php echo htmlspecialchars(substr($s['commentaire'] ?? '', 0, 60)) ?: '—'; ?></td>
            <td style="font-size:12px;color:#64748b;"><?php echo date('d/m/Y', strtotime($s['created_at'])); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if ($totalPages > 1): ?>
      <nav>
        <ul class="pagination justify-content-center">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
          </li>
          <?php endfor; ?>
        </ul>
      </nav>
      <?php endif; ?>
      <?php else: ?>
      <p class="text-muted">Aucun avis pour le moment.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
