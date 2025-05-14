<?php
session_start();
require_once 'config/db_connection.php';
require_once 'models/TwoFAModel.php';
require_once 'vendor/autoload.php';

use OTPHP\TOTP;

class TwoFAController {
    private $twoFAModel;

    public function __construct($pdo) {
        $this->twoFAModel = new TwoFAModel($pdo);
    }

    public function toggle2FA() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'enable') {
                $this->twoFAModel->enable2FA($user_id);
                header("Location: index.php?page=enable_2fa");
                exit;
            } elseif ($action === 'disable') {
                $this->twoFAModel->disable2FA($user_id);
                header("Location: index.php?page=homepage");
                exit;
            }
        }

        header("Location: index.php?page=homepage");
        exit;
    }

    public function enable2FA() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit();
        }

        $user_id = $_SESSION['user_id'];
        $username = $_SESSION['username'];
        $secret = $this->twoFAModel->getTotpSecret($user_id);

        if (!$secret) {
            $totp = TOTP::create();
            $totp->setLabel($username);
            $totp->setIssuer('MyApp');
            $secret = $totp->getSecret();
            $this->twoFAModel->saveTotpSecret($user_id, $secret);
        }

        $totp = TOTP::create($secret);
        $totp->setLabel($username);
        $totp->setIssuer('MyApp');
        $otpUrl = $totp->getProvisioningUri();

        require 'views/user/enable_2fa.php';
    }

    public function verifyTotp() {
        if (!isset($_SESSION["2fa_totp_user"])) {
            header("Location: index.php?page=login");
            exit();
        }

        $user_id = $_SESSION["2fa_totp_user"];
        $error = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = trim($_POST['totp_code'] ?? "");
            $user = $this->twoFAModel->getUserFor2FA($user_id);

            if ($user) {
                $totp = TOTP::create($user['totp_secret']);
                if ($totp->verify($code)) {
                    $_SESSION["logged_in"] = true;
                    $_SESSION["username"] = $user['username'];
                    $_SESSION["user_id"] = $user_id;
                    unset($_SESSION["2fa_totp_user"]);
                    unset($_SESSION["temp_username"]);
                    header("Location: index.php?page=homepage");
                    exit();
                } else {
                    $error = "Invalid authentication code.";
                }
            } else {
                $error = "User not found.";
            }
        }

        require 'views/user/verify_totp.php';
    }
}
?>