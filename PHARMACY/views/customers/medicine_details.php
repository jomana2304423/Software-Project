<?php
session_start();
require_once __DIR__.'/../../models/auth.php';
require_once __DIR__.'/../../models/rbac.php';
require_once __DIR__.'/../../models/helpers.php';

require_login();
require_role('Customer');

$config = require __DIR__.'/../../app/config/config.php';

$medicine_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$medicine = null;
if ($medicine_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                m.id,
                m.name,
                m.category,
                m.description,
                MIN(mb.unit_price) as min_price,
                MAX(mb.unit_price) as max_price,
                SUM(mb.quantity) as total_quantity,
                MIN(mb.expiry_date) as nearest_expiry
            FROM medicines m
            JOIN medicine_batches mb ON m.id = mb.medicine_id
            WHERE m.id = ? AND mb.quantity > 0 AND mb.expiry_date > CURDATE()
            GROUP BY m.id, m.name, m.category, m.description
        ");
        $stmt->execute([$medicine_id]);
        $medicine = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching medicine details: " . $e->getMessage());
    }
}

if (!$medicine): ?>
    <div class="alert alert-danger text-center" role="alert">
        Medicine not found or unavailable.
    </div>
<?php else: ?>
    <div class="row">
        <div class="col-md-5 text-center">
            <!-- Placeholder image for medicine -->
            <img src="https://via.placeholder.com/200/0d6efd/FFFFFF?text=<?php echo urlencode($medicine['name']); ?>" 
                 class="img-fluid rounded mb-3" alt="<?php echo htmlspecialchars($medicine['name']); ?>">
            <p><small class="text-muted">Category: <?php echo htmlspecialchars($medicine['category']); ?></small></p>
        </div>
        <div class="col-md-7">
            <h4><?php echo htmlspecialchars($medicine['name']); ?></h4>
            <p class="lead text-muted"><?php echo htmlspecialchars($medicine['description']); ?></p>
            <hr>
            <p class="fs-5 mb-1">
                <strong>Price: </strong>
                <?php if ($medicine['min_price'] == $medicine['max_price']): ?>
                    <span class="text-success">₹<?php echo number_format($medicine['min_price'], 2); ?></span>
                <?php else: ?>
                    <span class="text-success">₹<?php echo number_format($medicine['min_price'], 2); ?> - ₹<?php echo number_format($medicine['max_price'], 2); ?></span>
                <?php endif; ?>
            </p>
            <p class="fs-5 mb-1">
                <strong>Availability: </strong>
                <span class="text-primary"><?php echo $medicine['total_quantity']; ?> units</span>
            </p>
            <p class="fs-5 mb-3">
                <strong>Nearest Expiry: </strong>
                <span class="text-warning"><?php echo format_date($medicine['nearest_expiry']); ?></span>
            </p>
            
            <div class="d-flex align-items-center mb-3">
                <label for="quantity" class="form-label me-2 mb-0">Quantity:</label>
                <input type="number" id="medicineQuantity" class="form-control w-25" value="1" min="1" max="<?php echo $medicine['total_quantity']; ?>">
            </div>

            <button type="button" class="btn btn-success mt-3" 
                    onclick="addToCartFromModal(<?php echo $medicine['id']; ?>, document.getElementById('medicineQuantity').value)">
                <i class="bi bi-cart-plus"></i> Add to Cart
            </button>
        </div>
    </div>
<?php endif; ?>

<script>
    // This script is for the modal, not directly part of the medicine_details.php file itself
    function addToCartFromModal(medicineId, quantity) {
        // In a real application, this would send an AJAX request to add to cart
        console.log(`Adding medicine ${medicineId} with quantity ${quantity} to cart.`);
        showAlert(`Added ${quantity} of Medicine ID ${medicineId} to cart!`, 'success');
        // Optionally close modal
        bootstrap.Modal.getInstance(document.getElementById('medicineModal')).hide();
    }
</script>


