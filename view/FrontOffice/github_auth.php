<?php
require_once dirname(__DIR__, 2) . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$client_id = defined('GITHUB_CLIENT_ID') && GITHUB_CLIENT_ID !== '' ? GITHUB_CLIENT_ID : 'Ov23liGC8ESkcViBlU00';
$redirect_uri = BASE_URL . '/view/FrontOffice/github_callback.php';
$scope = "user:email";
$state = bin2hex(random_bytes(16));
$_SESSION['github_oauth_state'] = $state;

$url = "https://github.com/login/oauth/authorize?" . http_build_query([
    'client_id' => $client_id,
    'redirect_uri' => $redirect_uri,
    'scope' => $scope,
    'state' => $state
]);

header("Location: $url");
exit;

