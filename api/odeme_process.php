<?php
// Hata raporlamayı en üste alalım (Geliştirme için)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// config.php'yi dahil et (Supabase sabitleri ve API fonksiyonu için)
require_once __DIR__ . '/config.php';

if (file_exists(__DIR__ . '/products_data.php')) {
    include __DIR__ . '/products_data.php';
} else {
    error_log("odeme_process.php: products_data.php bulunamadı.");
    header('Location: odeme.php?error_msg_odeme=' . urlencode("Ürün verileri yüklenemedi."));
    exit;
}

// 1. GİRİŞ VE İSTEK KONTROLÜ
$user_logged_in = false;
$user_id_from_post = null;
$user_cookie_name = 'asikzade_user_session';

if (isset($_POST['user_id_field']) && !empty($_POST['user_id_field'])) {
    $user_id_from_post = $_POST['user_id_field'];
    if (isset($_COOKIE[$user_cookie_name])) {
        $user_data_json = $_COOKIE[$user_cookie_name];
        $user_data = json_decode($user_data_json, true);
        if ($user_data && isset($user_data['user_id']) && $user_data['user_id'] === $user_id_from_post) {
            $user_logged_in = true;
        }
    }
}

if (!$user_logged_in) {
    $login_redirect_params = [
        'error_msg' => urlencode("Ödeme işlemi için lütfen önce giriş yapın."),
        'redirect_after_login' => 'odeme.php'
    ];
    header('Location: login.php?' . http_build_query($login_redirect_params));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: odeme.php?error_msg_odeme=' . urlencode("Geçersiz istek türü."));
    exit;
}

// 2. SEPET KONTROLÜ
$cart_cookie_data = isset($_COOKIE['asikzade_cart']) ? json_decode($_COOKIE['asikzade_cart'], true) : null;
if (empty($cart_cookie_data) || !is_array($cart_cookie_data) || count($cart_cookie_data) === 0) {
    header('Location: sepet.php?error_msg=' . urlencode("Sepetiniz boş. Lütfen ödeme yapmadan önce sepetinize ürün ekleyin."));
    exit;
}

// 3. FORM VERİLERİNİ ALMA
$country = trim($_POST['ulke'] ?? 'TR');
$first_name = trim($_POST['ad'] ?? '');
$last_name = trim($_POST['soyad'] ?? '');
$address_line1 = trim($_POST['adres_satiri1'] ?? '');
$address_line2 = trim($_POST['adres_satiri2'] ?? '');
$city = trim($_POST['sehir'] ?? '');
$province = trim($_POST['ilce'] ?? '');
$postal_code = trim($_POST['posta_kodu'] ?? '');
$phone = trim($_POST['telefon'] ?? '');
$payment_method_radio = $_POST['payment_method_radio'] ?? 'credit_card';

// Dummy card info
$card_number_dummy = $_POST['card_number'] ?? '';
$card_expiry_dummy = $_POST['expiry_date'] ?? '';
$card_cvv_dummy = $_POST['cvv'] ?? '';
$card_name_dummy = $_POST['card_name'] ?? '';

$errors = [];
if (empty($first_name)) $errors[] = "Ad alanı zorunludur.";
if (empty($last_name)) $errors[] = "Soyad alanı zorunludur.";
if (empty($address_line1)) $errors[] = "Adres (satır 1) alanı zorunludur.";
if (empty($city)) $errors[] = "Şehir alanı zorunludur.";
if (empty($province)) $errors[] = "İlçe alanı zorunludur.";
if (empty($phone)) $errors[] = "Telefon numarası zorunludur.";

if ($payment_method_radio === 'credit_card') {
    if (empty($card_number_dummy)) $errors[] = "Kart numarası zorunludur.";
    if (empty($card_expiry_dummy)) $errors[] = "Son kullanma tarihi zorunludur.";
    if (empty($card_cvv_dummy)) $errors[] = "CVV zorunludur.";
    if (empty($card_name_dummy)) $errors[] = "Kart üzerindeki isim zorunludur.";
}

