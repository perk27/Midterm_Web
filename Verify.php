<?php  
// Connect to database
require 'db_connection.php';  

// Get token from URL
if (isset($_GET['token'])) {  
    $token = $_GET['token'];  

    // Query to find user with the matching token
    $stmt = $pdo->prepare("SELECT * FROM users WHERE verification_token = ?");  
    $stmt->execute([$token]);  
    $user = $stmt->fetch(PDO::FETCH_ASSOC);  

    if ($user) {  
        // Update account status to "verified"
        $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE user_id = ?");  
        $stmt->execute([$user['user_id']]);  

        echo "Your account has been successfully verified! You can now log in.";  
    } else {  
        echo "Invalid verification token or the account has already been verified.";  
    }  
} else {  
    echo "No verification token was provided.";  
}  
?>
