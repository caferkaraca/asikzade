<?php
// GELİŞTİRME İÇİN HATA GÖSTERİMİ - CANLIDA KALDIRIN!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ana config dosyasını dahil et (SUPABASE_URL, SUPABASE_KEY_ANON, SUPABASE_SERVICE_ROLE_KEY sabitleri için)
require_once __DIR__ . '/config.php'; // config.php ile aynı dizinde olduğunu varsayıyoruz. Değilse yolu düzeltin.

// Define the admin auth cookie name
if (!defined('ADMIN_AUTH_COOKIE_NAME')) {
    define('ADMIN_AUTH_COOKIE_NAME', 'asikzade_admin_token');
}

// Function to get the admin token data (placeholder for JWT decoding)
// For now, it just checks if the token exists and returns a placeholder or the raw token
function get_admin_token_data() {
    if (isset($_COOKIE[ADMIN_AUTH_COOKIE_NAME])) {
        // In a real JWT scenario, you would decode and verify the token here.
        // For now, we'll just return a placeholder indicating the user is "logged in".
        // Or, return the raw token string if that's needed by other parts of the admin panel.
        // $token = $_COOKIE[ADMIN_AUTH_COOKIE_NAME];
        // $decoded_token = your_jwt_decode_and_verify_function($token);
        // return $decoded_token; // This would contain user_id, roles, etc.
        return ['admin_id' => 'temp_admin_id', 'admin_email' => 'admin@example.com', 'logged_in' => true]; // Placeholder with email
    }
    return null;
}


function admin_check_login($redirect_on_fail = true) {
    $admin_data = get_admin_token_data();

    if (!$admin_data) { // In a real JWT scenario, this would also check if token is valid
        if ($redirect_on_fail) {
            // Clear any potentially invalid/old cookie
            setcookie(ADMIN_AUTH_COOKIE_NAME, '', time() - 3600, "/"); // Adjust path if needed
            
            $error_param = 'Bu sayfaya erişim yetkiniz yok. Lütfen admin olarak giriş yapın.';
            // admin_login.php'nin yolu doğru olmalı. Eğer api klasöründeyse ve admin_login.php ana dizindeyse ../admin_login.php olmalı.
            // Ancak genellikle admin_config.php'yi çağıran scriptler ana dizinde veya admin klasöründe olur, bu yüzden 'admin_login.php' varsayımı yapılıyor.
            // Proje yapısına göre bu yolun düzeltilmesi gerekebilir.
            header('Location: admin_login.php?error_msg=' . urlencode($error_param));
            exit;
        }
        return false;
    }
    // Optionally, store decoded admin data in a global or pass it around if needed
    // For example: $GLOBALS['current_admin_user'] = $admin_data;
    return $admin_data; // Or true, if you only care about the login state
}

/**
 * Supabase API'sine file_get_contents ile istek gönderir.
 * Hem anonim anahtar hem de service role anahtarı ile çalışabilir.
 *
 * @param string $method HTTP metodu (GET, POST, PATCH, DELETE).
 * @param string $path API endpoint yolu (örn: /rest/v1/tablo_adi).
 * @param array $data Gönderilecek veri (POST, PATCH için). GET için query string parametreleri olarak eklenebilir.
 * @param array $custom_headers Ekstra özel header'lar (örn: ['Header-Name: Header-Value']).
 * @param bool $use_service_key True ise SUPABASE_SERVICE_ROLE_KEY kullanılır, false ise SUPABASE_KEY_ANON.
 * @return array API yanıtı ['data' => ..., 'http_code' => ..., 'error' => ...].
 */
