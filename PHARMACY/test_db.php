<?php
// Database Connection Test
echo "<h1>Database Connection Test</h1>";

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=pms_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test admin user
    $stmt = $pdo->prepare("SELECT username, password_hash FROM users WHERE username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p style='color: green;'>✓ Admin user found</p>";
        echo "<p>Username: " . $user['username'] . "</p>";
        
        // Test password
        if (password_verify('admin123', $user['password_hash'])) {
            echo "<p style='color: green;'>✓ Password verification successful</p>";
        } else {
            echo "<p style='color: red;'>✗ Password verification failed</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Admin user not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}
?>



