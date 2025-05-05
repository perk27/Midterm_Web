<?php
session_start();

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit();
}

require 'db_connection.php';

// Get user ID from session
$user_id = $_SESSION["user_id"];
$user_has_2fa_enabled = true;

try {
    $stmt = $pdo->prepare("SELECT username, email, two_factor_enabled FROM users WHERE user_id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("User not found.");
    }

    $username = $user['username'];
    $email = $user['email'];
    $user_has_2fa_enabled = (bool)$user['two_factor_enabled'];

} catch (PDOException $e) {
    die("Error fetching user data: " . $e->getMessage());
}

// Handle logout
if (isset($_GET["logged_out"]) && $_GET["logged_out"] == "true") {
    session_unset();
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/');
    header("Location: logout.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Welcome, <?= htmlspecialchars($username) ?>!</h2>
        <a href="?logged_out=true" class="btn btn-outline-secondary">Log Out</a>
    </div>

    <!-- Change Password -->
    <div class="card mb-4">
        <div class="card-header">Change Password</div>
        <div class="card-body">
            <p>To change your password, please verify your identity by clicking the button below.</p>
            <a href="email_verification.php" class="btn btn-primary">Verify Email to Change Password</a>
        </div>
    </div>  

    <!-- 2FA Toggle -->
    <div class="card mb-4">
        <div class="card-header">Two-Factor Authentication</div>
        <div class="card-body">
            <p>Status: <strong><?= $user_has_2fa_enabled ? 'Enabled' : 'Disabled' ?></strong></p>
            <form id="toggle-2fa-form" action="toggle_2fa.php" method="POST">
                <input type="hidden" name="action" value="<?= $user_has_2fa_enabled ? 'disable' : 'enable' ?>">
                <button type="submit" class="btn <?= $user_has_2fa_enabled ? 'btn-warning' : 'btn-success' ?>">
                    <?= $user_has_2fa_enabled ? 'Disable 2FA' : 'Enable 2FA' ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Delete Account -->
    <div class="card mb-4 border-danger">
        <div class=" charities:
card-header text-danger">Danger Zone</div>
        <div class="card-body">
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">Delete Account</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="delete_account.php" method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger" id="deleteModalLabel">Confirm Account Deletion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>This action cannot be undone. Are you sure you want to delete your account?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger">Yes, Delete My Account</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        $('#toggle-2fa-form').on('submit', function(e) {
            const action = $(this).find('input[name="action"]').val();
            const confirmMessage = action === 'enable' 
                ? 'Are you sure you want to enable Two-Factor Authentication?'
                : 'Are you sure you want to disable Two-Factor Authentication?';
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
            }
        });
    });
</script>
</body>
</html>