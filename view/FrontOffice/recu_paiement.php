<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// Requires logged in user
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    die('Accès refusé. Veuillez vous connecter.');
}

$id_paiement = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id_paiement <= 0) {
    die('Identifiant de paiement invalide.');
}

$db = config::getConnexion();

$stmt = $db->prepare("
    SELECT p.*, 
           c.numero_contrat, c.type_contrat as type_offre,
           c.id_user as contrat_client_id,
           u.nom, u.prenom, u.email
    FROM paiement p
    JOIN contrat c ON p.id_offre = c.id_contrat
    JOIN `user` u ON c.id_user = u.id_user
    WHERE p.id_paiement = ?
");
$stmt->execute([$id_paiement]);
$paiement = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$paiement) {
    die('Paiement introuvable.');
}

// Ensure the user owns this payment or is an admin
$sessionUserId = $_SESSION['user']['id_user'] ?? 0;
$sessionRole   = $_SESSION['user']['role'] ?? 'client';
$allowedRoles  = ['admin', 'superadmin', 'agent', 'admin_agence'];
if ($sessionUserId != ($paiement['contrat_client_id'] ?? -1) && !in_array($sessionRole, $allowedRoles)) {
    die('Accès refusé à ce reçu.');
}

// Generate HTML for the receipt
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #00b4d8; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { font-size: 28px; font-weight: bold; color: #00b4d8; }
        .logo span { color: #10b981; }
        .title { font-size: 20px; font-weight: bold; margin-top: 10px; text-transform: uppercase; letter-spacing: 1px; color: #555; }
        .receipt-info { margin-bottom: 40px; }
        .receipt-info table { width: 100%; }
        .receipt-info td { vertical-align: top; width: 50%; }
        .info-block { background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; }
        .info-title { font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: bold; margin-bottom: 5px; }
        .info-value { font-size: 15px; font-weight: bold; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .details-table th { background: #00b4d8; color: #fff; padding: 12px; text-align: left; font-size: 13px; text-transform: uppercase; }
        .details-table td { padding: 12px; border-bottom: 1px solid #e2e8f0; }
        .total-row td { font-weight: bold; font-size: 16px; background: #f1f5f9; border-bottom: 2px solid #cbd5e1; }
        .total-amount { color: #00b4d8; font-size: 18px; }
        .footer { text-align: center; margin-top: 50px; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 20px; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-valide { background: #d1fae5; color: #059669; border: 1px solid #34d399; }
        .badge-attente { background: #fef3c7; color: #d97706; border: 1px solid #fbbf24; }
        .badge-refuse { background: #fee2e2; color: #dc2626; border: 1px solid #f87171; }
        .badge-rembourse { background: #e0f2fe; color: #0284c7; border: 1px solid #38bdf8; }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo">PROTEX<span>.</span></div>
        <div class="title">Reçu de paiement</div>
    </div>

    <div class="receipt-info">
        <table>
            <tr>
                <td style="padding-right: 10px;">
                    <div class="info-block">
                        <div class="info-title">Informations du Client</div>
                        <div class="info-value">'.htmlspecialchars($paiement['prenom'].' '.$paiement['nom']).'</div>
                        <div style="color: #64748b; font-size: 13px; margin-top: 5px;">'.htmlspecialchars($paiement['email']).'</div>
                    </div>
                </td>
                <td style="padding-left: 10px;">
                    <div class="info-block">
                        <div class="info-title">Détails du Document</div>
                        <table style="width: 100%; margin-top: 5px; font-size: 13px;">
                            <tr><td style="color:#64748b; padding-bottom:5px; width:40%;">Référence:</td><td style="font-weight:bold;">'.htmlspecialchars($paiement['reference']).'</td></tr>
                            <tr><td style="color:#64748b; padding-bottom:5px;">Date:</td><td style="font-weight:bold;">'.date('d/m/Y H:i', strtotime($paiement['date_paiement'])).'</td></tr>
                            <tr><td style="color:#64748b;">Statut:</td><td>';
                            
                            if ($paiement['statut'] === 'valide') $html .= '<span class="badge badge-valide">Validé</span>';
                            elseif ($paiement['statut'] === 'en_attente') $html .= '<span class="badge badge-attente">En attente</span>';
                            elseif ($paiement['statut'] === 'refuse') $html .= '<span class="badge badge-refuse">Refusé</span>';
                            else $html .= '<span class="badge badge-rembourse">Remboursé</span>';

$html .= '                  </td></tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="details-table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Type d\'offre</th>
                <th>Méthode</th>
                <th style="text-align: right;">Montant</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div style="font-weight: bold; font-size: 14px;">Contrat N° '.htmlspecialchars($paiement['numero_contrat']).'</div>
                    <div style="color: #64748b; font-size: 12px; margin-top: 4px;">Période: '.ucfirst(htmlspecialchars($paiement['periodicite'])).'</div>
                </td>
                <td>'.ucfirst(htmlspecialchars($paiement['type_offre'])).'</td>
                <td>'.ucfirst(htmlspecialchars($paiement['methode'])).'</td>
                <td style="text-align: right; font-weight: bold; font-size: 14px;">'.number_format((float)$paiement['montant'], 3, ',', ' ').' TND</td>
            </tr>
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">TOTAL PAYÉ :</td>
                <td style="text-align: right;" class="total-amount">'.number_format((float)$paiement['montant'], 3, ',', ' ').' TND</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 40px; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 15px; border-radius: 8px; color: #166534; font-size: 13px;">
        <strong><i class="bi bi-shield-check"></i> Paiement sécurisé</strong><br>
        Votre transaction a été traitée avec succès. Merci de votre confiance en Protex Assurance.
    </div>

    <div class="footer">
        Protex Assurance — Siège Social : 123 Avenue de la République, Tunis<br>
        Tél: +216 71 123 456 | Email: contact@protex.tn | RC: B123456789<br>
        Document généré automatiquement le '.date('d/m/Y à H:i').'
    </div>

</body>
</html>';

try {
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 20,
        'margin_bottom' => 20,
        'margin_header' => 10,
        'margin_footer' => 10
    ]);
    
    $mpdf->SetTitle('Reçu - ' . $paiement['reference']);
    $mpdf->SetAuthor('Protex Assurance');
    $mpdf->WriteHTML($html);
    $mpdf->Output('Recu_Paiement_' . $paiement['reference'] . '.pdf', \Mpdf\Output\Destination::INLINE);
} catch (\Mpdf\MpdfException $e) {
    die('Erreur lors de la génération du PDF: ' . $e->getMessage());
}
