<?php
// views/user/delete_account.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <?php if ($success) : ?>
        <div class="alert alert-success">
            Your account has been successfully deleted. You will be redirected to the login page.
        </div>
        <script>
            setTimeout(() => {
                window.location.href = 'index.php?page=login';
            }, 3000);
        </script>
    <?php elseif (!empty($error)) : ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <a href="index.php?page=homepage" class="btn btn-primary">Back to Dashboard</a>
    <?php else : ?>
        <div class="alert alert-warning">
            Invalid request. Please try again.
        </div>
        <a href="index.php?page=homepage" class="btn btn-primary">Back to Dashboard</a>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
?>