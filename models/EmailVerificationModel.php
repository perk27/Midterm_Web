<?php
class EmailVerificationModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getToken($token) {
        $stmt = $this->pdo->prepare("
            SELECT evt.user_id, evt.token, evt.expires_at, u.is_verified
            FROM email_verification_tokens evt
            JOIN users u ON evt.user_id = u.user_id
            WHERE evt.token = ?
        ");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getValidToken($token) {
        $stmt = $this->pdo->prepare("SELECT user_id FROM email_verification_tokens WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function saveToken($user_id, $token, $expires_at) {
        $stmt = $this->pdo->prepare("INSERT INTO email_verification_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        return $stmt->execute([$user_id, $token, $expires_at]);
    }

    public function deleteToken($token) {
        $stmt = $this->pdo->prepare("DELETE FROM email_verification_tokens WHERE token = ?");
        return $stmt->execute([$token]);
    }

    public function deleteExpiredTokens($user_id) {
        $stmt = $this->pdo->prepare("DELETE FROM email_verification_tokens WHERE user_id = ? AND expires_at < NOW()");
        return $stmt->execute([$user_id]);
    }

    public function deleteExistingTokens($user_id) {
        $stmt = $this->pdo->prepare("DELETE FROM email_verification_tokens WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }
}
?>