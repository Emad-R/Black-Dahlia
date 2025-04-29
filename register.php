<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role']; // should be 'customer', 'employee', or 'manager'

    // Determine which table to insert into
    switch ($role) {
        case 'customer':
            $stmt = $conn->prepare("INSERT INTO customers (name, email, password_hash) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $password);
            break;

        case 'employee':
            $stmt = $conn->prepare("INSERT INTO employees (name, email, password_hash) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $password);
            break;

        case 'manager':
            $stmt = $conn->prepare("INSERT INTO managers (name, email, password_hash, title) VALUES (?, ?, ?, ?)");
            $title = 1; // Default privilege level for new managers
            $stmt->bind_param("sssi", $name, $email, $password, $title);
            break;

        default:
            echo "Invalid role.";
            exit;
    }

    // Execute insert
    if ($stmt->execute()) {
        echo "Registration successful as $role.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>