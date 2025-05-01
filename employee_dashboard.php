<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: sign_in.html");
    exit;
}

$messages = [];
$orderItems = [];

// Handle delete order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_order'])) {
        $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $_POST['order_id']);
        $stmt->execute();
        $messages[] = "Order #" . intval($_POST['order_id']) . " deleted.";
    }
    // Handle view order items
    if (isset($_POST['view_order'])) {
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
    input, button { padding: 8px; border: none; border-radius: 4px; margin-right: 10px; width: 100%; box-sizing: border-box; }
    button { background: #4e036e; color: white; cursor: pointer; margin-top: 5px; }
    .message { margin: 10px 0; padding: 10px; background: #333; border-left: 5px solid #4e036e; }
  </style>
</head>
<body>
<h1>Employee Dashboard</h1>
<a href="logout.php" class="logout-btn">Log out</a>

<?php foreach ($messages as $msg): ?>
  <div class="message"><?= htmlspecialchars($msg) ?></div>
<?php endforeach; ?>

<h2>Orders</h2>
<table>
  <tr>
    <th>ID</th><th>Customer ID</th><th>Employee ID</th><th>Date Ordered</th><th>Tracking #</th><th>Amount</th><th>Address</th><th>City</th><th>State</th><th>ZIP</th>
  </tr>
  <?php while ($o = $orders->fetch_assoc()): ?>
    <tr>
      <td><?= $o['order_id'] ?></td>
      <td><?= $o['customer_id'] ?></td>
      <td><?= $o['employee_id'] ?></td>
      <td><?= $o['date_ordered'] ?></td>
      <td><?= htmlspecialchars($o['tracking_number']) ?></td>
      <td>$<?= number_format($o['amount'], 2) ?></td>
      <td><?= htmlspecialchars($o['shipping_address']) ?></td>
      <td><?= htmlspecialchars($o['shipping_city']) ?></td>
      <td><?= htmlspecialchars($o['shipping_state']) ?></td>
      <td><?= htmlspecialchars($o['shipping_zip']) ?></td>
    </tr>
  <?php endwhile; ?>
</table>

<div class="form-block">
  <form method="POST">
    <input type="number" name="order_id" placeholder="Order ID to remove" required>
    <button name="delete_order">Delete Order</button>
  </form>
</div>

<div class="form-block">
  <h3>View Order Details</h3>
  <form method="POST">
    <input type="number" name="order_id_view" placeholder="Order ID to view items" required>
    <button name="view_order">View Items</button>
  </form>
</div>

<?php if (!empty($orderItems)): ?>
  <h2>Order #<?= htmlspecialchars($orderItems[0]['product_id']) ? '' : '' ?>Items</h2>
  <table>
    <tr><th>Product ID</th><th>Name</th><th>Quantity</th><th>Price at Time</th></tr>
    <?php foreach ($orderItems as $item): ?>
      <tr>
        <td><?= $item['product_id'] ?></td>
        <td><?= htmlspecialchars($item['name']) ?></td>
        <td><?= $item['quantity'] ?></td>
        <td>$<?= number_format($item['price_at_time'], 2) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

</body>
</html>



