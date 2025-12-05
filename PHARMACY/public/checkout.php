<?php
session_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container">
        <h2>Order Summary</h2>
        <div class="order-details">
            <p><strong>Item:</strong> Dummy Medicine A</p>
            <p><strong>Quantity:</strong> 2</p>
            <p><strong>Price:</strong> $15.00</p>
            <p><strong>Total:</strong> $30.00</p>
        </div>
        <form action="payment.php" method="GET">
            <button type="submit" class="btn btn-primary">Proceed to Payment</button>
        </form>
        <p class="mt-3"><a href="index.php">Continue Shopping</a></p>
    </div>
</body>
</html>
