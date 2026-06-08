<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();

require_once dirname(__DIR__, 2) . '/config.php';
$vendor = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (file_exists($vendor)) require_once $vendor;

$format = $_GET['format'] ?? 'pdf';
$month = $_GET['month'] ?? date('Y-m');
$agenceFilter = $_GET['agence'] ?? '';

$db = config::getConnexion();
$role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';
$idAgence = (int)($_SESSION['id_agence'] ?? $_SESSION['agence_id'] ?? 0);

// Build query
$where = "1=1";
$params = [];

if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
    $where .= " AND DATE_FORMAT(s.date_declaration, '%Y-%m') = :month";
    $params[':month'] = $month;
}
if (!in_array($role, ['superadmin'], true) && $idAgence > 0) {
    $where .= " AND s.id_agence = :agence";
    $params[':agence'] = $idAgence;
}
if ($agenceFilter && in_array($role, ['superadmin'], true)) {
    $where .= " AND s.id_agence = :agence_filtre";
    $params[':agence_filtre'] = (int)$agenceFilter;
}

$sql = "SELECT s.id_sinistre, s.type, s.description, s.date_declaration, s.statut,
               CONCAT(u.prenom, ' ', u.nom) AS client_nom,
               COALESCE(c.numero_contrat, CONCAT('CNT-', s.id_contrat)) AS numero_contrat,
               fa.score_global AS fraud_score, fa.niveau_risque AS fraud_niveau
        FROM sinistre s
        LEFT JOIN user u ON s.id_user = u.id_user
        LEFT JOIN contrat c ON s.id_contrat = c.id_contrat
        LEFT JOIN fraud_analysis fa ON s.id_sinistre = fa.id_sinistre
        WHERE $where
        ORDER BY s.date_declaration DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$sinistres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// KPI stats
$total = count($sinistres);
$attente = count(array_filter($sinistres, fn($s) => $s['statut'] === 'en_attente'));
$rembourse = count(array_filter($sinistres, fn($s) => $s['statut'] === 'rembourse'));
$refuse = count(array_filter($sinistres, fn($s) => $s['statut'] === 'refuse'));

match ($format) {
    'pdf' => exportPDF($sinistres, $month, $total, $attente, $rembourse, $refuse, $db),
    'excel' => exportExcel($sinistres, $month, $total, $attente, $rembourse, $refuse),
    default => exportPDF($sinistres, $month, $total, $attente, $rembourse, $refuse, $db),
};

