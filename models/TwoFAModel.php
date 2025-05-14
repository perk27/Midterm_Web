<?php
class TwoFAModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function enable2FA($user_id) {
        $stmt = $this->pdo->prepare("UPDATE users SET two_factor_enabled = 1 WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }

    public function disable2FA($user_id) {
        $stmt = $this->pdo->prepare("UPDATE users SET two_factor_enabled = 0, totp_secret = NULL WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }

    public function getTotpSecret($user_id) {
        $stmt = $this->pdo->prepare("SELECT totp_secret FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    }

    public function saveTotpSecret($user_id, $secret) {
        $stmt = $this->pdo->prepare("UPDATE users SET totp_secret = ? WHERE user_id = ?");
        return $stmt->execute([$secret, $user_id]);
    }

    public function getUserFor2FA($user_id) {
        $stmt = $this->pdo->prepare("SELECT username, totp_secret FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>