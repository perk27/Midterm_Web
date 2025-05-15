<?php
session_start();
require_once 'config/db_connection.php';
require_once 'models/NotesModel.php';
require_once 'models/SecureNotesModel.php';
require_once 'models/LabelsModel.php';

class NotesController {
    private $notesModel;
    private $secureNotesModel;
    private $labelsModel;

    public function __construct($pdo) {
        $this->notesModel = new NotesModel($pdo);
        $this->secureNotesModel = new SecureNotesModel($pdo);
        $this->labelsModel = new LabelsModel($pdo);
    }

    public function index() {
        // Ensure user is logged in
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: index.php?page=login');
            exit;
        }

        $errors = [];
        $success = isset($_GET['success']) ? $_GET['success'] : '';
        $view = isset($_GET['view']) && in_array($_GET['view'], ['list', 'grid']) ? $_GET['view'] : 'list';
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $label_filter = isset($_GET['label']) && is_numeric($_GET['label']) ? (int)$_GET['label'] : 0;

        // Clear password verification
        if (isset($_GET['clear_password_verification'])) {
            unset($_SESSION['verified_notes']);
            $this->redirect($view, $search, $label_filter);
        }

        // Handle password verification
        $password_required = false;
        $password_note_id = 0;
        $password_action = '';
        $password_error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_password'])) {
            $note_id = isset($_POST['note_id']) && is_numeric($_POST['note_id']) ? (int)$_POST['note_id'] : 0;
            $password = $_POST['password'] ?? '';
            $action = $_POST['password_action'] ?? '';

