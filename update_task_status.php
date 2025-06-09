<?php
session_start();
require_once 'includes/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['is_done'])) {

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die("Błąd CSRF - niedozwolone żądanie.");
    }

    $id = (int) $_POST['id'];
    $is_done = (int) $_POST['is_done'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE tasks SET is_done = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("iii", $is_done, $id, $user_id);
    $stmt->execute();
    $stmt->close();
}
