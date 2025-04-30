<?php
session_start();
require 'db.php';

// Redirect to login if not a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: sign_in.html");
    exit;
}

$customer_id = $_SESSION['user_id'];

// Handle Add to Cart form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'], $_POST['quantity'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = max(1, intval($_POST['quantity']));

    // Check if item already exists
    $stmt = $conn->prepare("SELECT quantity FROM cart_items WHERE customer_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $customer_id, $product_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        $update = $conn->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE customer_id = ? AND product_id = ?");
        $update->bind_param("iii", $quantity, $customer_id, $product_id);
        $update->execute();
        $update->close();
    } else {
        $stmt->close();
        $insert = $conn->prepare("INSERT INTO cart_items (customer_id, product_id, quantity) VALUES (?, ?, ?)");
        $insert->bind_param("iii", $customer_id, $product_id, $quantity);
        $insert->execute();
        $insert->close();
    }

    // Redirect to avoid resubmission
    header("Location: products.php");
    exit;
}

// Fetch products by category
$stmt = $conn->prepare("SELECT * FROM products ORDER BY category, name");
$stmt->execute();
$result = $stmt->get_result();

$products_by_category = [];
while ($row = $result->fetch_assoc()) {
    $products_by_category[$row['category']][] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Shop - Noir Blooms</title>
  <link rel="stylesheet" href="style.css" />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Roboto&display=swap" rel="stylesheet">
</head>
<body>

<header>
  <div class="logo">
    <img src="https://cyan.csam.montclair.edu/~salemm1/Project/photos/logo-white.png" alt="Noir Blooms Logo">
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

<section class="shop-hero">
  <div class="hero-content">
    <h1>Shop Noir Blooms</h1>
    <p>Discover gothic treasures crafted for the bold and beautiful.</p>
    <a href="#categories" class="btn">Explore Now</a>
  </div>
</section>

<main id="categories" class="shop-page">
  <?php foreach ($products_by_category as $category => $products): ?>
    <section class="category-section">
      <h2><?php echo htmlspecialchars($category); ?></h2>
      <div class="product-grid">
        <?php foreach ($products as $product): ?>
          <div class="product-card">
            <?php if (!empty($product['image_url'])): ?>
              <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            <?php endif; ?>

            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
            <p>$<?php echo number_format($product['price'], 2); ?></p>

            <form method="POST">
              <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
              <label for="qty-<?php echo $product['product_id']; ?>">Qty:</label>
              <input type="number" id="qty-<?php echo $product['product_id']; ?>" name="quantity" min="1" value="1" required />
              <button type="submit">Add to Cart</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>
</main>

</body>
</html>


