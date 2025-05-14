<?php
class UserModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getUserByUsername($username) {
        $stmt = $this->pdo->prepare("SELECT user_id, username, password, is_verified, two_factor_enabled, totp_secret FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT user_id, username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserById($user_id) {
        $stmt = $this->pdo->prepare("SELECT username, email, two_factor_enabled FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function userExists($username, $email) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        return $stmt->fetchColumn() > 0;
    }

    public function createUser($username, $email, $hashedPassword) {
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, is_verified) VALUES (?, ?, ?, 0)");
        if ($stmt->execute([$username, $email, $hashedPassword])) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    public function verifyUser($user_id) {
        $stmt = $this->pdo->prepare("UPDATE users SET is_verified = 1 WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }

    public function updatePassword($user_id, $hashedPassword) {
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        return $stmt->execute([$hashedPassword, $user_id]);
    }

    public function deleteUser($user_id) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }
}
?>