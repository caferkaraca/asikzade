<?php
// Hata raporlamayı en üste alalım (Geliştirme için)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php'; // Yolunu kontrol edin

if (file_exists(__DIR__ . '/products_data.php')) {
    include __DIR__ . '/products_data.php';
} else {
    $_SESSION['error_message_odeme'] = "Ürün verileri yüklenemedi.";
    error_log("odeme_process.php: products_data.php bulunamadı.");
    header('Location: odeme.php'); // Yolunu kontrol edin
    exit;
}

// 1. GİRİŞ VE İSTEK KONTROLÜ
//-----------------------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Ödeme işlemi için lütfen önce giriş yapın.";
    header('Location: login.php'); // Yolunu kontrol edin
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message_odeme'] = "Geçersiz istek türü.";
    header('Location: odeme.php');
    exit;
}

// 2. SEPET KONTROLÜ
//-----------------------------------------------------------------------------
$cart_cookie_data = isset($_COOKIE['asikzade_cart']) ? json_decode($_COOKIE['asikzade_cart'], true) : null;
if (empty($cart_cookie_data) || !is_array($cart_cookie_data) || count($cart_cookie_data) === 0) {
    $_SESSION['error_message_odeme'] = "Sepetiniz boş. Lütfen ödeme yapmadan önce sepetinize ürün ekleyin.";
    header('Location: sepet.php'); // Yolunu kontrol edin
    exit;
}

