<?php
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/rbac.php';

require_login();
require_role(['Admin', 'Pharmacist']);

$prescription_id = (int)($_GET['id'] ?? 0);

if (!$prescription_id) {
    http_response_code(400);
    die('Invalid prescription ID');
}

// Get prescription details
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as customer_name 
        FROM prescriptions p
        JOIN customers c ON p.customer_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$prescription_id]);
    $prescription = $stmt->fetch();
    
    if (!$prescription) {
        http_response_code(404);
        die('Prescription not found');
    }
} catch (PDOException $e) {
    http_response_code(500);
    die('Database error');
}

$file_path = __DIR__ . '/../../storage/prescriptions/' . $prescription['file_path'];

if (!file_exists($file_path)) {
    http_response_code(404);
    die('File not found');
}

// Get file info
$file_extension = strtolower(pathinfo($prescription['file_path'], PATHINFO_EXTENSION));
$mime_type = '';

switch ($file_extension) {
    case 'jpg':
    case 'jpeg':
        $mime_type = 'image/jpeg';
        break;
    case 'png':
        $mime_type = 'image/png';
        break;
    case 'gif':
        $mime_type = 'image/gif';
        break;
    case 'pdf':
        $mime_type = 'application/pdf';
        break;
    default:
        http_response_code(400);
        die('Unsupported file type');
}

// Set headers
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: private, max-age=3600');

// For images, allow inline display
if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
    header('Content-Disposition: inline; filename="' . $prescription['file_path'] . '"');
} else {
    header('Content-Disposition: attachment; filename="' . $prescription['file_path'] . '"');
}

// Clear any output buffering
while (ob_get_level()) {
    ob_end_clean();
}

// Output file
readfile($file_path);
exit;
?>
