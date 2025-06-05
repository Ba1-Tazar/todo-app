<?php
session_start();
require_once 'includes/db.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

$error = null;
$task = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'], $_POST['title'])) {
    // Aktualizacja zadania
    $task_id = (int)$_POST['task_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description'] ?? '');

    // Walidacja
    if (empty($title)) {
        $error = "Title cannot be empty.";
    } elseif (mb_strlen($title) > 60) {
        $error = "Title cannot exceed 60 characters";
    } elseif (mb_strlen($description) > 300) {
        $error = "Description cannot exceed 300 characters";
    } else {
        $stmt = $conn->prepare("UPDATE tasks SET title = ?, description = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssii", $title, $description, $task_id, $user_id);
        
        if ($stmt->execute()) {
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Error updating task: " . $conn->error;
        }
        $stmt->close();
    }
} elseif (isset($_GET['task_id'])) {
    // Pobierz dane zadania
    $task_id = (int)$_GET['task_id'];

    $stmt = $conn->prepare("SELECT title, description FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $task_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();
    
    if (!$task) {
        header("Location: dashboard.php");
        exit;
    }
    $stmt->close();
} else {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Edit Task</title>
    <link rel="stylesheet" href="./css/edit-task.css">
</head>
<body>
    <div id="edit-form">
        <h1>Edit Task</h1>
        
        <?php if (!empty($error)) : ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post" action="edit_task.php">
            <input type="hidden" name="task_id" value="<?= htmlspecialchars($task_id) ?>">
            
            <div class="form-group">
                <input type="text" name="title" id="edit-title" 
                       value="<?= htmlspecialchars($task['title']) ?>" 
                       maxlength="60" required
                       placeholder="Task title (max 60 chars)">
                <div class="char-counter">
                    <span id="title-char-count"><?= mb_strlen($task['title']) ?></span>/60
                </div>
            </div>
            
            <div class="form-group">
                <textarea name="description" id="edit-description"
                          maxlength="300" placeholder="Description (max 300 chars)"
                          rows="3"><?= htmlspecialchars($task['description']) ?></textarea>
                <div class="char-counter">
                    <span id="desc-char-count"><?= mb_strlen($task['description']) ?></span>/300
                </div>
            </div>
            
            <button type="submit">Save</button>
        </form>
        
        <div id="cancel-edit">
            <a href="dashboard.php">Cancel</a>
        </div>
    </div>

    <script>
        // Auto-resize textarea
        const textarea = document.getElementById('edit-description');
        if (textarea) {
            function autoResize() {
                textarea.style.height = 'auto';
                textarea.style.height = textarea.scrollHeight + 'px';
            }
            
            textarea.addEventListener('input', autoResize);
            autoResize(); // Initial resize
        }

        // Character counters
        document.getElementById('edit-title')?.addEventListener('input', function() {
            document.getElementById('title-char-count').textContent = this.value.length;
        });

        document.getElementById('edit-description')?.addEventListener('input', function() {
            document.getElementById('desc-char-count').textContent = this.value.length;
        });
    </script>
</body>
</html>