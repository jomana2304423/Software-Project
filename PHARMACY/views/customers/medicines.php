<?php
require_once __DIR__.'/../../models/auth.php';
require_once __DIR__.'/../../models/rbac.php';
require_once __DIR__.'/../../models/helpers.php';

require_login();
require_role('Customer');

$config = require __DIR__.'/../../app/config/config.php';

// Get search and filter parameters
$search = sanitize_input($_GET['search'] ?? '');
$category = sanitize_input($_GET['category'] ?? '');
$sort = sanitize_input($_GET['sort'] ?? 'name');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = ["mb.quantity > 0", "mb.expiry_date > CURDATE()"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(m.name LIKE ? OR m.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $where_conditions[] = "m.category = ?";
    $params[] = $category;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
try {
    $count_sql = "
        SELECT COUNT(DISTINCT m.id) as total
        FROM medicines m
        JOIN medicine_batches mb ON m.id = mb.medicine_id
        WHERE $where_clause
    ";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_medicines = $stmt->fetch()['total'];
} catch (PDOException $e) {
    $total_medicines = 0;
}

// Get medicines with available batches
try {
    $sql = "
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
        WHERE $where_clause
        GROUP BY m.id, m.name, m.category, m.description
        ORDER BY 
            CASE WHEN ? = 'price_low' THEN MIN(mb.unit_price) END ASC,
            CASE WHEN ? = 'price_high' THEN MIN(mb.unit_price) END DESC,
            CASE WHEN ? = 'name' THEN m.name END ASC,
            CASE WHEN ? = 'category' THEN m.category END ASC,
            CASE WHEN ? = 'expiry' THEN MIN(mb.expiry_date) END ASC
        LIMIT ? OFFSET ?
    ";
    
    $sort_params = array_fill(0, 5, $sort);
    $all_params = array_merge($sort_params, $params, [$per_page, $offset]);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($all_params);
    $medicines = $stmt->fetchAll();
} catch (PDOException $e) {
    $medicines = [];
}

// Get categories for filter
try {
    $stmt = $pdo->prepare("SELECT DISTINCT category FROM medicines WHERE category IS NOT NULL ORDER BY category");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $categories = [];
}

$page_title = 'Medicine Catalog';
include '../../views/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="bi bi-capsule"></i> Medicine Catalog
                <small class="text-muted">Browse and order medicines</small>
            </h2>
        </div>
    </div>
    
    <!-- Search and Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search medicines..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <select class="form-select" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" 
                                            <?php echo $category === $cat ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <select class="form-select" name="sort">
                                <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Sort by Name</option>
                                <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="category" <?php echo $sort === 'category' ? 'selected' : ''; ?>>Sort by Category</option>
                                <option value="expiry" <?php echo $sort === 'expiry' ? 'selected' : ''; ?>>Sort by Expiry</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Results Summary -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <p class="mb-0 text-muted">
                    Showing <?php echo count($medicines); ?> of <?php echo $total_medicines; ?> medicines
                </p>
                <?php if (!empty($search) || !empty($category)): ?>
                    <a href="medicines.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle"></i> Clear Filters
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Medicine Grid -->
    <?php if (empty($medicines)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-capsule text-muted" style="font-size: 4rem;"></i>
                        <h4 class="mt-3 text-muted">No medicines found</h4>
                        <p class="text-muted">
                            <?php if (!empty($search) || !empty($category)): ?>
                                Try adjusting your search criteria or filters.
                            <?php else: ?>
                                No medicines are currently available.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($search) || !empty($category)): ?>
                            <a href="medicines.php" class="btn btn-primary">
                                <i class="bi bi-arrow-clockwise"></i> View All Medicines
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($medicines as $medicine): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="card h-100 medicine-card">
                        <div class="card-body d-flex flex-column">
                            <div class="text-center mb-3">
                                <i class="bi bi-capsule text-primary" style="font-size: 3rem;"></i>
                            </div>
                            
                            <h5 class="card-title text-center">
                                <?php echo htmlspecialchars($medicine['name']); ?>
                            </h5>
                            
                            <div class="text-center mb-2">
                                <span class="badge bg-info"><?php echo htmlspecialchars($medicine['category']); ?></span>
                            </div>
                            
                            <?php if ($medicine['description']): ?>
                                <p class="card-text text-muted small flex-grow-1">
                                    <?php echo htmlspecialchars(substr($medicine['description'], 0, 100)); ?>
                                    <?php if (strlen($medicine['description']) > 100): ?>...<?php endif; ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="mt-auto">
                                <div class="row text-center mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Price Range</small>
                                        <div class="fw-bold text-success">
                                            <?php if ($medicine['min_price'] == $medicine['max_price']): ?>
                                                ₹<?php echo number_format($medicine['min_price'], 2); ?>
                                            <?php else: ?>
                                                ₹<?php echo number_format($medicine['min_price'], 2); ?> - ₹<?php echo number_format($medicine['max_price'], 2); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Available</small>
                                        <div class="fw-bold text-primary">
                                            <?php echo $medicine['total_quantity']; ?> units
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-primary btn-sm" 
                                            onclick="viewMedicineDetails(<?php echo $medicine['id']; ?>)">
                                        <i class="bi bi-eye"></i> View Details
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm" 
                                            onclick="addToCart(<?php echo $medicine['id']; ?>)">
                                        <i class="bi bi-cart-plus"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_medicines > $per_page): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <nav aria-label="Medicine pagination">
                        <ul class="pagination justify-content-center">
                            <?php
                            $total_pages = ceil($total_medicines / $per_page);
                            $current_page = $page;
                            
                            // Previous button
                            if ($current_page > 1):
                                $prev_url = "?page=" . ($current_page - 1);
                                if (!empty($search)) $prev_url .= "&search=" . urlencode($search);
                                if (!empty($category)) $prev_url .= "&category=" . urlencode($category);
                                if (!empty($sort)) $prev_url .= "&sort=" . urlencode($sort);
                            ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo $prev_url; ?>">
                                        <i class="bi bi-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            // Page numbers
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                                $page_url = "?page=" . $i;
                                if (!empty($search)) $page_url .= "&search=" . urlencode($search);
                                if (!empty($category)) $page_url .= "&category=" . urlencode($category);
                                if (!empty($sort)) $page_url .= "&sort=" . urlencode($sort);
                            ?>
                                <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo $page_url; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php
                            // Next button
                            if ($current_page < $total_pages):
                                $next_url = "?page=" . ($current_page + 1);
                                if (!empty($search)) $next_url .= "&search=" . urlencode($search);
                                if (!empty($category)) $next_url .= "&category=" . urlencode($category);
                                if (!empty($sort)) $next_url .= "&sort=" . urlencode($sort);
                            ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo $next_url; ?>">
                                        Next <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Medicine Details Modal -->
<div class="modal fade" id="medicineModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-capsule"></i> Medicine Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="medicineDetails">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="addToCartBtn">
                    <i class="bi bi-cart-plus"></i> Add to Cart
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentMedicineId = null;

function viewMedicineDetails(medicineId) {
    currentMedicineId = medicineId;
    const modal = new bootstrap.Modal(document.getElementById('medicineModal'));
    modal.show();
    
    // Load medicine details
    fetch(`medicine_details.php?id=${medicineId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('medicineDetails').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('medicineDetails').innerHTML = 
                '<div class="alert alert-danger">Error loading medicine details.</div>';
        });
}

function addToCart(medicineId) {
    // For now, just show an alert
    // In a real implementation, this would add to a shopping cart
    showAlert('Medicine added to cart! (Feature coming soon)', 'success');
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<style>
.medicine-card {
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.medicine-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.medicine-card .card-body {
    padding: 1.5rem;
}

.medicine-card .bi-capsule {
    color: #0d6efd;
}

.pagination .page-link {
    border-radius: 8px;
    margin: 0 2px;
    border: none;
    color: #0d6efd;
}

.pagination .page-item.active .page-link {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.pagination .page-link:hover {
    background: rgba(13, 110, 253, 0.1);
    transform: translateY(-1px);
}
</style>

<?php include '../../views/footer.php'; ?>


