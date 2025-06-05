<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['is_done'])) {
    $id = (int) $_POST['id'];
    $is_done = (int) $_POST['is_done'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE tasks SET is_done = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("iii", $is_done, $id, $user_id);
    $stmt->execute();
    $stmt->close();
}
