<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

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

    $stmt = $conn->prepare("SELECT $id_col, name, password_hash FROM $table WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id, $name, $hash);

    if ($stmt->fetch() && password_verify($password, $hash)) {
        $_SESSION['user_id'] = $id;
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $role;

        switch ($role) {
            case 'customer':
                header("Location: cart.php");
                break;
            case 'employee':
                header("Location: employee_dashboard.php");
                break;
            case 'manager':
                header("Location: manager_dashboard.php");
                break;
        }
        exit;
    } else {
        echo "Incorrect email or password.";
    }

    $stmt->close();
    $conn->close();
}
?>


