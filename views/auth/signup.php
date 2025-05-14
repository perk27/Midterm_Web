<?php
// views/auth/signup.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Sign Up</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .signup-container { margin-top: 5rem; }
    </style>
</head>
<body>
<div class="container signup-container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <h3 class="text-center text-secondary mb-4">Create an Account</h3>
            <form method="POST" action="" class="border rounded w-100 mx-auto p-4 bg-white shadow">
                <div class="form-group mb-3">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" class="form-control" placeholder="Choose a username" required>
                </div>
                <div class="form-group mb-3">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" class="form-control" placeholder="Enter your email" required>
                </div>
                <div class="form-group mb-3">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" class="form-control" placeholder="Create a password" required>
                </div>
                <div class="form-group mb-3">
                    <label for="confirm_password">Confirm Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" class="form-control" placeholder="Confirm your password" required>
                </div>
                <?php if (!empty($error)) : ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($error as $e) : ?>
                                <li><?php echo htmlspecialchars($e); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php elseif (!empty($success)) : ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <div class="form-group text-center mb-3">
                    <button type="submit" name="register" class="btn btn-primary px-5">Sign Up</button>
                </div>
                <div class="form-group text-center">
                    <p class="mb-0">Already have an account? <a href="index.php?page=login">Login here</a></p>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
?>