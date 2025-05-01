<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: sign_in.html");
    exit;
}

$messages = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_customer'])) {
        $stmt = $conn->prepare("DELETE FROM customers WHERE customer_id = ?");
        $stmt->bind_param("i", $_POST['customer_id']);
        $stmt->execute();
        $messages[] = "Customer #{$_POST['customer_id']} deleted.";
    } elseif (isset($_POST['delete_product'])) {
        $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $_POST['product_id']);
        $stmt->execute();
        $messages[] = "Product #{$_POST['product_id']} deleted.";
    } elseif (isset($_POST['delete_order'])) {
        $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $_POST['order_id']);
        $stmt->execute();
        $messages[] = "Order #{$_POST['order_id']} deleted.";
    } elseif (isset($_POST['add_employee'])) {
        $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO employees (name, email, password_hash, phone, birthdate, salary, startdate, address, manager_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssdssi", $_POST['name'], $_POST['email'], $password_hash, $_POST['phone'], $_POST['birthdate'], $_POST['salary'], $_POST['startdate'], $_POST['address'], $_SESSION['user_id']);
        $stmt->execute();
        $messages[] = "Employee added.";
    } elseif (isset($_POST['delete_employee'])) {
        $stmt = $conn->prepare("DELETE FROM employees WHERE employee_id = ?");
        $stmt->bind_param("i", $_POST['employee_id']);
        $stmt->execute();
        $messages[] = "Employee #{$_POST['employee_id']} deleted.";
    } elseif (isset($_POST['add_product'])) {
        $stmt = $conn->prepare("INSERT INTO products (name, description, category, price, available_stock, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdis", $_POST['name'], $_POST['description'], $_POST['category'], $_POST['price'], $_POST['stock'], $_POST['image_url']);
        $stmt->execute();
        $messages[] = "Product added.";
    }
}

$customers = $conn->query("SELECT * FROM customers ORDER BY created_at DESC");
$products = $conn->query("SELECT * FROM products ORDER BY product_id DESC");
$orders = $conn->query("SELECT * FROM orders ORDER BY date_ordered DESC");
$employees = $conn->query("SELECT * FROM employees ORDER BY employee_id DESC");
?>

