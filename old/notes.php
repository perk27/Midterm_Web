<?php
require 'db_connection.php'; // Database connection ($pdo should be defined here)
require 'secure_notes.php'; // Include SecureNotes class

// Khởi động session để lưu trạng thái xác minh mật khẩu
session_start();

// Tạo đối tượng SecureNotes
$secureNotes = new SecureNotes($pdo);

// Xóa trạng thái xác minh mật khẩu khi cần
if (isset($_GET['clear_password_verification'])) {
    unset($_SESSION['verified_notes']);
    header("Location: " . $_SERVER['PHP_SELF'] . '?' . http_build_query(array_diff_key($_GET, ['clear_password_verification' => ''])));
    exit;
}

// Determine view mode (default to list)
$view = isset($_GET['view']) && in_array($_GET['view'], ['list', 'grid']) ? $_GET['view'] : 'list';

// Handle search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle label filter
$label_filter = isset($_GET['label']) && is_numeric($_GET['label']) ? (int)$_GET['label'] : 0;

// Xử lý yêu cầu nhập mật khẩu để xem hoặc chỉnh sửa ghi chú
$password_required = false;
$password_note_id = 0;
$password_action = '';
$password_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_password'])) {
    // Debug: Ghi log dữ liệu gửi lên
    error_log("POST data for verify_password: " . print_r($_POST, true));

    $note_id = isset($_POST['note_id']) && is_numeric($_POST['note_id']) ? (int)$_POST['note_id'] : 0;
    $password = $_POST['password'] ?? '';
    $action = $_POST['password_action'] ?? '';

    // Debug: Ghi log các giá trị nhận được
    error_log("Verify password - Note ID: $note_id, Action: $action, Password provided: " . ($password ? 'Yes' : 'No'));

    if ($note_id > 0 && !empty($password)) {
        $result = $secureNotes->verifyPassword($note_id, $password);
        if ($result['success']) {
            if (!isset($_SESSION['verified_notes'])) {
                $_SESSION['verified_notes'] = [];
            }
            $_SESSION['verified_notes'][$note_id] = true;
            $redirect_params = ['view' => $view];
            if ($search) $redirect_params['search'] = $search;
            if ($label_filter) $redirect_params['label'] = $label_filter;
            if ($action === 'edit') {
                $redirect_params['edit'] = $note_id;
            } elseif ($action === 'delete') {
                $redirect_params['delete'] = $note_id;
            }
            // Debug: Ghi log trước khi redirect
            error_log("Password verified successfully for note ID $note_id, redirecting...");
            header("Location: " . $_SERVER['PHP_SELF'] . '?' . http_build_query($redirect_params));
            exit;
        } else {
            $password_required = true;
            $password_note_id = $note_id;
            $password_action = $action;
            $password_error = $result['message'];
            // Debug: Ghi log khi xác minh thất bại
            error_log("Password verification failed for note ID $note_id: " . $result['message']);
        }
    } else {
        $password_required = true;
        $password_note_id = $note_id;
        $password_action = $action;
        $password_error = 'Password is required.';
        // Debug: Ghi log khi thiếu mật khẩu
        error_log("Password verification failed: Password is required for note ID $note_id");
    }
}

// Handle label creation
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_label'])) {
    $label_name = trim($_POST['label_name'] ?? '');
    if (empty($label_name)) {
        $errors[] = "Label name is required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO labels (name) VALUES (:name)");
            $stmt->execute(['name' => $label_name]);
            $success = "Label created successfully!";
            $redirect_url = $_SERVER['PHP_SELF'] . '?view=' . urlencode($view) . '&success=' . urlencode($success) . ($search ? '&search=' . urlencode($search) : '') . ($label_filter ? '&label=' . $label_filter : '');
            header("Location: $redirect_url");
            exit;
        } catch(PDOException $e) {
            $errors[] = "Failed to create label: " . $e->getMessage();
        }
    }
}

// Handle label update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_label'])) {
    $label_id = $_POST['label_id'] ?? '';
    $label_name = trim($_POST['label_name'] ?? '');
    if (empty($label_name)) {
        $errors[] = "Label name is required.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE labels SET name = :name WHERE id = :id");
            $stmt->execute(['name' => $label_name, 'id' => $label_id]);
            $success = "Label updated successfully!";
            $redirect_url = $_SERVER['PHP_SELF'] . '?view=' . urlencode($view) . '&success=' . urlencode($success) . ($search ? '&search=' . urlencode($search) : '') . ($label_filter ? '&label=' . $label_filter : '');
            header("Location: $redirect_url");
            exit;
        } catch(PDOException $e) {
            $errors[] = "Failed to update label: " . $e->getMessage();
        }
    }
}

// Handle label deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_label'])) {
    $label_id = $_POST['label_id'] ?? '';
    try {
        $stmt = $pdo->prepare("DELETE FROM labels WHERE id = :id");
        $stmt->execute(['id' => $label_id]);
        $success = "Label deleted successfully!";
        $redirect_url = $_SERVER['PHP_SELF'] . '?view=' . urlencode($view) . '&success=' . urlencode($success) . ($search ? '&search=' . urlencode($search) : '');
        if ($label_filter == $label_id) {
            $label_filter = 0;
        } else {
            $redirect_url .= ($label_filter ? '&label=' . $label_filter : '');
        }
        header("Location: $redirect_url");
        exit;
    } catch(PDOException $e) {
        $errors[] = "Failed to delete label: " . $e->getMessage();
    }
}

