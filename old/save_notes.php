<?php
header('Content-Type: application/json');
require 'db_connection.php';

// Debug: Ghi log dữ liệu nhận được
error_log("Received data in save_note.php: " . print_r($_POST, true));
error_log("Received files in save_note.php: " . print_r($_FILES, true));

$response = ['success' => false, 'message' => 'Unknown error'];

try {
    $id = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $pinned = isset($_POST['pinned']) && $_POST['pinned'] == '1' ? 1 : 0;
    $labels = isset($_POST['labels']) && is_array($_POST['labels']) ? $_POST['labels'] : [];
    $images = isset($_FILES['images']) ? $_FILES['images'] : [];
    $image_paths = [];

    // Debug: Ghi log giá trị sau khi trim
    error_log("Title in save_note.php: '$title'");
    error_log("Content in save_note.php: '$content'");

    // Kiểm tra các trường bắt buộc
    if (empty($title) || empty($content)) {
        $response['message'] = 'Title and content are required.';
        echo json_encode($response);
        exit;
    }

    // Xử lý hình ảnh nếu có
    if (!empty($images['name'][0])) {
        $allowed_types = ['image/jpeg', 'image/png'];
        $max_size = 5 * 1024 * 1024;
        for ($i = 0; $i < count($images['name']); $i++) {
            if ($images['error'][$i] === UPLOAD_ERR_OK) {
                if (!in_array($images['type'][$i], $allowed_types)) {
                    $response['message'] = 'Only JPEG and PNG images are allowed.';
                    echo json_encode($response);
                    exit;
                }
                if ($images['size'][$i] > $max_size) {
                    $response['message'] = 'Image size must be less than 5MB.';
                    echo json_encode($response);
                    exit;
                }
                $ext = pathinfo($images['name'][$i], PATHINFO_EXTENSION);
                $image_path = 'uploads/' . uniqid() . '.' . $ext;
                if (!move_uploaded_file($images['tmp_name'][$i], $image_path)) {
                    $response['message'] = 'Failed to upload image ' . ($i + 1) . '.';
                    echo json_encode($response);
                    exit;
                }
                $image_paths[] = $image_path;
            } elseif ($images['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                $response['message'] = 'Error uploading image ' . ($i + 1) . '.';
                echo json_encode($response);
                exit;
            }
        }
    }

    if ($id > 0) {
        // Cập nhật ghi chú hiện có
        $stmt = $pdo->prepare("SELECT image FROM notes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);
        $existing_images = $note && $note['image'] ? json_decode($note['image'], true) : [];
        $image_paths = array_merge($existing_images, $image_paths);
        $image_json = !empty($image_paths) ? json_encode($image_paths) : null;

        $stmt = $pdo->prepare("UPDATE notes SET title = :title, content = :content, image = :image, pinned = :pinned WHERE id = :id");
        $stmt->execute(['title' => $title, 'content' => $content, 'image' => $image_json, 'pinned' => $pinned, 'id' => $id]);
    } else {
        // Tạo ghi chú mới
        $image_json = !empty($image_paths) ? json_encode($image_paths) : null;
        $stmt = $pdo->prepare("INSERT INTO notes (title, content, image, pinned) VALUES (:title, :content, :image, :pinned)");
        $stmt->execute(['title' => $title, 'content' => $content, 'image' => $image_json, 'pinned' => $pinned]);
        $id = $pdo->lastInsertId();
    }

    // Cập nhật nhãn
    $stmt = $pdo->prepare("DELETE FROM note_labels WHERE note_id = :note_id");
    $stmt->execute(['note_id' => $id]);
    if (!empty($labels)) {
        $stmt = $pdo->prepare("INSERT INTO note_labels (note_id, label_id) VALUES (:note_id, :label_id)");
        foreach ($labels as $label_id) {
            $stmt->execute(['note_id' => $id, 'label_id' => $label_id]);
        }
    }

    $response['success'] = true;
    $response['id'] = $id;
    $response['message'] = 'Note saved successfully.';
} catch (Exception $e) {
    $response['message'] = 'Error saving note: ' . $e->getMessage();
}

echo json_encode($response);
?>