<?php
// views/auth/email_verification.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Password Reset Request</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3>Password Reset</h3>
    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label">Enter your email address</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Request Reset</button>
    </form>
    <?php if (!empty($error)) : ?>
        <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($message)) : ?>
        <div class="alert alert-success mt-3"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
?>