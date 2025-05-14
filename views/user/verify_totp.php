<?php
// views/user/verify_totp.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify 2FA Code</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3>Enter your authentication code</h3>
    <form method="POST" action="">
        <div class="mb-3">
            <input type="text" name="totp_code" class="form-control" required pattern="\d{6}" placeholder="6-digit code">
        </div>
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>
    <?php if (!empty($error)) : ?>
        <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
?>