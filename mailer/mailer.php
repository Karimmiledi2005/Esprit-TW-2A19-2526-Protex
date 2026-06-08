<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

class Mailer
{
    public function __construct()
    {
        if (file_exists(__DIR__ . '/../config_services.php')) {
            require_once __DIR__ . '/../config_services.php';
        }
    }

    private function createMailer(): PHPMailer
    {
        if (!class_exists(PHPMailer::class)) {
            throw new \RuntimeException('PHPMailer indisponible');
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = defined('MAIL_SMTP_HOST') ? MAIL_SMTP_HOST : 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = defined('MAIL_SMTP_USER') ? MAIL_SMTP_USER : '';
        $mail->Password   = defined('MAIL_SMTP_PASS') ? MAIL_SMTP_PASS : '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = defined('MAIL_SMTP_PORT') ? MAIL_SMTP_PORT : 587;
        $mail->CharSet    = 'UTF-8';
        $fromEmail = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : $mail->Username;
        $fromName  = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Protex';
        $mail->setFrom($fromEmail, $fromName);
        
        if (defined('MAIL_SMTP_ALLOW_INSECURE_SSL') && MAIL_SMTP_ALLOW_INSECURE_SSL === true) {
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];
        }

        return $mail;
    }

    public function sendWelcome(string $toEmail, string $nom, string $prenom): void
    {
        $mail = $this->createMailer();
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Bienvenue sur Protex Assurance';
        $mail->Body    = "
            <div style='font-family:Arial,sans-serif;max-width:500px;margin:auto'>
                <h2 style='color:#2563eb'>Bienvenue {$prenom} {$nom} 👋</h2>
                <p>Votre compte <strong>Protex Assurance</strong> a été créé avec succès.</p>
                <p>Vous pouvez dès maintenant vous connecter et gérer vos assurances.</p>
                <hr>
                <p style='color:#888;font-size:12px'>L'équipe Protex Assurance</p>
            </div>
        ";
        try {
            $mail->send();
        } catch (Exception $e) {
            error_log("Mailer Error (sendWelcome): " . $e->getMessage());
        }
    }

    public function sendCompteBloque(string $toEmail, string $nom): void
    {
        $mail = $this->createMailer();
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Votre compte a été suspendu - Protex';
        $mail->Body    = "
            <div style='font-family:Arial,sans-serif;max-width:500px;margin:auto'>
                <h2 style='color:#dc2626'>Compte suspendu</h2>
                <p>Bonjour <strong>{$nom}</strong>,</p>
                <p>Votre compte Protex a été <strong>suspendu</strong> par un administrateur.</p>
                <p>Pour plus d'informations, contactez notre support.</p>
                <hr>
                <p style='color:#888;font-size:12px'>L'équipe Protex</p>
            </div>
        ";
        try {
            $mail->send();
        } catch (Exception $e) {
            error_log("Mailer Error (sendCompteBloque): " . $e->getMessage());
        }
    }

    public function sendCompteDebloque(string $toEmail, string $nom): void
    {
        $mail = $this->createMailer();
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Votre compte a été réactivé - Protex';
        $mail->Body    = "
            <div style='font-family:Arial,sans-serif;max-width:500px;margin:auto'>
                <h2 style='color:#16a34a'>Compte réactivé ✅</h2>
                <p>Bonjour <strong>{$nom}</strong>,</p>
                <p>Votre compte Protex a été <strong>réactivé</strong>.</p>
                <p>Vous pouvez vous connecter normalement.</p>
                <hr>
                <p style='color:#888;font-size:12px'>L'équipe Protex</p>
            </div>
        ";
        try {
            $mail->send();
        } catch (Exception $e) {
            error_log("Mailer Error (sendCompteDebloque): " . $e->getMessage());
        }
    }

