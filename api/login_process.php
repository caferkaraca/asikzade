<?php
ob_start(); // Çıktı tamponlamasını başlat

// config.php'yi dahil et (Supabase bağlantısı ve fonksiyonlar için)
// Bu dosyanın (login_process.php) bulunduğu yere göre config.php'nin yolunu ayarlayın.
// Eğer config.php bir üst dizindeyse: require_once __DIR__ . '/../config.php';
// Eğer aynı dizindeyse:
require_once 'config.php'; // Bu config içinde supabase_api_request (cURL'siz) tanımlı olmalı
                          // ve config.php'de artık session_start() OLMAMALI.

$form_data_email_value = ''; // Hata durumunda formu tekrar doldurmak için

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $sifre_girilen = $_POST['sifre'] ?? '';
    $form_data_email_value = $email; // E-postayı sakla

    $error_query_param = ''; // Hata kodunu saklamak için

    if (empty($email) || empty($sifre_girilen)) {
        $error_query_param = 'bos_alanlar';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_query_param = 'gecersiz_email';
    } else {
        // Kullanıcıyı e-posta ile veritabanından bul
        // Tablo adınızın 'kullanicilar' olduğundan emin olun.
        $path = '/rest/v1/kullanicilar?select=' . rawurlencode('id,ad,soyad,email,sifre_hash') . '&email=eq.' . rawurlencode($email);
        
        // supabase_api_request fonksiyonunun service key kullandığından emin olun
        // (ya fonksiyona parametre olarak true geçin ya da fonksiyon içinde service key'i zorunlu kılın)
        $userQueryResult = supabase_api_request(
            'GET',
            $path,
            [],    // data: GET için boş
            [],    // custom_headers: boş
            true   // use_service_key: TRUE olarak ayarlandı (sifre_hash okumak için)
        );

        if (!empty($userQueryResult['error'])) {
            error_log("Supabase DB Get User Error: " . ($userQueryResult['error']['message'] ?? 'Bilinmeyen API hatası') . " For email: " . $email . " | Response: " . json_encode($userQueryResult));
            $error_query_param = 'api_hata';
        } elseif (empty($userQueryResult['data'])) {
            $error_query_param = 'kullanici_yok';
        } else {
            $userDataFromDB = $userQueryResult['data'][0];
            $sifre_hash_from_db = $userDataFromDB['sifre_hash'] ?? null;

            if ($sifre_hash_from_db === null) {
                error_log("Missing password hash for user ID: " . ($userDataFromDB['id'] ?? 'N/A') . " for email: " . $email);
                $error_query_param = 'sifre_eksik';
            } elseif (password_verify($sifre_girilen, $sifre_hash_from_db)) {
                // Başarılı Giriş
                $cookie_user_data = [
                    'user_id' => $userDataFromDB['id'],
                    'user_email' => $userDataFromDB['email'],
                    'user_ad' => $userDataFromDB['ad'] ?? '', // 'ad' null ise boş string
                    'user_soyad' => $userDataFromDB['soyad'] ?? '' // 'soyad' null ise boş string
                ];
                $cookie_value = json_encode($cookie_user_data);
                // Cookie'yi 30 gün için ayarla, tüm site için geçerli olsun (path="/")
                // HTTPS üzerinden güvenli cookie (eğer siteniz HTTPS ise)
                $secure_cookie = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
                setcookie("asikzade_user_session", $cookie_value, time() + (86400 * 30), "/", "", $secure_cookie, true); // HttpOnly eklendi

                header('Location: /dashboard.php'); // KÖK DİZİNDE OLDUĞUNU VARSAYIYORUM
                ob_end_flush();
                exit;
            } else {
                $error_query_param = 'sifre_yanlis';
            }
        }
    }

    // Hata durumunda login.php'ye hata parametresi ve e-posta ile yönlendir
    $redirect_url = '/login.php'; // KÖK DİZİNDE OLDUĞUNU VARSAYIYORUM
    if (!empty($error_query_param)) {
        $redirect_url .= '?error=' . $error_query_param;
    }
    if (!empty($form_data_email_value)) { // Değişken adını düzelttim
        // Eğer URL'de zaten bir '?' varsa '&' ile, yoksa '?' ile ekle
        $redirect_url .= (strpos($redirect_url, '?') === false ? '?' : '&') . 'email=' . urlencode($form_data_email_value);
    }
    header('Location: ' . $redirect_url);
    ob_end_flush();
    exit;

} else {
    // POST dışındaki istekleri login sayfasına yönlendir
    header('Location: /login.php'); // KÖK DİZİNDE OLDUĞUNU VARSAYIYORUM
    ob_end_flush();
    exit;
}
// ob_end_flush(); // Buraya gerek yok, tüm çıkış yollarında zaten var.
?>