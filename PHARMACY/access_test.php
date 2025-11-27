<?php
// Access Test Page
echo "<h1>Pharmacy Management System - Access Test</h1>";

echo "<h2>Correct Access URLs:</h2>";
echo "<ul>";
echo "<li><strong>Landing Page:</strong> <a href='public/landing.php'>http://localhost/pharmacy/public/landing.php</a></li>";
echo "<li><strong>Login Page:</strong> <a href='public/login.php'>http://localhost/pharmacy/public/login.php</a></li>";
echo "<li><strong>Registration:</strong> <a href='public/register.php'>http://localhost/pharmacy/public/register.php</a></li>";
echo "</ul>";

echo "<h2>❌ Incorrect URLs (Don't use these):</h2>";
echo "<ul>";
echo "<li>❌ http://localhost/pharmacy/public/modules/dashboard/customer.php</li>";
echo "<li>❌ http://localhost/pharmacy/public/modules/dashboard/admin.php</li>";
echo "<li>❌ http://localhost/pharmacy/public/modules/dashboard/supplier.php</li>";
echo "</ul>";

echo "<h2>✅ Correct Access Flow:</h2>";
echo "<ol>";
echo "<li>Go to: <a href='public/landing.php'>Landing Page</a></li>";
echo "<li>Click 'Login' or go to: <a href='public/login.php'>Login Page</a></li>";
echo "<li>Login with: <strong>admin</strong> / <strong>admin123</strong></li>";
echo "<li>You'll be automatically redirected to the correct dashboard</li>";
echo "</ol>";

echo "<h2>Test Login:</h2>";
echo "<p><a href='public/login.php' class='btn btn-primary'>Go to Login Page</a></p>";

echo "<h2>Dashboard URLs (after login):</h2>";
echo "<ul>";
echo "<li><strong>Admin:</strong> http://localhost/pharmacy/modules/dashboard/admin.php</li>";
echo "<li><strong>Pharmacist:</strong> http://localhost/pharmacy/modules/dashboard/pharmacist.php</li>";
echo "<li><strong>Supplier:</strong> http://localhost/pharmacy/modules/dashboard/supplier.php</li>";
echo "<li><strong>Customer:</strong> http://localhost/pharmacy/modules/dashboard/customer.php</li>";
echo "</ul>";

echo "<p><strong>Note:</strong> These dashboard URLs require authentication and will redirect to login if accessed directly.</p>";
?>



