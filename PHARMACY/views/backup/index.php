<?php
require_once __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/rbac.php';
require_once __DIR__.'/../../includes/helpers.php';

require_login();
require_role('Admin');

$config = require __DIR__.'/../../config/config.php';

// Handle backup/restore actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_backup':
                try {
                    $backup_dir = __DIR__ . '/../../storage/backups/';
                    if (!is_dir($backup_dir)) {
                        mkdir($backup_dir, 0755, true);
                    }
                    
                    $backup_filename = 'pms_backup_' . date('Y-m-d_H-i-s') . '.sql';
                    $backup_path = $backup_dir . $backup_filename;
                    
                    // Create mysqldump command
                    $command = sprintf(
                        'mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
                        escapeshellarg($config['db_host']),
                        escapeshellarg($config['db_user']),
                        escapeshellarg($config['db_pass']),
                        escapeshellarg($config['db_name']),
                        escapeshellarg($backup_path)
                    );
                    
                    $output = [];
                    $return_code = 0;
                    exec($command, $output, $return_code);
                    
                    if ($return_code === 0 && file_exists($backup_path)) {
                        log_activity($_SESSION['user']['id'], 'Create Backup', "Created backup: $backup_filename");
                        header('Location: index.php?success=Backup created successfully');
                        exit;
                    } else {
                        $error = 'Failed to create backup. Please check MySQL configuration.';
                    }
                } catch (Exception $e) {
                    $error = 'Backup failed: ' . $e->getMessage();
                }
                break;
                
            case 'restore_backup':
                $backup_file = $_POST['backup_file'];
                $backup_path = __DIR__ . '/../../storage/backups/' . $backup_file;
                
                if (!file_exists($backup_path)) {
                    $error = 'Backup file not found.';
                } else {
                    try {
                        // Create mysql command for restore
                        $command = sprintf(
                            'mysql --host=%s --user=%s --password=%s %s < %s',
                            escapeshellarg($config['db_host']),
                            escapeshellarg($config['db_user']),
                            escapeshellarg($config['db_pass']),
                            escapeshellarg($config['db_name']),
                            escapeshellarg($backup_path)
                        );
                        
                        $output = [];
                        $return_code = 0;
                        exec($command, $output, $return_code);
                        
                        if ($return_code === 0) {
                            log_activity($_SESSION['user']['id'], 'Restore Backup', "Restored backup: $backup_file");
                            header('Location: index.php?success=Database restored successfully');
                            exit;
                        } else {
                            $error = 'Failed to restore backup. Please check the backup file.';
                        }
                    } catch (Exception $e) {
                        $error = 'Restore failed: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'delete_backup':
                $backup_file = $_POST['backup_file'];
                $backup_path = __DIR__ . '/../../storage/backups/' . $backup_file;
                
                if (file_exists($backup_path)) {
                    if (unlink($backup_path)) {
                        log_activity($_SESSION['user']['id'], 'Delete Backup', "Deleted backup: $backup_file");
                        header('Location: index.php?success=Backup deleted successfully');
                        exit;
                    } else {
                        $error = 'Failed to delete backup file.';
                    }
                } else {
                    $error = 'Backup file not found.';
                }
                break;
        }
    }
}

// Get list of backup files
$backup_dir = __DIR__ . '/../../storage/backups/';
$backup_files = [];

if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $file_path = $backup_dir . $file;
            $backup_files[] = [
                'name' => $file,
                'size' => filesize($file_path),
                'created' => filemtime($file_path)
            ];
        }
    }
    
    // Sort by creation time (newest first)
    usort($backup_files, function($a, $b) {
        return $b['created'] - $a['created'];
    });
}

// Get database info
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = ?");
    $stmt->execute([$config['db_name']]);
    $table_count = $stmt->fetch()['table_count'];
    
    $stmt = $pdo->prepare("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb FROM information_schema.tables WHERE table_schema = ?");
    $stmt->execute([$config['db_name']]);
    $db_size = $stmt->fetch()['size_mb'];
} catch (PDOException $e) {
    $table_count = 0;
    $db_size = 0;
}

$page_title = 'Backup & Restore';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-shield-check"></i> Backup & Restore</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBackupModal">
                    <i class="bi bi-plus-circle"></i> Create Backup
                </button>
            </div>
        </div>
    </div>
    
    <!-- Database Info -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $table_count; ?></h4>
                            <p class="mb-0">Database Tables</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-table" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $db_size; ?> MB</h4>
                            <p class="mb-0">Database Size</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-hdd" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo count($backup_files); ?></h4>
                            <p class="mb-0">Backup Files</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-archive" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Backup Files -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-archive"></i> Backup Files</h5>
        </div>
        <div class="card-body">
            <?php if (empty($backup_files)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-archive text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No backup files found</h5>
                    <p class="text-muted">Create your first backup to get started</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Size</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backup_files as $backup): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($backup['name']); ?></strong>
                                </td>
                                <td><?php echo number_format($backup['size'] / 1024, 2); ?> KB</td>
                                <td><?php echo format_datetime($backup['created']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-success" 
                                                onclick="restoreBackup('<?php echo htmlspecialchars($backup['name']); ?>')" 
                                                title="Restore">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                        <a href="download.php?file=<?php echo urlencode($backup['name']); ?>" 
                                           class="btn btn-outline-primary" title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <button class="btn btn-outline-danger" 
                                                onclick="deleteBackup('<?php echo htmlspecialchars($backup['name']); ?>')" 
                                                title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Important Notes -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="alert alert-warning">
                <h5><i class="bi bi-exclamation-triangle"></i> Important Notes</h5>
                <ul class="mb-0">
                    <li>Backups are stored in the <code>storage/backups/</code> directory</li>
                    <li>Restoring a backup will replace all current data</li>
                    <li>Make sure to create a backup before making major changes</li>
                    <li>Backup files are automatically named with timestamp</li>
                    <li>Regular backups are recommended for data safety</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Create Backup Modal -->
<div class="modal fade" id="createBackupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Create Backup</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <h6><i class="bi bi-info-circle"></i> Backup Information</h6>
                    <p class="mb-0">This will create a complete backup of your database including all tables, data, and structure.</p>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="create_backup">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-shield-check"></i> Create Backup Now
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Restore Backup Modal -->
<div class="modal fade" id="restoreBackupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-clockwise"></i> Restore Backup</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <h6><i class="bi bi-exclamation-triangle"></i> Warning!</h6>
                    <p class="mb-0">This will replace all current data with the backup data. This action cannot be undone.</p>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="restore_backup">
                    <input type="hidden" name="backup_file" id="restore_backup_file">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-arrow-clockwise"></i> Restore Backup
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Backup Modal -->
<div class="modal fade" id="deleteBackupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-trash"></i> Delete Backup</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <h6><i class="bi bi-exclamation-triangle"></i> Confirm Deletion</h6>
                    <p class="mb-0">Are you sure you want to delete this backup file? This action cannot be undone.</p>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="delete_backup">
                    <input type="hidden" name="backup_file" id="delete_backup_file">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Delete Backup
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function restoreBackup(filename) {
    document.getElementById('restore_backup_file').value = filename;
    new bootstrap.Modal(document.getElementById('restoreBackupModal')).show();
}

function deleteBackup(filename) {
    document.getElementById('delete_backup_file').value = filename;
    new bootstrap.Modal(document.getElementById('deleteBackupModal')).show();
}
</script>

<?php include '../../includes/footer.php'; ?>
