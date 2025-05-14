<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'enable') {
        $stmt = $pdo->prepare("UPDATE users SET two_factor_enabled = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
        header("Location: enable_2fa.php");
        exit;
    } elseif ($action === 'disable') {
        $stmt = $pdo->prepare("UPDATE users SET two_factor_enabled = 0, totp_secret = NULL WHERE user_id = ?");
        $stmt->execute([$user_id]);
        header("Location: homepage.php");
        exit;
    }
}

header("Location: homepage.php");
exit;