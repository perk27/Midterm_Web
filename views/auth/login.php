<?php
// views/auth/login.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .login-container { margin-top: 5rem; }
    </style>
</head>
<body>
<div class="container login-container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <h3 class="text-center text-secondary mb-4">User Login</h3>
            <form method="POST" action="" class="border rounded w-100 mx-auto p-4 bg-white shadow">
                <div class="form-group mb-3">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" class="form-control" placeholder="Enter username" value="<?= htmlspecialchars($saved_username) ?>" required>
                </div>
                <div class="form-group mb-3">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" class="form-control" placeholder="Enter password" required>
                </div>
                <div class="form-group form-check mb-3">
                    <input type="checkbox" name="remember" class="form-check-input" id="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <?php if (!empty($error)) : ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <div class="form-group text-center mb-3">
                    <button type="submit" name="login" class="btn btn-success px-5">Login</button>
                </div>
                <div class="form-group text-center">
                    <p class="mb-1">Forgot password? <a href="index.php?page=email_verification">Click here</a></p>
                    <p class="mb-0">Don't have an account? <a href="index.php?page=signup">Sign up here</a></p>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
?>