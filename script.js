let cart = [];
let quantities = {};

function addToCart(product, price) {
    const quantity = quantities[product] || 1;
    const existing = cart.find(item => item.product === product);
    if (existing) {
        existing.quantity += quantity;
    } else {
        cart.push({ product, price, quantity });
    }
    alert(`${quantity} ${product}(s) added to cart!`);
}

function increaseQuantity(product) {
    quantities[product] = (quantities[product] || 1);
    if (quantities[product] < 10) {
        quantities[product]++;
        document.getElementById(`qty-${product}`).innerText = quantities[product];
    }
}

function decreaseQuantity(product) {
    if (quantities[product] > 1) {
        quantities[product]--;
        document.getElementById(`qty-${product}`).innerText = quantities[product];
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
    alert('Cart has been cleared!');
    document.getElementById('cart-items').innerHTML = 'Cart is empty';
}

