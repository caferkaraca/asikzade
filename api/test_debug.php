<?php
// GELİŞTİRME İÇİN HATA GÖSTERİMİ (Her ihtimale karşı betik içinde de ayarlayalım)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>PHP Konfigürasyon Testi (Son Denemeler)</h1>";

echo "<h2>Çalışma Zamanı Yol Bilgileri</h2>";
echo "<p>Current Working Directory (getcwd): " . htmlspecialchars(getcwd()) . "</p>";
echo "<p>Bu Betiğin Dizini (__DIR__): " . htmlspecialchars(__DIR__) . "</p>";
$project_root_at_runtime = realpath(__DIR__ . '/../');
echo "<p>Tahmini Proje Kök Dizini (Çalışma Zamanında): " . htmlspecialchars($project_root_at_runtime ?: 'Hesaplanamadı') . "</p>";
$php_ini_scan_dir_env = getenv('PHP_INI_SCAN_DIR');
echo "<p>PHP_INI_SCAN_DIR Değeri (getenv): " . htmlspecialchars($php_ini_scan_dir_env ?: 'Ayarlanmamış') . "</p>";

echo "<h2>conf.d Klasör İçeriği Testi</h2>";
// getenv ile alınan PHP_INI_SCAN_DIR değerini kullanalım, eğer ayarlıysa
$conf_d_path_to_check = $php_ini_scan_dir_env ?: '/var/task/user/conf.d'; // Eğer getenv boşsa varsayılan bir yol deneyelim
echo "<p>Kontrol edilen conf.d yolu: " . htmlspecialchars($conf_d_path_to_check) . "</p>";

if (!empty($conf_d_path_to_check) && is_dir($conf_d_path_to_check)) {
    echo "<p style='color:green;'>Klasör bulundu: " . htmlspecialchars($conf_d_path_to_check) . "</p>";
    $files_in_conf_d = scandir($conf_d_path_to_check);
    echo "<p>Klasör içeriği: <pre>" . htmlspecialchars(print_r($files_in_conf_d, true)) . "</pre></p>";
    
    $custom_ini_path = $conf_d_path_to_check . '/custom.ini';
    if (file_exists($custom_ini_path)) {
        echo "<p style='color:green;'>custom.ini dosyası bulundu: " . htmlspecialchars($custom_ini_path) . "</p>";
        $custom_ini_content = file_get_contents($custom_ini_path);
        echo "<p>custom.ini içeriği (tamamı): <pre>" . htmlspecialchars($custom_ini_content) . "</pre></p>";
    } else {
        echo "<p style='color:red;'>custom.ini dosyası BULUNAMADI: " . htmlspecialchars($custom_ini_path) . "</p>";
    }
} else {
    echo "<p style='color:red;'>Klasör BULUNAMADI veya Kontrol Edilecek Yol Boş: " . htmlspecialchars($conf_d_path_to_check) . "</p>";
}

echo "<h2>php.ini Test Ayarı (custom.ini'den Beklenen)</h2>";
$my_test_value = ini_get('my_test_setting_for_php_ini');
if ($my_test_value) {
    echo "<p><strong>my_test_setting_for_php_ini değeri:</strong> " . htmlspecialchars($my_test_value) . "</p>";
    if ($my_test_value === "PHP INI DOSYASI OKUNDU (conf.d/custom.ini denemesi)") {
        echo "<p style='color:green;'>Bu, özel custom.ini dosyasındaki ayarın doğru okunduğunu gösterir!</p>";
    } else {
        echo "<p style='color:orange;'>Değer okundu ama custom.ini'deki beklenen değer değil. Farklı bir yerden geliyor olabilir.</p>";
    }
} else {
    echo "<p style='color:red;'>my_test_setting_for_php_ini değeri bulunamadı. Özel custom.ini dosyası yüklenmemiş veya ayar yanlış olabilir.</p>";
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