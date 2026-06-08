<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../connexion.php';
require_once __DIR__ . '/../config.php';

class ContratPdfController
{
    private PDO $db;
    private readonly string $secret;

    public function __construct()
    {
        $this->db = config::getConnexion();
        $this->secret = defined('QR_VERIFICATION_SECRET') ? QR_VERIFICATION_SECRET : 'protex_secret_2026';
    }

    public function generate(int $idContrat): void
    {
        $contrat = $this->getContratData($idContrat);
        if (!$contrat) {
            http_response_code(404);
            die('Contrat introuvable.');
        }

        $vendor = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($vendor)) require_once $vendor;

        $numero = htmlspecialchars($contrat['numero_contrat'] ?? "CNT-$idContrat");
        $clientNom = htmlspecialchars(trim(($contrat['prenom'] ?? '') . ' ' . ($contrat['nom'] ?? '')));
        $clientEmail = htmlspecialchars($contrat['email'] ?? '');
        $clientTel = htmlspecialchars($contrat['telephone'] ?? '');
        $clientAdresse = htmlspecialchars($contrat['adresse'] ?? '');
        $typeContrat = htmlspecialchars($contrat['type_contrat'] ?? '');
        $categorie = htmlspecialchars($contrat['nom_categorie'] ?? '');
        $formule = htmlspecialchars($contrat['nom_formule'] ?? $contrat['formule_contrat'] ?? '');
        $statut = htmlspecialchars($contrat['statut_contrat'] ?? '');
        $dateDebut = date('d/m/Y', strtotime($contrat['date_debut_contrat'] ?? date('Y-m-d')));
        $dateFin = date('d/m/Y', strtotime($contrat['date_fin_contrat'] ?? date('Y-m-d')));
        $prime = number_format((float)($contrat['prime_contrat'] ?? 0), 2, ',', ' ') . ' DT';
        $franchise = number_format((float)($contrat['franchise_contrat'] ?? 0), 2, ',', ' ') . ' DT';
        $modePaiement = htmlspecialchars($contrat['mode_paiement'] ?? 'annuel');

        // QR code URL
        $baseUrl = defined('BASE_URL') ? BASE_URL : '/assurance';
        $token = hash('sha256', $idContrat . $this->secret);
        $qrUrl = $baseUrl . '/view/FrontOffice/qrcode_contrat.php?id=' . $idContrat . '&token=' . $token;

        // Get guarantees
        $garanties = $this->getGaranties($idContrat, $contrat['id_formule'] ?? 0);

        // Logo base64
        $logoB64 = '';
        $logoPath = __DIR__ . '/../assets/images/logo_protex.png';
        if (file_exists($logoPath)) {
            $logoB64 = base64_encode(file_get_contents($logoPath));
        }