// Handle note creation (manual save)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_note'])) {
    // Debug: Ghi log dữ liệu gửi lên
    error_log("POST data for create_note: " . print_r($_POST, true));
    error_log("FILES data for create_note: " . print_r($_FILES, true));

    // Lấy dữ liệu từ form và loại bỏ khoảng trắng
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $pinned = isset($_POST['pinned']) && $_POST['pinned'] == '1' ? 1 : 0;
    $labels = isset($_POST['labels']) && is_array($_POST['labels']) ? $_POST['labels'] : [];
    $images = $_FILES['images'] ?? [];
    $image_paths = [];

    // Debug: Ghi log giá trị sau khi trim
    error_log("Title after trim: '$title'");
    error_log("Content after trim: '$content'");

    // Kiểm tra các trường bắt buộc
    $errors = [];
    if (empty($title)) {
        $errors[] = "Note Title is required.";
    }
    if (empty($content)) {
        $errors[] = "Note Content is required.";
    }

    // Xử lý hình ảnh (nếu có)
    if (!empty($images['name'][0])) {
        $allowed_types = ['image/jpeg', 'image/png'];
        $max_size = 5 * 1024 * 1024;
        for ($i = 0; $i < count($images['name']); $i++) {
            if ($images['error'][$i] === UPLOAD_ERR_OK) {
                if (!in_array($images['type'][$i], $allowed_types)) {
                    $errors[] = "Only JPEG and PNG images are allowed.";
                    break;
                }
                if ($images['size'][$i] > $max_size) {
                    $errors[] = "Image size must be less than 5MB.";
                    break;
                }
                $ext = pathinfo($images['name'][$i], PATHINFO_EXTENSION);
                $image_path = 'uploads/' . uniqid() . '.' . $ext;
                if (move_uploaded_file($images['tmp_name'][$i], $image_path)) {
                    $image_paths[] = $image_path;
                } else {
                    $errors[] = "Failed to upload image " . ($i + 1) . ".";
                }
            } elseif ($images['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = "Error uploading image " . ($i + 1) . ".";
            }
        }
    }

    // Kiểm tra mật khẩu khi bật
    if (isset($_POST['enable_password']) && empty(trim($_POST['note_password'] ?? ''))) {
        $errors[] = "Password is required to enable password protection.";
    }

    // Debug: Ghi log danh sách lỗi
    error_log("Errors after validation: " . print_r($errors, true));

    // Chỉ tạo ghi chú nếu không có lỗi
    if (empty($errors)) {
        try {
            $image_json = !empty($image_paths) ? json_encode($image_paths) : null;
            $stmt = $pdo->prepare("INSERT INTO notes (title, content, image, pinned) VALUES (:title, :content, :image, :pinned)");
            $stmt->execute(['title' => $title, 'content' => $content, 'image' => $image_json, 'pinned' => $pinned]);
            $note_id = $pdo->lastInsertId();

            if (!empty($labels)) {
                $stmt = $pdo->prepare("INSERT INTO note_labels (note_id, label_id) VALUES (:note_id, :label_id)");
                foreach ($labels as $label_id) {
                    $stmt->execute(['note_id' => $note_id, 'label_id' => $label_id]);
                }
            }

            // Xử lý mật khẩu
            if (isset($_POST['enable_password']) && !empty(trim($_POST['note_password']))) {
                $result = $secureNotes->enablePassword($note_id, $_POST['note_password']);
                if (!$result['success']) {
                    $errors[] = $result['message'];
                }
            }

            if (empty($errors)) {
                $success = "Note created successfully!";
                $redirect_url = $_SERVER['PHP_SELF'] . '?view=' . urlencode($view) . '&success=' . urlencode($success) . ($search ? '&search=' . urlencode($search) : '') . ($label_filter ? '&label=' . $label_filter : '');
                header("Location: $redirect_url");
                exit;
            }
        } catch(PDOException $e) {
            $errors[] = "Failed to save note: " . $e->getMessage();
        }
    }

    // Debug: Ghi log nếu có lỗi và không redirect
    if (!empty($errors)) {
        error_log("Create note failed with errors: " . print_r($errors, true));
    }
}

// Handle note update (manual save)
$edit_note = null;
$edit_note_labels = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_note'])) {
    $id = isset($_POST['id']) ? trim($_POST['id']) : '';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $pinned = isset($_POST['pinned']) && $_POST['pinned'] == '1' ? 1 : 0;
    $labels = isset($_POST['labels']) && is_array($_POST['labels']) ? $_POST['labels'] : [];
    $images = isset($_FILES['images']) ? $_FILES['images'] : [];
    $removed_images = isset($_POST['removed_images']) ? json_decode($_POST['removed_images'], true) : [];
    $image_paths = [];

    if (empty($id)) {
        $errors[] = "Note ID is missing.";
    }
    if (empty($title)) {
        $errors[] = "Note Title is required.";
    }
    if (empty($content)) {
        $errors[] = "Note Content is required.";
    }

    $existing_images = [];
    if ($id && empty($errors)) {
        $stmt = $pdo->prepare("SELECT image FROM notes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);
        $existing_images = $note && $note['image'] ? json_decode($note['image'], true) : [];
    }

    if (!empty($images['name'][0]) && empty($errors)) {
        $allowed_types = ['image/jpeg', 'image/png'];
        $max_size = 5 * 1024 * 1024;
        for ($i = 0; $i < count($images['name']); $i++) {
            if ($images['error'][$i] === UPLOAD_ERR_OK) {
                if (!in_array($images['type'][$i], $allowed_types)) {
                    $errors[] = "Only JPEG and PNG images are allowed.";
                    break;
                }
                if ($images['size'][$i] > $max_size) {
                    $errors[] = "Image size must be less than 5MB.";
                    break;
                }
                $ext = pathinfo($images['name'][$i], PATHINFO_EXTENSION);
                $image_path = 'uploads/' . uniqid() . '.' . $ext;
                if (move_uploaded_file($images['tmp_name'][$i], $image_path)) {
                    $image_paths[] = $image_path;
                } else {
                    $errors[] = "Failed to upload image " . ($i + 1) . ".";
                }
            } elseif ($images['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = "Error uploading image " . ($i + 1) . ".";
            }
        }
    }

    if (!empty($removed_images) && !empty($existing_images)) {
        $existing_images = array_filter($existing_images, fn($path) => !in_array($path, $removed_images));
    }

    // Kiểm tra mật khẩu khi bật/tắt
    if (isset($_POST['enable_password']) && empty(trim($_POST['note_password'] ?? ''))) {
        $errors[] = "Password is required to enable password protection.";
    }
    if (isset($_POST['disable_password']) && empty(trim($_POST['current_password'] ?? ''))) {
        $errors[] = "Current password is required to disable password protection.";
    }

    if (empty($errors)) {
        $image_paths = array_merge(array_values($existing_images), $image_paths);
        $image_json = !empty($image_paths) ? json_encode($image_paths) : null;
        try {
            $stmt = $pdo->prepare("UPDATE notes SET title = :title, content = :content, image = :image, pinned = :pinned WHERE id = :id");
            $stmt->execute(['title' => $title, 'content' => $content, 'image' => $image_json, 'pinned' => $pinned, 'id' => $id]);

            $stmt = $pdo->prepare("DELETE FROM note_labels WHERE note_id = :note_id");
            $stmt->execute(['note_id' => $id]);
            if (!empty($labels)) {
                $stmt = $pdo->prepare("INSERT INTO note_labels (note_id, label_id) VALUES (:note_id, :label_id)");
                foreach ($labels as $label_id) {
                    $stmt->execute(['note_id' => $id, 'label_id' => $label_id]);
                }
            }

            // Xử lý mật khẩu
            if (isset($_POST['enable_password']) && !empty(trim($_POST['note_password']))) {
                $result = $secureNotes->enablePassword($id, $_POST['note_password']);
                if (!$result['success']) {
                    $errors[] = $result['message'];
                }
            } elseif (isset($_POST['disable_password']) && !empty(trim($_POST['current_password']))) {
                $result = $secureNotes->disablePassword($id, $_POST['current_password']);
                if (!$result['success']) {
                    $errors[] = $result['message'];
                }
            }

            if (empty($errors)) {
                $success = "Note updated successfully!";
                $redirect_url = $_SERVER['PHP_SELF'] . '?view=' . urlencode($view) . '&success=' . urlencode($success) . ($search ? '&search=' . urlencode($search) : '') . ($label_filter ? '&label=' . $label_filter : '');
                header("Location: $redirect_url");
                exit;
            }
        } catch(PDOException $e) {
            $errors[] = "Failed to update note: " . $e->getMessage();
        }
    }
}

// Handle note pinning/unpinning
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_pin'])) {
    $id = $_POST['id'] ?? '';
    $pinned = $_POST['pinned'] == '1' ? 1 : 0;
    try {
        $stmt = $pdo->prepare("UPDATE notes SET pinned = :pinned WHERE id = :id");
        $stmt->execute(['pinned' => $pinned, 'id' => $id]);
        $success = $pinned ? "Note pinned successfully!" : "Note unpinned successfully!";
        $redirect_url = $_SERVER['PHP_SELF'] . '?view=' . urlencode($view) . '&success=' . urlencode($success) . ($search ? '&search=' . urlencode($search) : '') . ($label_filter ? '&label=' . $label_filter : '');
        header("Location: $redirect_url");
        exit;
    } catch(PDOException $e) {
        $errors[] = "Failed to toggle pin: " . $e->getMessage();
    }
}

