<?php
session_start();
require_once 'config/db_connection.php';
require_once 'models/UserModel.php';

class UserController {
    private $userModel;

    public function __construct($pdo) {
        $this->userModel = new UserModel($pdo);
    }

    public function homepage() {
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
            header("Location: index.php?page=login");
            exit();
        }

        $user_id = $_SESSION["user_id"];
        $user = $this->userModel->getUserById($user_id);

        if (!$user) {
            die("User not found.");
        }

        $username = $user['username'];
        $email = $user['email'];
        $user_has_2fa_enabled = (bool)$user['two_factor_enabled'];

        if (isset($_GET["logged_out"]) && $_GET["logged_out"] == "true") {
            session_unset();
            session_destroy();
            setcookie(session_name(), '', time() - 3600, '/');
            header("Location: index.php?page=logout");
            exit();
        }

        require 'views/user/homepage.php';
    }

    public function deleteAccount() {
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
            header("Location: index.php?page=login");
            exit();
        }

        $user_id = $_SESSION["user_id"];
        $error = "";
        $success = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->userModel->deleteUser($user_id);
                session_unset();
                session_destroy();
                setcookie(session_name(), '', time() - 3600, '/');
                $success = true;
            } catch (PDOException $e) {
                $error = "Error deleting account: " . $e->getMessage();
            }
        }

        require 'views/user/delete_account.php';
    }
}
?>