        try {
            $mpdf = new \Mpdf\Mpdf([
                'format' => 'A4',
                'margin_top' => 25,
                'margin_bottom' => 20,
                'margin_left' => 18,
                'margin_right' => 18,
            ]);

            $mpdf->SetTitle("Contrat $numero — Protex");
            $mpdf->SetAuthor('Protex Assurance');
            $mpdf->SetWatermarkText('PROTEX CERTIFIÉ');
            $mpdf->showWatermarkText = true;
            $mpdf->watermarkTextAlpha = 0.06;

            $garantiesHtml = '';
            if (!empty($garanties)) {
                $garantiesHtml = '
                <h3 style="color:#1A3A7A; border-bottom:2px solid #1A3A7A; padding-bottom:6px; margin-top:24px; font-size:12pt;">Garanties incluses</h3>
                <table style="width:100%; border-collapse:collapse; margin-top:8px; font-size:9pt;">
                    <thead>
                        <tr style="background:#1A3A7A; color:#fff;">
                            <th style="padding:8px 10px; text-align:left;">Garantie</th>
                            <th style="padding:8px 10px; text-align:right;">Plafond</th>
                            <th style="padding:8px 10px; text-align:right;">Franchise</th>
                        </tr>
                    </thead>
                    <tbody>';
                foreach ($garanties as $g) {
                    $garantiesHtml .= '
                        <tr style="border-bottom:1px solid #e2e8f0;">
                            <td style="padding:6px 10px;">' . htmlspecialchars($g['nom_garantie'] ?? '') . '</td>
                            <td style="padding:6px 10px; text-align:right;">' . number_format((float)($g['plafond'] ?? 0), 2, ',', ' ') . ' DT</td>
                            <td style="padding:6px 10px; text-align:right;">' . number_format((float)($g['franchise'] ?? 0), 2, ',', ' ') . ' DT</td>
                        </tr>';
                }
                $garantiesHtml .= '</tbody></table>';
            }

            // QR Code with endroid
            $qrImageData = '';
            try {
                $qrCode = new \Endroid\QrCode\QrCode($qrUrl);
                $qrCode->setSize(200);
                $qrCode->setMargin(10);
                $writer = new \Endroid\QrCode\Writer\PngWriter();
                $result = $writer->write($qrCode);
                $qrImageData = $result->getString();
            } catch (Throwable $e) {
                error_log('QR Code generation error: ' . $e->getMessage());
            }

            $html = '
            <div style="font-family:Helvetica, Arial, sans-serif; color:#15233C;">

                <!-- HEADER -->
                <table style="width:100%; border-bottom:3px solid #1A3A7A; padding-bottom:12px; margin-bottom:20px;">
                    <tr>
                        <td style="width:60px;">' . ($logoB64 ? '<img src="data:image/png;base64,' . $logoB64 . '" style="height:50px;">' : '<div style="width:50px;height:50px;background:#1A3A7A;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:24px;font-weight:800;">P</div>') . '</td>
                        <td>
                            <div style="font-size:18pt; font-weight:800; color:#1A3A7A;">PROTEX Assurance</div>
                            <div style="font-size:8pt; color:#6B7A90; margin-top:2px;">Contrat d\'assurance digital — Document officiel</div>
                        </td>
                        <td style="text-align:right; font-size:7pt; color:#94a3b8;">
                            Réf: ' . $numero . '<br>
                            Émis le ' . date('d/m/Y') . '
                        </td>
                    </tr>
                </table>

                <h1 style="font-size:16pt; color:#1A3A7A; margin:0 0 4px;">Contrat d\'Assurance</h1>
                <p style="font-size:9pt; color:#6B7A90; margin:0 0 20px;">Document généré électroniquement — valeur légale</p>

                <!-- TWO COLUMNS -->
                <table style="width:100%;">
                    <tr>
                        <td style="width:50%; vertical-align:top; padding-right:10px;">
                            <h3 style="color:#1A3A7A; border-bottom:1px solid #cbd5e1; padding-bottom:4px; font-size:11pt;">Assuré</h3>
                            <p style="font-size:9pt; line-height:1.8;">
                                <strong>' . $clientNom . '</strong><br>
                                ' . ($clientEmail ? "Email : $clientEmail<br>" : '') . '
                                ' . ($clientTel ? "Tél : $clientTel<br>" : '') . '
                                ' . ($clientAdresse ? "Adresse : $clientAdresse" : '') . '
                            </p>
                        </td>
                        <td style="width:50%; vertical-align:top; padding-left:10px;">
                            <h3 style="color:#1A3A7A; border-bottom:1px solid #cbd5e1; padding-bottom:4px; font-size:11pt;">Détails du contrat</h3>
                            <table style="font-size:9pt; line-height:1.8;">
                                <tr><td style="color:#6B7A90; width:90px;">Numéro</td><td><strong>' . $numero . '</strong></td></tr>
                                <tr><td style="color:#6B7A90;">Type</td><td>' . $typeContrat . '</td></tr>
                                <tr><td style="color:#6B7A90;">Catégorie</td><td>' . $categorie . '</td></tr>
                                <tr><td style="color:#6B7A90;">Formule</td><td>' . $formule . '</td></tr>
                                <tr><td style="color:#6B7A90;">Statut</td><td>' . $statut . '</td></tr>
                                <tr><td style="color:#6B7A90;">Paiement</td><td>' . ucfirst($modePaiement) . '</td></tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- DATES -->
                <table style="width:100%; margin-top:16px; background:#f8fafc; border-radius:6px; padding:10px; font-size:9pt;">
                    <tr>
                        <td style="text-align:center;"><span style="color:#6B7A90;">Date d\'effet</span><br><strong>' . $dateDebut . '</strong></td>
                        <td style="text-align:center;"><span style="color:#6B7A90;">Date d\'échéance</span><br><strong>' . $dateFin . '</strong></td>
                        <td style="text-align:center;"><span style="color:#6B7A90;">Prime</span><br><strong style="color:#1A3A7A; font-size:11pt;">' . $prime . '</strong></td>
                        <td style="text-align:center;"><span style="color:#6B7A90;">Franchise</span><br><strong>' . $franchise . '</strong></td>
                    </tr>
                </table>

                ' . $garantiesHtml . '

                <!-- CONDITIONS -->
                <h3 style="color:#1A3A7A; border-bottom:2px solid #1A3A7A; padding-bottom:6px; margin-top:24px; font-size:12pt;">Conditions générales</h3>
                <p style="font-size:8pt; color:#475569; line-height:1.6;">
                    Le présent contrat est régi par les dispositions du Code des Assurances. Toute déclaration inexacte ou omission
                    intentionnelle de la part du souscripteur entraîne la nullité du contrat conformément à l\'article L.113-8.
                    Le paiement de la prime doit intervenir aux échéances convenues. À défaut de paiement dans les 10 jours suivant
                    l\'échéance, la garantie est suspendue. Après 30 jours, le contrat peut être résilié.
                </p>

                <!-- FOOTER -->
                <div style="position:fixed; bottom:0; left:0; right:0; text-align:center; font-size:7pt; color:#94a3b8; padding:8px 0; border-top:1px solid #e2e8f0;">
                    Document généré le ' . date('d/m/Y à H:i') . ' — Valide jusqu\'au ' . $dateFin . ' — Réf: ' . $numero . ' — © Protex Assurance
                </div>
            </div>';

            $mpdf->WriteHTML($html);

            // Add QR Code image at bottom-right if generated
            if ($qrImageData) {
                $mpdf->Image('data://image/png;base64,' . base64_encode($qrImageData), 150, 245, 40, 40, 'png');
            }

            while (ob_get_level()) ob_end_clean();
            $mpdf->Output("Contrat_$numero.pdf", 'I');
            exit;

        } catch (Throwable $e) {
            error_log('mPDF generate error: ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors de la génération du PDF.');
        }
    }

    public function generateAttestation(int $idContrat): void
    {
        $contrat = $this->getContratData($idContrat);
        if (!$contrat) {
            http_response_code(404);
            die('Contrat introuvable.');
        }

        $vendor = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($vendor)) require_once $vendor;

        $numero = htmlspecialchars($contrat['numero_contrat'] ?? "CNT-$idContrat");
        $clientNom = htmlspecialchars(trim(($contrat['prenom'] ?? '') . ' ' . ($contrat['nom'] ?? '')));
        $typeContrat = htmlspecialchars($contrat['type_contrat'] ?? '');
        $dateDebut = date('d/m/Y', strtotime($contrat['date_debut_contrat'] ?? date('Y-m-d')));
        $dateFin = date('d/m/Y', strtotime($contrat['date_fin_contrat'] ?? date('Y-m-d')));
        $prime = number_format((float)($contrat['prime_contrat'] ?? 0), 2, ',', ' ') . ' DT';

        $baseUrl = defined('BASE_URL') ? BASE_URL : '/assurance';
        $token = hash('sha256', $idContrat . $this->secret);
        $qrUrl = $baseUrl . '/view/FrontOffice/qrcode_contrat.php?id=' . $idContrat . '&token=' . $token;

        $qrImageData = '';
        try {
            $qrCode = new \Endroid\QrCode\QrCode($qrUrl);
            $qrCode->setSize(150);
            $qrCode->setMargin(5);
            $writer = new \Endroid\QrCode\Writer\PngWriter();
            $result = $writer->write($qrCode);
            $qrImageData = $result->getString();
        } catch (Throwable $e) {}

        try {
            $mpdf = new \Mpdf\Mpdf([
                'format' => 'A5-L',
                'margin_top' => 12,
                'margin_bottom' => 12,
                'margin_left' => 12,
                'margin_right' => 12,
            ]);

            $html = '
            <div style="font-family:Helvetica,Arial,sans-serif; color:#15233C;">
                <div style="text-align:center; border-bottom:2px solid #1A3A7A; padding-bottom:8px; margin-bottom:12px;">
                    <div style="font-size:16pt; font-weight:800; color:#1A3A7A;">PROTEX Assurance</div>
                    <div style="font-size:10pt; color:#6B7A90; letter-spacing:2px; text-transform:uppercase;">Attestation d\'assurance</div>
                </div>

                <table style="width:100%; font-size:8pt; line-height:1.8;">
                    <tr>
                        <td style="color:#6B7A90; width:100px;">Assuré</td>
                        <td><strong>' . $clientNom . '</strong></td>
                    </tr>
                    <tr>
                        <td style="color:#6B7A90;">Contrat</td>
                        <td>' . $numero . '</td>
                    </tr>
                    <tr>
                        <td style="color:#6B7A90;">Type</td>
                        <td>' . $typeContrat . '</td>
                    </tr>
                    <tr>
                        <td style="color:#6B7A90;">Validité</td>
                        <td>Du ' . $dateDebut . ' au ' . $dateFin . '</td>
                    </tr>
                    <tr>
                        <td style="color:#6B7A90;">Prime</td>
                        <td>' . $prime . '</td>
                    </tr>
                </table>

                ' . ($typeContrat === 'Auto' || $typeContrat === 'auto' ? '
                <div style="margin-top:10px; font-size:8pt;">
                    <strong style="color:#1A3A7A;">Véhicule assuré :</strong>
                    ' . htmlspecialchars($contrat['details_contrat'] ?? 'Voir le contrat') . '
                </div>' : '') . '

                <div style="margin-top:14px; font-size:7pt; color:#475569; line-height:1.5; text-align:center; border-top:1px solid #e2e8f0; padding-top:10px;">
                    <strong>Garanties principales :</strong> Responsabilité civile · Défense et recours · Assistance 24h/24<br>
                    <span style="color:#1A3A7A; font-weight:700;">Urgences : +216 31 000 000</span>
                </div>
            </div>';

            $mpdf->WriteHTML($html);

            if ($qrImageData) {
                $mpdf->Image('data://image/png;base64,' . base64_encode($qrImageData), 160, 82, 35, 35, 'png');
            }

            while (ob_get_level()) ob_end_clean();
            $mpdf->Output("Attestation_{$typeContrat}_{$numero}.pdf", 'I');
            exit;

        } catch (Throwable $e) {
            error_log('mPDF attestation error: ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors de la génération de l\'attestation.');
        }
    }

    private function getContratData(int $idContrat): ?array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, cat.nom_categorie, u.nom, u.prenom, u.email, u.telephone, u.adresse,
                   f.nom_formule
            FROM contrat c
            LEFT JOIN categorie cat ON c.id_categorie = cat.id_categorie
            LEFT JOIN user u ON c.id_user = u.id_user
            LEFT JOIN formule f ON c.id_formule = f.id_formule
            WHERE c.id_contrat = :id
        ");
        $stmt->execute([':id' => $idContrat]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function getGaranties(int $idContrat, int $idFormule): array
    {
        if (!$idFormule) return [];
        $stmt = $this->db->prepare("
            SELECT g.nom_garantie, g.description_garantie,
                   COALESCE(cgo.plafond_custom, fg.plafond_formule, g.plafond_couvert_garantie) AS plafond,
                   COALESCE(cgo.franchise_custom, fg.franchise_formule, 0) AS franchise
            FROM formule_garantie fg
            JOIN garantie g ON fg.id_garantie = g.id_garantie
            LEFT JOIN contrat_garantie_override cgo ON cgo.id_contrat = :id_contrat AND cgo.id_garantie = g.id_garantie
            WHERE fg.id_formule = :id_formule
            ORDER BY g.nom_garantie ASC
        ");
        $stmt->execute([':id_contrat' => $idContrat, ':id_formule' => $idFormule]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Self-execute if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    SessionGuard::requireBackoffice();
    $id = (int)($_GET['id'] ?? 0);
    $type = $_GET['type'] ?? 'contrat';
    if (!$id) { http_response_code(400); die('ID contrat manquant.'); }
    $ctrl = new ContratPdfController();
    match ($type) {
        'attestation' => $ctrl->generateAttestation($id),
        default => $ctrl->generate($id),
    };
}