// Handle note deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_note'])) {
    $id = $_POST['delete_id'] ?? '';
    $has_password = $secureNotes->hasPassword($id);
    if ($has_password && (!isset($_SESSION['verified_notes']) || !isset($_SESSION['verified_notes'][$id]))) {
        $password_required = true;
        $password_note_id = $id;
        $password_action = 'delete';
        $password_error = 'This note is password-protected. Please enter the password to delete it.';
        // Debug: Ghi log khi yêu cầu mật khẩu để xóa
        error_log("Password required to delete note ID $id");
    } else {
        try {
            $stmt = $pdo->prepare("SELECT image FROM notes WHERE id = :id");
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
            $stmt = $pdo->prepare("DELETE FROM notes WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $success = "Note deleted successfully!";
            $redirect_url = $_SERVER['PHP_SELF'] . '?view=' . urlencode($view) . '&success=' . urlencode($success) . ($search ? '&search=' . urlencode($search) : '') . ($label_filter ? '&label=' . $label_filter : '');
            header("Location: $redirect_url");
            exit;
        } catch(PDOException $e) {
            $errors[] = "Failed to delete note: " . $e->getMessage();
        }
    }
}

// Load note for editing
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $note_id = (int)$_GET['edit'];
    $has_password = $secureNotes->hasPassword($note_id);
    if ($has_password && (!isset($_SESSION['verified_notes']) || !isset($_SESSION['verified_notes'][$note_id]))) {
        $password_required = true;
        $password_note_id = $note_id;
        $password_action = 'edit';
        $password_error = 'This note is password-protected. Please enter the password to edit it.';
        // Debug: Ghi log khi yêu cầu mật khẩu để chỉnh sửa
        error_log("Password required to edit note ID $note_id");
    } else {
        $stmt = $pdo->prepare("SELECT id, title, content, image, pinned, password_hash FROM notes WHERE id = :id");
        $stmt->execute(['id' => $note_id]);
        $edit_note = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($edit_note && $edit_note['image']) {
            $edit_note['image'] = json_decode($edit_note['image'], true) ?: [];
        }
        if ($edit_note) {
            $stmt = $pdo->prepare("SELECT label_id FROM note_labels WHERE note_id = :note_id");
            $stmt->execute(['note_id' => $edit_note['id']]);
            $edit_note_labels = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'label_id');
        }
    }
}

// Fetch all labels
$stmt = $pdo->prepare("SELECT id, name FROM labels ORDER BY name ASC");
$stmt->execute();
$labels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch label counts for sidebar
$label_counts = [];
foreach ($labels as $label) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM note_labels WHERE label_id = :label_id");
    $stmt->execute(['label_id' => $label['id']]);
    $label_counts[$label['id']] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

