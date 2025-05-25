<?php
// admin_logout.php
require_once 'admin_config.php'; // For ADMIN_AUTH_COOKIE_NAME

// Clear the admin authentication cookie
setcookie(ADMIN_AUTH_COOKIE_NAME, '', [
    'expires' => time() - 3600, // Past time to delete
    'path' => '/', // MUST match the path used when setting the cookie
    'domain' => '', // Should match domain used when setting
    'secure' => isset($_SERVER["HTTPS"]),
    'httponly' => true,
    'samesite' => 'Lax' // Should match samesite used when setting
]);

// Redirect to the login page
header('Location: admin_login.php?logout=success'); // Optional: message indicating logout
exit;
?>