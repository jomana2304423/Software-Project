<?php
// session_start(); // Removed redundant session_start()

$cartData = $_GET['cartData'] ?? '';
$cartItems = [];
$overallTotal = 0;

if (!empty($cartData)) {
    $decodedCart = json_decode(urldecode($cartData), true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $cartItems = $decodedCart;
        foreach ($cartItems as $item) {
            $overallTotal += ($item['price'] * $item['quantity']);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="register-container">
        <div class="container">
            <div class="row justify-content-center min-vh-100 align-items-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card shadow">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <i class="bi bi-receipt text-primary" style="font-size: 3rem;"></i>
                                <h2 class="mt-3 mb-1">Order Summary</h2>
                            </div>
                            <div class="order-details">
                                <?php if (empty($cartItems)): ?>
                                    <p>Your cart is empty.</p>
                                <?php else: ?>
                                    <?php foreach ($cartItems as $item): ?>
                                        <p>
                                            <strong>Item:</strong> <?php echo htmlspecialchars($item['name']); ?><br>
                                            <strong>Quantity:</strong> <?php echo htmlspecialchars($item['quantity']); ?><br>
                                            <strong>Price:</strong> ₹<?php echo number_format($item['price'], 2); ?>
                                        </p>
                                        <hr>
                                    <?php endforeach; ?>
                                    <p class="fs-4"><strong>Total:</strong> ₹<?php echo number_format($overallTotal, 2); ?></p>
                                <?php endif; ?>
                            </div>
                            <form action="payment.php" method="GET">
                                <input type="hidden" name="cartData" value="<?php echo htmlspecialchars($cartData); ?>">
                                <button type="submit" class="btn btn-primary w-100 mb-3">Proceed to Payment</button>
                            </form>
                            <div class="text-center">
                                <p class="mb-0"><a href="../views/customers/medicines.php" class="text-decoration-none">Continue Shopping</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
