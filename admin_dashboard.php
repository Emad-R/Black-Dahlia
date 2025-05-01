<?php
session_start();
require 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure only admins (title=0) can access
if (!isset($_SESSION['user_id']) || $_SESSION['title'] !== '0') {
    header('Location: sign_in.html');
    exit;
}

$messages = [];
$orderItems = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete Customer
    if (isset($_POST['delete_customer'])) {
        $stmt = $conn->prepare("DELETE FROM customers WHERE customer_id = ?");
        $stmt->bind_param("i", $_POST['customer_id']);
        $stmt->execute();
        $messages[] = "Customer #" . intval($_POST['customer_id']) . " deleted.";
    }
    // Delete Product
    elseif (isset($_POST['delete_product'])) {
        $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $_POST['product_id']);
        $stmt->execute();
        $messages[] = "Product #" . intval($_POST['product_id']) . " deleted.";
    }
    // Delete Order
    elseif (isset($_POST['delete_order'])) {
        $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $_POST['order_id']);
        $stmt->execute();
        $messages[] = "Order #" . intval($_POST['order_id']) . " deleted.";
    }
    // View Order Items
    elseif (isset($_POST['view_order'])) {
        $orderId = intval($_POST['order_id_view']);
        $stmt = $conn->prepare(
            "SELECT oi.product_id, p.name, oi.quantity, oi.price_at_time
             FROM order_items oi
             JOIN products p ON oi.product_id = p.product_id
             WHERE oi.order_id = ?"
        );
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $orderItems[] = $row;
        }
        if (empty($orderItems)) {
            $messages[] = "No items found for Order #{$orderId}.";
        }
    }
    // Add Employee
    elseif (isset($_POST['add_employee'])) {
        $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare(
            "INSERT INTO employees (name, email, password_hash, phone, birthdate, salary, startdate, address, manager_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssssdssi", $_POST['name'], $_POST['email'], $password_hash, $_POST['phone'], $_POST['birthdate'], $_POST['salary'], $_POST['startdate'], $_POST['address'], $_SESSION['user_id']);
        $stmt->execute();
        $messages[] = "Employee added.";
    }
    // Delete Employee
    elseif (isset($_POST['delete_employee'])) {
        $stmt = $conn->prepare("DELETE FROM employees WHERE employee_id = ?");
        $stmt->bind_param("i", $_POST['employee_id']);
        $stmt->execute();
        $messages[] = "Employee #" . intval($_POST['employee_id']) . " deleted.";
    }
    // Add Product
    elseif (isset($_POST['add_product'])) {
        $stmt = $conn->prepare(
            "INSERT INTO products (name, description, category, price, available_stock, image_url)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssdis", $_POST['name'], $_POST['description'], $_POST['category'], $_POST['price'], $_POST['stock'], $_POST['image_url']);
        $stmt->execute();
        $messages[] = "Product added.";
    }
    // Admin: Add Manager
    elseif (isset($_POST['add_manager'])) {
        $hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare(
            "INSERT INTO managers (title, name, email, password_hash, phone, birthdate, salary, startdate, address, city, state, zip)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("isssssdssss", $_POST['title'], $_POST['name'], $_POST['email'], $hash, $_POST['phone'], $_POST['birthdate'], $_POST['salary'], $_POST['startdate'], $_POST['address'], $_POST['city'], $_POST['state'], $_POST['zip']);
        $stmt->execute();
        $messages[] = "Manager '{$_POST['name']}' added.";
    }
    // Admin: Delete Manager
    elseif (isset($_POST['delete_manager'])) {
        $stmt = $conn->prepare("DELETE FROM managers WHERE manager_id = ?");
        $stmt->bind_param("i", $_POST['manager_id']);
        $stmt->execute();
        $messages[] = "Manager #" . intval($_POST['manager_id']) . " deleted.";
    }
}

