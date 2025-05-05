<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit();
}

require 'db_connection.php';

$user_id = $_SESSION["user_id"];
$error = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Delete user from the database
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :id");
        $stmt->execute(['id' => $user_id]);

        // Log out the user
        session_unset();
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');

        // Set success flag
        $success = true;

    } catch (PDOException $e) {
        $error = "Error deleting account: " . $e->getMessage();
    }
}
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
    <?php if ($success): ?>
        <div class="alert alert-success">
            Your account has been successfully deleted. You will be redirected to the login page.
        </div>
        <script>
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 3000);
        </script>
    <?php elseif (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <a href="homepage.php" class="btn btn-primary">Back to Dashboard</a>
    <?php else: ?>
        <!-- This section should not be reachable since deletion happens immediately on POST -->
        <div class="alert alert-warning">
            Invalid request. Please try again.
        </div>
        <a href="homepage.php" class="btn btn-primary">Back to Dashboard</a>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>