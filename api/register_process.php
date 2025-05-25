<?php
require_once 'config.php'; // session_start(), SUPABASE sabitleri ve supabase_api_request() için

// config.php'de session_start() yoksa buraya ekleyin:
// if (session_status() == PHP_SESSION_NONE) {
//     session_start();
// }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ad = trim($_POST['ad'] ?? '');
    $soyad = trim($_POST['soyad'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? '')); // E-postayı küçük harfe çevirerek tutarlılık sağla
    $sifre = $_POST['sifre'] ?? '';
    $sifre_tekrar = $_POST['sifre_tekrar'] ?? '';

    // Form verilerini session'da tut (hata durumunda inputları tekrar doldurmak için)
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
    if (strlen($sifre) < 6) { // Supabase Auth varsayılanı 6 karakter
        $_SESSION['error_message'] = "Şifre en az 6 karakter olmalıdır.";
        header('Location: register.php');
        exit;
    }
    if ($sifre !== $sifre_tekrar) {
        $_SESSION['error_message'] = "Şifreler eşleşmiyor.";
        header('Location: register.php');
        exit;
    }

    // 1. E-postanın zaten kayıtlı olup olmadığını `kullanicilar` tablosunda kontrol et
    // E-posta kontrolü için service_role_key kullanmak daha güvenli olabilir,
    // çünkü anonim key ile RLS ayarlarınızın SELECT'e izin vermesi gerekir.
    // Eğer RLS SELECT'e izin veriyorsa anon_key de kullanılabilir.
    // Şimdilik service_role_key kullandığımızı varsayalım.
    $checkEmailResult = supabase_api_request(
        'GET',
        '/rest/v1/kullanicilar?select=id&email=eq.' . urlencode($email), // Sadece id yeterli
        [], // data
        [], // custom_headers
        true // use_service_key = true (E-posta kontrolü için admin yetkisi)
    );

    if ($checkEmailResult['error']) {
        $_SESSION['error_message'] = "E-posta kontrolü sırasında bir sunucu hatası oluştu. Lütfen daha sonra tekrar deneyin.";
        error_log("Supabase Email Check Error: " . ($checkEmailResult['error']['message'] ?? 'Bilinmeyen hata') . " | Email: " . $email);
        header('Location: register.php');
        exit;
    }

    // Eğer $checkEmailResult['data'] boş değilse, e-posta zaten var demektir.
    if (!empty($checkEmailResult['data'])) {
        $_SESSION['error_message'] = "Bu e-posta adresi ile zaten bir hesap mevcut. Lütfen farklı bir e-posta deneyin veya giriş yapın.";
        header('Location: register.php');
        exit;
    }

    // 2. Şifreyi hash'le
    $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);
    if ($sifre_hash === false) {
        $_SESSION['error_message'] = "Şifre oluşturulurken bir sistem hatası oluştu. Lütfen tekrar deneyin.";
        error_log("password_hash failed for email: " . $email);
        header('Location: register.php');
        exit;
    }

    // 3. Kullanıcıyı `kullanicilar` tablosuna ekle
    // Yeni kullanıcı ekleme işlemi genellikle anonim key ile yapılır (eğer RLS izin veriyorsa).
    // Veya eğer Supabase Auth kullanıyorsanız, /auth/v1/signup endpoint'i kullanılabilir.
    // Bu örnekte direkt tabloya ekleme yapıyoruz (RLS'in anonim insert'e izin verdiğini varsayarak).
    // Eğer Supabase Auth'un kendi signup endpoint'ini kullanacaksanız, bu kısım değişir.
    $newUserData = [
        'ad' => $ad,
        'soyad' => $soyad,
        'email' => $email,
        'sifre_hash' => $sifre_hash
        // 'olusturulma_tarihi' gibi alanlar DB tarafından otomatik atanmalı (DEFAULT now())
        // 'id' alanı da DB tarafından otomatik atanmalı (DEFAULT uuid_generate_v4())
    ];

    // Yeni kullanıcıyı eklerken hangi API anahtarının kullanılacağına karar verin.
    // Genellikle, anonim bir kullanıcının kayıt olması için `SUPABASE_KEY_ANON` kullanılır
    // ve `kullanicilar` tablosundaki RLS kuralları buna izin vermelidir.
    // Eğer `service_role_key` kullanırsanız RLS bypass edilir.
    // Bu örnekte, `SUPABASE_KEY_ANON` (yani $use_service_key = false) kullanıldığını varsayalım.
    // Ancak, eğer `supabase_api_request` fonksiyonunuz $apiKeyToUse parametresini destekliyorsa,
    // doğrudan `SUPABASE_KEY_ANON` sabitini oraya geçmek daha iyi olurdu.
    // Mevcut `supabase_api_request` fonksiyonunuzda `use_service_key` parametresi var.
    $insertResult = supabase_api_request(
        'POST',
        '/rest/v1/kullanicilar',
        $newUserData, // $data
        [],            // $custom_headers
        false          // $use_service_key = false (Anonim kullanıcının kayıt olması için)
                       // ÖNEMLİ: Eğer bu false iken hata alıyorsanız,
                       // Supabase'de 'kullanicilar' tablosu için public (anon) rolüne INSERT yetkisi vermeniz gerekir.
                       // Veya burayı true yapıp service_role_key ile ekleyebilirsiniz.
    );


    if ($insertResult['error'] || $insertResult['http_code'] >= 300) { // 201 Created beklenir
        $errorMessage = $insertResult['error']['message'] ?? ($insertResult['data']['message'] ?? 'Kullanıcı kaydı sırasında bilinmeyen bir veritabanı hatası oluştu.');
        $_SESSION['error_message'] = "Kayıt işlemi başarısız oldu: " . htmlspecialchars($errorMessage);
        error_log("Supabase User Insert Error: " . $errorMessage . " | Data: " . json_encode($newUserData) . " | Response: " . json_encode($insertResult));
        header('Location: register.php');
        exit;
    }
    
    // Başarılı kayıt sonrası
    unset($_SESSION['form_data']); // Form verilerini temizle
    $_SESSION['success_message'] = "Hesabınız başarıyla oluşturuldu! Şimdi giriş yapabilirsiniz.";
    header('Location: login.php'); // login.php'ye yönlendir
    exit;

} else {
    // POST isteği değilse register.php'ye geri yönlendir
    header('Location: register.php');
    exit;
}
?>