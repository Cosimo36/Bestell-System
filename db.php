<?php
$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "bestellappdb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
