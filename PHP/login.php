<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password_hash, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($id, $hash, $role);

    if ($stmt->fetch() && password_verify($password, $hash)) {
        $_SESSION['user_id'] = $id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        header("Location: ../dashboard.html");
    } else {
        echo "Invalid credentials.";
    }

    $stmt->close();
    $conn->close();
}
?>
