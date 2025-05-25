<?php
// GELİŞTİRME İÇİN HATA GÖSTERİMİ - CANLIDA KALDIRIN!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ana config dosyasını dahil et (SUPABASE_URL, SUPABASE_KEY_ANON, SUPABASE_SERVICE_ROLE_KEY sabitleri için)
require_once __DIR__ . '/config.php'; // config.php ile aynı dizinde olduğunu varsayıyoruz. Değilse yolu düzeltin.

// session_start() zaten config.php'de çağrılıyor olmalı, tekrar çağırmaya gerek yok ama kontrol edelim.
if (session_status() == PHP_SESSION_NONE) {
    // Bu durum config.php'nin düzgün dahil edilmediğini veya session_start'ı atladığını gösterir.
    // Normalde buraya düşmemesi lazım.
    session_start();
}

function admin_check_login() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true ||
        !isset($_SESSION['admin_id']) ) {

        $_SESSION = array(); // Tüm session değişkenlerini temizle
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy(); // Session'ı yok et
        session_start();   // Yeni, temiz bir session başlat (mesaj için)

        $_SESSION['admin_error_message'] = "Bu sayfaya erişim yetkiniz yok. Lütfen admin olarak giriş yapın.";
        header('Location: admin_login.php'); // admin_login.php'nin yolu doğru olmalı
        exit;
    }
}

/**
 * Supabase API'sine cURL ile istek gönderir.
 * Hem anonim anahtar hem de service role anahtarı ile çalışabilir.
 *
 * @param string $method HTTP metodu (GET, POST, PATCH, DELETE).
 * @param string $path API endpoint yolu (örn: /rest/v1/tablo_adi).
 * @param array $data Gönderilecek veri (POST, PATCH için). GET için query string parametreleri olarak eklenebilir.
 * @param array $custom_headers Ekstra özel header'lar.
 * @param bool $use_service_key True ise SUPABASE_SERVICE_ROLE_KEY kullanılır, false ise SUPABASE_KEY_ANON.
 * @return array API yanıtı ['data' => ..., 'http_code' => ..., 'error' => ...].
 */
function supabase_api_request($method, $path, $data = [], $custom_headers = [], $use_service_key = false) {
    // SUPABASE_URL config.php'den gelmeli
    if (!defined('SUPABASE_URL')) {
        return ['error' => ['message' => 'SUPABASE_URL tanımlı değil.'], 'http_code' => 0, 'data' => null];
    }
    $url = SUPABASE_URL . $path;

    $apiKeyToUse = '';
    if ($use_service_key) {
        if (!defined('SUPABASE_SERVICE_ROLE_KEY') || SUPABASE_SERVICE_ROLE_KEY === 'BURAYA_SUPABASE_SERVICE_ROLE_KEYİNİZİ_YAPIŞTIRIN' || empty(SUPABASE_SERVICE_ROLE_KEY)) {
            error_log("UYARI: supabase_api_request'te use_service_key=true ancak SUPABASE_SERVICE_ROLE_KEY tanımlı değil veya boş!");
            return ['error' => ['message' => 'Service Role Key yapılandırılmamış.'], 'http_code' => 0, 'data' => null];
        }
        $apiKeyToUse = SUPABASE_SERVICE_ROLE_KEY;
    } else {
        if (!defined('SUPABASE_KEY_ANON')) {
             error_log("UYARI: supabase_api_request'te SUPABASE_KEY_ANON tanımlı değil!");
            return ['error' => ['message' => 'Anonim API Key yapılandırılmamış.'], 'http_code' => 0, 'data' => null];
        }
        $apiKeyToUse = SUPABASE_KEY_ANON;
    }

    $headers = [
        'apikey: ' . $apiKeyToUse,
        'Content-Type: application/json',
    ];

    // Service key kullanılıyorsa Authorization header'ını ekle
    if ($use_service_key) {
         $headers[] = 'Authorization: Bearer ' . $apiKeyToUse;
    }

    // POST, PATCH, DELETE işlemleri için güncel veriyi geri döndürmesini iste
    if (in_array(strtoupper($method), ['POST', 'PATCH', 'DELETE'])) {
        $headers[] = 'Prefer: return=representation';
    }

    if (!empty($custom_headers)) {
        $headers = array_merge($headers, $custom_headers);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // İstek zaman aşımı (saniye)
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Bağlantı zaman aşımı (saniye)

    $method = strtoupper($method); // Metodu büyük harfe çevir
    switch ($method) {
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case 'PATCH':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            // DELETE için $data payload olarak gönderilmez, gerekirse URL'e eklenir.
            break;
        case 'GET':
            // GET istekleri için $data query string olarak eklenebilir, ancak bu fonksiyonun $path'ine dahil edilmeli.
            // Örn: $path = '/rest/v1/tablo?param=deger'
            // http_build_query ile $data'yı query string'e çevirip $url'e eklemek de bir yöntemdir.
            if (!empty($data)) { // Eğer GET için $data verilmişse, query string yap
                 $queryString = http_build_query($data);
                 curl_setopt($ch, CURLOPT_URL, $url . (strpos($url, '?') === false ? '?' : '&') . $queryString);
            }
            break;
        default:
            // Desteklenmeyen metod
            curl_close($ch);
            return ['error' => ['message' => "Desteklenmeyen HTTP metodu: $method"], 'http_code' => 0, 'data' => null];
    }

    $response_body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErrorNo = curl_errno($ch);
    $curlErrorMsg = curl_error($ch);
    curl_close($ch);

    // Detaylı loglama (Geliştirme sırasında çok faydalı)
    $log_data_str = ($method === 'POST' || $method === 'PATCH') ? json_encode($data) : ( ($method === 'GET' && !empty($data)) ? json_encode($data) : 'N/A' );
    error_log("Supabase API Request --- URL: " . curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) . " | Method: $method | HTTP Code: $httpCode | cURL ErrNo: $curlErrorNo | cURL ErrMsg: $curlErrorMsg | Payload: ".$log_data_str." | Headers: ".json_encode($headers)." | Raw Response (ilk 500 karakter): ".substr($response_body, 0, 500));


    if ($curlErrorNo !== 0) {
        return ['error' => ['message' => "cURL Hatası ($curlErrorNo): " . $curlErrorMsg], 'http_code' => 0, 'data' => null];
    }

    $responseData = json_decode($response_body, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        $errorMessage = 'API Hatası (HTTP ' . $httpCode . ')';
        if (isset($responseData['message'])) $errorMessage = $responseData['message'];
        elseif (isset($responseData['error_description'])) $errorMessage = $responseData['error_description'];
        elseif (isset($responseData['error']) && is_string($responseData['error'])) $errorMessage = $responseData['error'];
        elseif (isset($responseData['msg'])) $errorMessage = $responseData['msg'];
        elseif (is_null($responseData) && !empty($response_body)) $errorMessage = "Geçersiz JSON yanıtı: " . substr($response_body, 0, 200);


        return ['error' => ['message' => $errorMessage], 'http_code' => $httpCode, 'data' => $responseData]; // $responseData'yı yine de döndür, belki ek bilgi içerir
    }

    return ['data' => $responseData, 'http_code' => $httpCode, 'error' => null];
}
?>