<?php
// admin_logout.php
$cookie_name = 'asikzade_admin_session';
$cookie_path = "/admin/"; // admin_login_process.php'de set ederken kullandığınız path ile aynı olmalı

// Cookie'yi geçmiş bir zamana ayarlayarak sil
setcookie($cookie_name, '', time() - 3600, $cookie_path, "", isset($_SERVER["HTTPS"]), true);
// Veya daha modern setcookie array syntax'ı ile:
/*
setcookie($cookie_name, '', [
    'expires' => time() - 3600,
    'path' => $cookie_path,
    'domain' => '', // admin_login_process.php'deki ile aynı
    'secure' => isset($_SERVER["HTTPS"]),
    'httponly' => true,
    'samesite' => 'Lax'
]);
*/

// Login sayfasına yönlendir
header('Location: admin_login.php?logout=success'); // İsteğe bağlı: çıkış yapıldığına dair mesaj
exit;
?>