<?php
session_start();
require_once __DIR__.'/../../models/auth.php';
require_once __DIR__.'/../../models/rbac.php';
require_once __DIR__.'/../../models/helpers.php';

require_login();
require_role('Customer');

$config = require __DIR__.'/../../app/config/config.php';

$page_title = 'Shopping Cart';
include '../../views/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="bi bi-cart"></i> Shopping Cart
                <small class="text-muted">Review your selected items</small>
            </h2>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="cart-items" id="cartItemsContainer">
                <!-- Cart items will be loaded here by JavaScript -->
                <div class="text-center text-muted py-4" id="emptyCartMessage">
                    <i class="bi bi-cart-x" style="font-size: 4rem;"></i>
                    <h4 class="mt-3 text-muted">Your cart is empty!</h4>
                    <a href="medicines.php" class="btn btn-primary mt-3">Start Shopping</a>
                </div>
            </div>

            <div class="text-end fs-4 mt-4" id="cartTotalContainer" style="display: none;">
                <strong>Total: ₹<span id="cartTotal">0.00</span></strong>
            </div>

            <div class="d-grid gap-2 mt-4">
                <a href="#" id="proceedToCheckoutLink" class="btn btn-primary btn-lg" style="display: none;">Proceed to Checkout</a>
                <a href="medicines.php" class="btn btn-secondary btn-lg">Continue Shopping</a>
            </div>
        </div>
    </div>
</div>

<script>
let cart = JSON.parse(localStorage.getItem('dummyCart')) || {};

const cartItemsContainer = document.getElementById('cartItemsContainer');
const emptyCartMessage = document.getElementById('emptyCartMessage');
const cartTotalContainer = document.getElementById('cartTotalContainer');
const cartTotalSpan = document.getElementById('cartTotal');
const proceedToCheckoutLink = document.getElementById('proceedToCheckoutLink'); // Changed to link

function renderCart() {
    cartItemsContainer.innerHTML = ''; // Clear existing items
    let total = 0;
    let hasItems = false;

    for (const medicineId in cart) {
        hasItems = true;
        const item = cart[medicineId];
        const itemTotal = item.quantity * item.price;
        total += itemTotal;

        const itemDiv = document.createElement('div');
        itemDiv.className = 'd-flex justify-content-between align-items-center border-bottom pb-3 mb-3';
        itemDiv.innerHTML = `
            <div>
                <h5>${item.name}</h5>
                <p class="text-muted mb-0">₹${item.price.toFixed(2)} x ${item.quantity}</p>
            </div>
            <div class="d-flex align-items-center">
                <button class="btn btn-sm btn-outline-secondary me-2" onclick="updateCartQuantity(${item.id}, -1)">-</button>
                <span>${item.quantity}</span>
                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="updateCartQuantity(${item.id}, 1)">+</button>
                <button class="btn btn-sm btn-danger ms-3" onclick="removeFromCart(${item.id})"><i class="bi bi-trash"></i></button>
            </div>
        `;
        cartItemsContainer.appendChild(itemDiv);
    }

    if (hasItems) {
        emptyCartMessage.style.display = 'none';
        cartTotalContainer.style.display = 'block';
        proceedToCheckoutLink.style.display = 'block'; // Use link
        // Update the href attribute with serialized cart data
        proceedToCheckoutLink.href = `../../public/checkout.php?cartData=${encodeURIComponent(JSON.stringify(cart))}`;
    } else {
        emptyCartMessage.style.display = 'block';
        cartTotalContainer.style.display = 'none';
        proceedToCheckoutLink.style.display = 'none';
        proceedToCheckoutLink.href = '#'; // Reset href
    }

    cartTotalSpan.textContent = total.toFixed(2);
}

function updateCartQuantity(medicineId, change) {
    if (cart[medicineId]) {
        cart[medicineId].quantity += change;
        if (cart[medicineId].quantity <= 0) {
            delete cart[medicineId];
        }
        localStorage.setItem('dummyCart', JSON.stringify(cart));
        renderCart();
    }
}

function removeFromCart(medicineId) {
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        delete cart[medicineId];
        localStorage.setItem('dummyCart', JSON.stringify(cart));
        renderCart();
    }
}

document.addEventListener('DOMContentLoaded', renderCart);
</script>

<?php include '../../views/footer.php'; ?>
