<?php
// config.php veya admin_config.php'yi dahil et
// Hangisi session_start() ve supabase_api_request() fonksiyonlarını içeriyorsa onu kullanın.
// Eğer admin_config.php ise:
require_once 'admin_config.php';
// Eğer normal kullanıcı girişindeki config.php ise:
// require_once 'config.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_girilen = trim($_POST['admin_email'] ?? ''); // Formdaki input name'i admin_email
    $sifre_girilen = $_POST['admin_password'] ?? '';    // Formdaki input name'i admin_password

    // Form verilerini session'a kaydet (hatalı girişte formu dolu tutmak için)
    $_SESSION['form_data_admin_login'] = ['email' => $email_girilen];

    if (empty($email_girilen) || empty($sifre_girilen)) {
        $_SESSION['admin_error_message'] = "E-posta ve şifre alanları zorunludur.";
        header('Location: admin_login.php');
        exit;
    }

    if (!filter_var($email_girilen, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['admin_error_message'] = "Geçersiz e-posta formatı.";
        header('Location: admin_login.php');
        exit;
    }

    // 1. Kullanıcıyı e-posta ile 'kullanicilar' tablosundan bul
    // İhtiyaç duyulan sütunlar: id, email, sifre_hash, is_admin (ve belki ad, soyad)
    // Tablo adınızın 'kullanicilar' ve public şemasında olduğunu varsayıyoruz.
    $path = '/rest/v1/kullanicilar?select=id,ad,soyad,email,sifre_hash,is_admin&email=eq.' . urlencode($email_girilen) . '&limit=1';

    // supabase_api_request fonksiyonunun üçüncü parametresi API anahtarı değil, gönderilecek veri (data).
    // API anahtarı (anon key) fonksiyonun içinde zaten kullanılıyor.
    $userQueryResult = supabase_api_request('GET', $path, []); // Veri göndermiyoruz, GET isteği

    if (isset($userQueryResult['error'])) {
        $_SESSION['admin_error_message'] = "Kullanıcı bilgileri alınırken bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
        error_log("Admin Login - Supabase DB Get User Error: " . ($userQueryResult['error']['message'] ?? 'Bilinmiyor') . " For email: " . $email_girilen);
        header('Location: admin_login.php');
        exit;
    }

    if (empty($userQueryResult['data'])) {
        $_SESSION['admin_error_message'] = "Geçersiz e-posta veya şifre."; // E-posta bulunamadı
        error_log("Admin Login Attempt Failed (Email Not Found in kullanicilar) - Email: " . $email_girilen);
        header('Location: admin_login.php');
        exit;
    }

    // Kullanıcı bulundu
    $userDataFromDB = $userQueryResult['data'][0]; // İlk eşleşen kullanıcıyı al
    $sifre_hash_from_db = $userDataFromDB['sifre_hash'] ?? null;
    $is_admin_from_db = $userDataFromDB['is_admin'] ?? false; // Varsayılan olarak false

    if ($sifre_hash_from_db === null) {
        $_SESSION['admin_error_message'] = "Hesap düzgün yapılandırılmamış (şifre eksik).";
        error_log("Admin Login - Missing password hash for user ID: " . ($userDataFromDB['id'] ?? 'N/A') . " Email: " . $email_girilen);
        header('Location: admin_login.php');
        exit;
    }

    // 2. Girilen şifre ile veritabanındaki hash'i karşılaştır
    if (password_verify($sifre_girilen, $sifre_hash_from_db)) {
        // Şifre doğru, şimdi admin mi diye kontrol et

        // is_admin değerini doğru bir boolean'a çevir (eğer metin olarak geliyorsa)
        $is_admin = false;
        if (is_bool($is_admin_from_db)) {
            $is_admin = $is_admin_from_db;
        } elseif (is_string($is_admin_from_db)) {
            $is_admin_string_lower = strtolower(trim($is_admin_from_db));
            $is_admin = ($is_admin_string_lower === 'true' || $is_admin_string_lower === '1');
        }
        // Eğer veritabanında sayısal (0/1) ise, (bool) cast'i yeterli olabilir:
        // $is_admin = (bool)$is_admin_from_db;

        if ($is_admin) {
            // Admin girişi başarılı
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $userDataFromDB['id'];
            $_SESSION['admin_email'] = $userDataFromDB['email'];
            $_SESSION['admin_user_ad'] = $userDataFromDB['ad'] ?? 'Admin'; // Varsa adını al, yoksa 'Admin'
            $_SESSION['admin_user_soyad'] = $userDataFromDB['soyad'] ?? '';
            $_SESSION['is_admin_user'] = true; // Bu, admin_check_login için önemli

            unset($_SESSION['admin_error_message']);
            unset($_SESSION['form_data_admin_login']); // Başarılı girişte form verilerini temizle

            // Şifre hash'inin güncellenmesi gerekip gerekmediğini kontrol et (opsiyonel)
            if (password_needs_rehash($sifre_hash_from_db, PASSWORD_DEFAULT)) {
                $new_hash = password_hash($sifre_girilen, PASSWORD_DEFAULT);
                $update_path = '/rest/v1/kullanicilar?id=eq.' . $userDataFromDB['id'];
                $update_data = ['sifre_hash' => $new_hash];
                // Bu güncelleme için kullanıcının kendi kaydını güncellemesine izin veren bir RLS veya service_role key gerekebilir.
                // supabase_api_request('PATCH', $update_path, $update_data, [], true); // true -> service_role veya uygun RLS
                error_log("Admin password needs rehash for user: " . $email_girilen . ". Consider updating the hash in 'kullanicilar' table.");
            }

            header('Location: admin_dashboard.php');
            exit;
        } else {
            // Şifre doğru ama kullanıcı admin değil
            $_SESSION['admin_error_message'] = "Giriş başarılı ancak admin yetkiniz bulunmamaktadır.";
            error_log("Admin Login Attempt - User Not Admin: " . $email_girilen . " (ID: " . $userDataFromDB['id'] . ")");
            header('Location: admin_login.php');
            exit;
        }
    } else {
        // Şifre yanlış
        $_SESSION['admin_error_message'] = "Geçersiz e-posta veya şifre.";
        error_log("Admin Login Attempt Failed (Password Mismatch) - Email: " . $email_girilen);
        header('Location: admin_login.php');
        exit;
    }

} else {
    // POST isteği değilse login sayfasına yönlendir
    header('Location: admin_login.php');
    exit;
}
?>