// 3. FORM VERİLERİNİ ALMA VE TEMEL DOĞRULAMA
//-----------------------------------------------------------------------------
$country = trim($_POST['country'] ?? 'TR');
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$address_line1 = trim($_POST['address'] ?? '');
$address_line2 = trim($_POST['apt_suite'] ?? '');
$city = trim($_POST['city'] ?? '');
$province = trim($_POST['province'] ?? '');
$postal_code = trim($_POST['postal_code'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$payment_method = $_POST['payment_method'] ?? 'credit_card';

$card_number_dummy = $_POST['card_number'] ?? '';
$card_expiry_dummy = $_POST['expiry_date'] ?? '';
$card_cvv_dummy = $_POST['cvv'] ?? '';
$card_name_dummy = $_POST['card_name'] ?? '';

$_SESSION['form_data_odeme'] = $_POST;

$errors = [];
if (empty($first_name)) $errors[] = "Ad alanı zorunludur.";
if (empty($last_name)) $errors[] = "Soyad alanı zorunludur.";
if (empty($address_line1)) $errors[] = "Adres (satır 1) alanı zorunludur.";
if (empty($city)) $errors[] = "Şehir alanı zorunludur.";
if (empty($province)) $errors[] = "İlçe alanı zorunludur.";
if (empty($phone)) $errors[] = "Telefon numarası zorunludur.";

if ($payment_method === 'credit_card') {
    if (empty($card_number_dummy)) $errors[] = "Kart numarası zorunludur.";
    if (empty($card_expiry_dummy)) $errors[] = "Son kullanma tarihi zorunludur.";
    if (empty($card_cvv_dummy)) $errors[] = "CVV zorunludur.";
    if (empty($card_name_dummy)) $errors[] = "Kart üzerindeki isim zorunludur.";
}

if (!empty($errors)) {
    $_SESSION['error_message_odeme'] = implode("<br>", $errors);
    header('Location: odeme.php');
    exit;
}

// 4. SİPARİŞ TOPLAMLARINI VE ÜRÜNLERİNİ SUNUCU TARAFINDA HESAPLAMA
//-----------------------------------------------------------------------------
$calculated_sub_total = 0;
$order_items_for_db = [];

if (!isset($products) || !is_array($products) || empty($products)) {
    $_SESSION['error_message_odeme'] = "Ürün listesi yüklenemedi. Sipariş oluşturulamıyor.";
    error_log("odeme_process.php: \$products dizisi boş veya tanımlı değil.");
    header('Location: odeme.php');
    exit;
}

foreach ($cart_cookie_data as $item_id_from_cookie => $item_data_from_cookie) {
    if (array_key_exists($item_id_from_cookie, $products) && isset($item_data_from_cookie['quantity'])) {
        $product_info = $products[$item_id_from_cookie];
        $quantity = max(1, (int)$item_data_from_cookie['quantity']);
        
        if (!isset($product_info['price']) || !is_numeric($product_info['price'])) {
            error_log("odeme_process.php: Product ID $item_id_from_cookie için geçersiz fiyat. Product data: " . print_r($product_info, true));
            $_SESSION['error_message_odeme'] = "Ürün fiyatlandırmasında bir sorun oluştu (" . htmlspecialchars($product_info['name'] ?? $item_id_from_cookie) . ").";
            header('Location: odeme.php');
            exit;
        }
        $item_price = (float)$product_info['price'];
        $item_subtotal = $item_price * $quantity;
        $calculated_sub_total += $item_subtotal;

        $order_items_for_db[] = [
            'urun_adi' => $product_info['name'],
            'miktar' => $quantity,
            'birim_fiyat' => $item_price,
            'ara_toplam' => $item_subtotal // Bu, siparis_urunleri tablosu için
        ];
    }
}

if (empty($order_items_for_db)) {
    $_SESSION['error_message_odeme'] = "Sipariş oluşturulacak geçerli ürün bulunamadı.";
    header('Location: odeme.php');
    exit;
}

$shipping_cost_calculated = 50.00;
$taxes_calculated = $calculated_sub_total * 0.18; // Bu KDV sadece hesaplama için, DB'ye yazılmayacak
$grand_total_calculated = $calculated_sub_total + $shipping_cost_calculated + $taxes_calculated; // DB'ye yazılacak olan toplam tutar

// 5. TESLİMAT ADRESİNİ BİRLEŞTİRME
//-----------------------------------------------------------------------------
$full_delivery_address = $first_name . " " . $last_name . "\n";
$full_delivery_address .= $address_line1;
if (!empty($address_line2)) {
    $full_delivery_address .= "\n" . $address_line2;
}
$full_delivery_address .= "\n" . $province . " / " . $city . (!empty($postal_code) ? (", " . $postal_code) : "");
$full_delivery_address .= "\n" . $country;
$full_delivery_address .= "\nTelefon: " . $phone;

// 6. ÖDEME İŞLEMİ (VARSIYIMSAL)
//-----------------------------------------------------------------------------
$payment_successful = true;
// $payment_transaction_id = "DUMMY_TRANS_" . strtoupper(bin2hex(random_bytes(8)));

if (!$payment_successful) {
    $_SESSION['error_message_odeme'] = "Ödeme işlemi sırasında bir hata oluştu.";
    header('Location: odeme.php');
    exit;
}

// 7. SİPARİŞİ VERİTABANINA KAYDETME
//-----------------------------------------------------------------------------
$kullanici_id_from_session = $_SESSION['user_id'];

$orderDataForSupabase = [
    'kullanici_id' => $kullanici_id_from_session,
    'toplam_tutar' => (float)number_format($grand_total_calculated, 2, '.', ''), // Sadece bu toplam tutar var
    'siparis_durumu' => 'hazırlanıyor',
    'teslimat_adresi' => $full_delivery_address,
    'odeme_yontemi' => $payment_method,
    // 'alt_toplam', 'kargo_ucreti', 'vergiler' alanları ÇIKARILDI
    // 'odeme_islem_id' => $payment_transaction_id ?? null,
];

error_log("odeme_process.php - Ana Sipariş Payload Gönderiliyor: " . json_encode($orderDataForSupabase));

$siparisEkleResult = supabase_api_request(
    'POST',
    '/rest/v1/siparisler',
    $orderDataForSupabase,
    [],
    false
);

if (!empty($siparisEkleResult['error']) || empty($siparisEkleResult['data'])) {
    $db_error_message = $siparisEkleResult['error']['message'] ?? ($siparisEkleResult['data']['message'] ?? 'Bilinmeyen bir veritabanı hatası.');
    $_SESSION['error_message_odeme'] = "Siparişiniz oluşturulurken bir veritabanı hatası oluştu: " . htmlspecialchars($db_error_message);
    error_log("Supabase Order Insert Error: " . $db_error_message . " | HTTP Code: " . ($siparisEkleResult['http_code'] ?? 'N/A') . " | Sent Data: " . json_encode($orderDataForSupabase) . " | Response: " . json_encode($siparisEkleResult));
    header('Location: odeme.php');
    exit;
}

$createdOrderData = $siparisEkleResult['data'];
$yeni_siparis_id = null;
if (is_array($createdOrderData) && count($createdOrderData) > 0 && isset($createdOrderData[0]['id'])) {
    $yeni_siparis_id = $createdOrderData[0]['id'];
} elseif (is_object($createdOrderData) && isset($createdOrderData->id)) {
    $yeni_siparis_id = $createdOrderData->id;
} elseif (isset($createdOrderData['id'])) {
     $yeni_siparis_id = $createdOrderData['id'];
}


if (!$yeni_siparis_id) {
    $_SESSION['error_message_odeme'] = "Siparişiniz oluşturuldu ancak sipariş numarası alınamadı.";
    error_log("Supabase Order Insert - Missing Order ID. Response: " . json_encode($createdOrderData));
    header('Location: odeme.php');
    exit;
}

// `siparis_urunleri` tablosuna ürünleri ekle
$siparisUrunleriKayitHatasi = false;
foreach ($order_items_for_db as $item) {
    $orderItemData = [
        'siparis_id' => $yeni_siparis_id,
        'urun_adi' => $item['urun_adi'],
        'miktar' => (int)$item['miktar'],
        'birim_fiyat' => (float)number_format($item['birim_fiyat'], 2, '.', ''),
        'ara_toplam' => (float)number_format($item['ara_toplam'], 2, '.', '') // Bu, siparis_urunleri tablosu için hala gerekli
    ];

    error_log("odeme_process.php - Sipariş Ürünü Payload Gönderiliyor: " . json_encode($orderItemData));
    
    $itemInsertResult = supabase_api_request(
        'POST',
        '/rest/v1/siparis_urunleri',
        $orderItemData,
        [],
        false
    );

    if (!empty($itemInsertResult['error'])) {
        $siparisUrunleriKayitHatasi = true;
        $item_error_message = $itemInsertResult['error']['message'] ?? ($itemInsertResult['data']['message'] ?? "Ürün ({$item['urun_adi']}) siparişe eklenemedi.");
        error_log("Supabase Order Item Insert Error for order ID $yeni_siparis_id, product '{$item['urun_adi']}': " . $item_error_message . " | HTTP Code: " . ($itemInsertResult['http_code'] ?? 'N/A') . " | Response: " . json_encode($itemInsertResult));
        $_SESSION['error_message_odeme'] = "Sipariş detayları kaydedilirken bir sorun oluştu: " . htmlspecialchars($item_error_message) . ". Sipariş No: #" . htmlspecialchars(substr($yeni_siparis_id,0,8));
        // Hata oluşursa döngüden çık ve kullanıcıyı bilgilendir.
        // Bu durumda ana sipariş oluşmuş ama ürünler eklenememiş olabilir.
        // Daha gelişmiş bir senaryoda, ana siparişi de silmek (rollback) veya durumunu güncellemek gerekebilir.
        break; 
    }
}

if ($siparisUrunleriKayitHatasi) {
    // Hata mesajı zaten session'da ayarlandı, odeme.php'ye yönlendir.
    header('Location: odeme.php');
    exit;
}

// 8. BAŞARILI SİPARİŞ SONRASI İŞLEMLER
//-----------------------------------------------------------------------------
setcookie('asikzade_cart', '', time() - 3600, "/"); // Sepet cookie'sini sil
unset($_SESSION['form_data_odeme']); // Form verilerini session'dan temizle

$_SESSION['success_message_dashboard'] = "Siparişiniz başarıyla oluşturuldu! Sipariş Numaranız: <strong>#" . htmlspecialchars(substr($yeni_siparis_id,0,8)) . "</strong>";
header('Location: dashboard.php?tab=siparislerim'); // Kullanıcıyı siparişlerim sekmesine yönlendir
exit;

?>