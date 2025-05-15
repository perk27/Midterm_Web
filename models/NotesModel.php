<?php
class NotesModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function createNote($title, $content, $image_paths, $pinned) {
        $image_json = !empty($image_paths) ? json_encode($image_paths) : null;
        $stmt = $this->pdo->prepare("INSERT INTO notes (title, content, image, pinned, created_at) VALUES (:title, :content, :image, :pinned, NOW())");
        $stmt->execute(['title' => $title, 'content' => $content, 'image' => $image_json, 'pinned' => $pinned]);
        return $this->pdo->lastInsertId();
    }

    public function updateNote($id, $title, $content, $image_paths, $pinned) {
        $image_json = !empty($image_paths) ? json_encode($image_paths) : null;
        $stmt = $this->pdo->prepare("UPDATE notes SET title = :title, content = :content, image = :image, pinned = :pinned WHERE id = :id");
        $stmt->execute(['title' => $title, 'content' => $content, 'image' => $image_json, 'pinned' => $pinned, 'id' => $id]);
    }

    public function getNoteById($id) {
        $stmt = $this->pdo->prepare("SELECT id, title, content, image, pinned, password_hash FROM notes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($note && $note['image']) {
            $note['image'] = json_decode($note['image'], true) ?: [];
        }
        return $note;
    }

    public function getNoteImages($id) {
        $stmt = $this->pdo->prepare("SELECT image FROM notes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);
        return $note && $note['image'] ? json_decode($note['image'], true) : [];
    }

    public function getNotes($search, $label_filter) {
        if ($label_filter) {
            $query = "SELECT n.id, n.title, n.content, n.created_at, n.image, n.pinned, n.password_hash
                      FROM notes n
                      JOIN note_labels nl ON n.id = nl.note_id
                      WHERE nl.label_id = :label_id";
            if ($search) {
                $query .= " AND (n.title LIKE :search OR n.content LIKE :search)";
            }
            $query .= " ORDER BY n.pinned DESC, n.created_at DESC";
            $stmt = $this->pdo->prepare($query);
            $params = ['label_id' => $label_filter];
            if ($search) $params['search'] = "%$search%";
            $stmt->execute($params);
        } else {
            $query = "SELECT id, title, content, created_at, image, pinned, password_hash FROM notes";
            if ($search) $query .= " WHERE title LIKE :search OR content LIKE :search";
            $query .= " ORDER BY pinned DESC, created_at DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($search ? ['search' => "%$search%"] : []);
        }
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($notes as &$note) {
            if ($note['image']) {
                $note['image'] = json_decode($note['image'], true) ?: [];
            }
        }
        return $notes;
    }

    public function getNoteLabels($note_id) {
        $stmt = $this->pdo->prepare("SELECT label_id FROM note_labels WHERE note_id = :note_id");
        $stmt->execute(['note_id' => $note_id]);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'label_id');
    }

    public function updateNoteLabels($note_id, $labels) {
        $stmt = $this->pdo->prepare("DELETE FROM note_labels WHERE note_id = :note_id");
        $stmt->execute(['note_id' => $note_id]);
        if (!empty($labels)) {
            $stmt = $this->pdo->prepare("INSERT INTO note_labels (note_id, label_id) VALUES (:note_id, :label_id)");
            foreach ($labels as $label_id) {
                $stmt->execute(['note_id' => $note_id, 'label_id' => $label_id]);
            }
        }
    }

    public function togglePin($id, $pinned) {
        $stmt = $this->pdo->prepare("UPDATE notes SET pinned = :pinned WHERE id = :id");
        $stmt->execute(['pinned' => $pinned, 'id' => $id]);
    }

    public function deleteNote($id) {
        $stmt = $this->pdo->prepare("SELECT image FROM notes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($note['image']) {
            $image_paths = json_decode($note['image'], true) ?: [];
            foreach ($image_paths as $path) {
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }
        $stmt = $this->pdo->prepare("DELETE FROM notes WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}
?>