// Fetch data
$customers = $conn->query("SELECT * FROM customers ORDER BY created_at DESC");
$products  = $conn->query("SELECT * FROM products ORDER BY product_id DESC");
$orders    = $conn->query("SELECT * FROM orders ORDER BY date_ordered DESC");
$employees = $conn->query("SELECT * FROM employees ORDER BY employee_id DESC");
$managers  = $conn->query("SELECT manager_id, title, name, email FROM managers ORDER BY manager_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { padding: 20px; background: #0a0a0a; color: #e0e0e0; font-family: sans-serif; }
    h1, h2 { color: #4e036e; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; background: #1a1a1a; }
    th, td { border: 1px solid #333; padding: 10px; text-align: left; }
    th { background: #2a2a2a; }
    .form-block { background: #1a1a1a; padding: 15px; margin-bottom: 20px; border-radius: 8px; }
    input, textarea, button { padding: 8px; border: none; border-radius: 4px; margin: 5px 0; width: 100%; box-sizing: border-box; }
    button { background: #4e036e; color: white; cursor: pointer; }
    .message { margin: 10px 0; padding: 10px; background: #333; border-left: 5px solid #4e036e; }
  </style>
</head>
<body>
  <h1>Administrator Dashboard</h1>
  <a href="logout.php" style="color:#e0e0e0;">Log out</a>

  <!-- Messages -->
  <?php foreach ($messages as $msg): ?>
    <div class="message"><?= htmlspecialchars($msg) ?></div>
  <?php endforeach; ?>

  <!-- Orders -->
  <h2>Orders</h2>
  <table>
    <tr><th>ID</th><th>Cust ID</th><th>Emp ID</th><th>Date Ordered</th><th>Tracking #</th><th>Amount</th><th>Ship Addr</th><th>City</th><th>State</th><th>ZIP</th></tr>
    <?php while ($o = $orders->fetch_assoc()): ?>
      <tr>
        <td><?= $o['order_id'] ?></td>
        <td><?= $o['customer_id'] ?></td>
        <td><?= $o['employee_id'] ?></td>
        <td><?= $o['date_ordered'] ?></td>
        <td><?= htmlspecialchars($o['tracking_number']) ?></td>
        <td>$<?= number_format($o['amount'],2) ?></td>
        <td><?= htmlspecialchars($o['shipping_address']) ?></td>
        <td><?= htmlspecialchars($o['shipping_city']) ?></td>
        <td><?= htmlspecialchars($o['shipping_state']) ?></td>
        <td><?= htmlspecialchars($o['shipping_zip']) ?></td>
      </tr>
    <?php endwhile; ?>
  </table>
  <div class="form-block">
    <form method="POST">
      <input type="number" name="order_id" placeholder="Order ID to delete" required>
      <button name="delete_order">Delete Order</button>
    </form>
    <form method="POST">
      <input type="number" name="order_id_view" placeholder="Order ID to view items" required>
      <button name="view_order">View Items</button>
    </form>
  </div>

  <!-- Display order items -->
  <?php if (!empty($orderItems)): ?>
    <h2>Order Details</h2>
    <table>
      <tr><th>Product ID</th><th>Name</th><th>Quantity</th><th>Price at Time</th></tr>
      <?php foreach ($orderItems as $item): ?>
        <tr>
          <td><?= $item['product_id'] ?></td>
          <td><?= htmlspecialchars($item['name']) ?></td>
          <td><?= $item['quantity'] ?></td>
          <td>$<?= number_format($item['price_at_time'],2) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

  <!-- Customers -->
  <h2>Customers</h2>
  <table>
    <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Birthdate</th><th>Address</th><th>City</th><th>State</th><th>ZIP</th><th>Created</th></tr>
    <?php while ($c = $customers->fetch_assoc()): ?>
      <tr>
        <td><?= $c['customer_id'] ?></td>
        <td><?= htmlspecialchars($c['name']) ?></td>
        <td><?= htmlspecialchars($c['email']) ?></td>
        <td><?= htmlspecialchars($c['phone']) ?></td>
        <td><?= $c['birthdate'] ?></td>
        <td><?= htmlspecialchars($c['address']) ?></td>
        <td><?= htmlspecialchars($c['city']) ?></td>
        <td><?= htmlspecialchars($c['state']) ?></td>
        <td><?= htmlspecialchars($c['zip']) ?></td>
        <td><?= $c['created_at'] ?></td>
      </tr>
    <?php endwhile; ?>
  </table>
  <div class="form-block">
    <form method="POST">
      <input type="number" name="customer_id" placeholder="Customer ID to delete" required>
      <button name="delete_customer">Delete Customer</button>
    </form>
  </div>

  <!-- Products -->
  <h2>Products</h2>
  <table>
    <tr><th>ID</th><th>Name</th><th>Description</th><th>Category</th><th>Price</th><th>Stock</th><th>Image URL</th></tr>
    <?php while ($p = $products->fetch_assoc()): ?>
      <tr>
        <td><?= $p['product_id'] ?></td>
        <td><?= htmlspecialchars($p['name']) ?></td>
        <td><?= htmlspecialchars($p['description']) ?></td>
        <td><?= htmlspecialchars($p['category']) ?></td>
        <td>$<?= number_format($p['price'],2) ?></td>
        <td><?= $p['available_stock'] ?></td>
        <td><?= htmlspecialchars($p['image_url']) ?></td>
      </tr>
    <?php endwhile; ?>
  </table>
  <div class="form-block">
    <form method="POST">
      <input type="number" name="product_id" placeholder="Product ID to delete" required>
      <button name="delete_product">Delete Product</button>
    </form>
    <form method="POST">
      <input name="name" placeholder="Name" required>
      <textarea name="description" placeholder="Description"></textarea>
      <input name="category" placeholder="Category">
      <input name="price" type="number" step="0.01" placeholder="Price">
      <input name="stock" type="number" placeholder="Stock">
      <input name="image_url" placeholder="Image URL">
      <button name="add_product">Add Product</button>
    </form>
  </div>

  <!-- Employees -->
  <h2>Employees</h2>
  <table>
    <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Birthdate</th><th>Salary</th><th>Start Date</th><th>Address</th><th>Manager ID</th></tr>
    <?php while ($e = $employees->fetch_assoc()): ?>
      <tr>
        <td><?= $e['employee_id'] ?></td>
        <td><?= htmlspecialchars($e['name']) ?></td>
        <td><?= htmlspecialchars($e['email']) ?></td>
        <td><?= htmlspecialchars($e['phone']) ?></td>
        <td><?= $e['birthdate'] ?></td>
        <td>$<?= number_format($e['salary'],2) ?></td>
        <td><?= $e['startdate'] ?></td>
        <td><?= htmlspecialchars($e['address']) ?></td>
        <td><?= $e['manager_id'] ?></td>
      </tr>
    <?php endwhile; ?>
  </table>
  <div class="form-block">\
    <form method="POST">\
      <input name="name" placeholder="Full Name" required>\
      <input name="email" type="email" placeholder="Email" required>\
      <input name="password" type="password" placeholder="Password" required>\
      <input name="phone" placeholder="Phone">\
      <input name="birthdate" type="date">\
      <input name="salary" type="number" step="0.01" placeholder="Salary">\
      <input name="startdate" type="date">\
      <input name="address" placeholder="Address">\
      <button name="add_employee">Add Employee</button>\
    </form>\
    <form method="POST">\
      <input type="number" name="employee_id" placeholder="Employee ID to delete" required>\
      <button name="delete_employee">Delete Employee</button>\
    </form>\
  </div>

  <!-- Managers -->
  <h2>Managers</h2>
  <table>
    <tr><th>ID</th><th>Title</th><th>Name</th><th>Email</th></tr>
    <?php while ($m = $managers->fetch_assoc()): ?>
      <tr>
        <td><?= $m['manager_id'] ?></td>
        <td><?= htmlspecialchars($m['title']) ?></td>
        <td><?= htmlspecialchars($m['name']) ?></td>
        <td><?= htmlspecialchars($m['email']) ?></td>
      </tr>
    <?php endwhile; ?>
  </table>
  <div class="form-block">
    <form method="POST">
      <input name="title" type="number" placeholder="Privilege Level (0=Admin)" required>
      <input name="name" placeholder="Full Name" required>
      <input name="email" type="email" placeholder="Email" required>
      <input name="password" type="password" placeholder="Password" required>
      <input name="phone" placeholder="Phone">
      <input name="birthdate" type="date">
      <input name="salary" type="number" step="0.01" placeholder="Salary">
      <input name="startdate" type="date">
      <input name="address`

]}



