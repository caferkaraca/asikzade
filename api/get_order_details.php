<?php
// Hata raporlamayı en üste alalım
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'admin_config.php'; // Contains supabase_api_request and new admin_check_login

// --- USER SESSION COOKIE HANDLING ---
$user_cookie_name = 'asikzade_user_session';
$user_logged_in = false;
$user_data_from_cookie = null; 

if (isset($_COOKIE[$user_cookie_name])) {
    $user_data_json = $_COOKIE[$user_cookie_name];
    $user_data_from_cookie = json_decode($user_data_json, true);
    if ($user_data_from_cookie && isset($user_data_from_cookie['user_id'])) {
        $user_logged_in = true;
    } else {
        $user_data_from_cookie = null; 
    }
}
// --- END USER SESSION COOKIE HANDLING ---

$response_data = ['error' => null, 'order_info' => null, 'items' => []];

$is_admin_view = isset($_GET['admin_view']) && $_GET['admin_view'] === 'true';

if ($is_admin_view) {
    admin_check_login(); 
} elseif (!$user_logged_in) { // Check using the cookie data
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

$user_id_filter_segment = ''; 
if (!$is_admin_view && $user_logged_in && isset($user_data_from_cookie['user_id'])) { // Check using cookie data
    $user_id_for_query = $user_data_from_cookie['user_id']; // Use user_id from cookie
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

$orderInfoResult = supabase_api_request(
    'GET',
    $order_path,
    [],
    [],
    $is_admin_view 
);

if ($orderInfoResult === null || (!empty($orderInfoResult['error']) || empty($orderInfoResult['data']))) {
    header('Content-Type: application/json; charset=utf-8');
    $api_error_message = 'Bilinmeyen bir hata oluştu.';
    if (isset($orderInfoResult['error']['message'])) {
        $api_error_message = $orderInfoResult['error']['message'];
    } elseif (isset($orderInfoResult['data']['message'])) {
        $api_error_message = $orderInfoResult['data']['message'];
    }
    $response_data['error'] = 'Sipariş bulunamadı veya erişim yetkiniz yok. Detay: ' . $api_error_message;
    $log_message = ($is_admin_view ? "ADMIN" : "USER") . " get_order_details - Ana sipariş hatası: " . $api_error_message . " | Order ID: " . $order_id;
    if (isset($orderInfoResult)) error_log($log_message . " | Full Response: ".json_encode($orderInfoResult)); else error_log($log_message . " | API Result was NULL");
    echo json_encode($response_data);
    exit;
}
$response_data['order_info'] = $orderInfoResult['data'][0];

// 2. Sipariş Ürünlerini Çek
$items_path = '/rest/v1/siparis_urunleri?siparis_id=eq.' . rawurlencode($order_id) . '&select=' . rawurlencode('urun_adi,miktar,birim_fiyat,ara_toplam');

$orderItemsResult = supabase_api_request(
    'GET',
    $items_path,
    [],
    [],
    $is_admin_view
);

if ($orderItemsResult === null) {
    error_log(($is_admin_view ? "ADMIN" : "USER") . " get_order_details - supabase_api_request NULL döndü (Order Items). Order ID: " . $order_id);
} elseif (!empty($orderItemsResult['error'])) {
    $api_items_error = $orderItemsResult['error']['message'] ?? ($orderItemsResult['data']['message'] ?? 'Bilinmeyen API hatası');
    error_log(($is_admin_view ? "ADMIN" : "USER") . " get_order_details - Sipariş ürünleri hatası: " . $api_items_error . " | Order ID: " . $order_id . " | Full Response: ".json_encode($orderItemsResult));
} elseif (!empty($orderItemsResult['data'])) {
    $response_data['items'] = $orderItemsResult['data'];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response_data);
exit;
?>