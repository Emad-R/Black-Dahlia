<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // collect & sanitize
    $name        = trim($_POST['name']);
    $email       = trim($_POST['email']);
    $password    = $_POST['password'];
    $confirm     = $_POST['confirm_password'];
    $phone       = trim($_POST['phone']);
    $birthdate   = $_POST['birthdate'];      // YYYY-MM-DD
    $address     = trim($_POST['address']);
    $city        = trim($_POST['city']);
    $state       = trim($_POST['state']);
    $zip         = trim($_POST['zip']);

    // basic validations
    if ($password !== $confirm) {
        die("Passwords do not match.");
    }
    if (!preg_match('/^\d{10}$/', $phone)) {
        die("Phone must be 10 digits.");
    }
    if (!preg_match('/^\d{5}$/', $zip)) {
        die("ZIP code must be 5 digits.");
    }

    // hash password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // insert into customers
    $stmt = $conn->prepare(
      "INSERT INTO customers
         (name, email, password_hash, phone, birthdate, address, city, state, zip)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    // bind 9 strings: name, email, pw-hash, phone, birthdate, address, city, state, zip
    $stmt->bind_param(
      "sssssssss",
      $name,
      $email,
      $password_hash,
      $phone,
      $birthdate,
      $address,
      $city,
      $state,
      $zip
    );

    if ($stmt->execute()) {
        echo "Account created successfully. <a href='sign_in.html'>Sign in here</a>.";
    } else {
        // e.g. duplicate email
        echo "Error: " . htmlspecialchars($stmt->error);
    }

    $stmt->close();
    $conn->close();
}
?>