            error_log("Verify password - Note ID: $note_id, Action: $action");
            if ($note_id > 0 && !empty($password)) {
                $result = $this->secureNotesModel->verifyPassword($note_id, $password);
                if ($result['success']) {
                    if (!isset($_SESSION['verified_notes'])) {
                        $_SESSION['verified_notes'] = [];
                    }
                    $_SESSION['verified_notes'][$note_id] = true;
                    $params = ['view' => $view];
                    if ($search) $params['search'] = $search;
                    if ($label_filter) $params['label'] = $label_filter;
                    if ($action === 'edit') $params['edit'] = $note_id;
                    elseif ($action === 'delete') $params['delete'] = $note_id;
                    $this->redirect($view, $search, $label_filter, $params);
                } else {
                    $password_required = true;
                    $password_note_id = $note_id;
                    $password_action = $action;
                    $password_error = $result['message'];
                }
            } else {
                $password_required = true;
                $password_note_id = $note_id;
                $password_action = $action;
                $password_error = 'Password is required.';
            }
        }

        // Handle label creation
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_label'])) {
            $label_name = trim($_POST['label_name'] ?? '');
            if (empty($label_name)) {
                $errors[] = 'Label name is required.';
            } else {
                try {
                    $this->labelsModel->createLabel($label_name);
                    $this->redirect($view, $search, $label_filter, ['success' => 'Label created successfully!']);
                } catch (PDOException $e) {
                    $errors[] = 'Failed to create label: ' . $e->getMessage();
                }
            }
        }

        // Handle label update
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_label'])) {
            $label_id = $_POST['label_id'] ?? '';
            $label_name = trim($_POST['label_name'] ?? '');
            if (empty($label_name)) {
                $errors[] = 'Label name is required.';
            } else {
                try {
                    $this->labelsModel->updateLabel($label_id, $label_name);
                    $this->redirect($view, $search, $label_filter, ['success' => 'Label updated successfully!']);
                } catch (PDOException $e) {
                    $errors[] = 'Failed to update label: ' . $e->getMessage();
                }
            }
        }

        // Handle label deletion
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_label'])) {
            $label_id = $_POST['label_id'] ?? '';
            try {
                $this->labelsModel->deleteLabel($label_id);
                $label_filter = ($label_filter == $label_id) ? 0 : $label_filter;
                $this->redirect($view, $search, $label_filter, ['success' => 'Label deleted successfully!']);
            } catch (PDOException $e) {
                $errors[] = 'Failed to delete label: ' . $e->getMessage();
            }
        }

        // Handle note creation
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_note'])) {
            $result = $this->handleNoteSave(false);
            if ($result['success']) {
                $this->redirect($view, $search, $label_filter, ['success' => 'Note created successfully!']);
            } else {
                $errors = $result['errors'];
            }
        }

        // Handle note update
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_note'])) {
            $result = $this->handleNoteSave(true);
            if ($result['success']) {
                $this->redirect($view, $search, $label_filter, ['success' => 'Note updated successfully!']);
            } else {
                $errors = $result['errors'];
            }
        }

        // Handle note pinning
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_pin'])) {
            $id = $_POST['id'] ?? '';
            $pinned = $_POST['pinned'] == '1' ? 1 : 0;
            try {
                $this->notesModel->togglePin($id, $pinned);
                $success = $pinned ? 'Note pinned successfully!' : 'Note unpinned successfully!';
                $this->redirect($view, $search, $label_filter, ['success' => $success]);
            } catch (PDOException $e) {
                $errors[] = 'Failed to toggle pin: ' . $e->getMessage();
            }
        }

        // Handle note deletion
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_note'])) {
            $id = $_POST['delete_id'] ?? '';
            $has_password = $this->secureNotesModel->hasPassword($id);
            if ($has_password && (!isset($_SESSION['verified_notes']) || !isset($_SESSION['verified_notes'][$id]))) {
                $password_required = true;
                $password_note_id = $id;
                $password_action = 'delete';
                $password_error = 'This note is password-protected. Please enter the password to delete it.';
            } else {
                try {
                    $this->notesModel->deleteNote($id);
                    $this->redirect($view, $search, $label_filter, ['success' => 'Note deleted successfully!']);
                } catch (PDOException $e) {
                    $errors[] = 'Failed to delete note: ' . $e->getMessage();
                }
            }
        }

        // Handle AJAX note save
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_save'])) {
            header('Content-Type: application/json');
            $result = $this->handleNoteSave(isset($_POST['id']) && !empty($_POST['id']));
            echo json_encode([
                'success' => $result['success'],
                'id' => $result['id'] ?? null,
                'message' => $result['success'] ? 'Note saved successfully.' : implode(', ', $result['errors'])
            ]);
            exit;
        }

        // Load note for editing
        $edit_note = null;
        $edit_note_labels = [];
        if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
            $note_id = (int)$_GET['edit'];
            $has_password = $this->secureNotesModel->hasPassword($note_id);
            if ($has_password && (!isset($_SESSION['verified_notes']) || !isset($_SESSION['verified_notes'][$note_id]))) {
                $password_required = true;
                $password_note_id = $note_id;
                $password_action = 'edit';
                $password_error = 'This note is password-protected. Please enter the password to edit it.';
            } else {
                $edit_note = $this->notesModel->getNoteById($note_id);
                if ($edit_note) {
                    $edit_note_labels = $this->notesModel->getNoteLabels($note_id);
                }
            }
        }

        // Fetch data for view
        $labels = $this->labelsModel->getAllLabels();
        $label_counts = $this->labelsModel->getLabelCounts();
        $notes = $this->notesModel->getNotes($search, $label_filter);
        foreach ($notes as &$note) {
            $note['labels'] = $this->labelsModel->getLabelsForNote($note['id']);
            $note['is_verified'] = !$this->secureNotesModel->hasPassword($note['id']) || (isset($_SESSION['verified_notes']) && isset($_SESSION['verified_notes'][$note['id']]));
        }
        unset($note);

        // Render view
        require 'views/notes/notes.php';
    }

    private function handleNoteSave($isUpdate) {
        $errors = [];
        $id = $isUpdate ? (isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : 0) : 0;
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        $pinned = isset($_POST['pinned']) && $_POST['pinned'] == '1' ? 1 : 0;
        $labels = isset($_POST['labels']) && is_array($_POST['labels']) ? $_POST['labels'] : [];
        $images = isset($_FILES['images']) ? $_FILES['images'] : [];
        $removed_images = isset($_POST['removed_images']) ? json_decode($_POST['removed_images'], true) : [];

        if (empty($title)) $errors[] = 'Note Title is required.';
        if (empty($content)) $errors[] = 'Note Content is required.';
        if ($isUpdate && empty($id)) $errors[] = 'Note ID is missing.';

        // Handle images
        $image_paths = [];
        $existing_images = $isUpdate ? $this->notesModel->getNoteImages($id) : [];
        if (!empty($images['name'][0])) {
            $allowed_types = ['image/jpeg', 'image/png'];
            $max_size = 5 * 1024 * 1024;
            for ($i = 0; $i < count($images['name']); $i++) {
                if ($images['error'][$i] === UPLOAD_ERR_OK) {
                    if (!in_array($images['type'][$i], $allowed_types)) {
                        $errors[] = 'Only JPEG and PNG images are allowed.';
                        break;
                    }
                    if ($images['size'][$i] > $max_size) {
                        $errors[] = 'Image size must be less than 5MB.';
                        break;
                    }
                    $ext = pathinfo($images['name'][$i], PATHINFO_EXTENSION);
                    $image_path = 'Uploads/' . uniqid() . '.' . $ext;
                    if (!move_uploaded_file($images['tmp_name'][$i], $image_path)) {
                        $errors[] = 'Failed to upload image ' . ($i + 1) . '.';
                    } else {
                        $image_paths[] = $image_path;
                    }
                } elseif ($images['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                    $errors[] = 'Error uploading image ' . ($i + 1) . '.';
                }
            }
        }
        if (!empty($removed_images) && !empty($existing_images)) {
            $existing_images = array_filter($existing_images, fn($path) => !in_array($path, $removed_images));
        }
        $image_paths = array_merge($existing_images, $image_paths);

        // Handle password
        if (isset($_POST['enable_password']) && empty(trim($_POST['note_password'] ?? ''))) {
            $errors[] = 'Password is required to enable password protection.';
        }
        if (isset($_POST['disable_password']) && empty(trim($_POST['current_password'] ?? ''))) {
            $errors[] = 'Current password is required to disable password protection.';
        }

        if (empty($errors)) {
            try {
                if ($isUpdate) {
                    $this->notesModel->updateNote($id, $title, $content, $image_paths, $pinned);
                } else {
                    $id = $this->notesModel->createNote($title, $content, $image_paths, $pinned);
                }
                $this->notesModel->updateNoteLabels($id, $labels);

                if (isset($_POST['enable_password']) && !empty(trim($_POST['note_password']))) {
                    $result = $this->secureNotesModel->enablePassword($id, $_POST['note_password']);
                    if (!$result['success']) $errors[] = $result['message'];
                }
                if (isset($_POST['disable_password']) && !empty(trim($_POST['current_password']))) {
                    $result = $this->secureNotesModel->disablePassword($id, $_POST['current_password']);
                    if (!$result['success']) $errors[] = $result['message'];
                }
            } catch (Exception $e) {
                $errors[] = 'Error saving note: ' . $e->getMessage();
            }
        }

        return ['success' => empty($errors), 'errors' => $errors, 'id' => $id];
    }

    private function redirect($view, $search, $label_filter, $additional_params = []) {
        $params = ['view' => $view];
        if ($search) $params['search'] = $search;
        if ($label_filter) $params['label'] = $label_filter;
        $params = array_merge($params, $additional_params);
        header('Location: index.php?page=notes&' . http_build_query($params));
        exit;
    }
}
?>