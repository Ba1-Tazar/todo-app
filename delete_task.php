<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['task_id'])) {
    $task_id = (int) $_POST['task_id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $task_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: dashboard.php");
exit;
?>
