<?php
// ============================================================
//  services/EmailService.php
//  Envoi email HTML via PHPMailer + Gmail SMTP
// ============================================================

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../lib/PHPMailer/PHPMailer.php')) {
    require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
    require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';
}
if (!defined('MAIL_SMTP_USER') && file_exists(__DIR__ . '/../config_services.php')) {
    require_once __DIR__ . '/../config_services.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class ReclamationMailer
{
    private static $lastError = '';

    public static function getLastError(): string
    {
        return self::$lastError;
    }

    private static function setLastError(string $message): void
    {
        self::$lastError = $message;
        error_log('[PROTEX MAIL ERREUR] ' . $message);
    }

    // Compatible PHP 7.4 : pas de match()
    private static function typeConfig(string $type): array
    {
        if ($type === 'rejet') {
            return [
                'couleur'  => '#ef4444',
                'bg'       => '#fef2f2',
                'emoji'    => '❌',
                'libelle'  => 'Réclamation rejetée',
                'intro'    => 'Nous avons examiné votre réclamation et malheureusement nous ne pouvons pas y donner suite pour la raison suivante :',
            ];
        }

        return [
            'couleur'  => '#22c55e',
            'bg'       => '#f0fdf4',
            'emoji'    => '✅',
            'libelle'  => 'Réclamation résolue',
            'intro'    => 'Nous avons examiné votre réclamation et voici notre réponse :',
        ];
    }

    private static function buildTemplate(string $objet, string $contenu, string $type, string $satisfactionUrl = ''): string
    {
        $cfg          = self::typeConfig($type);
        $objetHtml    = htmlspecialchars($objet, ENT_QUOTES, 'UTF-8');
        $contenuHtml  = nl2br(htmlspecialchars($contenu, ENT_QUOTES, 'UTF-8'));
        $satisfactionBlock = '';
        if ($satisfactionUrl !== '') {
            $satisfactionBlock = <<<HTML
          <div style="text-align:center;margin:28px 0 0;padding:24px;background:#f0fdf4;border-radius:12px;border:1px solid #bbf7d0;">
            <p style="margin:0 0 12px;color:#166534;font-size:14px;font-weight:600;">📝 Comment s'est passée votre expérience ?</p>
            <a href="{$satisfactionUrl}" style="display:inline-block;background:#22c55e;color:#fff;text-decoration:none;padding:12px 32px;border-radius:24px;font-size:14px;font-weight:700;">
              ⭐ Donner mon avis
            </a>
            <p style="margin:8px 0 0;color:#166534;font-size:11px;">Cela ne prend que quelques secondes.</p>
          </div>
HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0"
           style="background:#ffffff;border-radius:16px;overflow:hidden;
                  box-shadow:0 4px 24px rgba(0,0,0,.10);max-width:600px;width:100%;">

      <tr>
        <td style="background:linear-gradient(135deg,#1e3a5f 0%,#23458f 100%);padding:36px 40px;text-align:center;">
          <h1 style="margin:0;color:#ffffff;font-size:28px;font-weight:800;letter-spacing:1px;">PROTEX</h1>
          <p style="margin:8px 0 0;color:rgba(255,255,255,.70);font-size:12px;letter-spacing:2px;text-transform:uppercase;">Assurance Digitale</p>
        </td>
      </tr>

      <tr>
        <td style="padding:32px 40px 0;text-align:center;">
          <span style="display:inline-block;background:{$cfg['couleur']};color:#fff;font-size:14px;font-weight:700;padding:8px 24px;border-radius:24px;">
            {$cfg['emoji']} {$cfg['libelle']}
          </span>
        </td>
      </tr>

      <tr>
        <td style="padding:28px 40px;">
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border-radius:10px;overflow:hidden;margin-bottom:24px;">
            <tr>
              <td style="padding:16px 20px;">
                <span style="color:#94a3b8;font-size:11px;text-transform:uppercase;letter-spacing:1px;">Réclamation concernée</span><br>
                <strong style="color:#1e293b;font-size:15px;">{$objetHtml}</strong>
              </td>
            </tr>
          </table>

          <p style="margin:0 0 16px;color:#334155;font-size:15px;line-height:1.7;">{$cfg['intro']}</p>

            <div style="background:{$cfg['bg']};border-left:4px solid {$cfg['couleur']};border-radius:0 10px 10px 0;padding:20px;font-size:14px;color:#334155;line-height:1.8;margin-bottom:28px;">
            {$contenuHtml}
          </div>

          {$satisfactionBlock}

          <p style="margin:0;color:#64748b;font-size:13px;line-height:1.7;">
            Pour toute question complémentaire, n'hésitez pas à nous contacter.<br>
            Merci de votre confiance.
          </p>
        </td>
      </tr>

      <tr>
        <td style="background:#1e3a5f;padding:24px 40px;text-align:center;">
          <p style="margin:0;color:rgba(255,255,255,.60);font-size:12px;line-height:1.6;">
            © 2026 Protex Assurance<br>
            Email automatique, merci de ne pas y répondre.
          </p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }

    public static function envoyerNotificationReponse(
        string $email,
        string $objet,
        string $contenu,
        string $type = 'reponse',
        string $satisfactionUrl = ''
    ): bool {
        self::$lastError = '';
        $email = trim($email);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::setLastError('Adresse email invalide ou manquante : ' . $email);
            return false;
        }

        if (!defined('MAIL_SMTP_USER') || !defined('MAIL_SMTP_PASS') || !defined('MAIL_FROM_NAME')) {
            self::setLastError('Configuration email incomplète dans config_services.php.');
            return false;
        }

        $username = trim((string) MAIL_SMTP_USER);
        $password = preg_replace('/\s+/', '', (string) MAIL_SMTP_PASS);

        if ($username === '' || $password === '') {
            self::setLastError('MAIL_SMTP_USER ou MAIL_SMTP_PASS est vide.');
            return false;
        }

        $cfg  = self::typeConfig($type);
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $username;
            $mail->Password   = $password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 20;

            // XAMPP/local : évite les erreurs de certificat OpenSSL qui bloquent parfois Gmail SMTP.
            // Pour un hébergement réel, mettez MAIL_SMTP_ALLOW_INSECURE_SSL à false dans config_services.php.
            if (defined('MAIL_SMTP_ALLOW_INSECURE_SSL') && MAIL_SMTP_ALLOW_INSECURE_SSL === true) {
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true,
                    ],
                ];
            }

            $mail->setFrom($username, MAIL_FROM_NAME);
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = "{$cfg['emoji']} [Protex] {$cfg['libelle']} : {$objet}";
            $mail->Body    = self::buildTemplate($objet, $contenu, $type, $satisfactionUrl);
            $mail->AltBody = "Bonjour,\n\n{$cfg['intro']}\n\n{$contenu}\n\nCordialement,\nL'équipe Protex Assurance";

            $mail->send();
            error_log("[PROTEX MAIL OK] → {$email} | type: {$type} | objet: {$objet}");
            return true;
        } catch (PHPMailerException $e) {
            self::setLastError($email . ' : ' . ($mail->ErrorInfo ?: $e->getMessage()));
            return false;
        } catch (Throwable $e) {
            self::setLastError($email . ' : ' . $e->getMessage());
            return false;
        }
    }
}
