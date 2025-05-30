let cart = JSON.parse(localStorage.getItem('cart')) || [];
let quantities = JSON.parse(localStorage.getItem('quantities')) || {};

function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
    localStorage.setItem('quantities', JSON.stringify(quantities));
}

function addToCart(productId) {
  const qtyInput = document.getElementById(`qty-${productId}`);
  const quantity = parseInt(qtyInput.value) || 1;

  fetch('add_to_cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `product_id=${productId}&quantity=${quantity}`
  })
  .then(res => res.text())
  .then(response => {
    alert(response); // Replace with toast or modal in production
  })
  .catch(err => {
    console.error('Cart error:', err);
    alert("Could not add to cart.");
  });
}

function increaseQuantity(product) {
    quantities[product] = (quantities[product] || 1);
    if (quantities[product] < 10) {
        quantities[product]++;
        document.getElementById(`qty-${product}`).innerText = quantities[product];
        saveCart();
    }
}

function decreaseQuantity(product) {
    if (quantities[product] > 1) {
        quantities[product]--;
        document.getElementById(`qty-${product}`).innerText = quantities[product];
        saveCart();
    }
}

function toggleCart() {
    let checkoutSection = document.getElementById('checkout-section');
    checkoutSection.classList.toggle('hidden');
    if (!checkoutSection.classList.contains('hidden')) {
        let cartItems = cart.length ? cart.map(item => `${item.product} - $${item.price} x ${item.quantity}`).join('<br>') : 'Cart is empty';
        document.getElementById('cart-items').innerHTML = cartItems;
    }
}

function clearCart() {
    cart = [];
    quantities = {};
    saveCart();
    alert('Cart has been cleared!');
    document.getElementById('cart-items').innerHTML = 'Cart is empty';
    const quantitySpans = document.querySelectorAll('[id^="qty-"]');
    quantitySpans.forEach(span => span.innerText = '1');
}



// Restore quantity spans on load
window.onload = () => {
    for (let product in quantities) {
        const span = document.getElementById(`qty-${product}`);
        if (span) span.innerText = quantities[product];
    }
};
