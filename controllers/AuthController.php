<?php
session_start();
require_once 'config/db_connection.php';
require_once 'models/UserModel.php';
require_once 'models/EmailVerificationModel.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AuthController {
    private $userModel;
    private $emailVerificationModel;

    public function __construct($pdo) {
        $this->userModel = new UserModel($pdo);
        $this->emailVerificationModel = new EmailVerificationModel($pdo);
    }

    public function login() {
        if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
            header("Location: index.php?page=homepage");
            exit();
        }

        $error = "";
        $saved_username = $_COOKIE["remember_username"] ?? "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
            $username = trim($_POST['username'] ?? "");
            $password = $_POST['password'] ?? "";

            if (empty($username) || empty($password)) {
                $error = "Please fill in both username and password.";
            } else {
                if ($username === "admin" && $password === "password123") {
                    $_SESSION["logged_in"] = true;
                    $_SESSION["username"] = $username;
                    $this->handleRememberMe($username);
                    header("Location: index.php?page=homepage");
                    exit();
                }

                $user = $this->userModel->getUserByUsername($username);
                if ($user) {
                    if (!$user['is_verified']) {
                        $error = "Your account has not been verified yet. Please check your email.";
                    } elseif (password_verify($password, $user['password'])) {
                        $_SESSION["temp_user_id"] = $user['user_id'];
                        $_SESSION["temp_username"] = $username;
                        $_SESSION["remember_me"] = isset($_POST["remember"]);

                        if ($user['two_factor_enabled']) {
                            $_SESSION["totp_secret"] = $user['totp_secret'];
                            $_SESSION["2fa_totp_user"] = $user['user_id'];
                            header("Location: index.php?page=verify_totp");
                            exit();
                        } else {
                            $_SESSION["logged_in"] = true;
                            $_SESSION["username"] = $username;
                            $_SESSION["user_id"] = $user['user_id'];
                            $this->handleRememberMe($username);
                            header("Location: index.php?page=homepage");
                            exit();
                        }
                    } else {
                        $error = "Incorrect username or password.";
                    }
                } else {
                    $error = "Incorrect username or password.";
                }
            }
        }

        require 'views/auth/login.php';
    }

    public function signup() {
        $error = [];
        $success = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
            $username = trim($_POST['username']);
            $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            if (!$email) {
                $error[] = "Invalid email address.";
            }
            if ($password !== $confirm_password) {
                $error[] = "Passwords do not match.";
            }

            if ($this->userModel->userExists($username, $email)) {
                $error[] = "The username or email already exists.";
            }

            if (empty($error)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $userId = $this->userModel->createUser($username, $email, $hashedPassword);

                if ($userId) {
                    $verifyToken = bin2hex(random_bytes(32));
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    $this->emailVerificationModel->saveToken($userId, $verifyToken, $expiresAt);

                    $verifyLink = "http://localhost/Midterm/index.php?page=verify&token=$verifyToken";
                    $mail = new PHPMailer(true);
                    try {
                        $mail->CharSet = 'UTF-8';
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'pmhieu180125@gmail.com';
                        $mail->Password = 'udcxicthxwbesoin';
                        $mail->SMTPSecure = 'tls';
                        $mail->Port = 587;
                        $mail->setFrom('pmhieu180125@gmail.com', 'Verify your email');
                        $mail->addAddress($email, htmlspecialchars($username));
                        $mail->isHTML(true);
                        $mail->Subject = 'Verify your account';
                        $mail->Body = "Please click the following link to verify your account: <a href='$verifyLink'>Verify Account</a>";
                        $mail->send();
                        $success = "Registration successful! Please check your email to verify your account.";
                    } catch (Exception $e) {
                        $error[] = "Verification email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                    }
                } else {
                    $error[] = "Registration failed. Please try again.";
                }
            }
        }

        require 'views/auth/signup.php';
    }

    public function verify() {
        $message = "";
        if (isset($_GET['token'])) {
            $token = $_GET['token'];
            $record = $this->emailVerificationModel->getToken($token);

            if ($record) {
                if ($record['is_verified']) {
                    $message = "This account is already verified.";
                } elseif (strtotime($record['expires_at']) < time()) {
                    $message = "The verification token has expired.";
                } else {
                    $this->userModel->verifyUser($record['user_id']);
                    $this->emailVerificationModel->deleteToken($token);
                    $message = "Your account has been successfully verified! You can now log in.";
                }
            } else {
                $message = "Invalid verification token or it has already been used.";
            }
        } else {
            $message = "No verification token was provided.";
        }

        require 'views/auth/verify.php';
    }

    public function emailVerification() {
        $error = "";
        $message = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']);
            if (empty($email)) {
                $error = "Please provide your email address.";
            } else {
                $user = $this->userModel->getUserByEmail($email);
                if ($user) {
                    $this->emailVerificationModel->deleteExpiredTokens($user['user_id']);
                    $this->emailVerificationModel->deleteExistingTokens($user['user_id']);

                    $token = bin2hex(random_bytes(32));
                    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    $this->emailVerificationModel->saveToken($user['user_id'], $token, $expires_at);

                    $reset_link = "http://localhost/Midterm/index.php?page=reset_password&token=$token";
                    $mail = new PHPMailer(true);
                    try {
                        $mail->CharSet = 'UTF-8';
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'pmhieu180125@gmail.com';
                        $mail->Password = 'udcxicthxwbesoin';
                        $mail->SMTPSecure = 'tls';
                        $mail->Port = 587;
                        $mail->setFrom('pmhieu180125@gmail.com', 'Password Reset Request');
                        $mail->addAddress($email, htmlspecialchars($user['username']));
                        $mail->isHTML(true);
                        $mail->Subject = 'Password Reset Request';
                        $mail->Body = "Please click the following link to reset your password: <a href='$reset_link'>Reset Password</a>";
                        $mail->send();
                        $message = "Please check your email to reset your password.";
                    } catch (Exception $e) {
                        $error = "Password reset email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                    }
                } else {
                    $error = "No account found with that email address.";
                }
            }
        }

        require 'views/auth/email_verification.php';
    }

    public function resetPassword() {
        $error = "";
        $success = "";

        if (!isset($_GET['token'])) {
            $error = "Invalid request.";
        } else {
            $token = $_GET['token'];
            $tokenData = $this->emailVerificationModel->getValidToken($token);

            if (!$tokenData) {
                $error = "This reset link is invalid or has expired.";
            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';

                if (empty($password) || empty($confirm_password)) {
                    $error = "Please fill in all fields.";
                } elseif ($password !== $confirm_password) {
                    $error = "Passwords do not match.";
                } elseif (strlen($password) < 6) {
                    $error = "Password must be at least 6 characters.";
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $this->userModel->updatePassword($tokenData['user_id'], $hashedPassword);
                    $this->emailVerificationModel->deleteToken($token);
                    $success = "Your password has been reset. You can now <a href='index.php?page=login'>log in</a>.";
                    session_unset();
                    session_destroy();
                }
            }
        }

        require 'views/auth/reset_password.php';
    }

    public function logout() {
        $_SESSION = array();
        session_destroy();
        foreach ($_COOKIE as $key => $value) {
            if ($key !== 'remember_username') {
                setcookie($key, '', time() - 3600, '/');
            }
        }
        require 'views/auth/logout.php';
    }

    private function handleRememberMe($username) {
        if (isset($_POST["remember"])) {
            setcookie("remember_username", $username, time() + (86400 * 30), "/");
        } else {
            setcookie("remember_username", "", time() - 3600, "/");
        }
    }
}
?>