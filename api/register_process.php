<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ad = trim($_POST['ad'] ?? '');
    $soyad = trim($_POST['soyad'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sifre = $_POST['sifre'] ?? ''; // Şifreyi hash'lemeden önce al
    $sifre_tekrar = $_POST['sifre_tekrar'] ?? '';

    $_SESSION['form_data'] = ['ad' => $ad, 'soyad' => $soyad, 'email' => $email];

    // --- Temel Doğrulamalar ---
    if (empty($ad) || empty($soyad) || empty($email) || empty($sifre) || empty($sifre_tekrar)) {
        $_SESSION['error_message'] = "Lütfen tüm zorunlu alanları doldurun.";
        header('Location: register.php');
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Geçersiz e-posta formatı.";
        header('Location: register.php');
        exit;
    }
    if (strlen($sifre) < 6) { // Minimum şifre uzunluğu (Supabase Auth varsayılanı gibi)
        $_SESSION['error_message'] = "Şifre en az 6 karakter olmalıdır.";
        header('Location: register.php');
        exit;
    }
    if ($sifre !== $sifre_tekrar) {
        $_SESSION['error_message'] = "Şifreler eşleşmiyor.";
        header('Location: register.php');
        exit;
    }

    // 1. E-postanın zaten kayıtlı olup olmadığını kontrol et
    // Supabase REST endpoint: /rest/v1/kullanicilar?email=eq.{email_adresi}&select=id
    // `select=id` sadece id'yi çekmek için, `count` da kullanılabilir ama RLS'ye bağlı.
    $checkEmailResult = supabase_api_request(
        'GET',
        '/rest/v1/kullanicilar?select=email&email=eq.' . urlencode($email),
        SUPABASE_KEY
    );

    if ($checkEmailResult['error']) {
        $_SESSION['error_message'] = "E-posta kontrolü sırasında bir hata oluştu: " . $checkEmailResult['error']['message'];
        header('Location: register.php');
        exit;
    }

    if (!empty($checkEmailResult['data'])) {
        $_SESSION['error_message'] = "Bu e-posta adresi ile zaten bir hesap mevcut.";
        header('Location: register.php');
        exit;
    }

    // 2. Şifreyi hash'le
    $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);
    if ($sifre_hash === false) {
        $_SESSION['error_message'] = "Şifre oluşturulurken bir sorun oluştu.";
        error_log("password_hash failed for email: " . $email);
        header('Location: register.php');
        exit;
    }

    // 3. Kullanıcıyı `kullanicilar` tablosuna ekle
    $newUserData = [
        // 'id' alanı PostgreSQL'de DEFAULT uuid_generate_v4() olduğu için göndermiyoruz.
        'ad' => $ad,
        'soyad' => $soyad,
        'email' => $email,
        'sifre_hash' => $sifre_hash
        // 'telefon' ve 'adres' şimdilik NULL olacak (tabloda nullable olmalı veya default değeri olmalı)
    ];

    // Supabase REST endpoint: /rest/v1/kullanicilar
    $insertResult = supabase_api_request('POST', '/rest/v1/kullanicilar', SUPABASE_KEY, $newUserData);

    if ($insertResult['error'] || $insertResult['http_code'] >= 400) {
        // Supabase genellikle hatayı $insertResult['data']['message'] içinde döndürür
        // veya $insertResult['error']['message'] cURL/fonksiyon seviyesinde hata varsa.
        $errorMessage = $insertResult['error']['message'] ?? ($insertResult['data']['message'] ?? 'Kullanıcı eklenirken bilinmeyen bir hata oluştu.');
        $_SESSION['error_message'] = "Kayıt sırasında bir veritabanı hatası oluştu: " . $errorMessage;
        error_log("Supabase DB Insert Error: " . $errorMessage . " Data: " . json_encode($newUserData) . " Response: " . json_encode($insertResult));
        header('Location: register.php');
        exit;
    }
    
    // $insertResult['data'] genellikle eklenen satırın tamamını içerir (Prefer: return=representation sayesinde)
    // $createdUser = $insertResult['data'][0] ?? null; // Eğer dizi içinde dönüyorsa

    unset($_SESSION['form_data']);
    $_SESSION['success_message'] = "Hesabınız başarıyla oluşturuldu! Lütfen giriş yapın.";
    header('Location: login.php');
    exit;

} else {
    header('Location: register.php');
    exit;
}