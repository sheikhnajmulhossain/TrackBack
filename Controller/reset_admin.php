<?php
 

include '../Model/database.php';
include '../Model/functions.php';

$adminEmail = 'admin@trackback.com';
$adminPassword = 'admin123';
$adminName = 'System Administrator';


$result = createOrResetAdmin($conn, $adminEmail, $adminPassword, $adminName);

if ($result['success']) {
    if ($result['action'] === 'reset') {
        echo "<h2>✅ Admin password reset successfully!</h2>";
    } else {
        echo "<h2>✅ Admin created successfully!</h2>";
    }
    echo "<p>Email: <strong>$adminEmail</strong></p>";
    echo "<p>Password: <strong>$adminPassword</strong></p>";
} else {
    echo "<h2>❌ Error: " . $result['error'] . "</h2>";
}

echo "<br><br><p style='color: red; font-weight: bold;'>⚠️ DELETE THIS FILE (reset_admin.php) AFTER USE FOR SECURITY!</p>";
echo "<p><a href='../login.html'>Go to Login Page</a></p>";

$conn->close();
?>
