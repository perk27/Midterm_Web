<?php  
require 'vendor/autoload.php'; // Đảm bảo bạn đã cài đặt PHPMailer  

use PHPMailer\PHPMailer\PHPMailer;  
use PHPMailer\PHPMailer\Exception;  

// connect to db
$host = 'localhost';  
$dbname = 'UserAccountDB';  
$db_username = 'username';  
$db_password = 'password';  

try {  
    $pdo = new PDO("sqlsrv:server=$host;database=$dbname", $db_username, $db_password);  
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  
} catch (PDOException $e) {  
    die("Connection failed: " . $e->getMessage());  
}  

// register
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {  
    $username = $_POST['username'];  
    $email = $_POST['email'];  
    $password = $_POST['password'];  

    // check if user name or email valid
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");  
    $stmt->execute([$username, $email]);  
    $count = $stmt->fetchColumn();  

    if ($count > 0) {  
        echo "user name or email valid.";  
    } else {  
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);  
        // create token randomly
        $token = bin2hex(random_bytes(16)); // create token randomly
        
        // save data temporary into database
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_verified, verification_token) VALUES (?, ?, ?, 0, ?)");  
        
        if ($stmt->execute([$username, $email, $hashedPassword, $token])) {  
            // sent email
            $mail = new PHPMailer(true);  
            try {  
                // server setting
                $mail->isSMTP();  
                $mail->Host = 'smtp.gmail.com'; // SMTP server address
                $mail->SMTPAuth = true;  
                $mail->Username = 'hieu7a6.2005@gmail.com'; // email account
                $mail->Password = 'Mhiuu@1805'; // email password
                $mail->SMTPSecure = 'tls'; // sercure 
                $mail->Port = 587; // SMTP Port 

                // Email content
                $mail->setFrom('hieu7a6.2005@gmail.com', 'verification');  
                $mail->addAddress($email, $username);  
                $mail->isHTML(true);  
                $mail->Subject = 'Verify your account';  
                $mail->Body    = "please click on the link below to verify your account: <a href='http://your_domain/verify.php?token=$token'>Xác minh tài khoản</a>";  

                $mail->send();  
                echo "register successfully! please check your email to verify your account.";  
            } catch (Exception $e) {  
                echo "email sent error: {$mail->ErrorInfo}";  
            }  
        } else {  
            echo "register unsuccessfully, please retry.";  
        }  
    }  