    public function sendPasswordReset(string $toEmail, string $prenom, string $link): void
    {
        $mail = $this->createMailer();
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Réinitialisation de votre mot de passe - Protex';
        $mail->Body    = "
            <div style='font-family:sans-serif; max-width:500px; margin:auto; background:#0a0f1e; color:#fff; padding:40px; border-radius:24px; border:1px solid rgba(255,107,26,0.3)'>
                <div style='text-align:center; margin-bottom:30px'>
                    <h1 style='color:#FF6B1A; margin:0'>Protex</h1>
                </div>
                <h2 style='color:#fff'>Bonjour {$prenom} 👋</h2>
                <p style='color:rgba(255,255,255,0.8); line-height:1.6'>Vous avez demandé la réinitialisation de votre mot de passe Protex.</p>
                <p style='color:rgba(255,255,255,0.8); line-height:1.6'>Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe :</p>
                <div style='text-align:center; margin:40px 0'>
                    <a href='{$link}' style='background:#FF6B1A; color:#fff; padding:14px 30px; border-radius:10px; text-decoration:none; font-weight:600; display:inline-block'>Réinitialiser mon mot de passe</a>
                </div>
                <p style='color:rgba(255,255,255,0.6); font-size:13px'>Ce lien expirera dans 15 minutes.</p>
                <hr style='border:0; border-top:1px solid rgba(255,255,255,0.1); margin:30px 0'>
                <p style='color:rgba(255,255,255,0.5); font-size:12px; text-align:center'>L'équipe Protex</p>
            </div>
        ";
        try {
            $mail->send();
        } catch (Exception $e) {
            error_log("Mailer Error (sendPasswordReset): " . $e->getMessage());
        }
    }

    public function sendOTP(string $toEmail, string $nom, string $otp): void
    {
        $mail = $this->createMailer();
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Votre code de vérification - Protex';
        $mail->Body    = "
            <div style='font-family:Arial,sans-serif;max-width:500px;margin:auto;background:#0a0f1e;color:#fff;padding:40px;border-radius:24px;border:1px solid rgba(255,107,26,0.3)'>
                <div style='text-align:center;margin-bottom:30px'>
                    <h1 style='color:#FF6B1A;margin:0'>Protex</h1>
                </div>
                <h2 style='color:#FF6B1A;text-align:center'>Code de vérification</h2>
                <p style='text-align:center;color:rgba(255,255,255,0.8)'>Bonjour <strong>{$nom}</strong>,</p>
                <p style='text-align:center;color:rgba(255,255,255,0.8)'>Voici votre code de connexion sécurisé :</p>
                <div style='text-align:center;margin:30px 0;background:rgba(255,107,26,0.1);padding:20px;border-radius:12px'>
                    <span style='font-size:42px;font-weight:bold;letter-spacing:10px;color:#FF6B1A'>
                        {$otp}
                    </span>
                </div>
                <p style='text-align:center;color:rgba(255,255,255,0.6);font-size:14px'>⚠️ Ce code expire dans <strong>5 minutes</strong>.</p>
                <hr style='border:0;border-top:1px solid rgba(255,255,255,0.1);margin:30px 0'>
                <p style='color:rgba(255,255,255,0.4);font-size:12px;text-align:center'>Si vous n'avez pas demandé ce code, vous pouvez ignorer cet e-mail en toute sécurité.</p>
                <p style='color:rgba(255,255,255,0.4);font-size:12px;text-align:center'>L'équipe Protex</p>
            </div>
        ";
        try {
            $mail->send();
        } catch (Exception $e) {
            error_log("Mailer Error (sendOTP): " . $e->getMessage());
        }
    }

    public function sendMagicLink(string $toEmail, string $prenom, string $link): void
    {
        $mail = $this->createMailer();
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Lien magique — Connectez-vous en un clic - Protex';
        $mail->Body    = "
            <div style='font-family:sans-serif;max-width:500px;margin:auto;background:#0a0f1e;color:#fff;padding:40px;border-radius:24px;border:1px solid rgba(255,107,26,0.3)'>
                <div style='text-align:center;margin-bottom:30px'>
                    <h1 style='color:#FF6B1A;margin:0'>Protex</h1>
                </div>
                <h2 style='color:#fff'>Bonjour {$prenom} 👋</h2>
                <p style='color:rgba(255,255,255,0.8);line-height:1.6'>Vous avez demandé un <strong>lien magique</strong> pour vous connecter à votre compte Protex.</p>
                <p style='color:rgba(255,255,255,0.8);line-height:1.6'>Cliquez sur le bouton ci-dessous pour être connecté automatiquement :</p>
                <div style='text-align:center;margin:40px 0'>
                    <a href='{$link}' style='background:#FF6B1A;color:#fff;padding:14px 30px;border-radius:10px;text-decoration:none;font-weight:600;display:inline-block'>🔗 Connexion automatique</a>
                </div>
                <p style='color:rgba(255,255,255,0.6);font-size:13px'>Ce lien expirera dans 15 minutes.</p>
                <p style='color:rgba(255,255,255,0.5);font-size:12px;margin-top:20px'>Une fois connecté, vous pourrez changer votre mot de passe depuis votre profil.</p>
                <hr style='border:0;border-top:1px solid rgba(255,255,255,0.1);margin:30px 0'>
                <p style='color:rgba(255,255,255,0.4);font-size:12px;text-align:center'>L'équipe Protex</p>
            </div>
        ";
        try {
            $mail->send();
        } catch (Exception $e) {
            error_log("Mailer Error (sendMagicLink): " . $e->getMessage());
        }
    }

