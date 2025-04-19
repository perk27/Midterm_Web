<?php
// This is the password hash you'd store in your database (done once)
$stored_hash = password_hash("Shmumf2023", PASSWORD_DEFAULT);

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $entered_password = $_POST["password"];

    // Verify entered password with stored hash
    if (password_verify($entered_password, $stored_hash)) {
        echo "You are logged in!";
    } else {
        echo "Incorrect password!";
    }
}
?>

<!-- HTML form -->
<form method="post">
    <label for="password">Enter Password:</label>
    <input type="password" name="password" id="password" required>
    <button type="submit">Login</button>
</form>