function supabase_api_request($method, $path, $data = [], $custom_headers = [], $use_service_key = false) {
    // SUPABASE_URL config.php'den gelmeli
    if (!defined('SUPABASE_URL')) {
        return ['error' => ['message' => 'SUPABASE_URL tanımlı değil.'], 'http_code' => 0, 'data' => null];
    }
    $base_url = SUPABASE_URL;
    $request_url = $base_url . $path; // Sonradan GET parametreleri için güncellenecek

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

    $headers_array = [
        'apikey: ' . $apiKeyToUse,
        'Content-Type: application/json', // POST/PATCH için varsayılan, GET/DELETE için gereksiz ama zararsız
    ];

    if ($use_service_key) {
         $headers_array[] = 'Authorization: Bearer ' . $apiKeyToUse;
    }

    if (in_array(strtoupper($method), ['POST', 'PATCH', 'DELETE'])) {
        $headers_array[] = 'Prefer: return=representation';
    }

    if (!empty($custom_headers)) {
        $headers_array = array_merge($headers_array, $custom_headers);
    }

    $http_method = strtoupper($method);
    $options = [
        'http' => [
            'method' => $http_method,
            'header' => implode("
", $headers_array),
            'timeout' => 30, // İstek zaman aşımı (saniye)
            'ignore_errors' => true, // HTTP hata kodlarında içeriği okumak için
        ],
        // SSL doğrulaması için Vercel ortamında genellikle gerekmez ama gerekirse eklenebilir:
        // 'ssl' => [
        //     'verify_peer' => true,
        //     'verify_peer_name' => true,
        // ]
    ];

    $request_body_json = '';

    switch ($http_method) {
        case 'POST':
        case 'PATCH':
            if (!empty($data)) {
                $request_body_json = json_encode($data);
                $options['http']['content'] = $request_body_json;
            } else {
                // Boş $data ile POST/PATCH için, Content-Length: 0 anlamına gelen boş string.
                $options['http']['content'] = ''; 
            }
            break;
        case 'DELETE':
            // DELETE için $data payload olarak gönderilmez. URL'e eklenmiş olabilir.
            break;
        case 'GET':
            if (!empty($data)) {
                 $queryString = http_build_query($data);
                 $request_url .= (strpos($request_url, '?') === false ? '?' : '&') . $queryString;
            }
            // GET isteklerinde Content-Type göndermek gereksiz, headerlardan çıkarılabilir ama zararı da yok.
            break;
        default:
            return ['error' => ['message' => "Desteklenmeyen HTTP metodu: $http_method"], 'http_code' => 0, 'data' => null];
    }

    $context = stream_context_create($options);
    $response_body = @file_get_contents($request_url, false, $context); // @ ile olası E_WARNING'leri bastırıyoruz.

    $httpCode = 0;
    if (isset($http_response_header) && is_array($http_response_header) && count($http_response_header) > 0) {
        // HTTP durum kodunu headerdan al
        if (preg_match('/^HTTP\/[1-9\.]+\s+([0-9]{3})/', $http_response_header[0], $matches)) {
            $httpCode = intval($matches[1]);
        }
    }

    // Detaylı loglama (Geliştirme sırasında çok faydalı)
    $log_payload_str = ($http_method === 'POST' || $http_method === 'PATCH') ? $request_body_json : ( ($http_method === 'GET' && !empty($data)) ? json_encode($data) : 'N/A' );
    $file_get_contents_error = error_get_last(); // file_get_contents @ ile kullanıldığı için hataları buradan alalım.

    error_log("Supabase API Request --- URL: " . $request_url . " | Method: $http_method | HTTP Code: $httpCode | file_get_contents error: " . ($file_get_contents_error ? json_encode($file_get_contents_error) : 'None') . " | Payload: ".$log_payload_str." | Headers: ".json_encode($headers_array)." | Raw Response (ilk 500 karakter): ".substr((string)$response_body, 0, 500));

    if ($response_body === false) {
        $errorMessage = "file_get_contents API isteği başarısız oldu.";
        if ($file_get_contents_error) {
            $errorMessage .= " Hata: " . $file_get_contents_error['message'] . " (Dosya: " . $file_get_contents_error['file'] . ", Satır: " . $file_get_contents_error['line'] . ")";
        }
        return ['error' => ['message' => $errorMessage], 'http_code' => $httpCode, 'data' => null]; // $httpCode burada 0 olabilir
    }

    $responseData = json_decode($response_body, true);

    // json_decode hatası kontrolü (özellikle boş veya geçersiz JSON yanıtları için)
    if (json_last_error() !== JSON_ERROR_NONE && !empty(trim($response_body)) && !($httpCode >= 200 && $httpCode < 300 && trim($response_body) === '')) {
         // Sadece boş olmayan ve başarılı olmayan (veya başarılı ama boş olmayan) yanıtlar için json hatası logla
        error_log("Supabase API JSON Decode Error: " . json_last_error_msg() . " | Raw Response: " . substr($response_body, 0, 200));
    }


    if ($httpCode < 200 || $httpCode >= 300) {
        $errorMessage = 'API Hatası (HTTP ' . $httpCode . ')';
        if (isset($responseData['message'])) $errorMessage = $responseData['message'];
        elseif (isset($responseData['error_description'])) $errorMessage = $responseData['error_description'];
        elseif (isset($responseData['error']) && is_string($responseData['error'])) $errorMessage = $responseData['error'];
        elseif (isset($responseData['msg'])) $errorMessage = $responseData['msg'];
        elseif (is_null($responseData) && !empty($response_body) && $response_body !== "[]") { // [] boş array için hata vermemeli
             // Eğer responseData null ise ve response_body boş değilse, muhtemelen JSON parse hatası
             if (json_last_error() !== JSON_ERROR_NONE) {
                 $errorMessage = "Geçersiz JSON yanıtı (HTTP $httpCode): " . json_last_error_msg() . " - " . substr($response_body, 0, 200);
             } else {
                 $errorMessage = "Geçersiz JSON yanıtı (HTTP $httpCode): " . substr($response_body, 0, 200);
             }
        } elseif (empty($response_body) && $httpCode !== 204) { // 204 No Content hariç boş yanıtlar
            $errorMessage = "API'den boş yanıt alındı (HTTP $httpCode)";
        }

        return ['error' => ['message' => $errorMessage], 'http_code' => $httpCode, 'data' => $responseData];
    }
    
    // Başarılı yanıt, ancak response_body boş ve responseData null ise (örneğin 204 No Content)
    // $responseData'nın null kalması genellikle sorun değil, çağıran kod bunu kontrol etmeli.
    // Eğer $responseData null ise ve $response_body boş değilse, yukarıdaki $httpCode >= 300 bloğu bunu yakalamalıydı.

    return ['data' => $responseData, 'http_code' => $httpCode, 'error' => null];
}
?>