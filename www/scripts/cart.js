// scripts/cart.js

// Initialize cart from localStorage
let cart = JSON.parse(localStorage.getItem('cart')) || [];

// Update cart count in navbar
function updateCartCount() {
  const cartCount = cart.length;
  document.getElementById('cart-count').innerText = cartCount;
}

// Add product to cart
function addToCart(productId) {
  cart.push(productId);
  localStorage.setItem('cart', JSON.stringify(cart));
  updateCartCount();
  showToast('Product added to cart!');
}

// Remove product from cart entirely
function removeFromCart(productId) {
  cart = cart.filter(id => id !== productId);
  localStorage.setItem('cart', JSON.stringify(cart));
  updateCartCount();
}

// Decrease quantity of a product
function decreaseQuantity(productId) {
  const index = cart.indexOf(productId);
  if (index !== -1) {
    cart.splice(index, 1);
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
  }
}

// Increase quantity of a product
function increaseQuantity(productId) {
  cart.push(productId);
  localStorage.setItem('cart', JSON.stringify(cart));
  updateCartCount();
}

// Display a toast notification
function showToast(message) {
  let toastContainer = document.getElementById('toast-container');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.id = 'toast-container';
    // Added 'top-16' to offset the container by 64px (navbar height)
    toastContainer.className = 'toast toast-top toast-end top-16';
    document.body.appendChild(toastContainer);
  }
  const toast = document.createElement('div');
  toast.className = 'alert alert-success';
  toast.innerHTML = `
    <div>
      <span>${message}</span>
    </div>
  `;
  toastContainer.appendChild(toast);
  setTimeout(() => {
    toast.remove();
  }, 3000);
}


// Load cart on page load
document.addEventListener('DOMContentLoaded', updateCartCount);
