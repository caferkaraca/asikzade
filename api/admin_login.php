<?php
// require_once 'admin_config.php'; // Eğer admin_config.php session_start() yapıyorsa ve artık session kullanılmayacaksa bu satır düzenlenmeli veya kaldırılabilir.
                                   // Sadece başka ayarlar için gerekiyorsa session_start() olmadan dahil edilebilir.

$error_message_display = null;
$form_email_value = '';

// URL'den hata mesajını al
if (isset($_GET['error'])) {
    // Burada $_GET['error'] değerine göre hata mesajlarını belirleyebilirsiniz.
    // Örneğin:
    switch ($_GET['error']) {
        case 'bos_alanlar':
            $error_message_display = "E-posta ve şifre alanları zorunludur.";
            break;
        case 'gecersiz_email':
            $error_message_display = "Geçersiz e-posta formatı.";
            break;
        case 'kullanici_yok':
            $error_message_display = "Bu e-posta adresi ile kayıtlı bir admin bulunamadı.";
            break;
        case 'sifre_yanlis':
            $error_message_display = "E-posta veya şifre hatalı.";
            break;
        case 'yetki_yok':
            $error_message_display = "Bu hesaba admin yetkisi tanımlanmamış.";
            break;
        // admin_login_process.php'den gelebilecek diğer hata kodları...
        default:
            $error_message_display = "Bilinmeyen bir hata oluştu veya geçersiz hata kodu.";
            break;
    }
}

// URL'den e-posta değerini al (formu tekrar doldurmak için)
if (isset($_GET['email'])) {
    $form_email_value = htmlspecialchars($_GET['email']);
}

// Eğer kullanıcı zaten admin olarak giriş yapmışsa (cookie ile kontrol)
// Bu cookie'nin adı ve değeri admin_login_process.php'de nasıl set edildiğine bağlı olacaktır.
// Örnek bir cookie adı: 'asikzade_admin_session'
if (isset($_COOKIE['asikzade_admin_session'])) {
    // İdealde, cookie'nin değeri de doğrulanmalı, sadece varlığı yeterli olmayabilir.
    // Bu, admin_login_process.php'de nasıl bir token oluşturulduğuna bağlı.
    // Şimdilik basitçe varlığını kontrol ediyoruz, bu "admin yetkisine sahip" anlamına gelecek şekilde kurgulanmalı.
    header('Location: admin_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi - AŞIKZADE</title>
  <link rel="stylesheet" href="/gecis_animasyonlari.css">
    <style>
        /* Stil kodlarınız aynı kalabilir */
        :root {
            --asikzade-content-bg: #fef6e6;
            --asikzade-green: #8ba86d;
            --asikzade-dark-green: #6a8252;
            --asikzade-dark-text: #2d3e2a;
            --asikzade-light-text: #fdfcf8;
            --asikzade-gray: #7a7a7a;
        }
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--asikzade-content-bg);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .login-container {
            background-color: #fff;
            padding: 40px 35px;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 420px;
            text-align: center;
        }
        .login-container img.logo {
            height: 70px;
            margin-bottom: 25px;
        }
        .login-container h1 {
            color: var(--asikzade-dark-text);
            margin-bottom: 30px;
            font-size: 1.9rem;
            font-weight: 500;
        }
        .form-group {
            margin-bottom: 22px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: #555;
            font-weight: 500;
        }
        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group input[type="email"] { /* E-posta için de aynı stil */
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--asikzade-green);
            box-shadow: 0 0 0 2px rgba(139, 168, 109, 0.2);
        }
        .login-btn {
            background-color: var(--asikzade-green);
            color: var(--asikzade-light-text);
            border: none;
            width: 100%;
            padding: 14px;
            border-radius: 25px;
            font-size: 1.05rem;
            font-weight: 500;
            cursor: pointer;
            margin-top: 15px;
            transition: background-color 0.3s;
        }
        .login-btn:hover {
            background-color: var(--asikzade-dark-green);
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size:0.9rem;
            text-align: left;
        }
    </style>
