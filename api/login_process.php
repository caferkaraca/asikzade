<?php

// config.php, supabase_api_request fonksiyonunu içeren admin_config.php'yi dahil etmeli.
// Bu dosyanın (login_process.php) bulunduğu yere göre config.php'nin yolunu ayarlayın.
// Eğer login_process.php ve config.php aynı dizindeyse (örn: api/):
require_once 'config.php';
// Eğer config.php bir üst dizindeyse:
// require_once __DIR__ . '/../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $sifre_girilen = $_POST['sifre'] ?? '';

    $_SESSION['form_data'] = ['email' => $email]; // Form verisini session'a kaydet (hatada tekrar doldurmak için)

    if (empty($email) || empty($sifre_girilen)) {
        $_SESSION['error_message'] = "E-posta ve şifre alanları zorunludur.";
        header('Location: /login.php'); // login.php'nin doğru yolunu belirtin
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Geçersiz e-posta formatı.";
        header('Location: /login.php');
        exit;
    }

    // Kullanıcıyı e-posta ile veritabanından bul
    $path = '/rest/v1/kullanicilar?select=' . rawurlencode('id,ad,soyad,email,sifre_hash') . '&email=eq.' . rawurlencode($email);
    $userQueryResult = supabase_api_request(
        'GET',
        $path,
        [],    // $data: GET isteği için boş dizi
        [],    // $custom_headers: Boş dizi
        false  // $use_service_key: Giriş için anonim anahtar kullanılacak (false)
    );

    if (!empty($userQueryResult['error'])) { // Hata kontrolünü önce yap
        $_SESSION['error_message'] = "Kullanıcı bilgileri alınırken bir hata oluştu: " . ($userQueryResult['error']['message'] ?? 'Bilinmeyen API hatası');
        error_log("Supabase DB Get User Error: " . ($userQueryResult['error']['message'] ?? 'Bilinmeyen API hatası') . " For email: " . $email . " | Response: " . json_encode($userQueryResult));
        header('Location: /login.php');
        exit;
    }

    if (empty($userQueryResult['data'])) {
        $_SESSION['error_message'] = "Bu e-posta adresi ile kayıtlı bir kullanıcı bulunamadı.";
        header('Location: /login.php');
        exit;
    }

    $userDataFromDB = $userQueryResult['data'][0];
    $sifre_hash_from_db = $userDataFromDB['sifre_hash'] ?? null;

    if ($sifre_hash_from_db === null) {
        $_SESSION['error_message'] = "Kullanıcı hesabı düzgün yapılandırılmamış (şifre bilgisi eksik).";
        error_log("Missing password hash for user ID: " . ($userDataFromDB['id'] ?? 'N/A') . " for email: " . $email);
        header('Location: /login.php');
        exit;
    }

    if (password_verify($sifre_girilen, $sifre_hash_from_db)) {
        unset($_SESSION['form_data']); // Başarılı girişte form verisini temizle
        $_SESSION['user_id'] = $userDataFromDB['id'];
        $_SESSION['user_email'] = $userDataFromDB['email'];
        $_SESSION['user_ad'] = $userDataFromDB['ad'];
        $_SESSION['user_soyad'] = $userDataFromDB['soyad'];

        // Dashboard'a yönlendir. Dashboard.php'nin yolunu kontrol edin.
        // Eğer login_process.php ve dashboard.php aynı dizindeyse (örn: api/):
        header('Location: /dashboard.php');
        // Eğer dashboard.php kök dizindeyse:
        // header('Location: ../dashboard.php');
        exit;
    } else {
        $_SESSION['error_message'] = "E-posta veya şifre hatalı.";
        header('Location: /login.php');
        exit;
    }

} else {
    // POST dışındaki istekleri login sayfasına yönlendir
    header('Location: /login.php');
    exit;
}
?>