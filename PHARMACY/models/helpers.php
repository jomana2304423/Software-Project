<?php
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function format_currency($amount) {
    return '₹' . number_format($amount, 2);
}

function format_date($date) {
    return date('d M Y', strtotime($date));
}

function format_datetime($datetime) {
    return date('d M Y H:i', strtotime($datetime));
}

function generate_invoice_number() {
    return 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function generate_po_number() {
    return 'PO-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function show_alert($message, $type = 'info') {
    $alert_class = 'alert-' . $type;
    echo "<div class='alert $alert_class alert-dismissible fade show' role='alert'>";
    echo htmlspecialchars($message);
    echo "<button type='button' class='btn-close' data-bs-dismiss='alert'></button>";
    echo "</div>";
}

function get_low_stock_count() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM medicine_batches mb 
            JOIN medicines m ON mb.medicine_id = m.id 
            WHERE mb.quantity <= m.reorder_level
        ");
        $stmt->execute();
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        return 0;
    }
}

function get_expiry_count() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM medicine_batches 
            WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        return 0;
    }
}

function get_today_sales_count() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM sales 
            WHERE DATE(created_at) = CURDATE()
        ");
        $stmt->execute();
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        return 0;
    }
}

function get_unread_notifications_count() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0");
        $stmt->execute();
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        return 0;
    }
}

// Supplier-specific functions
function get_supplier_id_by_user($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id FROM suppliers WHERE contact_name = (SELECT full_name FROM users WHERE id = ?)");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result ? $result['id'] : null;
    } catch (PDOException $e) {
        return null;
    }
}

function get_supplier_pending_orders_count() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM purchase_orders 
            WHERE status = 'Pending'
        ");
        $stmt->execute();
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        return 0;
    }
}

function get_supplier_completed_orders_count() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM purchase_orders 
            WHERE status = 'Delivered'
        ");
        $stmt->execute();
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        return 0;
    }
}

function get_supplier_products_count() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT m.id) as count 
            FROM medicines m
            JOIN medicine_batches mb ON m.id = mb.medicine_id
        ");
        $stmt->execute();
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        return 0;
    }
}

// Customer-specific functions
function get_customer_id_by_user($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE name = (SELECT full_name FROM users WHERE id = ?)");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result ? $result['id'] : null;
    } catch (PDOException $e) {
        return null;
    }
}

function get_customer_pending_prescriptions_count() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM prescriptions 
            WHERE status = 'Pending'
        ");
        $stmt->execute();
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        return 0;
    }
}

function get_customer_completed_orders_count() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM sales s
            JOIN customers c ON s.customer_id = c.id
        ");
        $stmt->execute();
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        return 0;
    }
}

function get_customer_total_orders_count() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM sales s
            JOIN customers c ON s.customer_id = c.id
        ");
        $stmt->execute();
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        return 0;
    }
}
?>
