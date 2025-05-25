<?php
// GELİŞTİRME İÇİN HATA GÖSTERİMİ
ini_set('display_errors', 1); // Betik içinde ayarlamaya devam edelim, php.ini'den gelmezse diye
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>PHP Konfigürasyon Testi (PHP_INI_SCAN_DIR ile)</h1>";

echo "<h2>Çalışma Zamanı Yol Bilgileri</h2>";
echo "<p>Current Working Directory (getcwd): " . htmlspecialchars(getcwd()) . "</p>";
echo "<p>Bu Betiğin Dizini (__DIR__): " . htmlspecialchars(__DIR__) . "</p>";
// Eğer bu betik api/ klasöründeyse, projenin kök dizini bir üst seviyedir.
$project_root_at_runtime = realpath(__DIR__ . '/../'); 
echo "<p>Tahmini Proje Kök Dizini (Çalışma Zamanında): " . htmlspecialchars($project_root_at_runtime) . "</p>";
echo "<p>PHP_INI_SCAN_DIR Değeri (getenv): " . htmlspecialchars(getenv('PHP_INI_SCAN_DIR') ?: 'Ayarlanmamış') . "</p>";


echo "<h2>php.ini Test Ayarı</h2>";
// ... (test_debug.php'nin geri kalanı aynı) ...