<?php
// views/auth/logout.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Logout Successful</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        let timeLeft = 10;
        function countdown() {
            if (timeLeft > 0) {
                document.getElementById("timer").innerText = timeLeft;
                timeLeft--;
                setTimeout(countdown, 1000);
            } else {
                window.location.href = "index.php?page=login";
            }
        }
        window.onload = countdown;
    </script>
</head>
<body style="background-color: #f8f9fa;">
<div class="container">
    <div class="row justify-content-center align-items-center" style="height: 100vh;">
        <div class="col-md-6 p-4 border rounded bg-white text-center">
            <h4>Logout Successful</h4>
            <p>Your account has been logged out.</p>
            <p>Click <a href="index.php?page=login">here</a> to return to login, or wait <span class="text-danger" id="timer">10</span> seconds to be redirected automatically.</p>
            <button class="btn btn-success px-5" onclick="window.location.href='index.php?page=login'">Login Again</button>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
?>