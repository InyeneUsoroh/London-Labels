// Placeholder for Google OAuth login
header('Location: ' . (defined('BASE_URL') ? BASE_URL : '/index.php'));
exit;

<?php
// Google OAuth sign-in scaffold
// 1. Set your Google client ID, client secret, and redirect URI below

$clientId = defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '362858628545-vs7vp07q7qmkh2o894nj6995bueea7er.apps.googleusercontent.com';
$clientSecret = defined('GOOGLE_CLIENT_SECRET') ? GOOGLE_CLIENT_SECRET : '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';
$redirectUri = 'http://localhost/LondonLabels/auth/google.php';

if (!isset($_GET['code'])) {
	// Debug output for OAuth values
	echo '<pre>';
	echo 'client_id: ' . $clientId . PHP_EOL;
	echo 'redirect_uri: ' . $redirectUri . PHP_EOL;
	echo '</pre>';
	// Step 1: Redirect to Google
	$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
		'client_id' => $clientId,
		'redirect_uri' => $redirectUri,
		'response_type' => 'code',
		'scope' => 'email profile',
		'access_type' => 'online',
		'prompt' => 'select_account',
	]);
	header('Location: ' . $authUrl);
	exit;
}

// Step 2: Exchange code for token
$code = $_GET['code'];
$tokenUrl = 'https://oauth2.googleapis.com/token';
$postFields = [
	'code' => $code,
	'client_id' => $clientId,
	'client_secret' => $clientSecret,
	'redirect_uri' => $redirectUri,
	'grant_type' => 'authorization_code',
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
$tokenData = json_decode($response, true);

if (!isset($tokenData['access_token'])) {
	echo 'Google sign-in failed.';
	exit;
}

// Step 3: Get user info
$userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init($userInfoUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenData['access_token']]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$userInfo = json_decode(curl_exec($ch), true);
curl_close($ch);

if (!isset($userInfo['email'])) {
	echo 'Google sign-in failed.';
	exit;
}

require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../db_functions.php';

$pdo = get_pdo();
$email = $userInfo['email'];
$name = $userInfo['name'] ?? 'GoogleUser';

$user = get_user_by_email($email);
if ($user) {
    // User exists, log them in
    login_user($user['user_id'], $user['username'], $user['email'], $user['role'] ?? 'customer');
} else {
    // User does not exist, create new user
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($name));
    if (!$username) $username = 'googleuser';
    $username = substr($username, 0, 20);
    $randomPassword = bin2hex(random_bytes(8));
    $userId = create_user($username, $email, $randomPassword, 'customer');
    login_user($userId, $username, $email, 'customer');
}

// Redirect to dashboard or home
header('Location: http://localhost/LondonLabels/index.php');
exit;
