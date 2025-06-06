<?php
$host = 'localhost';
$db   = 'todo_app';
$user = 'root';
$pass = '';     
$port = 3306;
$charset = 'utf8mb4';

// Połączenie z bazą
$conn = new mysqli($host, $user, $pass, $db, $port);

// Sprawdzenie błędów
if ($conn->connect_error) {
    die('Błąd połączenia z bazą danych: ' . $conn->connect_error);
}
?>