function exportPDF(array $sinistres, string $month, int $total, int $attente, int $rembourse, int $refuse, PDO $db): never
{
    $logoPath = dirname(__DIR__, 2) . '/assets/images/logo_protex.png';
    $logoB64 = '';
    if (file_exists($logoPath)) {
        $logoB64 = base64_encode(file_get_contents($logoPath));
    }

    $html = '
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            @page { margin: 20mm 15mm; }
            body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; font-size: 10pt; color: #15233C; }
            .header { text-align: center; padding-bottom: 15px; border-bottom: 3px solid #1A3A7A; margin-bottom: 20px; }
            .header img { max-height: 50px; }
            .header h1 { font-size: 18pt; color: #1A3A7A; margin: 8px 0 4px; }
            .header p { font-size: 9pt; color: #6B7A90; margin: 0; }
            .kpi-grid { display: flex; gap: 10px; margin-bottom: 20px; }
            .kpi-card { flex: 1; padding: 10px; border-radius: 6px; text-align: center; }
            .kpi-card.blue { background: #e8f0fe; }
            .kpi-card.gold { background: #fef3e2; }
            .kpi-card.green { background: #e0f7f4; }
            .kpi-card.red { background: #fde8ea; }
            .kpi-value { font-size: 16pt; font-weight: 700; }
            .kpi-label { font-size: 7pt; color: #6B7A90; text-transform: uppercase; letter-spacing: 0.5px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 8pt; }
            th { background: #1A3A7A; color: #fff; padding: 8px 6px; text-align: left; font-size: 7pt; text-transform: uppercase; }
            td { padding: 6px; border-bottom: 1px solid #e2e8f0; }
            tr:nth-child(even) { background: #f8fafc; }
            .statut-en_attente { color: #EF9F27; font-weight: 600; }
            .statut-rembourse { color: #2EC4B6; font-weight: 600; }
            .statut-refuse { color: #e63946; font-weight: 600; }
            .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 7pt; color: #94a3b8; padding: 10px 0; border-top: 1px solid #e2e8f0; }
            .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 72pt; color: rgba(26,58,122,0.06); font-weight: 900; z-index: -1; }
            .fraud-eleve { color: #e63946; font-weight: 700; }
            .fraud-modere { color: #EF9F27; }
            .fraud-faible { color: #2EC4B6; }
        </style>
    </head>
    <body>
        <div class="watermark">PROTEX CERTIFIÉ</div>
        <div class="header">
            ' . ($logoB64 ? '<img src="data:image/png;base64,' . $logoB64 . '">' : '') . '
            <h1>Rapport mensuel des sinistres</h1>
            <p>Période : ' . htmlspecialchars($month) . ' — Généré le ' . date('d/m/Y à H:i') . '</p>
        </div>
        <div class="kpi-grid">
            <div class="kpi-card blue"><div class="kpi-value">' . $total . '</div><div class="kpi-label">Total sinistres</div></div>
            <div class="kpi-card gold"><div class="kpi-value">' . $attente . '</div><div class="kpi-label">En attente</div></div>
            <div class="kpi-card green"><div class="kpi-value">' . $rembourse . '</div><div class="kpi-label">Remboursés</div></div>
            <div class="kpi-card red"><div class="kpi-value">' . $refuse . '</div><div class="kpi-label">Refusés</div></div>
        </div>
        <table>
            <thead><tr><th>ID</th><th>Client</th><th>Contrat</th><th>Type</th><th>Date</th><th>Statut</th><th>Score fraude</th></tr></thead>
            <tbody>';
    foreach ($sinistres as $s) {
        $score = $s['fraud_score'] ?? 0;
        $scoreClass = $score >= 70 ? 'fraud-eleve' : ($score >= 40 ? 'fraud-modere' : 'fraud-faible');
        $html .= '<tr>
            <td>#' . $s['id_sinistre'] . '</td>
            <td>' . htmlspecialchars($s['client_nom'] ?? '—') . '</td>
            <td>' . htmlspecialchars($s['numero_contrat'] ?? '—') . '</td>
            <td>' . htmlspecialchars($s['type'] ?? '—') . '</td>
            <td>' . htmlspecialchars($s['date_declaration'] ?? '—') . '</td>
            <td class="statut-' . $s['statut'] . '">' . htmlspecialchars($s['statut']) . '</td>
            <td class="' . $scoreClass . '">' . $score . '/100</td>
        </tr>';
    }
    $html .= '</tbody></table>
        <div class="footer">Protex Assurance — Document confidentiel — Ref: RAPPORT_SINISTRES_' . $month . '</div>
    </body></html>';

    try {
        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4',
            'margin_top' => 20,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);
        $mpdf->SetWatermarkText('PROTEX CERTIFIÉ');
        $mpdf->showWatermarkText = true;
        $mpdf->watermarkTextAlpha = 0.06;
        $mpdf->WriteHTML($html);
        $mpdf->Output('Sinistres_' . $month . '.pdf', 'D');
    } catch (Throwable $e) {
        error_log('mPDF export error: ' . $e->getMessage());
        header('Content-Type: text/html; charset=utf-8');
        echo '<html><body onload="window.print()">' . $html . '</body></html>';
    }
    exit;
}

function exportExcel(array $sinistres, string $month, int $total, int $attente, int $rembourse, int $refuse): never
{
    try {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sinistres ' . $month);

        // Title row
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'Rapport des sinistres — ' . $month);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF1A3A7A');

        // KPI row
        $sheet->setCellValue('A3', 'Total: ' . $total);
        $sheet->setCellValue('B3', 'En attente: ' . $attente);
        $sheet->setCellValue('C3', 'Remboursés: ' . $rembourse);
        $sheet->setCellValue('D3', 'Refusés: ' . $refuse);
        $sheet->getStyle('A3:D3')->getFont()->setBold(true);

        // Header row
        $headers = ['ID Sinistre', 'Client', 'Contrat', 'Type', 'Date déclaration', 'Statut', 'Score fraude'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '5', $h);
            $sheet->getStyle($col . '5')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($col . '5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF1A3A7A');
            $col++;
        }

        // Data rows
        $rowNum = 6;
        foreach ($sinistres as $i => $s) {
            $score = (int)($s['fraud_score'] ?? 0);
            $sheet->setCellValue('A' . $rowNum, '#' . $s['id_sinistre']);
            $sheet->setCellValue('B' . $rowNum, $s['client_nom'] ?? '—');
            $sheet->setCellValue('C' . $rowNum, $s['numero_contrat'] ?? '—');
            $sheet->setCellValue('D' . $rowNum, $s['type'] ?? '—');
            $sheet->setCellValue('E' . $rowNum, $s['date_declaration'] ?? '—');
            $sheet->setCellValue('F' . $rowNum, $s['statut'] ?? '—');
            $sheet->setCellValue('G' . $rowNum, $score);

            // Color fraud score
            $cellRef = 'G' . $rowNum;
            if ($score > 70) {
                $sheet->getStyle($cellRef)->getFont()->getColor()->setARGB('FFe63946');
                $sheet->getStyle($cellRef)->getFont()->setBold(true);
            } elseif ($score >= 40) {
                $sheet->getStyle($cellRef)->getFont()->getColor()->setARGB('FFEF9F27');
            } else {
                $sheet->getStyle($cellRef)->getFont()->getColor()->setARGB('FF2EC4B6');
            }

            // Alternating row colors
            if ($i % 2 === 0) {
                $sheet->getStyle('A' . $rowNum . ':G' . $rowNum)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF8FAFC');
            }
            $rowNum++;
        }

        // Auto-width columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Sinistres_' . $month . '.xlsx"');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
    } catch (Throwable $e) {
        error_log('PhpSpreadsheet export error: ' . $e->getMessage());
        // Fallback to CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Sinistres_' . $month . '.csv');
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, ['ID Sinistre', 'Client', 'Contrat', 'Type', 'Date déclaration', 'Statut', 'Score fraude'], ';');
        foreach ($sinistres as $s) {
            fputcsv($out, [
                '#' . $s['id_sinistre'],
                $s['client_nom'] ?? '—',
                $s['numero_contrat'] ?? '—',
                $s['type'] ?? '—',
                $s['date_declaration'] ?? '—',
                $s['statut'] ?? '—',
                (int)($s['fraud_score'] ?? 0) . '/100',
            ], ';');
        }
        fclose($out);
    }
    exit;
}
