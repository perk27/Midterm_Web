<?php
// views/user/homepage.php
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
        <a href="index.php?page=homepage&logged_out=true" class="btn btn-outline-secondary">Log Out</a>
    </div>
    <div class="card mb-4">
        <div class="card-header">Change Password</div>
        <div class="card-body">
            <p>To change your password, please verify your identity by clicking the button below.</p>
            <a href="index.php?page=email_verification" class="btn btn-primary">Verify Email to Change Password</a>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header">Two-Factor Authentication</div>
        <div class="card-body">
            <p>Status: <strong><?= $user_has_2fa_enabled ? 'Enabled' : 'Disabled' ?></strong></p>
            <form id="toggle-2fa-form" action="index.php?page=toggle_2fa" method="POST">
                <input type="hidden" name="action" value="<?= $user_has_2fa_enabled ? 'disable' : 'enable' ?>">
                <button type="submit" class="btn <?= $user_has_2fa_enabled ? 'btn-warning' : 'btn-success' ?>">
                    <?= $user_has_2fa_enabled ? 'Disable 2FA' : 'Enable 2FA' ?>
                </button>
            </form>
        </div>
    </div>
    <div class="card mb-4 border-danger">
        <div class="card-header text-danger">Danger Zone</div>
        <div class="card-body">
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">Delete Account</button>
        </div>
    </div>
</div>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="index.php?page=delete_account" method="POST" class="modal-content">
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
?>