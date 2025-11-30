<?php
require_once __DIR__.'/../../models/auth.php';
require_once __DIR__.'/../../models/rbac.php';
require_once __DIR__.'/../../models/helpers.php';

require_login();
require_role('Customer');

$medicine_id = intval($_GET['id'] ?? 0);

if (!$medicine_id) {
    echo '<div class="alert alert-danger">Invalid medicine ID.</div>';
    exit;
}

try {
    // Get medicine details
    $stmt = $pdo->prepare("
        SELECT m.*, 
               MIN(mb.unit_price) as min_price,
               MAX(mb.unit_price) as max_price,
               SUM(mb.quantity) as total_quantity,
               MIN(mb.expiry_date) as nearest_expiry
        FROM medicines m
        JOIN medicine_batches mb ON m.id = mb.medicine_id
        WHERE m.id = ? AND mb.quantity > 0 AND mb.expiry_date > CURDATE()
        GROUP BY m.id
    ");
    $stmt->execute([$medicine_id]);
    $medicine = $stmt->fetch();
    
    if (!$medicine) {
        echo '<div class="alert alert-danger">Medicine not found or not available.</div>';
        exit;
    }
    
    // Get available batches
    $stmt = $pdo->prepare("
        SELECT mb.*, 
               CASE 
                   WHEN mb.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'warning'
                   WHEN mb.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 'info'
                   ELSE 'success'
               END as expiry_status
        FROM medicine_batches mb
        WHERE mb.medicine_id = ? AND mb.quantity > 0 AND mb.expiry_date > CURDATE()
        ORDER BY mb.expiry_date ASC, mb.unit_price ASC
    ");
    $stmt->execute([$medicine_id]);
    $batches = $stmt->fetchAll();
    
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error loading medicine details.</div>';
    exit;
}
?>

<div class="row">
    <div class="col-md-4">
        <div class="text-center">
            <i class="bi bi-capsule text-primary" style="font-size: 5rem;"></i>
            <h4 class="mt-3"><?php echo htmlspecialchars($medicine['name']); ?></h4>
            <span class="badge bg-info fs-6"><?php echo htmlspecialchars($medicine['category']); ?></span>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="row mb-3">
            <div class="col-sm-4">
                <strong>Price Range:</strong>
            </div>
            <div class="col-sm-8">
                <span class="text-success fs-5">
                    <?php if ($medicine['min_price'] == $medicine['max_price']): ?>
                        ₹<?php echo number_format($medicine['min_price'], 2); ?>
                    <?php else: ?>
                        ₹<?php echo number_format($medicine['min_price'], 2); ?> - ₹<?php echo number_format($medicine['max_price'], 2); ?>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-sm-4">
                <strong>Available Quantity:</strong>
            </div>
            <div class="col-sm-8">
                <span class="text-primary fs-5"><?php echo $medicine['total_quantity']; ?> units</span>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-sm-4">
                <strong>Nearest Expiry:</strong>
            </div>
            <div class="col-sm-8">
                <span class="text-muted"><?php echo format_date($medicine['nearest_expiry']); ?></span>
            </div>
        </div>
        
        <?php if ($medicine['description']): ?>
            <div class="row mb-3">
                <div class="col-sm-4">
                    <strong>Description:</strong>
                </div>
                <div class="col-sm-8">
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($medicine['description'])); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<hr>

<h5 class="mb-3">
    <i class="bi bi-boxes"></i> Available Batches
</h5>

<?php if (empty($batches)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> No available batches found.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Batch Number</th>
                    <th>Expiry Date</th>
                    <th>Unit Price</th>
                    <th>Available Qty</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($batches as $batch): ?>
                    <tr>
                        <td>
                            <code><?php echo htmlspecialchars($batch['batch_number']); ?></code>
                        </td>
                        <td>
                            <?php echo format_date($batch['expiry_date']); ?>
                            <?php
                            $days_to_expiry = (strtotime($batch['expiry_date']) - time()) / (60 * 60 * 24);
                            if ($days_to_expiry <= 30) {
                                echo '<br><small class="text-warning"><i class="bi bi-exclamation-triangle"></i> Expires soon</small>';
                            }
                            ?>
                        </td>
                        <td>
                            <span class="fw-bold text-success">₹<?php echo number_format($batch['unit_price'], 2); ?></span>
                        </td>
                        <td>
                            <span class="badge bg-primary"><?php echo $batch['quantity']; ?> units</span>
                        </td>
                        <td>
                            <?php
                            $status_class = '';
                            $status_text = '';
                            switch ($batch['expiry_status']) {
                                case 'warning':
                                    $status_class = 'bg-warning';
                                    $status_text = 'Expires Soon';
                                    break;
                                case 'info':
                                    $status_class = 'bg-info';
                                    $status_text = 'Good';
                                    break;
                                case 'success':
                                    $status_class = 'bg-success';
                                    $status_text = 'Fresh';
                                    break;
                            }
                            ?>
                            <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-success" 
                                    onclick="addBatchToCart(<?php echo $batch['id']; ?>, '<?php echo htmlspecialchars($batch['batch_number']); ?>', <?php echo $batch['unit_price']; ?>)">
                                <i class="bi bi-cart-plus"></i> Add to Cart
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script>
function addBatchToCart(batchId, batchNumber, price) {
    // For now, just show an alert
    // In a real implementation, this would add the specific batch to cart
    showAlert(`Added batch ${batchNumber} (₹${price.toFixed(2)}) to cart! (Feature coming soon)`, 'success');
}
</script>


