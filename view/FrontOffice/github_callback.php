<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
require_once __DIR__ . '/../../controller/Client_Con.php';

$client_id = defined('GITHUB_CLIENT_ID') && GITHUB_CLIENT_ID !== '' ? GITHUB_CLIENT_ID : '';
$client_secret = defined('GITHUB_CLIENT_SECRET') && GITHUB_CLIENT_SECRET !== '' ? GITHUB_CLIENT_SECRET : '';
$redirect_uri = BASE_URL . '/view/FrontOffice/github_callback.php';

$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

if (!$code || $state !== ($_SESSION['github_oauth_state'] ?? '')) {
    error_log('GitHub callback: invalid state or missing code');
    header('Location: client.php?error=oauth_failed');
    exit;
}

// 1. Échanger le code contre un access token
$ch = curl_init("https://github.com/login/oauth/access_token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'code' => $code,
    'redirect_uri' => $redirect_uri
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

$access_token = $data['access_token'] ?? '';

if (!$access_token) {
    error_log('GitHub callback: missing access token');
    header('Location: client.php?error=oauth_failed');
    exit;
}

// 2. Récupérer les infos de l'utilisateur
$ch = curl_init("https://api.github.com/user");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: token ' . $access_token,
    'User-Agent: Protex-App'
]);
$github_user = json_decode(curl_exec($ch), true);
curl_close($ch);

if (!isset($github_user['id'])) {
    error_log('GitHub callback: missing user id from GitHub response');
    header('Location: client.php?error=oauth_failed');
    exit;
}

// 3. Récupérer l'email (si non présent dans /user)
if (empty($github_user['email'])) {
    $ch = curl_init("https://api.github.com/user/emails");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $access_token,
        'User-Agent: Protex-App'
    ]);
    $emails = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    foreach ($emails as $email) {
        if ($email['primary'] && $email['verified']) {
            $github_user['email'] = $email['email'];
            break;
        }
    }
}

// 4. Connecter ou créer l'utilisateur dans Protex
$controller = new UserController();
$result = $controller->findOrCreateGithubUser($github_user);

if ($result['success']) {
    header("Location: client.php");
} else {
    error_log('GitHub callback: local signin error: ' . ($result['message'] ?? ''));
    header('Location: client.php?error=signin_failed');
    exit;
}
exit;

