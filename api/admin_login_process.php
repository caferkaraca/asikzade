<?php
// config.php veya admin_config.php'yi dahil et
// Hangisi supabase_api_request() fonksiyonunu içeriyorsa onu kullanın.
// Eğer admin_config.php ise:
require_once 'admin_config.php'; // session_start() bu dosyada olmamalı veya bu dosya session'sız çalışabilmeli
// Eğer normal kullanıcı girişindeki config.php ise:
// require_once 'config.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_girilen = trim($_POST['admin_email'] ?? '');
    $sifre_girilen = $_POST['admin_password'] ?? '';

    // Hata durumunda e-postayı geri göndermek için URL'ye ekle
    $redirect_url_params = '?email=' . urlencode($email_girilen);

    if (empty($email_girilen) || empty($sifre_girilen)) {
        header('Location: admin_login.php' . $redirect_url_params . '&error=bos_alanlar');
        exit;
    }

    if (!filter_var($email_girilen, FILTER_VALIDATE_EMAIL)) {
        header('Location: admin_login.php' . $redirect_url_params . '&error=gecersiz_email');
        exit;
    }

    // 1. Kullanıcıyı e-posta ile 'kullanicilar' tablosundan bul
    $path = '/rest/v1/kullanicilar?select=id,ad,soyad,email,sifre_hash,is_admin&email=eq.' . urlencode($email_girilen) . '&limit=1';
    $userQueryResult = supabase_api_request('GET', $path, []);

    if (isset($userQueryResult['error'])) {
        error_log("Admin Login - Supabase DB Get User Error: " . ($userQueryResult['error']['message'] ?? 'Bilinmiyor') . " For email: " . $email_girilen);
        // Genel bir sunucu hatası olduğu için e-postayı geri göndermeyebiliriz, veya gönderebiliriz.
        header('Location: admin_login.php?error=api_hata'); // E-postayı burada göndermemek daha iyi olabilir
        exit;
    }

    if (empty($userQueryResult['data'])) {
        error_log("Admin Login Attempt Failed (Email Not Found in kullanicilar) - Email: " . $email_girilen);
        header('Location: admin_login.php' . $redirect_url_params . '&error=kullanici_yok'); // veya sifre_yanlis
        exit;
    }

    $userDataFromDB = $userQueryResult['data'][0];
    $sifre_hash_from_db = $userDataFromDB['sifre_hash'] ?? null;
    $is_admin_from_db = $userDataFromDB['is_admin'] ?? false;

    if ($sifre_hash_from_db === null) {
        error_log("Admin Login - Missing password hash for user ID: " . ($userDataFromDB['id'] ?? 'N/A') . " Email: " . $email_girilen);
        header('Location: admin_login.php' . $redirect_url_params . '&error=sifre_eksik');
        exit;
    }

    if (password_verify($sifre_girilen, $sifre_hash_from_db)) {
        $is_admin = false;
        if (is_bool($is_admin_from_db)) {
            $is_admin = $is_admin_from_db;
        } elseif (is_string($is_admin_from_db)) {
            $is_admin_string_lower = strtolower(trim($is_admin_from_db));
            $is_admin = ($is_admin_string_lower === 'true' || $is_admin_string_lower === '1');
        }
        // Eğer sayısal ise: $is_admin = (bool)$is_admin_from_db;

        if ($is_admin) {
            // Admin girişi başarılı
            $admin_token = bin2hex(random_bytes(32)); // Generate a secure token

            $cookie_expire = time() + (86400 * 30); // 30 gün
            $cookie_path = "/"; // Cookie valid across the entire domain
            $cookie_domain = ""; // Current domain
            $cookie_secure = isset($_SERVER["HTTPS"]); // True if HTTPS
            $cookie_httponly = true; // Prevent JS access
            $cookie_samesite = 'Lax'; // CSRF protection

            setcookie(ADMIN_AUTH_COOKIE_NAME, $admin_token, [
                'expires' => $cookie_expire,
                'path' => $cookie_path,
                'domain' => $cookie_domain,
                'secure' => $cookie_secure,
                'httponly' => $cookie_httponly,
                'samesite' => $cookie_samesite
            ]);

            // Şifre hash'inin güncellenmesi gerekip gerekmediğini kontrol et (opsiyonel)
            if (password_needs_rehash($sifre_hash_from_db, PASSWORD_DEFAULT)) {
                $new_hash = password_hash($sifre_girilen, PASSWORD_DEFAULT);
                $update_path = '/rest/v1/kullanicilar?id=eq.' . $userDataFromDB['id'];
                $update_data = ['sifre_hash' => $new_hash];
                // Supabase RLS kurallarınızın bu güncellemeyi anon key ile (veya uygun bir rolle)
                // yapmaya izin verdiğinden emin olun. Genellikle service_role key gerekir.
                // $rehashResult = supabase_api_request('PATCH', $update_path, $update_data, [], true); // true, service_role key kullanımı için
                // if (isset($rehashResult['error'])) {
                //    error_log("Admin password rehash failed for user: " . $email_girilen . ". Error: " . ($rehashResult['error']['message'] ?? 'Unknown'));
                // } else {
                //    error_log("Admin password rehashed successfully for user: " . $email_girilen);
                // }
                error_log("Admin password needs rehash for user: " . $email_girilen . ". Consider updating the hash in 'kullanicilar' table (manual or with service_role).");
            }

            header('Location: admin_dashboard.php');
            exit;
        } else {
            // Şifre doğru ama kullanıcı admin değil
            error_log("Admin Login Attempt - User Not Admin: " . $email_girilen . " (ID: " . $userDataFromDB['id'] . ")");
            header('Location: admin_login.php' . $redirect_url_params . '&error=yetki_yok');
            exit;
        }
    } else {
        // Şifre yanlış
        error_log("Admin Login Attempt Failed (Password Mismatch) - Email: " . $email_girilen);
        header('Location: admin_login.php' . $redirect_url_params . '&error=sifre_yanlis');
        exit;
    }

} else {
    // POST isteği değilse login sayfasına yönlendir
    header('Location: admin_login.php');
    exit;
}
?>