    public function sendSOS(string $toEmail, string $toPrenom, string $senderNom, string $senderPrenom, ?string $mapsLink = null, ?int $accuracy = null): void
    {
        $mail = $this->createMailer();
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = '🆘 ALERTE SOS — ' . $senderPrenom . ' ' . $senderNom . ' a besoin d\'aide !';
        $heure = date('d/m/Y à H:i:s');

        if ($mapsLink) {
            $precisionStr = $accuracy ? " (précision ±{$accuracy}m)" : '';
            $locationBlock = "
                <div style='background:rgba(255,71,87,0.15);border:1px solid rgba(255,71,87,0.5);border-radius:14px;padding:20px;margin:20px 0;text-align:center'>
                    <div style='font-size:28px'>📍</div>
                    <p style='color:#fff;font-weight:700;margin:8px 0 4px'>Position GPS localisée{$precisionStr}</p>
                    <p style='color:rgba(255,255,255,0.6);font-size:12px;margin:0 0 14px'>Coordonnées transmises au moment de l'alerte</p>
                    <a href='{$mapsLink}' target='_blank'
                       style='background:#ff4757;color:#fff;padding:12px 28px;border-radius:10px;text-decoration:none;font-weight:700;font-size:15px;display:inline-block'>
                        🗺️ Voir la position sur Google Maps
                    </a>
                </div>";
        } else {
            $locationBlock = "
                <div style='background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.15);border-radius:14px;padding:16px;margin:20px 0;text-align:center'>
                    <p style='color:rgba(255,255,255,0.5);margin:0;font-size:13px'>
                        ⚠️ Position GPS non disponible — la géolocalisation a été refusée ou désactivée
                    </p>
                </div>";
        }

        $mail->Body = "
            <div style='font-family:Arial,sans-serif;max-width:580px;margin:auto;background:#0a0f1e;color:#fff;padding:40px;border-radius:20px;border:2px solid #ff4757'>
                <div style='text-align:center;margin-bottom:24px'>
                    <div style='font-size:64px;line-height:1'>🆘</div>
                    <h1 style='color:#ff4757;margin:10px 0;font-size:28px;letter-spacing:2px'>ALERTE SOS</h1>
                    <p style='color:rgba(255,255,255,0.5);font-size:13px;margin:0'>{$heure}</p>
                </div>
                <p style='font-size:16px;color:rgba(255,255,255,0.9);margin-bottom:8px'>Bonjour <strong>{$toPrenom}</strong>,</p>
                <p style='font-size:15px;color:rgba(255,255,255,0.8);line-height:1.7;margin-bottom:20px'>
                    Votre contact de confiance <strong style='color:#ff4757'>{$senderPrenom} {$senderNom}</strong>
                    a déclenché une <strong>alerte SOS d'urgence</strong> sur la plateforme <strong>Protex</strong>.
                </p>
                {$locationBlock}
                <div style='text-align:center;margin:28px 0'>
                    <a href='tel:15' style='background:#ff4757;color:#fff;padding:13px 24px;border-radius:10px;text-decoration:none;font-weight:700;font-size:15px;display:inline-block;margin:5px'>📞 SAMU — 15</a>
                </div>
                <p style='color:rgba(255,255,255,0.35);font-size:11px;text-align:center;margin:0'>Envoyé par Protex</p>
            </div>
        ";
        try {
            $mail->send();
        } catch (Exception $e) {
            error_log("Mailer Error (sendSOS): " . $e->getMessage());
        }
    }
}
?>
