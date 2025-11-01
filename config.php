<?php
if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1') {
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "livre";
} else {
    $host = "sql111.infinityfree.com";
    $user = "if0_40287488";
    $pass = "medkha05";
    $dbname = "if0_40287488_livre";
}

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Erreur de connexion à la base de données: " . $conn->connect_error);
}
?>
