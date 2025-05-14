<?php
$host = 'localhost';
$dbname = 'midterm';          
$db_username = 'root';        // Default username for XAMPP
$db_password = '';            // Default password is empty

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '+00:00';");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
