<?php
    session_start();
    require_once '../includes/db.php';

    header("Content-Type: application/json");
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));

    //zapytanie
    $test = $conn->prepare("SELECT * FROM tasks");
    $test->execute();

    //pobieram wyniki
    $result = $test->get_result();

    //var_dump($result);

    //wypisanie danych
    $data = [];
    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }
    echo json_encode($data);

    $test->close();

?>