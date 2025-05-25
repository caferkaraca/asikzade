<?php
require_once 'config.php'; // Sadece session_start() için

// PHP session'ını temizle
$_SESSION = array(); // Session değişkenlerini boşalt
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

header('Location: login.php');
exit;