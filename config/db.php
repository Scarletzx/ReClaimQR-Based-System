<?php
$host = "localhost";
$user = "priorisy_reclaim";
$pass = "020708Wafi!";
$db   = "priorisy_reclaim";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>