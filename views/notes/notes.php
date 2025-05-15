<?php
$pinned_notes = array_filter($notes, fn($note) => $note['pinned']);
$unpinned_notes = array_filter($notes, fn($note) => !$note['pinned']);
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
            color: var(--sidebar-text, #666);
            margin: 10px 0;
        }

        .sidebar .label-item {
            display: inline-flex;
            align-items: center;
            padding: 8px 10px;
            border-radius: 4px;
            font-size: 14px;
            color: var(--sidebar-text, var(--text-color));
            background: transparent;
            transition: background 0s;
            justify-content: center;
        }

        .sidebar .label-item.active {
            background: #f0f0f0;
            font-weight: 500;
        }

        [data-theme="dark"] .sidebar .label-item.active {
            background: #444;
        }

        .sidebar .label-item:hover {
            background: #f0f0f0;
        }

        [data-theme="dark"] .sidebar .label-item:hover {
            background: #444;
        }

        .sidebar .label-item a {
            flex: 1;
            color: var(--sidebar-text, var(--text-color));
            text-decoration: none;
        }

        /* Main Content Styling */
        .main-content {
            flex: 1;
            overflow-y: auto;
            padding: 0;
            display: block;
        }

        .note-form-container {
            margin: 20px;
        }

        .note-display-container {
            margin: 20px;
            padding-bottom: 20px;
        }

        .header, .search-form, .password-form, .note-form-container, .note-display-container {
            width: 100%;
        }

        .main-content {
            min-height: calc(100vh - 60px);
            overflow-y: auto;
        }

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
            background: #444;
        }

        .theme-toggle input {
            display: none;
        }

        .theme-label {
            font-size: 14px;
            color: var(--text-color);
            user-select: none;
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
            max-height: calc(100vh - 400px);
            overflow-y: auto;
        }

        /* Label Edit Form */
        .label-edit-form {
            display: none;
            margin-top: 10px;
        }

        .label-edit-form input {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
            background: var(--card-bg);
            color: var(--text-color);
        }

        .label-edit-form button {
            background: var(--primary-color);
            color: #fff;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }

        .label-edit-form button:hover {
            background: #2699c7;
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
                    <a href="index.php?page=notes&view=<?php echo htmlspecialchars($view); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">All Notes</a>
                </li>
                <?php foreach ($labels as $label): ?>
                    <li class="label-item <?php echo $label_filter == $label['id'] ? 'active' : ''; ?>">
                        <a href="index.php?page=notes&view=<?php echo htmlspecialchars($view); ?>&label=<?php echo $label['id'] . ($search ? '&search=' . urlencode($search) : ''); ?>">
                            <?php echo htmlspecialchars($label['name']); ?> (<?php echo $label_counts[$label['id']] ?? 0; ?>)
                        </a>
                        <div class="actions">
                            <button type="button" class="edit-label" onclick="editLabel(<?php echo $label['id']; ?>, '<?php echo htmlspecialchars($label['name'], ENT_QUOTES); ?>')">Edit</button>
                            <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this label?');">
                                <input type="hidden" name="label_id" value="<?php echo $label['id']; ?>">
                                <button type="submit" name="delete_label" class="delete-label">Delete</button>
                            </form>
                        </div>
                        <form method="POST" action="" class="label-edit-form" id="edit-label-form-<?php echo $label['id']; ?>">
                            <input type="hidden" name="label_id" value="<?php echo $label['id']; ?>">
                            <input type="text" name="label_name" value="<?php echo htmlspecialchars($label['name']); ?>">
                            <button type="submit" name="update_label">Save</button>
                        </form>
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
                <form method="GET" action="index.php">
                    <input type="hidden" name="page" value="notes">
                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                    <?php if ($label_filter): ?>
                        <input type="hidden" name="label" value="<?php echo $label_filter; ?>">
                    <?php endif; ?>
                    <input type="text" name="search" placeholder="Search notes..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Search</button>
                    <?php if ($search): ?>
                        <a href="index.php?page=notes&view=<?php echo htmlspecialchars($view); ?><?php echo $label_filter ? '&label=' . $label_filter : ''; ?>" class="edit-button" style="background-color: #6c757d; padding: 8px 16px; display: inline-block;">Clear Search</a>
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
                        <a href="index.php?page=notes&view=<?php echo htmlspecialchars($view); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $label_filter ? '&label=' . $label_filter : ''; ?>" class="edit-button" style="background-color: #6c757d;">Cancel</a>
                    </form>
                </div>
            <?php endif; ?>
            <!-- Note Creation/Update Form -->
            <div class="note-form-container">
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
                            <a href="index.php?page=notes&view=<?php echo htmlspecialchars($view); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $label_filter ? '&label=' . $label_filter : ''; ?>" class="edit-button" style="background-color: #6c757d;">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <!-- Note Display Section -->
            <div class="note-display-container">
                <!-- View Toggle -->
                <div class="view-toggle">
                    <a href="index.php?page=notes&view=list<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $label_filter ? '&label=' . $label_filter : ''; ?>" class="<?php echo $view === 'list' ? 'active' : ''; ?>">List View</a>
                    <a href="index.php?page=notes&view=grid<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $label_filter ? '&label=' . $label_filter : ''; ?>" class="<?php echo $view === 'grid' ? 'active' : ''; ?>">Grid View</a>
                    <?php if (isset($_SESSION['verified_notes']) && !empty($_SESSION['verified_notes'])): ?>
                        <a href="index.php?page=notes&view=<?php echo htmlspecialchars($view); ?>&clear_password_verification=1<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $label_filter ? '&label=' . $label_filter : ''; ?>" class="lock-button">Clear Password Verifications</a>
                    <?php endif; ?>
                </div>
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
                                            <a href="index.php?page=notes&edit=<?php echo $note['id']; ?>&view=<?php echo $view . ($search ? '&search=' . urlencode($search) : '') . ($label_filter ? '&label=' . $label_filter : ''); ?>" class="edit-button">Edit</a>
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
                                            <a href="index.php?page=notes&edit=<?php echo $note['id']; ?>&view=<?php echo $view . ($search ? '&search=' . urlencode($search) : '') . ($label_filter ? '&label=' . $label_filter : ''); ?>" class="edit-button">Edit</a>
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
                                            <a href="index.php?page=notes&edit=<?php echo $note['id']; ?>&view=<?php echo $view . ($search ? '&search=' . urlencode($search) : '') . ($label_filter ? '&label=' . $label_filter : ''); ?>" class="edit-button">Edit</a>
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
                                            <a href="index.php?page=notes&edit=<?php echo $note['id']; ?>&view=<?php echo $view . ($search ? '&search=' . urlencode($search) : '') . ($label_filter ? '&label=' . $label_filter : ''); ?>" class="edit-button">Edit</a>
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
            const enablePasswordCheckbox = document.getElementById('enable-password');
            const disablePasswordCheckbox = document.getElementById('disable-password');
            const notePasswordInput = document.getElementById('note-password');
            const currentPasswordInput = document.getElementById('current-password');
            let typingTimer;
            let isSubmitting = false;
            let removedImages = JSON.parse(removedImagesInput.value || '[]');

            // Handle image removal
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

            // Autosave function
            const autoSave = () => {
                if (isSubmitting) return;

                const formData = new FormData(form);
                formData.append('ajax_save', '1');

                // Only include password fields if relevant
                if (enablePasswordCheckbox && !enablePasswordCheckbox.checked) {
                    formData.delete('note_password');
                }
                if (disablePasswordCheckbox && !disablePasswordCheckbox.checked) {
                    formData.delete('current_password');
                }

                fetch('index.php?page=notes', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        saveStatus.textContent = 'Saved!';
                        saveStatus.style.color = 'var(--success-text)';
                        if (data.id && !noteIdInput.value) {
                            noteIdInput.value = data.id;
                        }
                        setTimeout(() => saveStatus.textContent = '', 2000);
                    } else {
                        saveStatus.textContent = data.message || 'Error saving note.';
                        saveStatus.style.color = 'var(--error-text)';
                    }
                })
                .catch(error => {
                    saveStatus.textContent = 'Error saving note.';
                    saveStatus.style.color = 'var(--error-text)';
                    console.error('Autosave error:', error);
                });
            };

            // Debounce autosave on input
            const handleInput = () => {
                clearTimeout(typingTimer);
                typingTimer = setTimeout(autoSave, 1000);
            };

            titleInput.addEventListener('input', handleInput);
            contentInput.addEventListener('input', handleInput);
            imageInput.addEventListener('change', handleInput);
            form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', handleInput);
            });

            // Prevent autosave during form submission
            form.addEventListener('submit', () => {
                isSubmitting = true;
                saveStatus.textContent = 'Saving...';
            });

            // Theme toggle
            const themeSwitch = document.getElementById('theme-switch');
            themeSwitch.addEventListener('change', () => {
                const theme = themeSwitch.checked ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', theme);
                document.cookie = `theme=${theme}; path=/; max-age=31536000`;
            });

            // Initialize theme
            if (themeSwitch.checked) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }

            // Label edit functionality
            window.editLabel = (id, name) => {
                const form = document.getElementById(`edit-label-form-${id}`);
                const allForms = document.querySelectorAll('.label-edit-form');
                allForms.forEach(f => f.style.display = 'none');
                form.style.display = 'block';
                form.querySelector('input[name="label_name"]').focus();
            };
        });
    </script>
</body>
</html>