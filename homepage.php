<?php
session_start();

if(!isset($_SESSION["logged_in"])|| $_SESSION["logged_in"] !== true){   
   header("Location: login.php");
   exit();
}

if(isset($_GET["logged_out"]) && $_GET["logged_out"] == "true"){
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
    <title>Home</title>
    <style>
        body {
            display: flex;
            height: 100vh;
            align-items: center;
            justify-content: center;
            background: #e0f7fa;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .home-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
        }
        button {
            padding: 10px 20px;
            background: #f44336;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        button:hover {
            background: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="home-container">
        <h1>Welcome!</h1>
        <p>You are now logged in.</p>

        <form action="logout.php" method="post">
            <button type="submit">Logout</button>
        </form>
    </div>
</body>
</html>