// Fetch all notes with search, label filter, and pinning support
if ($label_filter) {
    $query = "SELECT n.id, n.title, n.content, n.created_at, n.image, n.pinned, n.password_hash
              FROM notes n
              JOIN note_labels nl ON n.id = nl.note_id
              WHERE nl.label_id = :label_id";
    if ($search) {
        $query .= " AND (n.title LIKE :search OR n.content LIKE :search)";
    }
    $query .= " ORDER BY n.pinned DESC, n.created_at DESC";
    $stmt = $pdo->prepare($query);
    $params = ['label_id' => $label_filter];
    if ($search) {
        $params['search'] = "%$search%";
    }
    $stmt->execute($params);
} else {
    $query = "SELECT id, title, content, created_at, image, pinned, password_hash FROM notes";
    if ($search) {
        $query .= " WHERE title LIKE :search OR content LIKE :search";
    }
    $query .= " ORDER BY pinned DESC, created_at DESC";
    $stmt = $pdo->prepare($query);
    if ($search) {
        $stmt->execute(['search' => "%$search%"]);
    } else {
        $stmt->execute();
    }
}
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($notes as &$note) {
    if ($note['image']) {
        $note['image'] = json_decode($note['image'], true) ?: [];
    }
    $stmt = $pdo->prepare("SELECT l.id, l.name FROM labels l JOIN note_labels nl ON l.id = nl.label_id WHERE nl.note_id = :note_id");
    $stmt->execute(['note_id' => $note['id']]);
    $note['labels'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $note['is_verified'] = !$secureNotes->hasPassword($note['id']) || (isset($_SESSION['verified_notes']) && isset($_SESSION['verified_notes'][$note['id']]));
    // Debug: Ghi log trạng thái xác minh của ghi chú
    error_log("Note ID {$note['id']} - Has password: " . ($secureNotes->hasPassword($note['id']) ? 'Yes' : 'No') . ", Is verified: " . ($note['is_verified'] ? 'Yes' : 'No'));
}
unset($note);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Note Management</title>
    <style>
        /* Import a font similar to Notion's (Inter) */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        :root {
            --bg-color: #f5f5f5;
            --text-color: #2f2f2f;
            --sidebar-bg: #fafafa;
            --card-bg: #fff;
            --border-color: #e0e0e0;
            --primary-color: #2eaadc;
            --success-bg: #e6ffed;
            --success-text: #2f855a;
            --error-bg: #ffe6e6;
            --error-text: #e53e3e;
        }

        [data-theme="dark"] {
            --bg-color: #1a1a1a;
            --text-color: #e0e0e0;
            --sidebar-bg: #2d2d2d;
            --card-bg: #333;
            --border-color: #444;
            --primary-color: #4dabf7;
            --success-bg: #1a3c2b;
            --success-text: #a3e4bc;
            --error-bg: #3c1a1a;
            --error-text: #f4a3a3;
            /* Đổi màu chữ trong sidebar thành trắng khi ở chế độ tối */
            --sidebar-text: #fff;
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-color);
            color: var(--text-color);
            transition: all 0.3s ease;
        }

        .container {
            display: flex;
            height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 250px;
            background: var(--sidebar-bg);
            padding: 20px;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .sidebar h3, .sidebar h2 {
            font-size: 14px;
            font-weight: 600;
            color: var(--sidebar-text, #666); /* Sử dụng màu trắng trong chế độ tối */
            margin: 10px 0;
        }

        .sidebar .label-item {
            display: inline-flex;
            align-items: center;
            padding: 8px 10px;
            border-radius: 4px;
            font-size: 14px;
            color: var(--sidebar-text, var(--text-color)); /* Sử dụng màu trắng trong chế độ tối */
            background: transparent;
            transition: background 0s;
            justify-content: center;
        }

        .sidebar .label-item.active {
            background: #f0f0f0;
            font-weight: 500;
        }

        [data-theme="dark"] .sidebar .label-item.active {
            background: #444; /* Đảm bảo nền của item active trong dark mode không làm mờ chữ */
        }

        .sidebar .label-item:hover {
            background: #f0f0f0;
        }

        [data-theme="dark"] .sidebar .label-item:hover {
            background: #444; /* Đảm bảo hover trong dark mode không làm mờ chữ */
        }

        .sidebar .label-item a {
            flex: 1;
            color: var(--sidebar-text, var(--text-color)); /* Sử dụng màu trắng trong chế độ tối */
            text-decoration: none;
        }

        /* Main Content Styling */
        .main-content {
            flex: 1;
            overflow-y: auto;
            padding: 0;
            display: block; /* Change from flex to block to stack vertically */
        }

        .note-form-container {
            margin: 20px;
        }

        .note-display-container {
            margin: 20px;
            padding-bottom: 20px; /* Ensure space at the bottom */
        }

        /* Ensure form and note display stack vertically */
        .header, .search-form, .password-form, .note-form-container, .note-display-container {
            width: 100%;
        }

        /* Adjust height and overflow for the entire main content */
        .main-content {
            min-height: calc(100vh - 60px); /* Adjust based on header/sidebar height */
            overflow-y: auto;
        }

        /* Keep existing styles for note lists and grids */
        .pinned-list, .note-list, .pinned-grid, .note-grid {
            list-style: none;
            padding: 0;
        }

        .pinned-grid, .note-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        /* Header with Background Image */
        .header {
            position: relative;
            height: 200px;
            background: url('https://images.unsplash.com/photo-1746794263753-d8f74743c25c?q=80&w=1948&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D') no-repeat center center;
            background-size: cover;
            border-radius: 8px;
            margin: 20px;
            display: flex;
            align-items: flex-end;
            padding: 20px;
        }

        .header h1 {
            color: #fff;
            font-size: 28px;
            font-weight: 600;
            margin: 0;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .header-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
        }

        /* Form Styling */
        .note-form, .password-form {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin: 20px;
        }

        .note-form h2, .password-form h2 {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-color);
            margin: 0 0 15px;
        }

        .note-form input, .note-form textarea, .password-form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
            background: var(--card-bg);
            color: var(--text-color);
        }

        .note-form textarea {
            height: 100px;
            resize: vertical;
        }

        .note-form button, .password-form button {
            background: var(--primary-color);
            color: #fff;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .note-form button:hover, .password-form button:hover {
            background: #2699c7;
        }

        /* Search Form */
        .search-form {
            margin: 20px;
            display: flex;
            gap: 10px;
        }

        .search-form input[type="text"] {
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
            background: var(--card-bg);
            color: var(--text-color);
            flex: 1;
        }

        .search-form button {
            background: var(--primary-color);
            color: #fff;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .search-form button:hover {
            background: #2699c7;
        }

        /* Success/Error Messages */
        .success, .error {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            font-size: 14px;
        }

        .success {
            background: var(--success-bg);
            color: var(--success-text);
        }

        .error {
            background: var(--error-bg);
            color: var(--error-text);
        }

        /* Note Display Styling */
        .view-toggle {
            margin: 20px;
        }

        .view-toggle a {
            padding: 8px 16px;
            margin-right: 10px;
            text-decoration: none;
            color: var(--primary-color);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--card-bg);
        }

        .view-toggle a.active {
            background: var(--primary-color);
            color: #fff;
        }

        .view-toggle a:hover {
            background: #2699c7;
            color: #fff;
        }

        .pinned-list, .note-list {
            list-style: none;
            padding: 0 20px;
        }

        .pinned-item, .note-item {
            background: var(--card-bg);
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .pinned-item h3, .note-item h3 {
            margin: 0 0 10px;
            font-size: 16px;
            font-weight: 600;
            color: var(--text-color);
        }

        .pinned-item.password-protected h3, .note-item.password-protected h3 {
            color: #666;
        }

        .pinned-item p, .note-item p {
            margin: 0 0 10px;
            color: var(--text-color);
        }

        .note-labels span {
            background: #f0f0f0;
            padding: 4px 8px;
            border-radius: 4px;
            margin-right: 5px;
            font-size: 12px;
        }

        .note-images img {
            max-width: 100px;
            margin-right: 10px;
            border-radius: 4px;
        }

        .date {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
        }

        .action-buttons {
            margin-top: 10px;
        }

        .action-buttons a, .action-buttons button {
            padding: 6px 12px;
            margin-right: 5px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
        }

        .edit-button {
            background: var(--primary-color);
            color: #fff;
        }

        .edit-button:hover {
            background: #2699c7;
        }

        .delete-button {
            background: #e53e3e;
            color: #fff;
        }

        .delete-button:hover {
            background: #c53030;
        }

        .pin-button, .lock-button {
            background: #6c757d;
            color: #fff;
        }

        .pin-button:hover, .lock-button:hover {
            background: #5a6268;
        }

        .no-notes {
            text-align: center;
            padding: 20px;
            background: var(--card-bg);
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin: 20px;
        }

        .pinned-grid, .note-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .pinned-card, .note-card {
            background: var(--card-bg);
            padding: 15px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .pinned-card h3, .note-card h3 {
            margin: 0 0 10px;
            font-size: 16px;
            font-weight: 600;
            color: var(--text-color);
        }

        .pinned-card.password-protected h3, .note-card.password-protected h3 {
            color: #666;
        }

        .pinned-card p, .note-card p {
            margin: 0 0 10px;
            color: var(--text-color);
        }

        /* Theme Toggle */
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .theme-toggle:hover {
            background: #444; /* Tăng tương phản khi hover trong chế độ tối */
        }

        .theme-toggle input {
            display: none;
        }

        .theme-label {
            font-size: 14px;
            color: var(--text-color);
            user-select: none; /* Prevent text selection */
        }

        .switch-container {
            display: inline-block;
        }

        .theme-toggle .switch {
            position: relative;
            width: 40px;
            height: 20px;
            background: #ccc;
            border-radius: 10px;
            transition: background 0.3s;
            cursor: pointer;
        }

        .theme-toggle .switch::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            background: #fff;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: transform 0.3s;
        }

        .theme-toggle input:checked + .switch-container .switch {
            background: var(--primary-color);
        }

        .theme-toggle input:checked + .switch-container .switch::after {
            transform: translateX(20px);
        }
        /* Checkbox Sections */
        .labels-section, .checkbox-section, .password-section {
            margin-bottom: 15px;
        }

        .labels-section p, .password-section p {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-color);
            margin: 0 0 8px;
        }

        .labels-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .label-item, .checkbox-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .label-item input[type="checkbox"], .checkbox-item input[type="checkbox"] {
            display: none;
        }

        .checkbox-custom {
            width: 16px;
            height: 16px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--card-bg);
            position: relative;
            cursor: pointer;
        }

        .checkbox-custom::after {
            content: '';
            position: absolute;
            width: 8px;
            height: 4px;
            border-left: 2px solid transparent;
            border-bottom: 2px solid transparent;
            top: 4px;
            left: 3px;
            transform: rotate(-45deg);
            transition: border-color 0.2s;
        }

        input[type="checkbox"]:checked + .checkbox-custom::after {
            border-left-color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .label-item label, .checkbox-item label {
            font-size: 14px;
            color: var(--text-color);
            cursor: pointer;
            user-select: none;
        }

        .note-display-container {
        margin: 20px;
        padding-bottom: 20px;
        max-height: calc(100vh - 400px); /* Adjust based on form height */
        overflow-y: auto;
}
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar for Label Management -->
        <div class="sidebar">
            <div class="label-form">
                <h2>Add Label</h2>
                <form method="POST" action="">
                    <input type="text" name="label_name" placeholder="Label Name" value="">
                    <button type="submit" name="create_label">Add</button>
                </form>
            </div>
            <h3>Labels</h3>
            <ul class="label-list">
                <li class="label-item <?php echo $label_filter == 0 ? 'active' : ''; ?>">
                    <a href="?view=<?php echo htmlspecialchars($view); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">All Notes</a>
                </li>
                <?php foreach ($labels as $label): ?>
                    <li class="label-item <?php echo $label_filter == $label['id'] ? 'active' : ''; ?>">
                        <a href="?view=<?php echo htmlspecialchars($view); ?>&label=<?php echo $label['id'] . ($search ? '&search=' . urlencode($search) : ''); ?>">
                            <?php echo htmlspecialchars($label['name']); ?> (<?php echo $label_counts[$label['id']] ?? 0; ?>)
                        </a>
                        <div class="actions">
                            <button type="button" class="edit-label" onclick="editLabel(<?php echo $label['id']; ?>, '<?php echo htmlspecialchars($label['name'], ENT_QUOTES); ?>')">Edit</button>
                            <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this label?');">
                                <input type="hidden" name="label_id" value="<?php echo $label['id']; ?>">
                                <button type="submit" name="delete_label" class="delete-label">Delete</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="main-content">
    <!-- Header with Background Image -->
    <div class="header">
        <div class="header-overlay"></div>
        <h1>Home</h1>
    </div>
    <!-- Search Form -->
    <div class="search-form">
        <form method="GET" action="">
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
            <?php if ($label_filter): ?>
                <input type="hidden" name="label" value="<?php echo $label_filter; ?>">
            <?php endif; ?>
            <input type="text" name="search" placeholder="Search notes..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Search</button>
            <?php if ($search): ?>
                <a href="?view=<?php echo htmlspecialchars($view); ?><?php echo $label_filter ? '&label=' . $label_filter : ''; ?>" class="edit-button" style="background-color: #6c757d; padding: 8px 16px; display: inline-block;">Clear Search</a>
            <?php endif; ?>
        </form>
    </div>
    <!-- Password Verification Form -->
    <?php if ($password_required): ?>
        <div class="password-form">
            <h2>Enter Password</h2>
            <?php if ($password_error): ?>
                <div class="error"><?php echo htmlspecialchars($password_error); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="hidden" name="note_id" value="<?php echo $password_note_id; ?>">
                <input type="hidden" name="password_action" value="<?php echo htmlspecialchars($password_action); ?>">
                <input type="password" name="password" placeholder="Enter password" required>
                <button type="submit" name="verify_password">Verify</button>
                <a href="?view=<?php echo htmlspecialchars($view); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $label_filter ? '&label=' . $label_filter : ''; ?>" class="edit-button" style="background-color: #6c757d;">Cancel</a>
            </form>
        </div>
    <?php endif; ?>
    <!-- Note Creation/Update Form -->
    <div class="note-form-container">
        <div class="note-form">
            <h2><?php echo $edit_note ? 'Edit Note' : 'Create a New Note'; ?></h2>
            <?php if (isset($_GET['success'])): ?>
                <div class="success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            <form method="POST" action="" id="note-form" enctype="multipart/form-data">
                <input type="hidden" name="id" id="note-id" value="<?php echo htmlspecialchars($edit_note ? $edit_note['id'] : ''); ?>">
                <input type="hidden" name="removed_images" id="removed-images" value='[]'>
                <input type="text" name="title" id="note-title" placeholder="Note Title" value="<?php echo htmlspecialchars($edit_note ? $edit_note['title'] : ($_POST['title'] ?? '')); ?>">
                <textarea name="content" id="note-content" placeholder="Note Content"><?php echo htmlspecialchars($edit_note ? $edit_note['content'] : ($_POST['content'] ?? '')); ?></textarea>
                
                <!-- Labels Section -->
                <div class="labels-section">
                    <p>Labels:</p>
                    <?php if (empty($labels)): ?>
                        <p>No labels available. Add some labels in the sidebar.</p>
                    <?php else: ?>
                        <div class="labels-list">
                            <?php foreach ($labels as $label): ?>
                                <div class="label-item">
                                    <input type="checkbox" name="labels[]" id="label-<?php echo $label['id']; ?>" value="<?php echo $label['id']; ?>" <?php echo in_array($label['id'], $edit_note_labels) ? 'checked' : ''; ?>>
                                    <span class="checkbox-custom"></span>
                                    <label for="label-<?php echo $label['id']; ?>"><?php echo htmlspecialchars($label['name']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pin to Top Section -->
                <div class="checkbox-section">
                    <div class="checkbox-item">
                        <input type="checkbox" name="pinned" id="note-pinned" value="1" <?php echo $edit_note && $edit_note['pinned'] ? 'checked' : ''; ?>>
                        <span class="checkbox-custom"></span>
                        <label for="note-pinned">Pin to Top</label>
                    </div>
                </div>
                
                <!-- Password Protection Section -->
                <div class="password-section">
                    <p>Password Protection:</p>
                    <?php if ($edit_note && !empty($edit_note['password_hash'])): ?>
                        <div class="checkbox-item">
                            <input type="checkbox" name="disable_password" id="disable-password" value="1">
                            <span class="checkbox-custom"></span>
                            <label for="disable-password">Disable Password</label>
                        </div>
                        <input type="password" name="current_password" id="current-password" placeholder="Enter current password to disable">
                    <?php else: ?>
                        <div class="checkbox-item">
                            <input type="checkbox" name="enable_password" id="enable-password" value="1">
                            <span class="checkbox-custom"></span>
                            <label for="enable-password">Enable Password</label>
                        </div>
                        <input type="password" name="note_password" id="note-password" placeholder="Set a password">
                    <?php endif; ?>
                </div>
                
                <input type="file" name="images[]" id="note-images" accept="image/jpeg,image/png" multiple>
                <?php if ($edit_note && !empty($edit_note['image'])): ?>
                    <div class="image-container" id="image-container">
                        <p>Current Images:</p>
                        <?php foreach ($edit_note['image'] as $image): ?>
                            <div class="image-wrapper" data-image-path="<?php echo htmlspecialchars($image); ?>">
                                <img src="<?php echo htmlspecialchars($image); ?>" alt="Current Note Image">
                                <button type="button" class="remove-button">X</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <button type="submit" name="<?php echo $edit_note ? 'update_note' : 'create_note'; ?>">
                    <?php echo $edit_note ? 'Update Note' : 'Create Note'; ?>
                </button>
                <span class="save-status" id="save-status"></span>
                <?php if ($edit_note): ?>
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?view=' . urlencode($view) . ($search ? '&search=' . urlencode($search) : '') . ($label_filter ? '&label=' . $label_filter : '')); ?>" class="edit-button" style="background-color: #6c757d;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <!-- Note Display Section -->
    <div class="note-display-container">
        <!-- View Toggle -->
        <div class="view-toggle">
            <a href="?view=list<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $label_filter ? '&label=' . $label_filter : ''; ?>" class="<?php echo $view === 'list' ? 'active' : ''; ?>">List View</a>
            <a href="?view=grid<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $label_filter ? '&label=' . $label_filter : ''; ?>" class="<?php echo $view === 'grid' ? 'active' : ''; ?>">Grid View</a>
            <?php if (isset($_SESSION['verified_notes']) && !empty($_SESSION['verified_notes'])): ?>
                <a href="?view=<?php echo htmlspecialchars($view); ?>&clear_password_verification=1<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $label_filter ? '&label=' . $label_filter : ''; ?>" class="lock-button">Clear Password Verifications</a>
            <?php endif; ?>
        </div>
        <?php
        $pinned_notes = array_filter($notes, fn($note) => $note['pinned']);
        $unpinned_notes = array_filter($notes, fn($note) => !$note['pinned']);
        ?>
        <?php if ($view === 'list'): ?>
            <?php if (!empty($pinned_notes)): ?>
                <ul class="pinned-list">
                    <h3>Pinned Notes</h3>
                    <?php foreach ($pinned_notes as $note): ?>
                        <li class="pinned-item <?php echo !empty($note['password_hash']) ? 'password-protected' : ''; ?>">
                            <?php if ($note['is_verified']): ?>
                                <h3><?php echo htmlspecialchars($note['title']); ?></h3>
                                <p><?php echo htmlspecialchars($note['content']); ?></p>
                                <?php if (!empty($note['labels'])): ?>
                                    <div class="note-labels">
                                        <?php foreach ($note['labels'] as $label): ?>
                                            <span><?php echo htmlspecialchars($label['name']); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($note['image'])): ?>
                                    <div class="note-images">
                                        <?php foreach ($note['image'] as $image): ?>
                                            <img src="<?php echo htmlspecialchars($image); ?>" alt="Note Image">
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="date">Created: <?php echo date('F j, Y, g:i a', strtotime($note['created_at'])); ?></div>
                                <div class="action-buttons">
                                    <a href="?edit=<?php echo $note['id']; ?>&view=<?php echo $view . ($search ? '&search=' . urlencode($search) : '') . ($label_filter ? '&label=' . $label_filter : ''); ?>" class="edit-button">Edit</a>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this note?');">
                                        <input type="hidden" name="delete_id" value="<?php echo $note['id']; ?>">
                                        <button type="submit" name="delete_note" class="delete-button">Delete</button>
                                    </form>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $note['id']; ?>">
                                        <input type="hidden" name="pinned" value="0">
                                        <button type="submit" name="toggle_pin" class="pin-button">Unpin</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <h3 class="password-protected"><?php echo htmlspecialchars($note['title']); ?> (Locked)</h3>
                                <p><em>This note is password-protected.</em></p>
                                <div class="action-buttons">
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                                        <input type="hidden" name="password_action" value="view">
                                        <button type="submit" name="verify_password" class="lock-button">Unlock</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <ul class="note-list">
                <?php if (!empty($unpinned_notes)): ?>
                    <?php foreach ($unpinned_notes as $note): ?>
                        <li class="note-item <?php echo !empty($note['password_hash']) ? 'password-protected' : ''; ?>">
                            <?php if ($note['is_verified']): ?>
                                <h3><?php echo htmlspecialchars($note['title']); ?></h3>
                                <p><?php echo htmlspecialchars($note['content']); ?></p>
                                <?php if (!empty($note['labels'])): ?>
                                    <div class="note-labels">
                                        <?php foreach ($note['labels'] as $label): ?>
                                            <span><?php echo htmlspecialchars($label['name']); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($note['image'])): ?>
                                    <div class="note-images">
                                        <?php foreach ($note['image'] as $image): ?>
                                            <img src="<?php echo htmlspecialchars($image); ?>" alt="Note Image">
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="date">Created: <?php echo date('F j, Y, g:i a', strtotime($note['created_at'])); ?></div>
                                <div class="action-buttons">
                                    <a href="?edit=<?php echo $note['id']; ?>&view=<?php echo $view . ($search ? '&search=' . urlencode($search) : '') . ($label_filter ? '&label=' . $label_filter : ''); ?>" class="edit-button">Edit</a>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this note?');">
                                        <input type="hidden" name="delete_id" value="<?php echo $note['id']; ?>">
                                        <button type="submit" name="delete_note" class="delete-button">Delete</button>
                                    </form>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $note['id']; ?>">
                                        <input type="hidden" name="pinned" value="1">
                                        <button type="submit" name="toggle_pin" class="pin-button">Pin</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <h3 class="password-protected"><?php echo htmlspecialchars($note['title']); ?> (Locked)</h3>
                                <p><em>This note is password-protected.</em></p>
                                <div class="action-buttons">
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                                        <input type="hidden" name="password_action" value="view">
                                        <button type="submit" name="verify_password" class="lock-button">Unlock</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="no-notes list">
                        <p><?php echo $search || $label_filter ? 'No matching notes found.' : 'No notes found.'; ?></p>
                    </li>
                <?php endif; ?>
            </ul>
        <?php else: ?>
            <?php if (!empty($pinned_notes)): ?>
                <div class="pinned-grid">
                    <h3>Pinned Notes</h3>
                    <?php foreach ($pinned_notes as $note): ?>
                        <div class="pinned-card <?php echo !empty($note['password_hash']) ? 'password-protected' : ''; ?>">
                            <?php if ($note['is_verified']): ?>
                                <h3><?php echo htmlspecialchars($note['title']); ?></h3>
                                <p><?php echo htmlspecialchars($note['content']); ?></p>
                                <?php if (!empty($note['labels'])): ?>
                                    <div class="note-labels">
                                        <?php foreach ($note['labels'] as $label): ?>
                                            <span><?php echo htmlspecialchars($label['name']); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($note['image'])): ?>
                                    <div class="note-images">
                                        <?php foreach ($note['image'] as $image): ?>
                                            <img src="<?php echo htmlspecialchars($image); ?>" alt="Note Image">
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="date">Created: <?php echo date('F j, Y, g:i a', strtotime($note['created_at'])); ?></div>
                                <div class="action-buttons">
                                    <a href="?edit=<?php echo $note['id']; ?>&view=<?php echo $view . ($search ? '&search=' . urlencode($search) : '') . ($label_filter ? '&label=' . $label_filter : ''); ?>" class="edit-button">Edit</a>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this note?');">
                                        <input type="hidden" name="delete_id" value="<?php echo $note['id']; ?>">
                                        <button type="submit" name="delete_note" class="delete-button">Delete</button>
                                    </form>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $note['id']; ?>">
                                        <input type="hidden" name="pinned" value="0">
                                        <button type="submit" name="toggle_pin" class="pin-button">Unpin</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <h3 class="password-protected"><?php echo htmlspecialchars($note['title']); ?> (Locked)</h3>
                                <p><em>This note is password-protected.</em></p>
                                <div class="action-buttons">
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                                        <input type="hidden" name="password_action" value="view">
                                        <button type="submit" name="verify_password" class="lock-button">Unlock</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="note-grid">
                <?php if (!empty($unpinned_notes)): ?>
                    <?php foreach ($unpinned_notes as $note): ?>
                        <div class="note-card <?php echo !empty($note['password_hash']) ? 'password-protected' : ''; ?>">
                            <?php if ($note['is_verified']): ?>
                                <h3><?php echo htmlspecialchars($note['title']); ?></h3>
                                <p><?php echo htmlspecialchars($note['content']); ?></p>
                                <?php if (!empty($note['labels'])): ?>
                                    <div class="note-labels">
                                        <?php foreach ($note['labels'] as $label): ?>
                                            <span><?php echo htmlspecialchars($label['name']); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($note['image'])): ?>
                                    <div class="note-images">
                                        <?php foreach ($note['image'] as $image): ?>
                                            <img src="<?php echo htmlspecialchars($image); ?>" alt="Note Image">
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="date">Created: <?php echo date('F j, Y, g:i a', strtotime($note['created_at'])); ?></div>
                                <div class="action-buttons">
                                    <a href="?edit=<?php echo $note['id']; ?>&view=<?php echo $view . ($search ? '&search=' . urlencode($search) : '') . ($label_filter ? '&label=' . $label_filter : ''); ?>" class="edit-button">Edit</a>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this note?');">
                                        <input type="hidden" name="delete_id" value="<?php echo $note['id']; ?>">
                                        <button type="submit" name="delete_note" class="delete-button">Delete</button>
                                    </form>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $note['id']; ?>">
                                        <input type="hidden" name="pinned" value="1">
                                        <button type="submit" name="toggle_pin" class="pin-button">Pin</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <h3 class="password-protected"><?php echo htmlspecialchars($note['title']); ?> (Locked)</h3>
                                <p><em>This note is password-protected.</em></p>
                                <div class="action-buttons">
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                                        <input type="hidden" name="password_action" value="view">
                                        <button type="submit" name="verify_password" class="lock-button">Unlock</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-notes grid">
                        <p><?php echo $search || $label_filter ? 'No matching notes found.' : 'No notes found.'; ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
    <!-- Theme Toggle -->
    <div class="theme-toggle">
        <input type="checkbox" id="theme-switch" <?php echo isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'checked' : ''; ?>>
        <div class="switch-container">
            <div class="switch"></div>
        </div>
        <span class="theme-label">Dark Mode</span>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('note-form');
            const titleInput = document.getElementById('note-title');
            const contentInput = document.getElementById('note-content');
            const imageInput = document.getElementById('note-images');
            const noteIdInput = document.getElementById('note-id');
            const removedImagesInput = document.getElementById('removed-images');
            const imageContainer = document.getElementById('image-container');
            const saveStatus = document.getElementById('save-status');
            let typingTimer;
            let isSubmitting = false;
            let removedImages = JSON.parse(removedImagesInput.value || '[]');

            const removeButtons = document.querySelectorAll('.remove-button');
            removeButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const wrapper = button.parentElement;
                    const imagePath = wrapper.getAttribute('data-image-path');
                    wrapper.style.display = 'none';
                    if (!removedImages.includes(imagePath)) {
                        removedImages.push(imagePath);
                        removedImagesInput.value = JSON.stringify(removedImages);
                    }
                });
            });

            const autoSave = () => {
                if (isSubmitting) return;

                const title = titleInput.value.trim();
                const content = contentInput.value.trim();
                const noteId = noteIdInput.value;
                const pinned = document.getElementById('note-pinned').checked ? 1 : 0;
                const labels = Array.from(document.querySelectorAll('input[name="labels[]"]:checked')).map(input => input.value);

                if (!title || !content) {
                    saveStatus.textContent = '';
                    return;
                }

                saveStatus.textContent = 'Saving...';

                const formData = new FormData();
                formData.append('id', noteId);
                formData.append('title', title);
                formData.append('content', content);
                formData.append('pinned', pinned);
                labels.forEach(label => formData.append('labels[]', label));
                if (imageInput.files.length > 0) {
                    for (let file of imageInput.files) {
                        formData.append('images[]', file);
                    }
                }

                fetch('save_note.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        saveStatus.textContent = 'Saved';
                        if (!noteId && data.id) {
                            noteIdInput.value = data.id;
                            window.location.href = `?view=${encodeURIComponent($view)}${data.search ? '&search=' + encodeURIComponent(data.search) : ''}${data.label_filter ? '&label=' + data.label_filter : ''}`;
                        }
                        setTimeout(() => {
                            saveStatus.textContent = '';
                        }, 2000);
                    } else {
                        saveStatus.textContent = 'Error saving: ' + (data && data.message ? data.message : 'Unknown error');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    saveStatus.textContent = 'Error saving: Network or server issue';
                });
            };

            const debounceAutoSave = () => {
                if (isSubmitting) return;
                clearTimeout(typingTimer);
                typingTimer = setTimeout(autoSave, 2000);
            };

            titleInput.addEventListener('input', debounceAutoSave);
            contentInput.addEventListener('input', debounceAutoSave);
            imageInput.addEventListener('change', autoSave);
            document.querySelectorAll('input[name="labels[]"]').forEach(checkbox => {
                checkbox.addEventListener('change', autoSave);
            });

            form.addEventListener('submit', (event) => {
                isSubmitting = true;
                console.log('Form submitted manually');
                console.log('Final removed images on submit:', removedImagesInput.value);
            });

            window.editLabel = (id, name) => {
                const newName = prompt("Enter new label name:", name);
                if (newName && newName.trim() !== '') {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';
                    form.style.display = 'none';

                    const labelIdInput = document.createElement('input');
                    labelIdInput.type = 'hidden';
                    labelIdInput.name = 'label_id';
                    labelIdInput.value = id;
                    form.appendChild(labelIdInput);

                    const labelNameInput = document.createElement('input');
                    labelNameInput.type = 'hidden';
                    labelNameInput.name = 'label_name';
                    labelNameInput.value = newName.trim();
                    form.appendChild(labelNameInput);

                    const updateInput = document.createElement('input');
                    updateInput.type = 'hidden';
                    updateInput.name = 'update_label';
                    updateInput.value = '1';
                    form.appendChild(updateInput);

                    document.body.appendChild(form);
                    form.submit();
                }
            };

           // Theme Toggle Logic
            const themeSwitch = document.getElementById('theme-switch');
            const switchElement = document.querySelector('.switch');
            const currentTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

            // Set initial theme and checkbox state
            if (currentTheme === 'dark') {
                document.body.setAttribute('data-theme', 'dark');
                themeSwitch.checked = true;
            }

            // Handle checkbox change (triggered by clicking the switch)
            themeSwitch.addEventListener('change', () => {
                if (themeSwitch.checked) {
                    document.body.setAttribute('data-theme', 'dark');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.body.removeAttribute('data-theme');
                    localStorage.setItem('theme', 'light');
                }
            });

            // Handle clicking the switch div to toggle the checkbox
            switchElement.addEventListener('click', (event) => {
                event.stopPropagation(); // Prevent click from bubbling to parent elements
                themeSwitch.checked = !themeSwitch.checked; // Toggle checkbox state
                const changeEvent = new Event('change', { bubbles: true });
                themeSwitch.dispatchEvent(changeEvent); // Trigger the change event
            });
        });

        document.querySelectorAll('.checkbox-custom').forEach(span => {
        span.addEventListener('click', (event) => {
        event.preventDefault();
        const checkbox = span.previousElementSibling;
        if (checkbox && checkbox.type === 'checkbox') {
            checkbox.checked = !checkbox.checked;
            const changeEvent = new Event('change', { bubbles: true });
            checkbox.dispatchEvent(changeEvent);
                }
            });
        });
    </script>
</body>
</html>