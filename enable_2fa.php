<?php
session_start();
require 'vendor/autoload.php';
require 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

use OTPHP\TOTP;

$stmt = $pdo->prepare("SELECT totp_secret FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$existing = $stmt->fetchColumn();

if (!$existing) {
    $totp = TOTP::create();
    $totp->setLabel($_SESSION['username']);
    $totp->setIssuer('MyApp');
    $secret = $totp->getSecret();

    $stmt = $pdo->prepare("UPDATE users SET totp_secret = ? WHERE user_id = ?");
    $stmt->execute([$secret, $_SESSION['user_id']]);
} else {
    $secret = $existing;
    $totp = TOTP::create($secret);
    $totp->setLabel($_SESSION['username']);
    $totp->setIssuer('MyApp');
}

$otpUrl = $totp->getProvisioningUri();
?>

<h2>Enable Two-Factor Authentication</h2>
<p>Scan this QR code in Google Authenticator:</p>
<img src="https://api.qrserver.com/v1/create-qr-code/?data=<?= urlencode($otpUrl) ?>&size=200x200" />
<p>Or enter the code manually: <strong><?= htmlspecialchars($secret) ?></strong></p>

<form method="POST" action="verify_totp.php">
    <label for="code">Enter 6-digit code:</label>
    <input type="text" name="totp_code" required pattern="\d{6}">
    <button type="submit">Verify</button>
</form>
