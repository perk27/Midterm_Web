<!DOCTYPE html>
<html lang="en">
<head>
  <title>Sign Up</title>
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
    .signup-container {
      margin-top: 5rem;
    }
  </style>
</head>
<body>

<div class="container signup-container">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <h3 class="text-center text-secondary mb-4">Create an Account</h3>
      
      <form method="POST" action="signup.php" class="border rounded w-100 mx-auto p-4 bg-white shadow">
        <div class="form-group">
          <label for="username">Username</label>
          <input id="username" name="username" type="text" class="form-control" placeholder="Choose a username" required>
        </div>

        <div class="form-group">
          <label for="email">Email address</label>
          <input id="email" name="email" type="email" class="form-control" placeholder="Enter your email" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" class="form-control" placeholder="Create a password" required>
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <input id="confirm_password" name="confirm_password" type="password" class="form-control" placeholder="Confirm your password" required>
        </div>

        <!-- Error/Success Messages -->
        <?php if (!empty($error)) : ?>
          <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php elseif (!empty($success)) : ?>
          <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="form-group text-center">
          <button type="submit" class="btn btn-primary px-5">Sign Up</button>
        </div>

        <div class="form-group text-center">
          <p class="mb-0">Already have an account? <a href="login.php">Login here</a></p>
        </div>
      </form>

    </div>
  </div>
</div>

</body>
</html>
