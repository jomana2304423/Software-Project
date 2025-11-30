<?php
require_once __DIR__.'/../../models/auth.php';
require_once __DIR__.'/../../models/rbac.php';
require_once __DIR__.'/../../models/helpers.php';

require_login();
require_role('Admin');

$config = require __DIR__.'/../../app/config/config.php';

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_user':
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $full_name = trim($_POST['full_name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $role_id = intval($_POST['role_id'] ?? 0);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                if ($username && $password && $full_name && $email && $role_id) {
                    try {
                        // Check if username exists
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                        $stmt->execute([$username]);
                        if ($stmt->fetch()) {
                            $error = 'Username already exists.';
                        } else {
                            $password_hash = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("
                                INSERT INTO users (username, password_hash, full_name, email, role_id, is_active) 
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([$username, $password_hash, $full_name, $email, $role_id, $is_active]);
                            
                            $success = 'User created successfully!';
                            $_POST = []; // Clear form
                        }
                    } catch (PDOException $e) {
                        $error = 'Failed to create user.';
                    }
                } else {
                    $error = 'All fields are required.';
                }
                break;
                
            case 'toggle_status':
                $user_id = intval($_POST['user_id'] ?? 0);
                if ($user_id && $user_id != $_SESSION['user']['id']) {
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
                        $stmt->execute([$user_id]);
                        $success = 'User status updated successfully!';
                    } catch (PDOException $e) {
                        $error = 'Failed to update user status.';
                    }
                }
                break;
                
            case 'delete_user':
                $user_id = intval($_POST['user_id'] ?? 0);
                if ($user_id && $user_id != $_SESSION['user']['id']) {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                        $stmt->execute([$user_id]);
                        $success = 'User deleted successfully!';
                    } catch (PDOException $e) {
                        $error = 'Failed to delete user.';
                    }
                }
                break;
        }
    }
}

// Get all users with their roles
try {
    $stmt = $pdo->prepare("
        SELECT u.*, r.name as role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
}

// Get all roles
try {
    $stmt = $pdo->prepare("SELECT * FROM roles ORDER BY name");
    $stmt->execute();
    $roles = $stmt->fetchAll();
} catch (PDOException $e) {
    $roles = [];
}

$page_title = 'User Management';
include '../../views/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="bi bi-people"></i> User Management
                </h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus"></i> Add User
                </button>
            </div>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success" role="alert">
            <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                            <?php if ($user['id'] == $_SESSION['user']['id']): ?>
                                                <span class="badge bg-primary">You</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($user['role_name']) {
                                                    'Admin' => 'danger',
                                                    'Pharmacist' => 'success',
                                                    'Supplier' => 'warning',
                                                    'Customer' => 'info',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <?php echo htmlspecialchars($user['role_name']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo format_datetime($user['created_at']); ?></td>
                                        <td>
                                            <?php if ($user['id'] != $_SESSION['user']['id']): ?>
                                                <div class="btn-group" role="group">
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-<?php echo $user['is_active'] ? 'warning' : 'success'; ?>" 
                                                                onclick="return confirm('Are you sure you want to <?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?> this user?')">
                                                            <i class="bi bi-<?php echo $user['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                            <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display: inline;" 
                                                          onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">Current User</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_user">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role_id" class="form-label">Role</label>
                        <select class="form-select" id="role_id" name="role_id" required>
                            <option value="">Select role</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../views/footer.php'; ?>