<?php
// views/user/enable_2fa.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enable Two-Factor Authentication</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Enable Two-Factor Authentication</h2>
    <p>Scan this QR code in Google Authenticator:</p>
    <img src="https://api.qrserver.com/v1/create-qr-code/?data=<?= urlencode($otpUrl) ?>&size=200x200" />
    <p>Or enter the code manually: <strong><?= htmlspecialchars($secret) ?></strong></p>
    <form method="POST" action="index.php?page=verify_totp">
        <div class="mb-3">
            <label for="totp_code" class="form-label">Enter 6-digit code:</label>
            <input type="text" name="totp_code" id="totp_code" class="form-control" required pattern="\d{6}">
        </div>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
?>