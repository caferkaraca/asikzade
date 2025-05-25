<?php
echo "ODEME.PHP BASLADI<br>"; // 1. Mesaj

require_once 'config.php';
echo "config.php BASARIYLA DAHIL EDILDI<br>"; // 2. Mesaj

include 'products_data.php';
echo "products_data.php BASARIYLA DAHIL EDILDI<br>"; // 3. Mesaj

// Hata ayıklama için şimdilik script'i burada durduralım.
exit;
?>