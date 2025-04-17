<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $email = $_POST['email'];
    $role = 'customer';

    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, email, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password, $email, $role);
    
    if ($stmt->execute()) {
        echo "Registration successful.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
