<?php
function require_role($roles) {
    if (empty($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
    
    $allowed = is_array($roles) ? $roles : [$roles];
    if (!in_array($_SESSION['user']['role'], $allowed, true)) {
        http_response_code(403);
        echo '<div class="alert alert-danger">Access denied. You do not have permission to access this page.</div>';
        exit;
    }
}

function is_admin() {
    return !empty($_SESSION['user']) && $_SESSION['user']['role'] === 'Admin';
}

function is_pharmacist() {
    return !empty($_SESSION['user']) && in_array($_SESSION['user']['role'], ['Admin', 'Pharmacist']);
}

function is_supplier() {
    return !empty($_SESSION['user']) && $_SESSION['user']['role'] === 'Supplier';
}

function is_customer() {
    return !empty($_SESSION['user']) && $_SESSION['user']['role'] === 'Customer';
}

function can_manage_users() {
    return is_admin();
}

function can_manage_suppliers() {
    return is_admin();
}

function can_process_sales() {
    return is_pharmacist();
}

function can_view_reports() {
    return is_admin();
}

function can_manage_inventory() {
    return is_admin() || is_supplier();
}

function can_upload_prescriptions() {
    return is_customer();
}

function can_view_orders() {
    return is_customer() || is_supplier();
}
?>
