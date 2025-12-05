<?php
session_start();

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
                                <p><strong>Item:</strong> Dummy Medicine A</p>
                                <p><strong>Quantity:</strong> 2</p>
                                <p><strong>Price:</strong> $15.00</p>
                                <p><strong>Total:</strong> $30.00</p>
                            </div>
                            <form action="payment.php" method="GET">
                                <button type="submit" class="btn btn-primary w-100 mb-3">Proceed to Payment</button>
                            </form>
                            <div class="text-center">
                                <p class="mb-0"><a href="index.php" class="text-decoration-none">Continue Shopping</a></p>
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
