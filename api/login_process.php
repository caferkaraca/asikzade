<?php
ob_start(); // Çıktı tamponlamasını başlat

// config.php'yi dahil et (Supabase bağlantısı ve fonksiyonlar için)
// Bu dosyanın (login_process.php) bulunduğu yere göre config.php'nin yolunu ayarlayın.
require_once 'config.php'; // Bu config içinde supabase_api_request tanımlı olmalı

$form_data_email = ''; // Hata durumunda formu tekrar doldurmak için

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $sifre_girilen = $_POST['sifre'] ?? '';
    $form_data_email = $email; // Form verisini sakla

    if (empty($email) || empty($sifre_girilen)) {
        // Cookie ile hata mesajı göndermek yerine, login.php'ye query string ile gönderebilirsiniz
        // veya login.php'de bu durumları doğrudan JavaScript ile kontrol edebilirsiniz.
        // Şimdilik basitçe yönlendirelim, login.php bu durumu yakalayabilir.
        header('Location: /login.php?error=bos_alanlar'); // Yolları / ile başlatın
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: /login.php?error=gecersiz_email'); // Yolları / ile başlatın
        exit;
    }

    // Kullanıcıyı e-posta ile veritabanından bul
    $path = '/rest/v1/kullanicilar?select=' . rawurlencode('id,ad,soyad,email,sifre_hash') . '&email=eq.' . rawurlencode($email);
    $userQueryResult = supabase_api_request(
        'GET',
        $path,
        [],
        [],
        true  // SERVICE ROLE KEY KULLANIN (Supabase RLS politikalarına göre gerekebilir)
    );

    $error_query_param = '';

    if (!empty($userQueryResult['error'])) {
        error_log("Supabase DB Get User Error: " . ($userQueryResult['error']['message'] ?? 'Bilinmeyen API hatası') . " For email: " . $email);
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
                'user_ad' => $userDataFromDB['ad'],
                'user_soyad' => $userDataFromDB['soyad']
            ];
            $cookie_value = json_encode($cookie_user_data);
            // Cookie'yi 30 gün için ayarla, tüm site için geçerli olsun (path="/")
            setcookie("asikzade_user_session", $cookie_value, time() + (86400 * 30), "/");

            header('Location: /dashboard.php'); // Yolları / ile başlatın
            ob_end_flush();
            exit;
        } else {
            $error_query_param = 'sifre_yanlis';
        }
    }

    // Hata durumunda login.php'ye hata parametresi ve e-posta ile yönlendir
    $redirect_url = '/login.php';
    if (!empty($error_query_param)) {
        $redirect_url .= '?error=' . $error_query_param;
    }
    if (!empty($form_data_email)) {
        $redirect_url .= (strpos($redirect_url, '?') === false ? '?' : '&') . 'email=' . urlencode($form_data_email);
    }
    header('Location: ' . $redirect_url);
    ob_end_flush();
    exit;

} else {
    header('Location: /login.php'); // Yolları / ile başlatın
    ob_end_flush();
    exit;
}
// ob_end_flush(); // Gerekirse, tüm yönlendirmelerden sonra çağrılabilir
?>