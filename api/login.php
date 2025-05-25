<?php
ob_start(); // Çıktı tamponlamasını başlat

// Cookie varsa dashboard'a yönlendir
if (isset($_COOKIE['asikzade_user_session'])) {
    // İsteğe bağlı: Cookie'yi doğrula
    header('Location: /dashboard.php'); // Yolları / ile başlatın
    exit;
}

// products_data.php ve sepet sayısını dahil et
include 'products_data.php';
$cart_item_count = 0;
if (function_exists('get_cart_count')) {
    $cart_item_count = get_cart_count();
}

$error_message_display = '';
$form_email_value = $_GET['email'] ?? ''; // Formu tekrar doldurmak için

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'bos_alanlar':
            $error_message_display = "E-posta ve şifre alanları zorunludur.";
            break;
        case 'gecersiz_email':
            $error_message_display = "Geçersiz e-posta formatı.";
            break;
        case 'api_hata':
            $error_message_display = "Kullanıcı bilgileri alınırken bir sunucu hatası oluştu.";
            break;
        case 'kullanici_yok':
            $error_message_display = "Bu e-posta adresi ile kayıtlı bir kullanıcı bulunamadı.";
            break;
        case 'sifre_eksik':
            $error_message_display = "Kullanıcı hesabı düzgün yapılandırılmamış.";
            break;
        case 'sifre_yanlis':
            $error_message_display = "E-posta veya şifre hatalı.";
            break;
        default:
            $error_message_display = "Bilinmeyen bir hata oluştu.";
            break;
    }
}
?>
<!DOCTYPE html>
<!-- ... login.php HTML kodunuz ... -->
            <?php if (!empty($error_message_display)): ?>
                <div class="message-box message-error"><?php echo htmlspecialchars($error_message_display); ?></div>
            <?php endif; ?>
            <!-- ... -->
            <form action="/login_process.php" method="POST" class="login-form"> <!-- action yolunu / ile başlatın -->
                <div class="input-group">
                    <label for="email">E-posta Adresiniz</label>
                    <input type="email" id="email" name="email" placeholder="ornek@eposta.com" required value="<?php echo htmlspecialchars($form_email_value); ?>">
                </div>
            <!-- ... geri kalan form ... -->
<?php
ob_end_flush();
?>