<?php
session_start();
require_once 'includes/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$timeout = 1800; // 30 minut

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    header("Location: auth/login.php");
    exit();
}

$_SESSION['last_activity'] = time();

if (!isset($_SESSION['username'])) {
    header("Location: auth/login.php");
    exit;
}

$username = $_SESSION['username'];
$error = null;

// Obsługa dodawania nowego zadania
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['title'])) {

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die("Błąd CSRF - niedozwolone żądanie.");
    }

    $title = trim($_POST['title']);
    $description = trim($_POST['description'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    // Walidacja
    if (empty($title)) {
        $error = "Title cannot be empty";
    } elseif (mb_strlen($title) > 60) {
        $error = "Title cannot exceed 60 characters";
    } elseif (mb_strlen($description) > 300) {
        $error = "Description cannot exceed 300 characters";
    } else {
        $stmt = $conn->prepare("INSERT INTO tasks (user_id, title, description) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $title, $description);
        
        if ($stmt->execute()) {
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Error adding task: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/dashboard.css">
</head>
<body>

    <div class="container">
        <h2 style="font-size: 30px;">Welcome, <?php echo htmlspecialchars($username); ?>!</h2>

        <div class="task-form">
            <h3>Add new task</h3>
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <!-- Title -->
                 <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="form-group">
                    <input type="text" name="title" maxlength="60" id="task-title-input" 
                        placeholder="Task title (max 60 chars)" required
                        value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">
                    <div class="char-counter title-char-counter">
                        <span id="title-char-count"><?= isset($_POST['title']) ? mb_strlen($_POST['title']) : 0 ?></span>/60
                    </div>
                </div>
                <!-- Description -->
                <div class="form-group textarea-group">
                    <textarea name="description" id="task-description-input" 
                            placeholder="Description (max 300 chars)" rows="3" 
                            maxlength="300"></textarea>
                    <div class="char-counter desc-char-counter">
                        <span id="desc-char-count">0</span>/300
                    </div>
                </div>
                <!-- Add button -->
                <button type="submit">Add</button>
            </form>
        </div>

        <h3>Your Tasks:</h3>
        <div class="task-list-wrapper">
            
            
            <!-- Sekcja Niezrobionych Zadań -->
            <div class="task-section" id="task-section-left">
                <div class="section-header" onclick="toggleSection('pending')">
                    <h4>To do (<span id="pending-count">0</span>)</h4>
                    <span class="toggle-icon">▼</span>
                </div>
                <ul id="pending-tasks" class="task-list">
                    <?php
                    $pending = $conn->prepare("SELECT id, title, description, is_done, created_at FROM tasks WHERE user_id = ? AND is_done = 0 ORDER BY created_at DESC");
                    $pending->bind_param("i", $_SESSION['user_id']);
                    $pending->execute();
                    $pending_result = $pending->get_result();
                    
                    while ($row = $pending_result->fetch_assoc()) {
                        render_task_item($row);
                    }
                    $pending->close();
                    ?>
                </ul>
            </div>
            
            <!-- Sekcja Zrobionych Zadań -->
            <div class="task-section" id="task-section-right">
                <div class="section-header" onclick="toggleSection('completed')">
                    <h4>Completed (<span id="completed-count">0</span>)</h4>
                    <span class="toggle-icon">▼</span>
                </div>
                <ul id="completed-tasks" class="task-list">
                    <?php
                    $completed = $conn->prepare("SELECT id, title, description, is_done, created_at FROM tasks WHERE user_id = ? AND is_done = 1 ORDER BY created_at DESC");
                    $completed->bind_param("i", $_SESSION['user_id']);
                    $completed->execute();
                    $completed_result = $completed->get_result();
                    
                    while ($row = $completed_result->fetch_assoc()) {
                        render_task_item($row);
                    }
                    $completed->close();
                    
                    function render_task_item($row) {
                        $checked = $row['is_done'] ? "checked" : "";
                        $class = $row['is_done'] ? "task-completed" : "";
                        $created_at = date('d.m.Y H:i', strtotime($row['created_at']));
                        $title = htmlspecialchars($row['title']);
                        $desc = nl2br(htmlspecialchars(substr($row['description'], 0, 255)));
                        $task_id = $row['id'];
                        $csrf = $_SESSION['csrf_token'];

                        echo "
                        <li data-id='$task_id'>
                            <div class='task-item'>
                                <div class='task-title-wrapper'>
                                    <input type='checkbox' class='task-checkbox' data-id='$task_id' $checked>
                                    <span class='task-title $class'>$title</span>
                                </div>
                                <div class='task-description $class'>$desc</div>
                                <div class='task-bottom-row'>
                                    <div class='task-time'>
                                        <i class='far fa-clock'></i> $created_at
                                    </div>
                                    <div class='task-buttons-right'>
                                        <form method='get' action='edit_task.php'>
                                            <input type='hidden' name='task_id' value='$task_id'>
                                            <button type='submit' class='edit-button'>Edit</button>
                                        </form>
                                        <form method='post' action='delete_task.php'>
                                            <input type='hidden' name='task_id' value='$task_id'>
                                            <input type='hidden' name='csrf_token' value='$csrf'>
                                            <button type='submit' class='delete-button'>Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </li>";
                    }
                    ?>
                </ul>
            </div>
        </div>
        <form method="post" action="logout.php" style="text-align: center;">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <button type="submit" class="logout-button">Logout</button>
        </form>
    
    <!-- Obsługa zaznaczania i odznaczania zadań -->
    <script>
        document.querySelectorAll('.task-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const taskId = this.dataset.id;
            const isChecked = this.checked ? 1 : 0;

            const taskText = this.nextElementSibling;
            taskText.classList.toggle('task-completed', isChecked);

            // Dodane: Znajdź element opisu i zastosuj tę samą klasę
            const taskDescription = this.closest('.task-item').querySelector('.task-description');
            if (taskDescription) {
                taskDescription.classList.toggle('task-completed', isChecked);
            }

            fetch('update_task_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${taskId}&is_done=${isChecked}&csrf_token=${csrfToken}`
            });
        });
    });
    </script>

    <!-- Edytowanie Zadań -->
    <script>
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function () {
                const id = this.dataset.id;
                const span = document.querySelector(`.task-title[data-id="${id}"]`);
                const currentText = span.textContent;

                // Stwórz input
                const input = document.createElement('input');
                input.type = 'text';
                input.value = currentText;
                input.classList.add('edit-input');

                // Zastąp tekst polem input
                span.replaceWith(input);
                this.textContent = 'Save';

                this.addEventListener('click', function saveEdit() {
                    const newText = input.value;

                    fetch('update_task_title.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `id=${id}&title=${encodeURIComponent(newText)}&csrf_token=${csrfToken}`
                    }).then(() => {
                        const newSpan = document.createElement('span');
                        newSpan.className = 'task-title';
                        newSpan.dataset.id = id;
                        newSpan.textContent = newText;
                        input.replaceWith(newSpan);
                        this.textContent = 'Edit';
                        this.removeEventListener('click', saveEdit);
                    });
                }, { once: true });
            });
        });
    </script>

    <script>
        // Licznik dla tytułu
        const titleInput = document.getElementById('task-title-input');
        const titleCounter = document.getElementById('title-char-count');
        if (titleInput) {
            titleInput.addEventListener('input', () => {
                titleCounter.textContent = titleInput.value.length;
                titleInput.nextElementSibling.classList.toggle('warning', titleInput.value.length > 50);
            });
        }

        // Licznik dla opisu
        const descInput = document.getElementById('task-description-input');
        const descCounter = document.getElementById('desc-char-count');
        if (descInput) {
            descInput.addEventListener('input', () => {
                descCounter.textContent = descInput.value.length;
                descInput.nextElementSibling.classList.toggle('warning', descInput.value.length > 270);
            });
        }
    </script>

    <!-- Animacja przesuwania zadań przy zaznaczeniu (zrobione na dół)-->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const taskList = document.getElementById('task-list');
            
            document.querySelectorAll('.task-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const taskId = this.dataset.id;
                    const isChecked = this.checked ? 1 : 0;
                    const taskItem = this.closest('li');
                    
                    // Dodajemy animację
                    taskItem.style.transition = "all 0.3s ease";
                    taskItem.style.opacity = "0.6";
                    
                    setTimeout(() => {
                        // Wysyłamy żądanie AJAX do serwera
                        fetch('update_task_status.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `id=${taskId}&is_done=${isChecked}&csrf_token=${csrfToken}`
                        })
                        .then(response => response.text())
                        .then(() => {
                            // Aktualizujemy status zadania
                            taskItem.dataset.status = isChecked;
                            
                            // Dodajemy/usuwamy klasę task-completed
                            const taskTitle = taskItem.querySelector('.task-title');
                            const taskDesc = taskItem.querySelector('.task-description');
                            
                            if (isChecked) {
                                taskTitle.classList.add('task-completed');
                                taskDesc.classList.add('task-completed');
                                
                                // Przenosimy zadanie na koniec listy
                                taskList.appendChild(taskItem);
                            } else {
                                taskTitle.classList.remove('task-completed');
                                taskDesc.classList.remove('task-completed');
                                
                                // Przenosimy zadanie na początek listy
                                const firstPending = document.querySelector('li[data-status="0"]');
                                if (firstPending) {
                                    taskList.insertBefore(taskItem, firstPending);
                                } else {
                                    taskList.prepend(taskItem);
                                }
                            }
                            
                            // Przywracamy pełną widoczność
                            taskItem.style.opacity = "1";
                        });
                    }, 300);
                });
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicjalizacja liczników
            updateTaskCounters();
            
            // Obsługa checkboxów
            document.querySelectorAll('.task-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const taskId = this.dataset.id;
                    const isChecked = this.checked;
                    const taskItem = this.closest('li');
                    
                    // Animacja
                    taskItem.style.transition = "all 0.3s ease";
                    taskItem.style.opacity = "0.5";
                    
                    setTimeout(() => {
                        fetch('update_task_status.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `id=${taskId}&is_done=${isChecked ? 1 : 0}&csrf_token=${csrfToken}`
                        })
                        .then(() => {
                            // Przenieś zadanie do odpowiedniej sekcji
                            const targetList = isChecked 
                                ? document.getElementById('completed-tasks')
                                : document.getElementById('pending-tasks');
                            
                            // Aktualizacja klasy
                            const taskTitle = taskItem.querySelector('.task-title');
                            const taskDesc = taskItem.querySelector('.task-description');
                            
                            if (isChecked) {
                                taskTitle.classList.add('task-completed');
                                taskDesc.classList.add('task-completed');
                            } else {
                                taskTitle.classList.remove('task-completed');
                                taskDesc.classList.remove('task-completed');
                            }
                            
                            targetList.appendChild(taskItem);
                            taskItem.style.opacity = "1";
                            updateTaskCounters();
                        });
                    }, 300);
                });
            });
            
            // Funkcja aktualizująca liczniki
            function updateTaskCounters() {
                document.getElementById('pending-count').textContent = 
                    document.querySelectorAll('#pending-tasks li').length;
                document.getElementById('completed-count').textContent = 
                    document.querySelectorAll('#completed-tasks li').length;
            }
            
            // Obsługa localStorage dla stanu sekcji
            if (localStorage.getItem('pendingCollapsed') === 'true') {
                toggleSection('pending', false);
            }
            if (localStorage.getItem('completedCollapsed') === 'true') {
                toggleSection('completed', false);
            }
        });

        // Funkcja do rozwijania/zwijania sekcji
        function toggleSection(section, saveToStorage = true) {
            const list = document.getElementById(`${section}-tasks`);
            const icon = document.querySelector(`#${section}-tasks + .section-header .toggle-icon`);
            
            list.classList.toggle('collapsed');
            
            if (saveToStorage) {
                localStorage.setItem(`${section}Collapsed`, list.classList.contains('collapsed'));
            }
        }
    </script>
    
    <script>
        const csrfToken = '<?= $_SESSION['csrf_token'] ?>';
    </script>
</body>
</html>