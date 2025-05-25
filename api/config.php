<?php

// Supabase Proje Bilgileri
if (!defined('SUPABASE_URL')) {
    define('SUPABASE_URL', 'https://winiynluwwmthurgiyar.supabase.co');
}
if (!defined('SUPABASE_KEY_ANON')) {
    define('SUPABASE_KEY_ANON', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Indpbml5bmx1d3dtdGh1cmdpeWFyIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDgxNTI2MzUsImV4cCI6MjA2MzcyODYzNX0.lfmepRzjkC1_XuSVnbiDVGsOBNtnvtRu0Bg2fVSlRjA');
}
if (!defined('SUPABASE_KEY')) {
    define('SUPABASE_KEY', SUPABASE_KEY_ANON); // dashboard.php bu sabiti kullanıyor
}
if (!defined('SUPABASE_SERVICE_ROLE_KEY')) {
    define('SUPABASE_SERVICE_ROLE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Indpbml5bmx1d3dtdGh1cmdpeWFyIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc0ODE1MjYzNSwiZXhwIjoyMDYzNzI4NjM1fQ.t2hGLP2XUxUwNLzqc4v8Yr_rhhMSkUKYvQmbuU8ObB0'); // GERÇEK KEY'İNİZİ GİRİN!
}


// admin_config.php, ana API fonksiyonunu içerdiği için onu burada dahil ediyoruz.
// Dosya yolunun doğru olduğundan emin olun. Eğer config.php ve admin_config.php
// aynı dizindeyse bu yol çalışır.
// Eğer admin_config.php bir alt dizindeyse (örn: admin/admin_config.php), yolu düzeltin.
// Eğer admin_config.php api/ dizininde, config.php ana dizindeyse:
// require_once __DIR__ . '/api/admin_config.php'; (Örnek, kendi yapınıza göre uyarlayın)

// ŞİMDİLİK, config.php ve admin_config.php'nin aynı dizinde olduğunu varsayıyorum.
// EĞER DEĞİLSE, BU YOLU DÜZELTİN!
// Örn: Eğer admin_config.php bir üst dizindeki "includes" klasöründeyse:
// require_once __DIR__ . '/../includes/admin_config.php';

// VEYA, dashboard.php ve admin_config.php aynı dizinde ise (örn: her ikisi de /api/ dizininde)
// ve config.php ana dizinde ise:
// require_once __DIR__ . '/admin_config.php'; (Bu config.php'den çağrıldığı için __DIR__ config.php'nin dizinini verir)
// Bu durumda dashboard.php ve get_order_details.php, require_once '../config.php'; yapmalı.

// En yaygın senaryo:
// Proje Kökü
// |- config.php  <-- BU DOSYA
// |- dashboard.php
// |- admin_config.php (veya includes/admin_config.php)
// |- api/
//    |- get_order_details.php

// Eğer config.php ve admin_config.php kök dizindeyse:
if (file_exists(__DIR__ . '/admin_config.php')) { // Eğer admin_config.php kök dizindeyse
    require_once __DIR__ . '/admin_config.php';
} elseif (file_exists(__DIR__ . '/api/admin_config.php')) { // Eğer admin_config.php api/ dizinindeyse (config.php kökteyse)
     require_once __DIR__ . '/api/admin_config.php';
} else {
    // admin_config.php bulunamadı, manuel yolu deneyin veya yapıyı kontrol edin
    // Örnek: require_once '/path/to/your/project/admin_config.php';
    error_log("config.php: admin_config.php dosyası bulunamadı. Lütfen yolu kontrol edin.");
    // Fonksiyon tanımsız kalacağı için bir hata verelim veya işlemi durduralım
    // die("Kritik yapılandırma hatası: admin_config.php yüklenemedi.");
}


// products_data.php ve get_cart_count() gibi fonksiyonlar için gerekli diğer dahil etmeler veya fonksiyonlar burada olabilir.
if (file_exists(__DIR__ . '/functions.php')) {
    // require_once __DIR__ . '/functions.php';
}
?>