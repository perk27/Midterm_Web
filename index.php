<?php
date_default_timezone_set('UTC');
require_once 'config/db_connection.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/UserController.php';
require_once 'controllers/TwoFAController.php';
require_once 'controllers/NotesController.php';

$page = $_GET['page'] ?? 'login';

$authController = new AuthController($pdo);
$userController = new UserController($pdo);
$twoFAController = new TwoFAController($pdo);
$notesController = new NotesController($pdo);

switch ($page) {
    case 'login':
        $authController->login();
        break;
    case 'signup':
        $authController->signup();
        break;
    case 'verify':
        $authController->verify();
        break;
    case 'email_verification':
        $authController->emailVerification();
        break;
    case 'reset_password':
        $authController->resetPassword();
        break;
    case 'logout':
        $authController->logout();
        break;
    case 'homepage':
        $userController->homepage();
        break;
    case 'delete_account':
        $userController->deleteAccount();
        break;
    case 'toggle_2fa':
        $twoFAController->toggle2FA();
        break;
    case 'enable_2fa':
        $twoFAController->enable2FA();
        break;
    case 'verify_totp':
        $twoFAController->verifyTotp();
        break;
    case 'notes':
        $notesController->index();
        break;
    default:
        $authController->login();
}
?>