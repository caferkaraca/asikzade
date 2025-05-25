<?php
// GELİŞTİRME İÇİN HATA GÖSTERİMİ
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>PHP Konfigürasyon Testi (NOW_PHP_DEBUG=1 ile)</h1>";

echo "<h2>php.ini Test Ayarı</h2>";
$my_test_value = ini_get('my_test_setting_for_php_ini');
if ($my_test_value) {
    echo "<p><strong>my_test_setting_for_php_ini değeri:</strong> " . htmlspecialchars($my_test_value) . "</p>";
    echo "<p style='color:green;'>Bu, özel php.ini dosyasının en azından bir kısmının okunduğunu gösterir!</p>";
} else {
    echo "<p style='color:red;'>my_test_setting_for_php_ini değeri bulunamadı. Özel php.ini dosyası yüklenmemiş veya ayar yanlış olabilir.</p>";
}

echo "<h2>cURL Testi</h2>";
if (function_exists('curl_init')) {
    echo "<p style='color:green;'><strong>cURL eklentisi (curl_init) YÜKLÜ ve AKTİF.</strong></p>";
    $curl_version = curl_version();
    echo "<p>cURL Sürümü: " . htmlspecialchars($curl_version['version']) . "</p>";
    echo "<p>SSL Sürümü: " . htmlspecialchars($curl_version['ssl_version']) . "</p>";
    echo "<p>LibZ Sürümü: " . htmlspecialchars($curl_version['libz_version']) . "</p>";
    echo "<p>Protokoller: " . htmlspecialchars(implode(', ', $curl_version['protocols'])) . "</p>";

} else {
    echo "<p style='color:red;'><strong>cURL eklentisi (curl_init) YÜKLÜ DEĞİL veya AKTİF DEĞİL.</strong></p>";
}

echo "<hr><h2>phpinfo() Çıktısı</h2>";
phpinfo();
?>