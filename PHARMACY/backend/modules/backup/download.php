<?php
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/rbac.php';

require_login();
require_role('Admin');

$filename = $_GET['file'] ?? '';

if (!$filename) {
    http_response_code(400);
    die('Invalid file parameter');
}

// Sanitize filename
$filename = basename($filename);
$file_path = __DIR__ . '/../../storage/backups/' . $filename;

if (!file_exists($file_path)) {
    http_response_code(404);
    die('File not found');
}

// Check if file is a SQL backup
if (pathinfo($filename, PATHINFO_EXTENSION) !== 'sql') {
    http_response_code(403);
    die('Invalid file type');
}

// Set headers for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Clear any output buffering
while (ob_get_level()) {
    ob_end_clean();
}

// Read and output file
readfile($file_path);
exit;
?>
