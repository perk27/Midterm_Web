<?php  
// connect to db
$host = 'SQLEXPRESS';  
$dbname = 'User_accounts';  
$db_username = 'your_username';  
$db_password = 'your_password';  

try {  
    $pdo = new PDO("sqlsrv:server=$host;database=$dbname", $db_username, $db_password);  
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  
} catch (PDOException $e) {  
    die("Connection failed: " . $e->getMessage());  
}  

// Lấy token từ URL  
if (isset($_GET['token'])) {  
    $token = $_GET['token'];  

    // Thực hiện truy vấn để tìm người dùng có token tương ứng  
    $stmt = $pdo->prepare("SELECT * FROM users WHERE verification_token = ?");  
    $stmt->execute([$token]);  
    $user = $stmt->fetch(PDO::FETCH_ASSOC);  

    if ($user) {  
        // Cập nhật trạng thái tài khoản thành đã xác minh  `
        $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");  
        $stmt->execute([$user['id']]);  
        
        echo "Tài khoản của bạn đã được xác minh thành công! Bạn có thể đăng nhập vào hệ thống.";  
    } else {  
        echo "Token xác minh không hợp lệ hoặc tài khoản đã được xác minh trước đó.";  
    }  
} else {  
    echo "Không có token xác minh nào được cung cấp.";  
}  
?>  