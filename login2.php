<?php  
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;  
use PHPMailer\PHPMailer\Exception;  

// Connect to database
$host = 'SQLEXPRESS';  
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
        echo "Tên người dùng hoặc email đã tồn tại. Vui lòng chọn khác.";  
    } else {  
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);  

        // save data temporary into database
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_verified) VALUES (?, ?, ?, 0)");  
        
        if ($stmt->execute([$username, $email, $hashedPassword])) {  
            $userId = $pdo->lastInsertId();  

            // email verification
            $mail = new PHPMailer(true);  
            try {  
                // Cấu hình máy chủ  
                $mail->isSMTP();  
                $mail->Host = 'smtp.gmail.com'; // Địa chỉ máy chủ SMTP  
                $mail->SMTPAuth = true;  
                $mail->Username = 'your_email@example.com'; // Tài khoản email  
                $mail->Password = 'your_email_password'; // Mật khẩu email  
                $mail->SMTPSecure = 'tls'; // Hoặc 'ssl'  
                $mail->Port = 587; // Cổng SMTP  

                // Nội dung email  
                $mail->setFrom('your_email@example.com', 'Tên của bạn');  
                $mail->addAddress($email, $username);  
                $mail->isHTML(true);  
                $mail->Subject = 'Xác minh tài khoản của bạn';  
                $mail->Body    = "Vui lòng nhấp vào liên kết sau để xác minh tài khoản của bạn: <a href='http://your_domain/verify.php?id=$userId'>Xác minh tài khoản</a>";  

                $mail->send();  
                echo "Đăng ký thành công! Vui lòng kiểm tra email để xác minh tài khoản.";  
            } catch (Exception $e) {  
                echo "Lỗi gửi email xác minh: {$mail->ErrorInfo}";  
            }  
        } else {  
            echo "Đăng ký không thành công. Vui lòng thử lại.";  
        }  
    }  
}  

// Xử lý đăng nhập  
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {  
    $username = $_POST['username'];  
    $password = $_POST['password'];  

    // Kiểm tra thông tin đăng nhập  
    $stmt = $pdo->prepare("SELECT password, is_verified FROM users WHERE username = ?");  
    $stmt->execute([$username]);  
    $user = $stmt->fetch(PDO::FETCH_ASSOC);  

    if ($user) {  
        // Kiểm tra xem tài khoản đã được xác minh chưa  
        if ($user['is_verified'] == 0) {  
            echo "Tài khoản chưa được xác minh. Vui lòng kiểm tra email để xác minh.";  
        } else {  
            // Kiểm tra mật khẩu  
            if (password_verify($password, $user['password'])) {  
                echo "Đăng nhập thành công!";  
            } else {  
                echo "Tên người dùng hoặc mật khẩu không đúng.";  
            }  
        }  
    } else {  
        echo "Tên người dùng hoặc mật khẩu không đúng.";  
    }  
}  
?>  