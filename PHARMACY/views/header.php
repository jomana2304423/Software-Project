<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Pharmacy Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="public/assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php if (is_logged_in()): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-hospital"></i> PMS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-house"></i> Dashboard
                        </a>
                    </li>
                    
                    <?php if (can_process_sales()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../views/sales/cart.php">
                            <i class="bi bi-cart"></i> Sales
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (is_admin() || is_pharmacist()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../views/medicines/list.php">
                            <i class="bi bi-capsule"></i> Medicines
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (can_manage_suppliers()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../views/suppliers/list.php">
                            <i class="bi bi-truck"></i> Suppliers
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (is_supplier()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../views/suppliers/products.php">
                            <i class="bi bi-box"></i> My Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../views/suppliers/orders.php">
                            <i class="bi bi-list-check"></i> Orders
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (is_customer()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../views/customers/medicines.php">
                            <i class="bi bi-capsule"></i> Medicine Catalog
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../views/prescriptions/upload.php">
                            <i class="bi bi-cloud-upload"></i> Upload Prescription
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../views/prescriptions/view.php">
                            <i class="bi bi-file-medical"></i> My Prescriptions
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (can_view_reports()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../views/reports/index.php">
                            <i class="bi bi-graph-up"></i> Reports
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (can_manage_users()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../views/users/list.php">
                            <i class="bi bi-people"></i> Users
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user']['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <main class="container-fluid py-4">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
