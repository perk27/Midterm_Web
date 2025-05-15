<?php
class SecureNotesModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function enablePassword($note_id, $password) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("UPDATE notes SET password_hash = :password_hash WHERE id = :id");
            $stmt->execute(['password_hash' => $hashed_password, 'id' => $note_id]);
            error_log("Password enabled for note ID $note_id");
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Error enabling password for note ID $note_id: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to enable password: ' . $e->getMessage()];
        }
    }

    public function disablePassword($note_id, $current_password) {
        try {
            $stmt = $this->pdo->prepare("SELECT password_hash FROM notes WHERE id = :id");
            $stmt->execute(['id' => $note_id]);
            $note = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$note || empty($note['password_hash'])) {
                error_log("No password found for note ID $note_id");
                return ['success' => false, 'message' => 'No password set for this note.'];
            }

            if (!password_verify($current_password, $note['password_hash'])) {
                error_log("Incorrect password for note ID $note_id");
                return ['success' => false, 'message' => 'Incorrect password.'];
            }

            $stmt = $this->pdo->prepare("UPDATE notes SET password_hash = NULL WHERE id = :id");
            $stmt->execute(['id' => $note_id]);
            error_log("Password disabled for note ID $note_id");
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Error disabling password for note ID $note_id: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to disable password: ' . $e->getMessage()];
        }
    }

    public function verifyPassword($note_id, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT password_hash FROM notes WHERE id = :id");
            $stmt->execute(['id' => $note_id]);
            $note = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$note || empty($note['password_hash'])) {
                error_log("No password found for note ID $note_id during verification");
                return ['success' => false, 'message' => 'No password set for this note.'];
            }

            if (password_verify($password, $note['password_hash'])) {
                error_log("Password verified for note ID $note_id");
                return ['success' => true];
            } else {
                error_log("Incorrect password for note ID $note_id during verification");
                return ['success' => false, 'message' => 'Incorrect password.'];
            }
        } catch (PDOException $e) {
            error_log("Error verifying password for note ID $note_id: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to verify password: ' . $e->getMessage()];
        }
    }

    public function hasPassword($note_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT password_hash FROM notes WHERE id = :id");
            $stmt->execute(['id' => $note_id]);
            $note = $stmt->fetch(PDO::FETCH_ASSOC);
            $has_password = $note && !empty($note['password_hash']);
            error_log("Checked password for note ID $note_id: " . ($has_password ? 'Has password' : 'No password'));
            return $has_password;
        } catch (PDOException $e) {
            error_log("Error checking password for note ID $note_id: " . $e->getMessage());
            return false;
        }
    }
}
?>