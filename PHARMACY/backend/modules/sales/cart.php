<?php
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/rbac.php';
require_once __DIR__.'/../../includes/helpers.php';

require_login();
require_role(['Admin', 'Pharmacist']);

// Initialize cart in session if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_to_cart':
                $batch_id = (int)$_POST['batch_id'];
                $quantity = (int)$_POST['quantity'];
                
                // Get batch details
                try {
                    $stmt = $pdo->prepare("
                        SELECT mb.*, m.name as medicine_name, m.category 
                        FROM medicine_batches mb 
                        JOIN medicines m ON mb.medicine_id = m.id 
                        WHERE mb.id = ? AND mb.quantity >= ?
                    ");
                    $stmt->execute([$batch_id, $quantity]);
                    $batch = $stmt->fetch();
                    
                    if ($batch) {
                        if (isset($_SESSION['cart'][$batch_id])) {
                            $_SESSION['cart'][$batch_id]['quantity'] += $quantity;
                        } else {
                            $_SESSION['cart'][$batch_id] = [
                                'batch_id' => $batch_id,
                                'medicine_name' => $batch['medicine_name'],
                                'batch_number' => $batch['batch_number'],
                                'unit_price' => $batch['unit_price'],
                                'quantity' => $quantity,
                                'expiry_date' => $batch['expiry_date']
                            ];
                        }
                        
                        log_activity($_SESSION['user']['id'], 'Add to Cart', "Added {$batch['medicine_name']} to cart");
                        header('Location: cart.php?success=Item added to cart');
                        exit;
                    } else {
                        $error = 'Insufficient stock or invalid batch';
                    }
                } catch (PDOException $e) {
                    $error = 'Failed to add item to cart: ' . $e->getMessage();
                }
                break;
                
            case 'update_cart':
                $batch_id = (int)$_POST['batch_id'];
                $quantity = (int)$_POST['quantity'];
                
                if ($quantity <= 0) {
                    unset($_SESSION['cart'][$batch_id]);
                } else {
                    $_SESSION['cart'][$batch_id]['quantity'] = $quantity;
                }
                
                header('Location: cart.php');
                exit;
                break;
                
            case 'remove_from_cart':
                $batch_id = (int)$_POST['batch_id'];
                unset($_SESSION['cart'][$batch_id]);
                
                header('Location: cart.php');
                exit;
                break;
                
            case 'clear_cart':
                $_SESSION['cart'] = [];
                header('Location: cart.php');
                exit;
                break;
        }
    }
}

// Get available medicines for search
$search = $_GET['search'] ?? '';
$medicines = [];

if ($search) {
    try {
        $stmt = $pdo->prepare("
            SELECT mb.*, m.name as medicine_name, m.category, m.reorder_level
            FROM medicine_batches mb 
            JOIN medicines m ON mb.medicine_id = m.id 
            WHERE mb.quantity > 0 
            AND (m.name LIKE ? OR m.category LIKE ? OR mb.batch_number LIKE ?)
            ORDER BY m.name, mb.expiry_date ASC
        ");
        $search_term = "%$search%";
        $stmt->execute([$search_term, $search_term, $search_term]);
        $medicines = $stmt->fetchAll();
    } catch (PDOException $e) {
        $medicines = [];
    }
}

// Calculate cart totals
$cart_total = 0;
$cart_count = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['unit_price'] * $item['quantity'];
    $cart_count += $item['quantity'];
}

$page_title = 'Sales Cart';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-cart"></i> Sales Cart</h2>
                <div class="d-flex gap-2">
                    <span class="badge bg-primary fs-6">Items: <?php echo count($_SESSION['cart']); ?></span>
                    <span class="badge bg-success fs-6">Total: <?php echo format_currency($cart_total); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Search Medicines -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-search"></i> Search Medicines</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by name, category, or batch..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                    
                    <?php if ($search && !empty($medicines)): ?>
                        <div class="list-group">
                            <?php foreach ($medicines as $medicine): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($medicine['medicine_name']); ?></h6>
                                            <p class="mb-1">
                                                <small class="text-muted">
                                                    Batch: <?php echo htmlspecialchars($medicine['batch_number']); ?> | 
                                                    Expiry: <?php echo format_date($medicine['expiry_date']); ?>
                                                </small>
                                            </p>
                                            <small class="text-success fw-bold">
                                                Stock: <?php echo $medicine['quantity']; ?> | 
                                                Price: <?php echo format_currency($medicine['unit_price']); ?>
                                            </small>
                                        </div>
                                        <form method="POST" class="d-flex align-items-center gap-2">
                                            <input type="hidden" name="action" value="add_to_cart">
                                            <input type="hidden" name="batch_id" value="<?php echo $medicine['id']; ?>">
                                            <input type="number" name="quantity" class="form-control form-control-sm" 
                                                   min="1" max="<?php echo $medicine['quantity']; ?>" value="1" style="width: 60px;">
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($search && empty($medicines)): ?>
                        <div class="text-center py-3">
                            <i class="bi bi-search text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2">No medicines found</p>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="bi bi-search text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2">Search for medicines to add to cart</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Cart Items -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-cart-check"></i> Cart Items</h5>
                    <?php if (!empty($_SESSION['cart'])): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="clear_cart">
                            <button type="submit" class="btn btn-sm btn-outline-danger" 
                                    onclick="return confirm('Clear all items from cart?')">
                                <i class="bi bi-trash"></i> Clear Cart
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($_SESSION['cart'])): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-cart-x text-muted" style="font-size: 3rem;"></i>
                            <h5 class="text-muted mt-3">Your cart is empty</h5>
                            <p class="text-muted">Search for medicines and add them to your cart</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Medicine</th>
                                        <th>Batch</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($_SESSION['cart'] as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['medicine_name']); ?></strong>
                                            <br><small class="text-muted">Expiry: <?php echo format_date($item['expiry_date']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['batch_number']); ?></td>
                                        <td><?php echo format_currency($item['unit_price']); ?></td>
                                        <td>
                                            <form method="POST" class="d-flex align-items-center gap-2">
                                                <input type="hidden" name="action" value="update_cart">
                                                <input type="hidden" name="batch_id" value="<?php echo $item['batch_id']; ?>">
                                                <input type="number" name="quantity" class="form-control form-control-sm" 
                                                       min="1" value="<?php echo $item['quantity']; ?>" style="width: 80px;">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="fw-bold"><?php echo format_currency($item['unit_price'] * $item['quantity']); ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="remove_from_cart">
                                                <input type="hidden" name="batch_id" value="<?php echo $item['batch_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                        onclick="return confirm('Remove this item from cart?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-success">
                                        <th colspan="4">Total Amount</th>
                                        <th><?php echo format_currency($cart_total); ?></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-3">
                            <a href="checkout.php" class="btn btn-success btn-lg">
                                <i class="bi bi-credit-card"></i> Proceed to Checkout
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