<!DOCTYPE html>
<html>
<head>
  <title>Manager Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { padding: 20px; background: #0a0a0a; color: #e0e0e0; font-family: sans-serif; }
    h1, h2 { color: #4e036e; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 30px; background: #1a1a1a; }
    th, td { border: 1px solid #333; padding: 10px; text-align: left; }
    th { background: #2a2a2a; }
    form { margin-top: 10px; }
    input, button, textarea {
      padding: 8px;
      border: none;
      border-radius: 4px;
      margin-right: 10px;
      width: 100%;
      box-sizing: border-box;
    }
    button { background: #4e036e; color: white; cursor: pointer; margin-top: 5px; }
    .message { margin: 10px 0; padding: 10px; background: #333; border-left: 5px solid #4e036e; }
    .form-block { background: #1a1a1a; padding: 15px; margin-bottom: 20px; border-radius: 8px; }
  </style>
</head>
<body>
<h1>Manager Dashboard</h1>
<a href="logout.php" class="logout-btn">Log out</a>

<?php foreach ($messages as $msg): ?>
  <div class="message"><?php echo htmlspecialchars($msg); ?></div>
<?php endforeach; ?>

<h2>Customers</h2>
<table>
  <tr>
    <th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Birthdate</th><th>Address</th><th>City</th><th>State</th><th>ZIP</th><th>Created At</th>
  </tr>
  <?php while ($c = $customers->fetch_assoc()): ?>
    <tr>
      <td><?php echo $c['customer_id']; ?></td>
      <td><?php echo htmlspecialchars($c['name']); ?></td>
      <td><?php echo htmlspecialchars($c['email']); ?></td>
      <td><?php echo htmlspecialchars($c['phone']); ?></td>
      <td><?php echo $c['birthdate']; ?></td>
      <td><?php echo htmlspecialchars($c['address']); ?></td>
      <td><?php echo htmlspecialchars($c['city']); ?></td>
      <td><?php echo htmlspecialchars($c['state']); ?></td>
      <td><?php echo htmlspecialchars($c['zip']); ?></td>
      <td><?php echo $c['created_at']; ?></td>
    </tr>
  <?php endwhile; ?>
</table>
<form method="POST">
  <input type="number" name="customer_id" placeholder="Customer ID to remove" required>
  <button name="delete_customer">Delete Customer</button>
</form>

<h2>Products</h2>
<table>
  <tr>
    <th>ID</th><th>Name</th><th>Description</th><th>Category</th><th>Price</th><th>Stock</th><th>Image URL</th>
  </tr>
  <?php while ($p = $products->fetch_assoc()): ?>
    <tr>
      <td><?php echo $p['product_id']; ?></td>
      <td><?php echo htmlspecialchars($p['name']); ?></td>
      <td><?php echo htmlspecialchars($p['description']); ?></td>
      <td><?php echo htmlspecialchars($p['category']); ?></td>
      <td><?php echo number_format($p['price'], 2); ?></td>
      <td><?php echo $p['available_stock']; ?></td>
      <td><?php echo htmlspecialchars($p['image_url']); ?></td>
    </tr>
  <?php endwhile; ?>
</table>
<form method="POST">
  <input type="number" name="product_id" placeholder="Product ID to remove" required>
  <button name="delete_product">Delete Product</button>
</form>

<div class="form-block">
  <h3>Add Product</h3>
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

<h2>Orders</h2>
<table>
  <tr>
    <th>ID</th><th>Customer ID</th><th>Employee ID</th><th>Date</th><th>Tracking #</th><th>Amount</th><th>Address</th><th>City</th><th>State</th><th>ZIP</th>
  </tr>
  <?php while ($o = $orders->fetch_assoc()): ?>
    <tr>
      <td><?php echo $o['order_id']; ?></td>
      <td><?php echo $o['customer_id']; ?></td>
      <td><?php echo $o['employee_id']; ?></td>
      <td><?php echo $o['date_ordered']; ?></td>
      <td><?php echo htmlspecialchars($o['tracking_number']); ?></td>
      <td>$<?php echo number_format($o['amount'], 2); ?></td>
      <td><?php echo htmlspecialchars($o['shipping_address']); ?></td>
      <td><?php echo htmlspecialchars($o['shipping_city']); ?></td>
      <td><?php echo htmlspecialchars($o['shipping_state']); ?></td>
      <td><?php echo htmlspecialchars($o['shipping_zip']); ?></td>
    </tr>
  <?php endwhile; ?>
</table>
<form method="POST">
  <input type="number" name="order_id" placeholder="Order ID to remove" required>
  <button name="delete_order">Delete Order</button>
</form>

<h2>Employees</h2>
<table>
  <tr>
    <th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Birthdate</th><th>Salary</th><th>Start Date</th><th>Address</th><th>Manager ID</th><th>Created At</th>
  </tr>
  <?php while ($e = $employees->fetch_assoc()): ?>
    <tr>
      <td><?php echo $e['employee_id']; ?></td>
      <td><?php echo htmlspecialchars($e['name']); ?></td>
      <td><?php echo htmlspecialchars($e['email']); ?></td>
      <td><?php echo htmlspecialchars($e['phone']); ?></td>
      <td><?php echo $e['birthdate']; ?></td>
      <td><?php echo $e['salary']; ?></td>
      <td><?php echo $e['startdate']; ?></td>
      <td><?php echo htmlspecialchars($e['address']); ?></td>
      <td><?php echo $e['manager_id']; ?></td>
      <td><?php echo $e['created_at']; ?></td>
    </tr>
  <?php endwhile; ?>
</table>
<form method="POST">
  <input type="number" name="employee_id" placeholder="Employee ID to remove" required>
  <button name="delete_employee">Delete Employee</button>
</form>

<div class="form-block">
  <h3>Add Employee</h3>
  <form method="POST">
    <input name="name" placeholder="Full Name" required>
    <input name="email" type="email" placeholder="Email" required>
    <input name="password" type="password" placeholder="Password" required>
    <input name="phone" placeholder="Phone">
    <input name="birthdate" type="date">
    <input name="salary" type="number" step="0.01" placeholder="Salary">
    <input name="startdate" type="date">
    <input name="address" placeholder="Address">
    <button name="add_employee">Add Employee</button>
  </form>
</div>

</body>
</html>