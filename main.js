// Cart Management
function addToCart(productName) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    cart.push(productName);
    localStorage.setItem('cart', JSON.stringify(cart));
    alert(productName + " added to cart!");
  }
  
  function displayCart() {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const cartContainer = document.getElementById('cart-items');
  
    if (cart.length === 0) {
      cartContainer.innerHTML = "<p>Your cart is empty.</p>";
    } else {
      cartContainer.innerHTML = "<ul>" + cart.map(item => `<li>${item}</li>`).join('') + "</ul>";
    }
  }
  
  function clearCart() {
    localStorage.removeItem('cart');
    window.location.reload();
  }
  
  // Authentication Management
  function updateAuthLink() {
    const authLink = document.getElementById('auth-link');
    const user = JSON.parse(localStorage.getItem('user'));
  
    if (user && user.loggedIn) {
      authLink.innerHTML = '<a href="#" onclick="signOut()">Sign Out</a>';
    } else {
      authLink.innerHTML = '<a href="sign_in.html">Sign In</a>';
    }
  }
  
  function signOut() {
    const user = JSON.parse(localStorage.getItem('user'));
    if (user) {
      user.loggedIn = false;
      localStorage.setItem('user', JSON.stringify(user));
    }
    window.location.href = 'index.html';
  }
  
  document.addEventListener('DOMContentLoaded', function() {
    updateAuthLink();
  });
  