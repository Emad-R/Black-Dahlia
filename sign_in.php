<?php
session_start();
require 'db.php';

// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role']; // 'customer', 'employee', or 'manager'

    // Determine table and ID column
    switch ($role) {
        case 'customer':
            $table = 'customers';
            $id_col = 'customer_id';
            break;
        case 'employee':
            $table = 'employees';
            $id_col = 'employee_id';
            break;
        case 'manager':
            $table = 'managers';
            $id_col = 'manager_id';
            break;
        default:
            die("Invalid role selected.");
    }

    // Build appropriate query
    if ($role === 'manager') {
        $query = "SELECT $id_col, name, password_hash, title FROM $table WHERE email = ?";
    } else {
        $query = "SELECT $id_col, name, password_hash FROM $table WHERE email = ?";
    }

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();

    // Bind results
    if ($role === 'manager') {
        $stmt->bind_result($id, $name, $hash, $title);
    } else {
        $stmt->bind_result($id, $name, $hash);
    }

    // Fetch and verify password
    if ($stmt->fetch() && password_verify($password, $hash)) {
        // Store common session data
        $_SESSION['user_id'] = $id;
        $_SESSION['name']    = $name;
        $_SESSION['email']   = $email;
        $_SESSION['role']    = $role;

        // Manager-specific session data
        if ($role === 'manager') {
            $_SESSION['title'] = $title;
            // Redirect admins vs. regular managers
            if ((int)$title === 0) {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: manager_dashboard.php");
            }
        }
        elseif ($role === 'employee') {
            header("Location: employee_dashboard.php");
        }
        elseif ($role === 'customer') {
            header("Location: cart.php");
        }
        exit;
    } else {
        // Authentication failed
        $_SESSION['error'] = "Incorrect email or password.";
        header("Location: sign_in.html");
        exit;
    }

    // Clean up
    $stmt->close();
    $conn->close();
}
?>