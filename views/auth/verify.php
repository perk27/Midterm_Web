<?php
// views/auth/verify.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Verify Account</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="alert alert-info">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <a href="index.php?page=login" class="btn btn-primary">Go to Login</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
?>