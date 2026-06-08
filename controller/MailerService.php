<?php
/**
 * controller/MailerService.php
 * Service d'envoi d'emails Protex (avec PHPMailer + Gmail SMTP)
 * 
 * Usage :
 *   $mailer = new MailerService();
 *   $mailer->envoyerNotificationDevis($devis, $statut, $montant, $reponse);
 */

// Charger PHPMailer
require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';
require_once __DIR__ . '/../lib/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailerService
{
    // ═══════════════════════════════════════════════════════════════
    // ⚙️ CONFIGURATION SMTP
    // ═══════════════════════════════════════════════════════════════

    private function getSmtpConfig(): array
    {
        $configFile = __DIR__ . '/../config.env.php';
        $env = file_exists($configFile) ? require $configFile : [];

        return [
            'host'      => $env['smtp_host']     ?? 'smtp.gmail.com',
            'port'      => (int)($env['smtp_port'] ?? 587),
            'username'  => $env['smtp_user']     ?? '',
            'password'  => $env['smtp_pass']     ?? '',
            'from_email'=> $env['smtp_from_email'] ?? ($env['smtp_user'] ?? ''),
            'from_name' => $env['smtp_from_name'] ?? 'Protex Assurance',
        ];
    }

    public function envoyer(string $destinataireEmail, string $destinataireNom, string $sujet, string $corpsHTML, string $corpsTexte = ''): array
    {
        $smtp = $this->getSmtpConfig();

        // Try SMTP first
        if (!empty($smtp['password'])) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = $smtp['host'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtp['username'];
                $mail->Password   = $smtp['password'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = $smtp['port'];
                $mail->CharSet    = 'UTF-8';
                $mail->Encoding   = 'base64';
                $mail->SMTPDebug  = 0;

                $mail->setFrom($smtp['username'], $smtp['from_name']);
                $mail->addAddress($destinataireEmail, $destinataireNom);
                $mail->addReplyTo($smtp['username'], $smtp['from_name']);

                $mail->isHTML(true);
                $mail->Subject = $sujet;
                $mail->Body    = $corpsHTML;
                $mail->AltBody = $corpsTexte ?: strip_tags($corpsHTML);

                $mail->send();
                return ['success' => true, 'message' => "Email envoyé à $destinataireEmail"];
            } catch (Exception $e) {
                // SMTP failed, fall through to native mail()
            }
        }

        // Fallback: native PHP mail()
        $headers = "From: {$smtp['from_name']} <{$smtp['username']}>\r\n";
        $headers .= "Reply-To: {$smtp['username']}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        if (@mail($destinataireEmail, $sujet, $corpsHTML, $headers)) {
            return ['success' => true, 'message' => "Email envoyé (mode local) à $destinataireEmail"];
        }

        // Last resort: log email to file for local dev
        $logDir = __DIR__ . '/../view/email/logs';
        if (!is_dir($logDir)) { mkdir($logDir, 0777, true); }

        $logFile = $logDir . '/email_' . date('Y-m-d_H-i-s') . '_' . md5($destinataireEmail) . '.html';
        $logContent = "<!-- To: $destinataireEmail ($destinataireNom) | Subject: $sujet | Date: " . date('Y-m-d H:i:s') . " -->\n" . $corpsHTML;
        file_put_contents($logFile, $logContent);

        return ['success' => true, 'message' => "Email enregistré localement dans view/email/logs/"];
    }

    // ═══════════════════════════════════════════════════════════════
    // 🎯 ENVOI NOTIFICATION DEVIS — Méthode principale
    // ═══════════════════════════════════════════════════════════════

    /**
     * Envoie un email au client selon le nouveau statut de son devis
     * 
     * @param array  $devis    Données du devis (id, nom, prenom, email, type_assurance, etc.)
     * @param string $statut   Nouveau statut (accepte, refuse, en_cours, etc.)
     * @param float|null $montant Montant estimé (optionnel)
     * @param string $reponseAdmin Message personnalisé de l'admin
     */
    public function envoyerNotificationDevis(array $devis, string $statut, ?float $montant = null, string $reponseAdmin = ''): array
    {
        $email     = $devis['email'] ?? '';
        $nomComplet = trim(($devis['prenom'] ?? '') . ' ' . ($devis['nom'] ?? ''));
        $reference = 'DEV-2026-' . str_pad((string)($devis['id_devis'] ?? 0), 4, '0', STR_PAD_LEFT);

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email du client invalide'];
        }

        // Choix du template selon le statut
        $templates = [
            'accepte'    => ['file' => 'devis_accepte.html',  'subject' => "✅ Votre devis $reference a été ACCEPTÉ — Protex Assurance"],
            'refuse'     => ['file' => 'devis_refuse.html',   'subject' => "❌ Suivi de votre devis $reference — Protex Assurance"],
            'en_cours'   => ['file' => 'devis_en_cours.html', 'subject' => "🔄 Votre devis $reference est en cours de traitement"],
            'en_attente' => ['file' => 'devis_general.html',  'subject' => "📋 Votre devis $reference — Protex Assurance"],
            'expire'     => ['file' => 'devis_general.html',  'subject' => "⏰ Votre devis $reference a expiré — Protex Assurance"],
        ];

        $config = $templates[$statut] ?? $templates['en_attente'];
        $templatePath = __DIR__ . '/../view/email/' . $config['file'];

        if (!file_exists($templatePath)) {
            return ['success' => false, 'message' => "Template manquant : $templatePath"];
        }

        // Charger et remplir le template
        $html = file_get_contents($templatePath);
        $html = $this->remplirTemplate($html, [
            'CLIENT_NOM'      => htmlspecialchars($nomComplet),
            'REFERENCE'       => htmlspecialchars($reference),
            'TYPE_ASSURANCE'  => $this->getTypeLabel($devis['type_assurance'] ?? ''),
            'STATUT'          => $this->getStatutLabel($statut),
            'MONTANT'         => $montant !== null ? number_format($montant, 3, ',', ' ') . ' DT' : '—',
            'REPONSE_ADMIN'   => nl2br(htmlspecialchars($reponseAdmin ?: 'Aucune note supplémentaire.')),
            'DATE_ENVOI'      => date('d/m/Y à H:i'),
            'ANNEE'           => date('Y'),
        ]);

        return $this->envoyer($email, $nomComplet, $config['subject'], $html);
    }

    // ═══════════════════════════════════════════════════════════════
    // 🎰 ENVOI NOTIFICATION ROULETTE — Gain de fidélité
    // ═══════════════════════════════════════════════════════════════

    /**
     * Envoie un email au client avec son gain de roulette
     * 
     * @param array $data Données du gain (email, nom, prenom, palier, cadeau_label, cadeau_icone, code_promo, valeur)
     */
    public function envoyerNotificationRoulette(array $data): array
    {
        $email = $data['email'] ?? '';
        $nomComplet = trim(($data['prenom'] ?? '') . ' ' . ($data['nom'] ?? ''));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email du client invalide'];
        }

        $templatePath = __DIR__ . '/../view/email/roulette_gain.html';

        if (!file_exists($templatePath)) {
            return ['success' => false, 'message' => "Template manquant : $templatePath"];
        }

        $html = file_get_contents($templatePath);
        $html = $this->remplirTemplate($html, [
            'CLIENT_NOM'   => htmlspecialchars($nomComplet),
            'PALIER_LABEL' => 'Fidélité',
            'PALIER_COLOR' => '#FF6B1A',
            'PALIER_BG'    => '#fff5f0',
            'CADEAU_ICONE' => htmlspecialchars($data['cadeau_icone'] ?? '🎁'),
            'CADEAU_LABEL' => htmlspecialchars($data['cadeau_label'] ?? ''),
            'CODE_PROMO'   => htmlspecialchars($data['code_promo'] ?? ''),
            'DATE_ENVOI'   => date('d/m/Y à H:i'),
            'ANNEE'        => date('Y'),
        ]);

        $sujet = "🎰 Félicitations ! Vous avez gagné un cadeau Protex — " . htmlspecialchars($data['cadeau_label'] ?? '');

        return $this->envoyer($email, $nomComplet, $sujet, $html);
    }

    // ═══════════════════════════════════════════════════════════════
    // 🛠 HELPERS
    // ═══════════════════════════════════════════════════════════════

    private function remplirTemplate(string $html, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $html = str_replace('{{' . $key . '}}', (string)$value, $html);
        }
        return $html;
    }

    private function getTypeLabel(string $type): string
    {
        $map = ['auto' => 'Automobile 🚗', 'habitation' => 'Habitation 🏠', 'sante' => 'Santé ❤️'];
        return $map[strtolower($type)] ?? ucfirst($type);
    }

    private function getStatutLabel(string $statut): string
    {
        $map = [
            'en_attente' => 'En attente',
            'en_cours'   => 'En cours de traitement',
            'accepte'    => 'Accepté ✅',
            'refuse'     => 'Refusé',
            'expire'     => 'Expiré',
        ];
        return $map[$statut] ?? ucfirst($statut);
    }
}