if (!empty($errors)) {
    $error_query_param = http_build_query(['error_msg_odeme' => implode("<br>", $errors)]);
    header('Location: odeme.php?' . $error_query_param);
    exit;
}

// 4. SİPARİŞ TOPLAMLARINI HESAPLAMA
$calculated_sub_total = 0;
$order_items_for_db = [];

if (!isset($products) || !is_array($products) || empty($products)) {
    header('Location: odeme.php?error_msg_odeme=' . urlencode("Ürün listesi yüklenemedi. Sipariş oluşturulamıyor."));
    exit;
}

foreach ($cart_cookie_data as $item_id_from_cookie => $item_data_from_cookie) {
    if (array_key_exists($item_id_from_cookie, $products) && isset($item_data_from_cookie['quantity'])) {
        $product_info = $products[$item_id_from_cookie];
        $quantity = max(1, (int)$item_data_from_cookie['quantity']);
        if (!isset($product_info['price']) || !is_numeric($product_info['price'])) {
            header('Location: odeme.php?error_msg_odeme=' . urlencode("Ürün fiyatlandırmasında bir sorun oluştu (" . htmlspecialchars($product_info['name'] ?? $item_id_from_cookie) . ")."));
            exit;
        }
        $item_price = (float)$product_info['price'];
        $item_subtotal = $item_price * $quantity;
        $calculated_sub_total += $item_subtotal;
        $order_items_for_db[] = [
            'urun_adi' => $product_info['name'],
            'miktar' => $quantity,
            'birim_fiyat' => $item_price,
            'ara_toplam' => $item_subtotal
        ];
    }
}

if (empty($order_items_for_db)) {
    header('Location: odeme.php?error_msg_odeme=' . urlencode("Sipariş oluşturulacak geçerli ürün bulunamadı. Sepetinizi kontrol edin."));
    exit;
}

$shipping_cost_calculated = 50.00;
$kdv_orani_calculated = 0.20;
$taxes_calculated = $calculated_sub_total * $kdv_orani_calculated;
$grand_total_calculated = $calculated_sub_total + $shipping_cost_calculated + $taxes_calculated;

// 5. TESLİMAT ADRESİNİ BİRLEŞTİRME
$full_delivery_address = $first_name . " " . $last_name . "\n";
$full_delivery_address .= $address_line1;
if (!empty($address_line2)) {
    $full_delivery_address .= "\n" . $address_line2;
}
$full_delivery_address .= "\n" . $province . " / " . $city . (!empty($postal_code) ? (", " . $postal_code) : "");
$full_delivery_address .= "\n" . $country;
$full_delivery_address .= "\nTelefon: " . $phone;

// 6. ÖDEME İŞLEMİ (VARSIYIMSAL)
$payment_successful = true; 
// $payment_transaction_id = "DUMMY_TRANS_" . strtoupper(bin2hex(random_bytes(8))); // Bu ID artık Supabase'e gönderilmiyor.

if (!$payment_successful) {
    header('Location: odeme.php?error_msg_odeme=' . urlencode("Ödeme işlemi sırasında bir hata oluştu. Lütfen tekrar deneyin veya farklı bir kart kullanın."));
    exit;
}

// 7. SİPARİŞİ VERİTABANINA KAYDETME
$orderDataForSupabase = [
    'kullanici_id' => $user_id_from_post,
    'toplam_tutar' => (float)number_format($grand_total_calculated, 2, '.', ''),
    'siparis_durumu' => 'beklemede',
    'teslimat_adresi' => $full_delivery_address,
    'odeme_yontemi' => $payment_method_radio
    // 'alt_toplam' => (float)number_format($calculated_sub_total, 2, '.', ''),
    // 'kargo_ucreti' => (float)number_format($shipping_cost_calculated, 2, '.', ''),
    // 'vergiler' => (float)number_format($taxes_calculated, 2, '.', ''),
];

