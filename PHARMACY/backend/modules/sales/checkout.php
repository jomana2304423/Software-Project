<?php
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/rbac.php';
require_once __DIR__.'/../../includes/helpers.php';

require_login();
require_role(['Admin', 'Pharmacist']);

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    header('Location: cart.php?error=Cart is empty');
    exit;
}

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = sanitize_input($_POST['customer_name'] ?? '');
    $customer_phone = sanitize_input($_POST['customer_phone'] ?? '');
    $customer_email = sanitize_input($_POST['customer_email'] ?? '');
    $discount = (float)($_POST['discount'] ?? 0);
    
    // Calculate totals
    $subtotal = 0;
    foreach ($_SESSION['cart'] as $item) {
        $subtotal += $item['unit_price'] * $item['quantity'];
    }
    $total = $subtotal - $discount;
    
    try {
        $pdo->beginTransaction();
        
        // Create customer if provided
        $customer_id = null;
        if ($customer_name) {
            $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email) VALUES (?, ?, ?)");
            $stmt->execute([$customer_name, $customer_phone, $customer_email]);
            $customer_id = $pdo->lastInsertId();
        }
        
        // Create sale record
        $invoice_no = generate_invoice_number();
        $stmt = $pdo->prepare("
            INSERT INTO sales (invoice_no, customer_id, pharmacist_id, subtotal, discount, total) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$invoice_no, $customer_id, $_SESSION['user']['id'], $subtotal, $discount, $total]);
        $sale_id = $pdo->lastInsertId();
        
        // Create sale items and update stock
        foreach ($_SESSION['cart'] as $item) {
            $line_total = $item['unit_price'] * $item['quantity'];
            
            // Add sale item
            $stmt = $pdo->prepare("
                INSERT INTO sale_items (sale_id, medicine_batch_id, quantity, unit_price, line_total) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$sale_id, $item['batch_id'], $item['quantity'], $item['unit_price'], $line_total]);
            
            // Update stock
            $stmt = $pdo->prepare("UPDATE medicine_batches SET quantity = quantity - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['batch_id']]);
        }
        
        $pdo->commit();
        
        // Clear cart
        $_SESSION['cart'] = [];
        
        log_activity($_SESSION['user']['id'], 'Process Sale', "Completed sale with invoice: $invoice_no");
        
        // Redirect to invoice
        header('Location: invoice.php?id=' . $sale_id);
        exit;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Failed to process sale: ' . $e->getMessage();
    }
}

// Calculate cart totals
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['unit_price'] * $item['quantity'];
}

$page_title = 'Checkout';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="cart.php">Cart</a></li>
                    <li class="breadcrumb-item active">Checkout</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-credit-card"></i> Checkout</h2>
                <a href="cart.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Cart
                </a>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Customer Information -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-person"></i> Customer Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Customer Name</label>
                            <input type="text" name="customer_name" class="form-control" 
                                   placeholder="Enter customer name (optional)">
                            <div class="form-text">Leave blank for walk-in customer</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" name="customer_phone" class="form-control" 
                                           placeholder="Phone number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="customer_email" class="form-control" 
                                           placeholder="Email address">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Discount Amount (₹)</label>
                            <input type="number" name="discount" class="form-control" 
                                   min="0" max="<?php echo $subtotal; ?>" step="0.01" value="0">
                            <div class="form-text">Maximum discount: <?php echo format_currency($subtotal); ?></div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-check-circle"></i> Complete Sale
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-receipt"></i> Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['cart'] as $item): ?>
                                <tr>
                                    <td>
                                        <small><?php echo htmlspecialchars($item['medicine_name']); ?></small>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($item['batch_number']); ?></small>
                                    </td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo format_currency($item['unit_price']); ?></td>
                                    <td><?php echo format_currency($item['unit_price'] * $item['quantity']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3">Subtotal</th>
                                    <th><?php echo format_currency($subtotal); ?></th>
                                </tr>
                                <tr>
                                    <th colspan="3">Discount</th>
                                    <th class="text-danger">-₹0.00</th>
                                </tr>
                                <tr class="table-success">
                                    <th colspan="3">Total</th>
                                    <th><?php echo format_currency($subtotal); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle"></i> Payment Information</h6>
                        <p class="mb-0">Payment will be processed at the counter. Invoice will be generated after completion.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update discount display
document.querySelector('input[name="discount"]').addEventListener('input', function() {
    const discount = parseFloat(this.value) || 0;
    const subtotal = <?php echo $subtotal; ?>;
    const total = subtotal - discount;
    
    document.querySelector('.text-danger').textContent = '-₹' + discount.toFixed(2);
    document.querySelector('.table-success th:last-child').textContent = '₹' + total.toFixed(2);
});
</script>

<?php include '../../includes/footer.php'; ?>
