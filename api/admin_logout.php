<?php
require_once 'admin_config.php'; // session_start() burada olmalı

// Tüm session değişkenlerini temizle
$_SESSION = array();

// Session cookie'sini sil (isteğe bağlı ama önerilir)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Session'ı yok et
session_destroy();

// Admin giriş sayfasına yönlendir
header('Location: admin_login.php');
exit;
?>