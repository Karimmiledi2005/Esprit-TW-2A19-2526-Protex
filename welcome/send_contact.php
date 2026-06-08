<?php
/**
 * welcome/send_contact.php
 * Reçoit le formulaire de contact et envoie un email à l'équipe Protex
 */
ob_start(); // Capture tout output parasite
// Log all errors but never display them to clients
error_reporting(E_ALL);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
// Safer CORS: allow only known origins (prevent wildcard exposure)
$allowed = ['http://localhost','http://localhost:3000','https://protex.tn','http://protex.tn'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && in_array($origin, $allowed, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}

// Refuse non-POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée. Utilisez POST.']);
    exit;
}


// ── Destination de réception ──
define('DEST_EMAIL', 'protexinsurance0@gmail.com');
define('DEST_NAME',  'Équipe Protex');

// ── Lecture des données POST ──
// Lecture sécurisée des données POST
$prenom  = trim(strip_tags($_POST['prenom']  ?? ''));
$nom     = trim(strip_tags($_POST['nom']     ?? ''));
$email   = trim($_POST['email']   ?? '');
$phone   = trim(strip_tags($_POST['phone']   ?? ''));
$subject = trim(strip_tags($_POST['subject'] ?? ''));
$message = trim(strip_tags($_POST['message'] ?? ''));

// Prévention d'injection d'en-têtes (CRLF)
$clean = function(string $s): string { return str_replace(["\r", "\n"], '', $s); };
$prenom = $clean($prenom);
$nom    = $clean($nom);
$email  = $clean($email);
$subject = $clean($subject);

// ── Validation ──
if (!$prenom || !$nom || !$email || !$subject || !$message) {
    echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Adresse email invalide.']);
    exit;
}

// Rate limiting simple par IP: max 5 envois / heure
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateFile = __DIR__ . '/.contact_rate.json';
$rates = [];
if (file_exists($rateFile)) {
  $content = @file_get_contents($rateFile);
  $rates = $content ? json_decode($content, true) ?? [] : [];
}
$now = time();
$bucket = $rates[$ip] ?? ['count' => 0, 'start' => $now];
// reset window older than 3600s
if ($now - ($bucket['start'] ?? 0) > 3600) {
  $bucket = ['count' => 0, 'start' => $now];
}
if (($bucket['count'] ?? 0) >= 5) {
  http_response_code(429);
  echo json_encode(['success' => false, 'message' => 'Trop de requêtes. Réessayez dans une heure.']);
  exit;
}
// increment and persist
$bucket['count'] = ($bucket['count'] ?? 0) + 1;
$rates[$ip] = $bucket;
@file_put_contents($rateFile, json_encode($rates));

// ── Construction du corps email ──
$fullName  = htmlspecialchars("$prenom $nom", ENT_QUOTES, 'UTF-8');
$safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$safePhone = $phone ? htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') : 'Non renseigné';
$safeSub   = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
$safeMsg   = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
$date      = date('d/m/Y à H:i');

$body = "
<!DOCTYPE html>
<html lang='fr'>
<head><meta charset='UTF-8'><style>
  body{font-family:Arial,sans-serif;background:#f7f9fc;margin:0;padding:0;}
  .wrap{max-width:600px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);}
  .header{background:#1A3A7A;padding:28px 32px;color:#fff;}
  .header h1{margin:0;font-size:22px;letter-spacing:-.02em;}
  .header span{color:#F4A261;font-weight:700;}
  .body{padding:28px 32px;}
  .field{margin-bottom:18px;}
  .field label{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#8da9c4;font-weight:700;display:block;margin-bottom:4px;}
  .field p{margin:0;font-size:15px;color:#0D1B2A;background:#f7f9fc;padding:10px 14px;border-radius:8px;border-left:3px solid #1A3A7A;}
  .msg-box{background:#f7f9fc;border-radius:8px;padding:16px;border-left:3px solid #F4A261;line-height:1.7;color:#0D1B2A;font-size:14.5px;}
  .footer-bar{background:#0D1B2A;padding:16px 32px;font-size:12px;color:rgba(255,255,255,.5);text-align:center;}
</style></head>
<body>
<div class='wrap'>
  <div class='header'><h1>Pro<span>tex</span> — Nouveau message de contact</h1></div>
  <div class='body'>
    <div class='field'><label>Expéditeur</label><p>$fullName — <a href='mailto:$safeEmail' style='color:#1A3A7A'>$safeEmail</a></p></div>
    <div class='field'><label>Téléphone</label><p>$safePhone</p></div>
    <div class='field'><label>Sujet</label><p>$safeSub</p></div>
    <div class='field'><label>Message</label><div class='msg-box'>$safeMsg</div></div>
    <p style='font-size:12px;color:#8da9c4;margin-top:24px'>Reçu le $date via le formulaire de contact Protex.</p>
  </div>
  <div class='footer-bar'>© Protex Assurance — Esprit Ghazela, Ariana</div>
</div>
</body></html>
";

// ── Envoi via mail() ──
$to      = DEST_NAME . ' <' . DEST_EMAIL . '>';
$subLine = '=?UTF-8?B?' . base64_encode("[Protex Contact] $subject — $fullName") . '?=';
$headers  = "From: Formulaire Protex <noreply@protex.tn>\r\n";
$headers .= "Reply-To: $fullName <$safeEmail>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

// Tentative: utiliser PHPMailer si disponible (mailer/Mailer.php), sinon mail()
$sent = false;
if (class_exists('PHPMailer\\PHPMailer\\PHPMailer') || class_exists('Mailer')) {
  try {
    // Prefer lightweight internal Mailer if present
    if (class_exists('Mailer')) {
      $m = new Mailer();
      // sendWelcome signature (toEmail, nom, prenom) est adaptée pour messages types — on fera un send via mail() fallback
      // utiliser mail() via Mailer n'est pas généralisé ici; nous retombons sur mail() to guarantee send
    }
  } catch (Exception $e) {
    error_log('Mailer init error: ' . $e->getMessage());
  }
}
// Fallback to mail() for compatibility
$sent = mail($to, $subLine, $body, $headers);

// ── Réponse de confirmation ──
if ($sent) {
    echo json_encode(['success' => true, 'message' => 'Votre message a été envoyé avec succès ! Nous vous répondrons sous 24h.']);
} else {
    // Fallback : log l'échec mais ne pas exposer l'erreur
    error_log("[Protex Contact] mail() failed for $safeEmail — subject: $subject");
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi. Veuillez nous contacter directement par téléphone.']);
}
