<?php
session_start();
require 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Only customers may place orders
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: sign_in.html");
    exit;
}

$customer_id = $_SESSION['user_id'];
$cart_items   = [];
$total        = 0;
$placed       = false;

// 1) Fetch customer’s default shipping info to pre-fill the form:
$address = $city = $state = $zip = '';
$stmt = $conn->prepare("SELECT address, city, state, zip FROM customers WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$stmt->bind_result($address, $city, $state, $zip);
$stmt->fetch();
$stmt->close();

// 2) Handle “Place Order” submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    // Override defaults if the user has edited them:
    $address = trim($_POST['shipping_address']);
    $city    = trim($_POST['shipping_city']);
    $state   = trim($_POST['shipping_state']);
    $zip     = trim($_POST['shipping_zip']);

    // Pull all items from the cart
    $stmt = $conn->prepare(
        "SELECT ci.product_id, ci.quantity, p.price
           FROM cart_items ci
           JOIN products p ON ci.product_id = p.product_id
          WHERE ci.customer_id = ?"
    );
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $order_items = [];
    $order_total = 0;
    while ($row = $result->fetch_assoc()) {
        $order_items[] = $row;
        $order_total  += $row['price'] * $row['quantity'];
    }
    $stmt->close();

    if (!empty($order_items)) {
        // Generate a tracking number and leave employee assignment null
        $tracking    = 'NB' . strtoupper(substr(md5(uniqid()), 0, 10));
        $employee_id = 1;

        // Insert the order header, including whatever shipping info the user provided:
        $stmt = $conn->prepare(
            "INSERT INTO orders 
               (customer_id, employee_id, tracking_number, amount,
                shipping_address, shipping_city, shipping_state, shipping_zip)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "iisdssss",
            $customer_id,
            $employee_id,
            $tracking,
            $order_total,
            $address,
            $city,
            $state,
            $zip
        );
        $stmt->execute();
        $order_id = $stmt->insert_id;
        $stmt->close();

        // Insert each line item into order_items
        foreach ($order_items as $item) {
            $stmt = $conn->prepare(
                "INSERT INTO order_items 
                   (order_id, product_id, quantity, price_at_time)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "iiid",
                $order_id,
                $item['product_id'],
                $item['quantity'],
                $item['price']
            );
            $stmt->execute();
            $stmt->close();
        }
        
        // Reduce stock after inserting the item
        $updateStockStmt = $conn->prepare("
            UPDATE products 
            SET available_stock = available_stock - ? 
            WHERE product_id = ? AND available_stock >= ?
        ");
        
        foreach ($order_items as $item) {
            $updateStockStmt->bind_param("iii", $item['quantity'], $item['product_id'], $item['quantity']);
            $updateStockStmt->execute();
        }
        $updateStockStmt->close();
        
        // Clear the cart
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE customer_id = ?");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $stmt->close();

        $placed = true;
    }
}

// 3) Load cart contents to display
$stmt = $conn->prepare(
    "SELECT p.product_id, p.name, p.price, ci.quantity
       FROM cart_items ci
       JOIN products p ON ci.product_id = p.product_id
      WHERE ci.customer_id = ?"
);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $total       += $row['price'] * $row['quantity'];
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Your Cart - Noir Blooms</title>
  <link rel="stylesheet" href="style.css" />
  <style>
    .cart-page { padding: 40px 20px; max-width: 800px; margin: auto; color: #e0e0e0; }
    .cart-item { background-color: #1a1a1a; padding: 20px; margin-bottom: 15px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; }
    .cart-total { font-size: 22px; font-weight: bold; text-align: right; margin-top: 30px; }
    .checkout-btn { margin-top: 20px; padding: 12px 24px; font-size: 16px; font-weight: bold; background-color: #4e036e; color: white; border: none; border-radius: 8px; cursor: pointer; }
    .checkout-btn:hover { background-color: #3a0254; }
    .empty { text-align: center; font-size: 18px; margin-top: 50px; }
    .success { background-color: #2a2a2a; padding: 20px; margin: 20px 0; border-left: 5px solid #4e036e; color: #9be0b7; font-weight: bold; }
    .shipping-form { background: #2a2a2a; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
    .shipping-form input { width: 100%; padding: 8px; margin: 6px 0; border-radius: 4px; border: 1px solid #444; background: #1a1a1a; color: #e0e0e0; }
    .shipping-form label { font-size: 14px; }
  </style>
</head>
<body>

<header>
  <div class="logo">
    <img src="https://cyan.csam.montclair.edu/~salemm1/Project/photos/logo-white.png" alt="Noir Blooms Logo" />
    <h1>Noir Blooms</h1>
  </div>
  <nav>
    <ul>
      <li><a href="home.html">Home</a></li>
      <li><a href="products.php">Shop</a></li>
      <li><a href="about.html">About</a></li>
      <li><a href="cart.php">Cart</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </nav>
</header>

<main class="cart-page">
  <h1>Your Cart</h1>

  <?php if ($placed): ?>
    <div class="success">
      Order placed successfully! Thank you for shopping with us.
    </div>
  <?php endif; ?>

  <?php if (empty($cart_items)): ?>
    <p class="empty">Your cart is empty.</p>
  <?php else: ?>

    <?php foreach ($cart_items as $item): ?>
      <div class="cart-item">
        <div>
          <h3><?php echo htmlspecialchars($item['name']); ?></h3>
          <p>$<?php echo number_format($item['price'], 2); ?> × <?php echo $item['quantity']; ?></p>
        </div>
        <div>
          <strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="cart-total">
      Total: $<?php echo number_format($total, 2); ?>
    </div>

    <!-- Shipping form -->
    <form method="POST" class="shipping-form">
      <h3>Shipping Information</h3>
      <label>Address
        <input type="text" name="shipping_address" required
               value="<?php echo htmlspecialchars($address); ?>">
      </label>
      <label>City
        <input type="text" name="shipping_city" required
               value="<?php echo htmlspecialchars($city); ?>">
      </label>
      <label>State
        <input type="text" name="shipping_state" required
               value="<?php echo htmlspecialchars($state); ?>">
      </label>
      <label>ZIP
        <input type="text" name="shipping_zip" required maxlength="5"
               value="<?php echo htmlspecialchars($zip); ?>">
      </label>

      <button type="submit" name="checkout" class="checkout-btn">
        Place Order
      </button>
    </form>

  <?php endif; ?>
</main>

</body>
</html>
