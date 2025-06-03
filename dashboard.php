<?php
session_start();

require_once 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['title'])) {
    $title = trim($_POST['title']);
    $user_id = $_SESSION['user_id'];

    if (!empty($title)) {
        $stmt = $conn->prepare("INSERT INTO tasks (user_id, title) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $title);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: dashboard.php");
    exit;

}


// Sprawdź, czy użytkownik jest zalogowany
if (!isset($_SESSION['username'])) {
    header("Location: auth/login.php");
    exit;
}

$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
    <p></p>
    
    <h3>Add new task</h3>
    <form method="post">
        <input type="text" name="title" placeholder="Treść zadania" required>
        <button type="submit">Add</button>
    </form>

    <h3>Your Tasks:</h3>
    <ul id="task-list">
        <?php
        $stmt = $conn->prepare("SELECT id, title, is_done FROM tasks WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $checked = $row['is_done'] ? "checked" : "";
            echo "<li>
                <input type='checkbox' class='task-checkbox' data-id='{$row['id']}' $checked>
                <span>" . htmlspecialchars($row['title']) . "</span>
            </li>";
        }

        $stmt->close();
        ?>
    </ul>

    <p><a href="logout.php">Logout</a></p>
</body>
</html>