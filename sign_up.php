<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        die("Passwords do not match.");
    }

    // Hash password securely
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Insert into customers table
    $stmt = $conn->prepare("INSERT INTO customers (name, email, password_hash) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $password_hash);

    if ($stmt->execute()) {
        echo "Account created successfully. <a href='sign_in.html'>Sign in here</a>.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>