</head>
<body>
     <div id="sayfa-gecis-katmani"></div>
      <div id="sayfa-kapanis-katmani"></div>
    <div class="login-container">
        <img src="https://i.imgur.com/rdZuONP.png" alt="Aşıkzade Logo" class="logo">
        <h1>Admin Paneli Girişi</h1>
        <?php if ($error_message_display): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message_display); ?></p>
        <?php endif; ?>
        <form action="admin_login_process.php" method="POST">
            <div class="form-group">
                <label for="admin_email">E-posta Adresiniz</label>
                <input type="email" id="admin_email" name="admin_email" required value="info@asikzade.com">
            </div>
            <div class="form-group">
                <label for="admin_password">Şifreniz</label>
                <input type="password" id="admin_password" name="admin_password" Value="123456" required>
            </div>
            <button type="submit" class="login-btn">Giriş Yap</button>
        </form>
        <?php // unset($_SESSION['form_data_admin_login']); // Session artık kullanılmadığı için bu satır gereksiz. ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
    const kapanisKatmani = document.getElementById('sayfa-kapanis-katmani');
    const kapanisAnimasyonSuresi = 600; // CSS'teki animation-duration ile aynı olmalı (ms cinsinden)

    // Sadece aynı domaindeki ve yeni sekmede açılmayan linkleri yakala
    document.querySelectorAll('a[href]').forEach(link => {
        // Harici linkler, # ile başlayan anchor linkler veya _blank hedefleri hariç
        if (link.hostname === window.location.hostname &&
            !link.href.startsWith(window.location.origin + window.location.pathname + '#') && // Sayfa içi anchor değilse
            link.target !== '_blank' &&
            !link.href.startsWith('mailto:') &&
            !link.href.startsWith('tel:')) {

            link.addEventListener('click', function(event) {
                event.preventDefault(); // Varsayılan link davranışını engelle
                const hedefUrl = this.href;

                // Kapanış animasyonunu başlat
                kapanisKatmani.classList.add('aktif');

                // Animasyon bittikten sonra sayfayı yönlendir
                setTimeout(() => {
                    window.location.href = hedefUrl;
                }, kapanisAnimasyonSuresi);
            });
        }
    });

    // Tarayıcının geri/ileri butonları için (bfcache - back/forward cache)
    // Eğer sayfa bfcache'den yükleniyorsa, açılış animasyonunu tekrar oynatmayabilir.
    // Bu durumda katmanı manuel olarak gizleyebiliriz.
    // Bu kısım daha karmaşık senaryolar için ve her zaman %100 çalışmayabilir.
    window.addEventListener('pageshow', function(event) {
        const acilisKatmani = document.getElementById('sayfa-acilis-katmani');
        if (event.persisted) { // Sayfa bfcache'den yüklendiyse
            // Açılış katmanının animasyonu zaten oynamış olabilir,
            // bu yüzden manuel olarak gizleyebiliriz veya body'yi direkt görünür yapabiliriz.
            if (acilisKatmani) {
                acilisKatmani.style.opacity = '0';
                acilisKatmani.style.visibility = 'hidden';
                acilisKatmani.style.pointerEvents = 'none';
            }
            document.body.style.opacity = '1'; // Body'yi hemen göster
            // Gerekirse kapanış katmanını da sıfırla
            if (kapanisKatmani && kapanisKatmani.classList.contains('aktif')) {
                kapanisKatmani.classList.remove('aktif');
                // Stilini CSS'teki başlangıç durumuna getirebiliriz.
                kapanisKatmani.style.clipPath = 'circle(0% at 50% 50%)';
                kapanisKatmani.style.opacity = '0';
                kapanisKatmani.style.visibility = 'hidden';
            }
        }
    });
});
    </script>
</body>
</html>