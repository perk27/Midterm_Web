<?php
class LabelsModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function createLabel($name) {
        $stmt = $this->pdo->prepare("INSERT INTO labels (name) VALUES (:name)");
        $stmt->execute(['name' => $name]);
    }

    public function updateLabel($id, $name) {
        $stmt = $this->pdo->prepare("UPDATE labels SET name = :name WHERE id = :id");
        $stmt->execute(['name' => $name, 'id' => $id]);
    }

    public function deleteLabel($id) {
        $stmt = $this->pdo->prepare("DELETE FROM labels WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function getAllLabels() {
        $stmt = $this->pdo->prepare("SELECT id, name FROM labels ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLabelCounts() {
        $label_counts = [];
        $labels = $this->getAllLabels();
        foreach ($labels as $label) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM note_labels WHERE label_id = :label_id");
            $stmt->execute(['label_id' => $label['id']]);
            $label_counts[$label['id']] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        }
        return $label_counts;
    }

    public function getLabelsForNote($note_id) {
        $stmt = $this->pdo->prepare("SELECT l.id, l.name FROM labels l JOIN note_labels nl ON l.id = nl.label_id WHERE nl.note_id = :note_id");
        $stmt->execute(['note_id' => $note_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>