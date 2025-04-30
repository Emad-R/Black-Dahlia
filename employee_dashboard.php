<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: sign_in.html");
    exit;
}

$messages = [];

// Handle delete order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $_POST['order_id']);
    $stmt->execute();
    $messages[] = "Order #{$_POST['order_id']} deleted.";
}

// Fetch orders
$orders = $conn->query("SELECT * FROM orders ORDER BY date_ordered DESC");
?>

<!DOCTYPE html>
<html>
<head>
  <title>Employee Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { padding: 20px; background: #0a0a0a; color: #e0e0e0; font-family: sans-serif; }
    h1, h2 { color: #4e036e; }
    table { width: 100%; border-collapse: collapse; background: #1a1a1a; margin-bottom: 20px; }
    th, td { border: 1px solid #333; padding: 10px; text-align: left; }
    th { background: #2a2a2a; }
    form { margin-top: 10px; }
    input, button {
      padding: 8px;
      border: none;
      border-radius: 4px;
      margin-right: 10px;
      width: 100%;
      box-sizing: border-box;
    }
    button { background: #4e036e; color: white; cursor: pointer; margin-top: 5px; }
    .message { margin: 10px 0; padding: 10px; background: #333; border-left: 5px solid #4e036e; }
  </style>
</head>
<body>
<h1>Employee Dashboard</h1>
<a href="logout.php" class="logout-btn">Log out</a>

<?php foreach ($messages as $msg): ?>
  <div class="message"><?php echo htmlspecialchars($msg); ?></div>
<?php endforeach; ?>

<h2>Orders</h2>
<table>
  <tr>
    <th>ID</th><th>Customer ID</th><th>Employee ID</th><th>Date Ordered</th><th>Tracking #</th><th>Amount</th><th>Address</th><th>City</th><th>State</th><th>ZIP</th>
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

</body>
</html>
