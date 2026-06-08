<?php
/** Service for sending claim-related emails */

// Load PHPMailer & Dompdf — vendor/ first, then lib/ fallback
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../lib/PHPMailer/PHPMailer.php')) {
    require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
    require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';
}

// Load config_services (SMTP credentials + MAIL_FROM_EMAIL)
if (!defined('MAIL_SMTP_USER') && file_exists(__DIR__ . '/../config_services.php')) {
    require_once __DIR__ . '/../config_services.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Dompdf\Dompdf;
use Dompdf\Options;

if (!class_exists('EmailService')):
class EmailService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    private static function smtpHost(): string {
        return defined('MAIL_SMTP_HOST') ? MAIL_SMTP_HOST : 'smtp.gmail.com';
    }
    private static function smtpPort(): int {
        return defined('MAIL_SMTP_PORT') ? (int)MAIL_SMTP_PORT : 587;
    }
    private static function smtpUser(): string {
        return defined('MAIL_SMTP_USER') ? MAIL_SMTP_USER : '';
    }
    private static function smtpPass(): string {
        return defined('MAIL_SMTP_PASS') ? MAIL_SMTP_PASS : '';
    }
    private static function fromEmail(): string {
        return defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : (self::smtpUser() ?: 'no-reply@example.com');
    }
    private static function fromName(): string {
        return defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Protex';
    }

    // Public API

    /**
     * Email 1 — Déclaration reçue : sinistre en attente / en cours d'instruction
     */
    public function sendSinistreEnCours(int $idSinistre): bool
    {
        $data = $this->fetchSinistreData($idSinistre);
        if (!$data || empty($data['email'])) return false;

        $prenom = $data['prenom'] ?? 'Client';
        $subject = "✅ Votre sinistre #{$idSinistre} a bien été reçu";

        $body = $this->wrapTemplate(
            $prenom,
            "Votre déclaration de sinistre a bien été enregistrée",
            "
            <p>Nous avons bien reçu votre déclaration de sinistre et elle est actuellement
               <strong>en cours d'instruction</strong> par nos équipes.</p>

            <table class='info-table'>
                <tr><td>Numéro de sinistre</td><td><strong>#{$idSinistre}</strong></td></tr>
                <tr><td>Type</td><td>{$data['type']}</td></tr>
                <tr><td>Contrat</td><td>{$data['numero_contrat']}</td></tr>
                <tr><td>Date de déclaration</td><td>{$data['date_declaration']}</td></tr>
                <tr><td>Statut</td><td><span class='badge badge-pending'>En attente</span></td></tr>
            </table>

            <p>Notre équipe examine votre dossier. Vous serez informé(e) par email dès
               qu'une décision sera prise.</p>
            ",
            '#f59e0b', // amber
            '⏳ En cours d\'instruction'
        );

        return $this->send($data['email'], $data['prenom'] . ' ' . $data['nom'], $subject, $body);
    }

    /**
     * Email 2a — Sinistre remboursé
     */
    public function sendSinistreRembourse(int $idSinistre, ?float $montant = null): bool
    {
        $data = $this->fetchSinistreData($idSinistre);
        if (!$data || empty($data['email'])) return false;

        $prenom = $data['prenom'] ?? 'Client';
        // Fallback to DB if montant is not provided
        if ($montant === null && isset($data['montant_indemnise'])) {
            $montant = (float)$data['montant_indemnise'];
        }

        $montantHtml = ($montant !== null && $montant > 0)
            ? "<tr><td>Montant indemnisé</td><td><strong>" . number_format($montant, 2, ',', ' ') . " TND</strong></td></tr>"
            : '';

        $subject = "🎉 Votre sinistre #{$idSinistre} a été accepté — Remboursement en cours";

        $body = $this->wrapTemplate(
            $prenom,
            "Bonne nouvelle : votre sinistre a été accepté !",
            "
            <p>Après examen de votre dossier, nous avons le plaisir de vous informer que
               votre sinistre a été <strong>accepté</strong> et qu'un remboursement va être
               effectué.</p>

            <table class='info-table'>
                <tr><td>Numéro de sinistre</td><td><strong>#{$idSinistre}</strong></td></tr>
                <tr><td>Type</td><td>{$data['type']}</td></tr>
                <tr><td>Contrat</td><td>{$data['numero_contrat']}</td></tr>
                {$montantHtml}
                <tr><td>Statut</td><td><span class='badge badge-success'>Remboursé</span></td></tr>
            </table>

            <p>Le remboursement sera crédité sur votre compte dans un délai de <strong>5 à 10 jours ouvrés</strong>.</p>
            <p>Veuillez trouver ci-joint votre reçu détaillé au format PDF.</p>
            <p>Pour toute question, n'hésitez pas à contacter notre service client.</p>
            ",
            '#10b981', // green
            '✅ Sinistre accepté'
        );

        $pdf = $this->generatePdfReceipt($data, 'Remboursé', $montant, null);

        return $this->send($data['email'], $data['prenom'] . ' ' . $data['nom'], $subject, $body, $pdf, "Recu_Sinistre_{$idSinistre}.pdf");
    }

    /**
     * Email 2b — Sinistre refusé
     */
    public function sendSinistreRefuse(int $idSinistre, ?string $motif = null): bool
    {
        $data = $this->fetchSinistreData($idSinistre);
        if (!$data || empty($data['email'])) return false;

        $prenom = $data['prenom'] ?? 'Client';
        // Fallback to DB if motif is not provided
        if ($motif === null && isset($data['message_agent'])) {
            $motif = $data['message_agent'];
        }

        $motifHtml = $motif
            ? "<p><strong>Motif :</strong> " . htmlspecialchars($motif) . "</p>"
            : '';

        $subject = "❌ Décision concernant votre sinistre #{$idSinistre}";

        $body = $this->wrapTemplate(
            $prenom,
            "Décision prise sur votre sinistre",
            "
            <p>Après examen attentif de votre dossier, nous avons le regret de vous informer
               que votre sinistre a été <strong>refusé</strong>.</p>

            <table class='info-table'>
                <tr><td>Numéro de sinistre</td><td><strong>#{$idSinistre}</strong></td></tr>
                <tr><td>Type</td><td>{$data['type']}</td></tr>
                <tr><td>Contrat</td><td>{$data['numero_contrat']}</td></tr>
                <tr><td>Statut</td><td><span class='badge badge-refused'>Refusé</span></td></tr>
            </table>

            {$motifHtml}

            <p>Veuillez trouver ci-joint le détail de cette décision au format PDF.</p>

            <p>Si vous souhaitez contester cette décision, vous pouvez nous contacter
               dans un délai de <strong>30 jours</strong> à compter de la réception de cet email.</p>
            ",
            '#ef4444', // red
            '❌ Sinistre refusé'
        );

        $pdf = $this->generatePdfReceipt($data, 'Refusé', null, $motif);

        return $this->send($data['email'], $data['prenom'] . ' ' . $data['nom'], $subject, $body, $pdf, "Decision_Sinistre_{$idSinistre}.pdf");
    }

    /**
     * Email 3 — Fraude détectée : sinistre refusé automatiquement
     */
    public function sendFraudeDetectee(int $idSinistre, float $score, string $niveau, string $suggestion = ''): bool
    {
        $data = $this->fetchSinistreData($idSinistre);
        if (!$data || empty($data['email'])) return false;

        $prenom = $data['prenom'] ?? 'Client';
        $subject = "🚨 Sinistre #{$idSinistre} — Décision après analyse";

        $body = $this->wrapTemplate(
            $prenom,
            "Résultat de l'analyse de votre sinistre",
            "
            <p>Suite à l'analyse automatique de votre dossier par notre système de vérification,
               votre sinistre a été <strong>refusé</strong>.</p>

            <table class='info-table'>
                <tr><td>Numéro de sinistre</td><td><strong>#{$idSinistre}</strong></td></tr>
                <tr><td>Type</td><td>{$data['type']}</td></tr>
                <tr><td>Contrat</td><td>{$data['numero_contrat']}</td></tr>
                <tr><td>Résultat analyse</td><td><span class='badge badge-fraud'>Dossier non conforme</span></td></tr>
            </table>

            <p>Notre système de contrôle a identifié des incohérences dans votre déclaration
               qui ne correspondent pas aux critères d'indemnisation de votre contrat.</p>

            <p>Si vous pensez qu'il s'agit d'une erreur, vous pouvez contacter notre service
               de gestion des sinistres pour un réexamen manuel de votre dossier dans un délai
               de <strong>15 jours ouvrés</strong>.</p>
            ",
            '#dc2626', // dark red
            '🚫 Dossier refusé — Analyse automatique'
        );

        return $this->send($data['email'], $data['prenom'] . ' ' . $data['nom'], $subject, $body);
    }

    // Private methods

    /**
     * Récupère les données du sinistre + client + traitement (si existe) depuis la DB
     */
    private function fetchSinistreData(int $idSinistre): ?array
    {
        try {
            $stmt = $this->db->prepare("\n                SELECT\n                    s.id_sinistre, s.type, s.description, s.date_declaration, s.statut,\n                    u.prenom, u.nom, u.email,\n                    COALESCE(c.numero_contrat, CONCAT('CNT-', s.id_contrat)) AS numero_contrat,\n                    t.montant_indemnise, t.message_agent\n                FROM sinistre s\n                LEFT JOIN user u    ON s.id_user    = u.id_user\n                LEFT JOIN contrat c ON s.id_contrat = c.id_contrat\n                LEFT JOIN traitement t ON s.id_sinistre = t.id_sinistre\n                WHERE s.id_sinistre = :id\n                ORDER BY t.id_traitement DESC LIMIT 1\n            ");
            $stmt->execute([':id' => $idSinistre]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (Exception $e) {
            error_log("[EmailService] fetchSinistreData error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Envoie le mail via PHPMailer (SMTP) ou mail() PHP en fallback
     */
    private function send(string $toEmail, string $toName, string $subject, string $body, ?string $pdfContent = null, ?string $pdfName = null): bool
    {
        // Try PHPMailer
        if (class_exists(PHPMailer::class)) {
            return $this->sendViaPHPMailer($toEmail, $toName, $subject, $body, $pdfContent, $pdfName);
        }

        // Fallback: native mail()
        if ($pdfContent) {
            error_log("[EmailService] ATTENTION : PHPMailer manquant, impossible d'attacher le PDF {$pdfName}. Envoi du mail simple uniquement.");
        }
        return $this->sendViaNativeMail($toEmail, $toName, $subject, $body);
    }

    private function sendViaPHPMailer(string $toEmail, string $toName, string $subject, string $htmlBody, ?string $pdfContent = null, ?string $pdfName = null): bool
    {
        try {
            $mail = new PHPMailer(true);

            // Serveur SMTP
            $mail->isSMTP();
            $mail->Host       = self::smtpHost();
            $mail->SMTPAuth   = true;
            $mail->Username   = self::smtpUser();
            $mail->Password   = self::smtpPass();
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = self::smtpPort();
            $mail->CharSet    = 'UTF-8';

            // Expéditeur / destinataire
            $mail->setFrom(self::fromEmail(), self::fromName());
            $mail->addAddress($toEmail, $toName);

            // Contenu
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '</p>', '</tr>'], "\n", $htmlBody));

            if ($pdfContent && $pdfName) {
                $mail->addStringAttachment($pdfContent, $pdfName);
            }

            $mail->send();
            error_log("[EmailService] Email envoyé à {$toEmail} — Sujet : {$subject}");
            return true;
        } catch (PHPMailerException $e) {
            error_log("[EmailService] PHPMailer error: " . $e->getMessage());
            return false;
        }
    }

    private function sendViaNativeMail(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . self::fromName() . " <" . self::fromEmail() . ">\r\n";
        $headers .= "Reply-To: " . self::fromEmail() . "\r\n";

        $ok = mail($toEmail, $subject, $htmlBody, $headers);
        if (!$ok) {
            error_log("[EmailService] mail() failed for {$toEmail} — sujet : {$subject}");
        }
        return $ok;
    }

    /**
     * Génère un reçu PDF avec Dompdf
     */
    private function generatePdfReceipt(array $data, string $statut, ?float $montant, ?string $motif): ?string
    {
        if (!class_exists(Dompdf::class)) {
            return null;
        }

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        $date = date('d/m/Y');
        $montantTxt = $montant !== null ? number_format($montant, 2, ',', ' ') . " TND" : "0,00 TND";
        $motifTxt = $motif ? htmlspecialchars($motif) : "Non précisé";
        
        $color = $statut === 'Remboursé' ? '#10b981' : '#ef4444';

        $html = "
        <html>
        <head>
            <style>
                body { font-family: Helvetica, sans-serif; color: #333; margin: 0; padding: 20px; }
                .header { text-align: center; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 30px; }
                .logo { font-size: 24px; font-weight: bold; color: #00b4d8; }
                .title { font-size: 20px; margin-top: 10px; color: #444; }
                .info-box { background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .info-box p { margin: 5px 0; }
                .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                .table th, .table td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
                .table th { background-color: #f2f2f2; font-weight: bold; }
                .status { font-weight: bold; color: {$color}; }
                .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #777; border-top: 1px solid #eee; padding-top: 20px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <div class='logo'>Protex Assurance</div>
                <div class='title'>Détail de la décision de sinistre</div>
            </div>

            <div class='info-box'>
                <p><strong>Date d\'émission :</strong> {$date}</p>
                <p><strong>Client :</strong> {$data['prenom']} {$data['nom']}</p>
                <p><strong>Email :</strong> {$data['email']}</p>
            </div>

            <table class='table'>
                <tr>
                    <th>Numéro de Sinistre</th>
                    <td>#{$data['id_sinistre']}</td>
                </tr>
                <tr>
                    <th>Contrat</th>
                    <td>{$data['numero_contrat']}</td>
                </tr>
                <tr>
                    <th>Type de Sinistre</th>
                    <td>{$data['type']}</td>
                </tr>
                <tr>
                    <th>Date de déclaration</th>
                    <td>{$data['date_declaration']}</td>
                </tr>
                <tr>
                    <th>Statut de la demande</th>
                    <td class='status'>{$statut}</td>
                </tr>
        ";

        if ($statut === 'Remboursé') {
            $html .= "
                <tr>
                    <th>Montant Indemnisé</th>
                    <td><strong>{$montantTxt}</strong></td>
                </tr>
            ";
        } elseif ($statut === 'Refusé') {
            $html .= "
                <tr>
                    <th>Motif du Refus</th>
                    <td>{$motifTxt}</td>
                </tr>
            ";
        }

        $html .= "
            </table>

            <div class='footer'>
                <p>Ce document a été généré automatiquement et sert de justificatif de traitement de votre sinistre.</p>
                <p>&copy; " . date('Y') . " Protex Assurance. Tous droits réservés.</p>
            </div>
        </body>
        </html>
        ";

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Génère un email HTML complet avec un design responsive
     */
    private function wrapTemplate(string $prenom, string $titre, string $contenu, string $couleur, string $badge): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$titre}</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { background:#f4f6f9; font-family: 'Segoe UI', Arial, sans-serif; color:#333; }
  .wrapper { max-width:600px; margin:30px auto; background:#fff; border-radius:12px;
             box-shadow:0 4px 20px rgba(0,0,0,.08); overflow:hidden; }
  .header { background:{$couleur}; padding:32px 40px; text-align:center; }
  .header h1 { color:#fff; font-size:22px; font-weight:700; letter-spacing:.3px; }
  .header .badge-label { display:inline-block; margin-top:10px; background:rgba(255,255,255,.2);
                         color:#fff; padding:4px 16px; border-radius:20px; font-size:13px; }
  .body { padding:36px 40px; }
  .body .greeting { font-size:17px; margin-bottom:20px; }
  .body p { line-height:1.7; margin-bottom:14px; font-size:15px; color:#444; }
  .info-table { width:100%; border-collapse:collapse; margin:20px 0; font-size:14px; }
  .info-table td { padding:10px 14px; border-bottom:1px solid #f0f0f0; }
  .info-table td:first-child { color:#888; width:45%; }
  .info-table td:last-child { font-weight:500; color:#222; }
  .badge { display:inline-block; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:600; }
  .badge-pending { background:#fef3c7; color:#92400e; }
  .badge-success { background:#d1fae5; color:#065f46; }
  .badge-refused { background:#fee2e2; color:#991b1b; }
  .badge-fraud   { background:#fce7f3; color:#9d174d; }
  .footer { background:#f9fafb; border-top:1px solid #eee; padding:20px 40px;
            text-align:center; font-size:12px; color:#999; }
  .footer a { color:{$couleur}; text-decoration:none; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>Service Gestion des Sinistres</h1>
    <span class="badge-label">{$badge}</span>
  </div>
  <div class="body">
    <p class="greeting">Bonjour <strong>{$prenom}</strong>,</p>
    <h2 style="font-size:18px;margin-bottom:16px;color:#1f2937">{$titre}</h2>
    {$contenu}
    <p style="margin-top:24px">Cordialement,<br>
       <strong>Le Service Sinistres</strong><br>
       <em style="color:#888;font-size:13px">Assurance — Département Gestion des Sinistres</em>
    </p>
  </div>
  <div class="footer">
    <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre directement.</p>
    <p style="margin-top:6px">© 2025 Assurance. Tous droits réservés.</p>
  </div>
</div>
</body>
</html>
HTML;
    }
}

endif; // class_exists EmailService
