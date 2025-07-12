<?php
$host = 'localhost';
$user = 'root';
$pass = ''; // your MySQL root password
$dbname = 'pharmacy_db';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
