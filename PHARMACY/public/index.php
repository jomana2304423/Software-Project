<?php
ob_start(); // Start output buffering to prevent "headers already sent" errors
require_once __DIR__.'/../models/auth.php';
require_once __DIR__.'/../models/rbac.php';
require_once __DIR__.'/../models/helpers.php';

// Redirect to landing page if not logged in
if (!is_logged_in()) {
    header('Location: landing.php');
    exit;
}

enforce_session_timeout(30);

$role = $_SESSION['user']['role'] ?? '';

// Redirect based on role
switch ($role) {
    case 'Admin':
        header('Location: ../views/dashboard/admin.php');
        break;
    case 'Pharmacist':
        header('Location: ../views/dashboard/pharmacist.php');
        break;
    case 'Supplier':
        header('Location: ../views/dashboard/supplier.php');
        break;
    case 'Customer':
        header('Location: ../views/dashboard/customer.php');
        break;
    default:
        header('Location: landing.php');
        break;
}
exit;
?>
