<?php
$host = 'localhost';
$db   = 'salemm1_Black_Dahlia_Project_db';
$user = 'salemm1_mo';
$pass = 'y&^IyX0w~6wS';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
