<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "projekmini2_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
