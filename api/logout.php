<?php
ob_start();
// Cookie'yi sil (geçmiş bir zamana ayarlayarak)
setcookie("asikzade_user_session", "", time() - 3600, "/");

header('Location: /login.php'); // Yolları / ile başlatın
ob_end_flush();
exit;
?>