error_log("odeme_process.php - Ana Sipariş Payload Gönderiliyor: " . json_encode($orderDataForSupabase));

if (!function_exists('supabase_api_request')) {
    error_log("odeme_process.php: supabase_api_request fonksiyonu bulunamadı.");
    header('Location: odeme.php?error_msg_odeme=' . urlencode("Sistem hatası: API bağlantısı kurulamadı."));
    exit;
}

$siparisEkleResult = supabase_api_request(
    'POST',
    '/rest/v1/siparisler',
    $orderDataForSupabase,
    [],
    false 
);

if (!empty($siparisEkleResult['error']) || empty($siparisEkleResult['data']) || !isset($siparisEkleResult['data'][0]['id'])) {
    $db_error_message = $siparisEkleResult['error']['message'] ?? ($siparisEkleResult['data']['message'] ?? 'Bilinmeyen bir veritabanı hatası.');
    error_log("Supabase Order Insert Error: " . $db_error_message . " | HTTP Code: " . ($siparisEkleResult['http_code'] ?? 'N/A') . " | Sent Data: " . json_encode($orderDataForSupabase) . " | Response: " . json_encode($siparisEkleResult));
    header('Location: odeme.php?error_msg_odeme=' . urlencode("Siparişiniz oluşturulurken bir veritabanı hatası oluştu: " . htmlspecialchars($db_error_message)));
    exit;
}

$createdOrderDataArray = $siparisEkleResult['data'];
$yeni_siparis_id = $createdOrderDataArray[0]['id'];

if (!$yeni_siparis_id) {
    error_log("Supabase Order Insert - Missing Order ID. Response: " . json_encode($createdOrderDataArray));
    header('Location: odeme.php?error_msg_odeme=' . urlencode("Siparişiniz oluşturuldu ancak sipariş numarası alınamadı. Lütfen yönetici ile iletişime geçin."));
    exit;
}

// `siparis_urunleri` tablosuna ürünleri ekle
foreach ($order_items_for_db as $item) {
    $orderItemData = [
        'siparis_id' => $yeni_siparis_id,
        'urun_adi' => $item['urun_adi'],
        'miktar' => (int)$item['miktar'],
        'birim_fiyat' => (float)number_format($item['birim_fiyat'], 2, '.', ''),
        'ara_toplam' => (float)number_format($item['ara_toplam'], 2, '.', '')
    ];
    
    $itemInsertResult = supabase_api_request(
        'POST',
        '/rest/v1/siparis_urunleri',
        $orderItemData,
        [],
        false
    );

    if (!empty($itemInsertResult['error'])) {
        $item_error_message = $itemInsertResult['error']['message'] ?? ($itemInsertResult['data']['message'] ?? "Ürün ({$item['urun_adi']}) siparişe eklenemedi.");
        error_log("Supabase Order Item Insert Error for order ID $yeni_siparis_id, product '{$item['urun_adi']}': " . $item_error_message);
        header('Location: odeme.php?error_msg_odeme=' . urlencode("Sipariş detayları kaydedilirken bir sorun oluştu: " . htmlspecialchars($item_error_message) . ". Lütfen destek ekibimizle iletişime geçin. Sipariş No: #" . htmlspecialchars(substr($yeni_siparis_id,0,8))));
        exit; 
    }
}

// 8. BAŞARILI SİPARİŞ SONRASI İŞLEMLER
setcookie('asikzade_cart', '', time() - 3600, "/"); // Sepet cookie'sini sil

$dashboard_redirect_params = [
    'tab' => 'siparislerim',
    'success_msg_dashboard' => urlencode("Siparişiniz başarıyla oluşturuldu! Sipariş Numaranız: <strong>#" . htmlspecialchars(substr($yeni_siparis_id,0,8)) . "</strong>")
];
header('Location: dashboard.php?' . http_build_query($dashboard_redirect_params));
exit;

?>