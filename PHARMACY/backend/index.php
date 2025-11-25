<?php
// Simple working index page
echo "<h1>Pharmacy Management System</h1>";
echo "<p>Welcome to your Pharmacy Management System!</p>";

echo "<h2>Quick Access Links:</h2>";
echo "<ul>";
echo "<li><a href='public/landing.php'>🏠 Landing Page</a></li>";
echo "<li><a href='public/login.php'>🔐 Login Page</a></li>";
echo "<li><a href='public/register.php'>📝 Registration</a></li>";
echo "</ul>";

echo "<h2>Test Pages:</h2>";
echo "<ul>";
echo "<li><a href='test.php'>🧪 System Test</a></li>";
echo "<li><a href='setup_database.php'>🔧 Database Setup</a></li>";
echo "<li><a href='test_db.php'>📊 Database Test</a></li>";
echo "<li><a href='test_login.php'>🔑 Login Test</a></li>";
echo "</ul>";

echo "<h2>Login Credentials:</h2>";
echo "<p><strong>Admin:</strong> admin / admin123</p>";

echo "<hr>";
echo "<p><strong>If you're getting 'Not Found' errors, try these URLs:</strong></p>";
echo "<ul>";
echo "<li>http://localhost/pharmacy/public/landing.php</li>";
echo "<li>http://localhost/pharmacy/public/login.php</li>";
echo "<li>http://localhost/pharmacy/test.php</li>";
echo "</ul>";
?>



