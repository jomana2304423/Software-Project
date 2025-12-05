<?php
echo "<!-- DEBUG: Loading auth.php -->"; // Temporary debug output
require_once __DIR__.'/../app/config/db.php';
require_once __DIR__.'/../app/config/config.php';

session_start();

function login($username, $password) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, r.name AS role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.username = ? AND u.is_active = 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role_name'],
                'last_activity' => time()
            ];
            
            // Log login activity
            log_activity($user['id'], 'User login', "User {$username} logged in");
            
            return true;
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
    }
    return false;
}

function require_login() {
    if (empty($_SESSION['user'])) {
        // Get the current directory to determine the correct path
        $current_dir = dirname($_SERVER['PHP_SELF']);
        if (strpos($current_dir, '/modules') !== false) {
            header('Location: ../../public/login.php');
        } else {
            header('Location: ../public/login.php');
        }
        exit;
    }
}

function logout() {
    if (!empty($_SESSION['user'])) {
        log_activity($_SESSION['user']['id'], 'User logout', "User logged out");
    }
    session_destroy();
}

function enforce_session_timeout($minutes) {
    if (!empty($_SESSION['user'])) {
        $inactive = time() - ($_SESSION['user']['last_activity'] ?? time());
        if ($inactive > ($minutes * 60)) {
            logout();
            // Get the current directory to determine the correct path
            $current_dir = dirname($_SERVER['PHP_SELF']);
            if (strpos($current_dir, '/modules') !== false) {
                header('Location: ../../public/login.php?timeout=1');
            } else {
                header('Location: ../public/login.php?timeout=1');
            }
            exit;
        }
        $_SESSION['user']['last_activity'] = time();
    }
}

function log_activity($user_id, $action, $details = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $action, $details]);
    } catch (PDOException $e) {
        error_log("Activity log error: " . $e->getMessage());
    }
}

function get_current_user_session() {
    return $_SESSION['user'] ?? null;
}

function is_logged_in() {
    return !empty($_SESSION['user']);
}
?>
