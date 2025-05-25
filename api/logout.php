<?php
ob_start(); // Çıktı tamponlamasını başlat

// Cookie'yi sil (geçmiş bir zamana ayarlayarak)
// HTTPS üzerinden güvenli cookie (eğer siteniz HTTPS ise)
$secure_cookie = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
setcookie("asikzade_user_session", "", time() - 3600, "/", "", $secure_cookie, true); // HttpOnly eklendi

header('Location: /login.php'); // KÖK DİZİNDE OLDUĞUNU VARSAYIYORUM
ob_end_flush(); // Çıktı tamponunu gönder
exit;
?>