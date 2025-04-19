<?php
session_start();

define("USERNAME", "admin");
define("PASSWORD", "password123");

if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
    header("Location: homepage.php");
    exit();
}

$error = "";
$saved_username = isset($_COOKIE["remember_username"]) ? $_COOKIE["remember_username"] : "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = isset($_POST["username"]) ? $_POST["username"] : "";
    $password = isset($_POST["password"]) ? $_POST["password"] : "";

    if ($username === USERNAME && $password === PASSWORD) {
        $_SESSION["logged_in"] = true;
        $_SESSION["username"] = $username;

        if (isset($_POST["remember"])) {
            // Set a persistent cookie (30 days)
            setcookie("remember_username", $username, time() + (86400 * 30), "/");
        } else {
            // Set a session cookie (deleted on browser close)
            setcookie("remember_username", $username, time() - 3600, "/");
        }

        header("Location: homepage.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Login</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  
  <!-- jQuery and Bootstrap JS -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

  <style>
    body {
      background-color: #f0f2f5;
    }
    .login-container {
      margin-top: 5rem;
    }
  </style>
</head>
<body>

<div class="container login-container">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <h3 class="text-center text-secondary mb-4">User Login</h3>
      
      <form method="POST" action="" class="border rounded w-100 mx-auto p-4 bg-white shadow">
        <div class="form-group">
          <label for="username">Username</label>
          <input id="username" name="username" type="text" class="form-control" placeholder="Enter username" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" class="form-control" placeholder="Enter password" required>
        </div>

        <div class="form-group custom-control custom-checkbox">
          <input type="checkbox" name="remember" class="custom-control-input" id="remember">
          <label class="custom-control-label" for="remember">Remember me</label>
        </div>

        <!-- Error message block (only shown if $error is not empty) -->
        <?php if (!empty($error)) : ?>
          <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-group text-center">
          <button type="submit" class="btn btn-success px-5">Login</button>
        </div>

        <div class="form-group text-center">
          <p class="mb-0">Forgot password? <a href="#">Click here</a></p>
        </div>
      </form>

    </div>
  </div>
</div>

</body>
</html>

