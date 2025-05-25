<?php
// Hata raporlamayı en üste alalım
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Admin config dosyasını dahil et (bu dosya config.php'yi de dahil etmeli)
// __DIR__ kullanmak dosya yollarını daha güvenilir yapar.
// Bu dosyanın (get_order_details.php) bulunduğu dizindeki admin_config.php'yi arar.
// Eğer api/ dizinindeyse ve admin_config.php bir üst dizindeyse, __DIR__ . '/../admin_config.php' gibi olmalı.
// Şimdilik aynı dizinde olduğunu varsayıyorum (örn: /api/admin_config.php ve /api/get_order_details.php)
// Eğer admin_config.php ana dizindeyse: require_once __DIR__ . '/../admin_config.php';
require_once 'admin_config.php'; // VEYA __DIR__ . '/admin_config.php';

// session_start() admin_config.php (veya onun çağırdığı config.php) içinde zaten yapılmalı.

$response_data = ['error' => null, 'order_info' => null, 'items' => []];

// Admin yetki kontrolü (isteğe bağlı, eğer bu script sadece admin tarafından çağrılıyorsa)
// Eğer bu dosya hem normal kullanıcı (dashboard'dan) hem de admin paneli için kullanılacaksa,
// $is_admin_view kontrolü mantıklı. Sadece admin içinse admin_check_login() çağrılabilir.
$is_admin_view = isset($_GET['admin_view']) && $_GET['admin_view'] === 'true';

if ($is_admin_view) {
    admin_check_login(); // Eğer admin görünümü ise, admin girişi yapılmış olmalı.
} elseif (!isset($_SESSION['user_id'])) { // Normal kullanıcı için (dashboard'dan)
    header('Content-Type: application/json; charset=utf-8');
    $response_data['error'] = 'Yetkisiz erişim. Lütfen giriş yapın.';
    echo json_encode($response_data);
    exit;
}

$order_id = $_GET['order_id'] ?? null;

if (empty($order_id)) {
    header('Content-Type: application/json; charset=utf-8');
    $response_data['error'] = 'Sipariş ID eksik.';
    echo json_encode($response_data);
    exit;
}

$user_id_filter_segment = ''; // Query string için segment
if (!$is_admin_view && isset($_SESSION['user_id'])) {
    $user_id_for_query = $_SESSION['user_id'];
    $user_id_filter_segment = 'kullanici_id=eq.' . rawurlencode($user_id_for_query);
}

// 1. Ana Sipariş Bilgilerini Çek
$select_fields_order = 'id,kullanici_id,siparis_tarihi,toplam_tutar,siparis_durumu,teslimat_adresi';
if ($is_admin_view) {
    $select_fields_order .= ',kullanicilar(email,ad,soyad)'; // Admin ise kullanıcı bilgilerini de çek
}

$order_path = '/rest/v1/siparisler?id=eq.' . rawurlencode($order_id);
if (!empty($user_id_filter_segment)) {
    $order_path .= '&' . $user_id_filter_segment;
}
$order_path .= '&select=' . rawurlencode($select_fields_order);

// Fonksiyonu doğru isimle ve parametrelerle çağır:
// supabase_api_request($method, $path, $data, $custom_headers, $use_service_key)
$orderInfoResult = supabase_api_request(
    'GET',
    $order_path,
    [],   // GET için data payload'ı boş
    [],   // Özel header yok
    $is_admin_view // Eğer admin görünümü ise service key kullan, değilse anon key (fonksiyon içindeki mantığa göre)
);


if ($orderInfoResult === null || (!empty($orderInfoResult['error']) || empty($orderInfoResult['data']))) {
    header('Content-Type: application/json; charset=utf-8');
    $api_error_message = 'Bilinmeyen bir hata oluştu.';
    if (isset($orderInfoResult['error']['message'])) {
        $api_error_message = $orderInfoResult['error']['message'];
    } elseif (isset($orderInfoResult['data']['message'])) { // Supabase bazen datada message dönebilir
        $api_error_message = $orderInfoResult['data']['message'];
    }
    $response_data['error'] = 'Sipariş bulunamadı veya erişim yetkiniz yok. Detay: ' . $api_error_message;
    $log_message = ($is_admin_view ? "ADMIN" : "USER") . " get_order_details - Ana sipariş hatası: " . $api_error_message . " | Order ID: " . $order_id;
    if (isset($orderInfoResult)) error_log($log_message . " | Full Response: ".json_encode($orderInfoResult)); else error_log($log_message . " | API Result was NULL");
    echo json_encode($response_data);
    exit;
}
$response_data['order_info'] = $orderInfoResult['data'][0]; // Genellikle data bir array içinde tek eleman olur


// 2. Sipariş Ürünlerini Çek
$items_path = '/rest/v1/siparis_urunleri?siparis_id=eq.' . rawurlencode($order_id) . '&select=' . rawurlencode('urun_adi,miktar,birim_fiyat,ara_toplam');

$orderItemsResult = supabase_api_request(
    'GET',
    $items_path,
    [],   // GET için data payload'ı boş
    [],   // Özel header yok
    $is_admin_view // Sipariş ürünlerini çekerken de yetkiye göre key kullan
);

if ($orderItemsResult === null) {
    error_log(($is_admin_view ? "ADMIN" : "USER") . " get_order_details - supabase_api_request NULL döndü (Order Items). Order ID: " . $order_id);
} elseif (!empty($orderItemsResult['error'])) {
    $api_items_error = $orderItemsResult['error']['message'] ?? ($orderItemsResult['data']['message'] ?? 'Bilinmeyen API hatası');
    error_log(($is_admin_view ? "ADMIN" : "USER") . " get_order_details - Sipariş ürünleri hatası: " . $api_items_error . " | Order ID: " . $order_id . " | Full Response: ".json_encode($orderItemsResult));
} elseif (!empty($orderItemsResult['data'])) {
    $response_data['items'] = $orderItemsResult['data'];
}


// Başarılı yanıt için header'ı burada ayarla
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response_data);
exit;
?>