<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "note_management";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}

// Handle note creation
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_note'])) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($title)) {
        $errors[] = "Title is required.";
    }
    if (empty($content)) {
        $errors[] = "Content is required.";
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("INSERT INTO notes (title, content) VALUES (:title, :content)");
            $stmt->execute(['title' => $title, 'content' => $content]);
            $success = "Note created successfully!";
        } catch(PDOException $e) {
            $errors[] = "Failed to create note: " . $e->getMessage();
        }
    }
}

// Handle note update
$edit_note = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_note'])) {
    $id = $_POST['id'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($title)) {
        $errors[] = "Title is required.";
    }
    if (empty($content)) {
        $errors[] = "Content is required.";
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("UPDATE notes SET title = :title, content = :content WHERE id = :id");
            $stmt->execute(['title' => $title, 'content' => $content, 'id' => $id]);
            $success = "Note updated successfully!";
        } catch(PDOException $e) {
            $errors[] = "Failed to update note: " . $e->getMessage();
        }
    }
}

// Handle note deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_note'])) {
    $id = $_POST['delete_id'] ?? '';
    try {
        $stmt = $conn->prepare("DELETE FROM notes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $success = "Note deleted successfully!";
    } catch(PDOException $e) {
        $errors[] = "Failed to delete note: " . $e->getMessage();
    }
}

// Load note for editing
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT id, title, content FROM notes WHERE id = :id");
    $stmt->execute(['id' => $_GET['edit']]);
    $edit_note = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all notes
$stmt = $conn->prepare("SELECT id, title, content, created_at FROM notes ORDER BY created_at DESC");
$stmt->execute();
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Determine view mode (default to list)
$view = isset($_GET['view']) && in_array($_GET['view'], ['list', 'grid']) ? $_GET['view'] : 'list';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Note Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .view-toggle {
            text-align: center;
            margin-bottom: 20px;
        }
        .view-toggle a {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 5px;
            text-decoration: none;
            color: #fff;
            background-color: #007bff;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .view-toggle a:hover {
            background-color: #0056b3;
        }
        .view-toggle a.active {
            background-color: #28a745;
        }
        /* Note Form (Create/Update) */
        .note-form {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .note-form h2 {
            margin: 0 0 15px;
            color: #333;
        }
        .note-form input, .note-form textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .note-form textarea {
            height: 100px;
            resize: vertical;
        }
        .note-form button {
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .note-form button:hover {
            background-color: #0056b3;
        }
        .success, .error {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        /* List View Styles */
        .note-list {
            list-style: none;
            padding: 0;
        }
        .note-item {
            background: #fff;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        /* Grid View Styles */
        .note-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 0;
        }
        .note-card {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        /* Shared Styles for Notes */
        .note-item h3, .note-card h3 {
            margin: 0 0 10px;
            color: #333;
        }
        .note-item p, .note-card p {
            margin: 0 0 10px;
            color: #666;
        }
        .note-item .date, .note-card .date {
            font-size: 0.9em;
            color: #999;
        }
        .no-notes {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .no-notes.list {
            margin-bottom: 10px;
        }
        .no-notes.grid {
            grid-column: 1 / -1;
        }
        /* Action Buttons */
        .action-buttons {
            margin-top: 10px;
        }
        .edit-button, .delete-button {
            display: inline-block;
            padding: 5px 10px;
            text-decoration: none;
            color: #fff;
            border-radius: 4px;
            font-size: 0.9em;
            margin-right: 5px;
            transition: background-color 0.3s;
        }
        .edit-button {
            background-color: #ffc107;
        }
        .edit-button:hover {
            background-color: #e0a800;
        }
        .delete-button {
            background-color: #dc3545;
        }
        .delete-button:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>My Notes</h1>
        <!-- Note Creation/Update Form -->
        <div class="note-form">
            <h2><?php echo $edit_note ? 'Edit Note' : 'Create a New Note'; ?></h2>
            <?php if ($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            <form method="POST" action="">
                <?php if ($edit_note): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_note['id']); ?>">
                <?php endif; ?>
                <input type="text" name="title" placeholder="Note Title" value="<?php echo htmlspecialchars($edit_note ? $edit_note['title'] : ($_POST['title'] ?? '')); ?>">
                <textarea name="content" placeholder="Note Content"><?php echo htmlspecialchars($edit_note ? $edit_note['content'] : ($_POST['content'] ?? '')); ?></textarea>
                <button type="submit" name="<?php echo $edit_note ? 'update_note' : 'create_note'; ?>">
                    <?php echo $edit_note ? 'Update Note' : 'Create Note'; ?>
                </button>
                <?php if ($edit_note): ?>
                    <a href="notes.php?view=<?php echo $view; ?>" class="edit-button" style="background-color: #6c757d;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
        <!-- View Toggle -->
        <div class="view-toggle">
            <a href="?view=list" class="<?php echo $view === 'list' ? 'active' : ''; ?>">List View</a>
            <a href="?view=grid" class="<?php echo $view === 'grid' ? 'active' : ''; ?>">Grid View</a>
        </div>
        <!-- Notes Display -->
        <?php if ($view === 'list'): ?>
            <ul class="note-list">
                <?php if (count($notes) > 0): ?>
                    <?php foreach ($notes as $note): ?>
                        <li class="note-item">
                            <h3><?php echo htmlspecialchars($note['title']); ?></h3>
                            <p><?php echo htmlspecialchars($note['content']); ?></p>
                            <div class="date">Created: <?php echo date('F j, Y, g:i a', strtotime($note['created_at'])); ?></div>
                            <div class="action-buttons">
                                <a href="?edit=<?php echo $note['id']; ?>&view=<?php echo $view; ?>" class="edit-button">Edit</a>
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this note?');">
                                    <input type="hidden" name="delete_id" value="<?php echo $note['id']; ?>">
                                    <button type="submit" name="delete_note" class="delete-button">Delete</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="no-notes list">
                        <p>No notes found.</p>
                    </li>
                <?php endif; ?>
            </ul>
        <?php else: ?>
            <div class="note-grid">
                <?php if (count($notes) > 0): ?>
                    <?php foreach ($notes as $note): ?>
                        <div class="note-card">
                            <h3><?php echo htmlspecialchars($note['title']); ?></h3>
                            <p><?php echo htmlspecialchars($note['content']); ?></p>
                            <div class="date">Created: <?php echo date('F j, Y, g:i a', strtotime($note['created_at'])); ?></div>
                            <div class="action-buttons">
                                <a href="?edit=<?php echo $note['id']; ?>&view=<?php echo $view; ?>" class="edit-button">Edit</a>
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this note?');">
                                    <input type="hidden" name="delete_id" value="<?php echo $note['id']; ?>">
                                    <button type="submit" name="delete_note" class="delete-button">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-notes grid">
                        <p>No notes found.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>