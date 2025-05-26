<?php
ob_start(); // Çıktı tamponlamasını başlat

// Cookie varsa dashboard'a yönlendir
if (isset($_COOKIE['asikzade_user_session'])) {
    header('Location: /dashboard.php'); // KÖK DİZİNDE OLDUĞUNU VARSAYIYORUM
    exit;
}

// products_data.php ve sepet sayısını dahil et (Eğer bu dosyalar varsa ve kullanılıyorsa)
$products_data_path = __DIR__ . '/products_data.php'; // login.php ile aynı dizinde olduğunu varsayar
if (file_exists($products_data_path)) {
    include $products_data_path;
}

$cart_item_count = 0;
if (function_exists('get_cart_count')) { // Bu fonksiyonun varlığını kontrol edin
    $cart_item_count = get_cart_count();
}

$error_message_display = '';
$success_message_display = ''; // Eğer kayıt sonrası yönlendirmede success mesajı varsa
$form_email_value = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; // Formu tekrar doldurmak için

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
        case 'invalid_cookie':
            $error_message_display = "Oturumunuz geçersiz. Lütfen tekrar giriş yapın.";
            break;
        default:
            $error_message_display = "Bilinmeyen bir hata oluştu.";
            break;
    }
}

// Kayıt sonrası başarı mesajı (register.php'den yönlendirilirse)
if (isset($_GET['registration']) && $_GET['registration'] === 'success') {
    $success_message_display = "Kayıt başarılı! Lütfen giriş yapın.";
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - AŞIKZADE</title>
    <link rel="stylesheet" href="/gecis_animasyonlari.css">
    <style>
        :root {
            --product-bg-text-light: rgba(255, 255, 255, 0.18);
            --product-bg-text-dark: rgba(0, 0, 0, 0.15);
            --asikzade-content-bg: #fef6e6;
            --asikzade-green: #8ba86d;
            --asikzade-dark-green: #6a8252;
            --asikzade-dark-text: #2d3e2a;
            --asikzade-light-text: #fdfcf8;
            --asikzade-gray: #7a7a7a;
            --asikzade-light-gray: #f8f8f8;
            --asikzade-border: #e5e5e5;
            --asikzade-promo-bg: #FFF7E0;
            --asikzade-contact-bg: #F8C8DC;
            --asikzade-contact-input-bg: #ECECEC;

            --login-input-bg: #FFFFFF;
            --login-input-border: #DDDDDD;
            --login-button-bg: #7fb3ec;
            --login-button-text: #000000;
            --login-button-hover-bg: #6aa3e0;

            --message-error-bg: #f8d7da;
            --message-error-text: #721c24;
            --message-error-border: #f5c6cb;
            --message-success-bg: #d4edda;
            --message-success-text: #155724;
            --message-success-border: #c3e6cb;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif; overflow-x: hidden; position: relative; color: var(--asikzade-dark-text); line-height: 1.6; background-color: var(--asikzade-content-bg); display: flex; flex-direction: column; min-height: 100vh; }
        .header { position: fixed; top: 0; width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 15px 50px; z-index: 1000; background: rgba(254, 246, 230, 0.95); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); box-shadow: 0 1px 0 rgba(0,0,0,0.05); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .logo-container { display: flex; align-items: center; gap: 10px; }
        .logo-container img { height: 48px; transition: height 0.3s ease; filter: none; }
        .logo-text { font-size: 22px; font-weight: 600; letter-spacing: 1.5px; transition: all 0.3s ease; color: var(--asikzade-dark-text); }
        .main-nav { display: flex; align-items: center; }
        .user-actions-group { display: flex; align-items: center; gap: 15px; }
        .nav-user-icon, .nav-cart-icon { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; border: 1.5px solid var(--asikzade-dark-text); color: var(--asikzade-dark-text); transition: all 0.3s ease; position: relative; text-decoration: none; }
        .nav-user-icon svg, .nav-cart-icon svg { width: 18px; height: 18px; stroke: currentColor; }
        .nav-user-icon:hover, .nav-cart-icon:hover { background-color: rgba(0,0,0,0.05); }
        .cart-badge { position: absolute; top: -5px; right: -8px; background-color: var(--asikzade-dark-green); color: var(--asikzade-light-text); border-radius: 50%; width: 20px; height: 20px; font-size: 12px; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 1px solid var(--asikzade-dark-text); }

        .login-page-wrapper { flex-grow: 1; display: flex; justify-content: center; align-items: center; padding-top: 120px; padding-bottom: 80px; width: 100%; }
        .login-form-container { width: 100%; max-width: 550px; padding: 20px; text-align: center; }
        .login-form-container h1 { font-size: 36px; margin-bottom: 30px; color: var(--asikzade-dark-text); font-weight: 600; }
        
        .message-box { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: .25rem; font-size: 14px; }
        .message-error { color: var(--message-error-text); background-color: var(--message-error-bg); border-color: var(--message-error-border); }
        .message-success { color: var(--message-success-text); background-color: var(--message-success-bg); border-color: var(--message-success-border); }

        .login-form { display: flex; flex-direction: column; gap: 20px; }
        .input-group { text-align: left; }
        .input-group label { display: block; margin-bottom: 8px; font-size: 14px; color: #333; font-weight: 500; }
        .input-group input[type="email"],
        .input-group input[type="password"] { width: 100%; padding: 12px 15px; border: 1px solid var(--login-input-border); background-color: var(--login-input-bg); border-radius: 6px; font-size: 14px; color: var(--asikzade-dark-text); }
        .input-group input::placeholder { color: #999; }
        .input-group input:focus { outline: none; border-color: var(--asikzade-green); box-shadow: 0 0 0 2px rgba(139, 168, 109, 0.2); }
        .forgot-password { display: block; text-align: right; font-size: 12px; color: #555; margin-top: 10px; text-decoration: none; font-weight: normal; }
        .forgot-password:hover { text-decoration: underline; }
        
        .login-btn { background-color: var(--login-button-bg); color: var(--login-button-text); border: none; width: 100%; padding: 15px; border-radius: 50px; font-size: 16px; font-weight: 500; cursor: pointer; margin-top: 15px; transition: background-color 0.3s ease, transform 0.2s ease; text-transform: none; letter-spacing: normal; }
        .login-btn:hover { background-color: var(--login-button-hover-bg); transform: translateY(-2px); }
        .login-btn:active { transform: translateY(0); }
        .signup-link { margin-top: 30px; font-size: 14px; color: var(--asikzade-gray); }
        .signup-link a { color: var(--asikzade-green); font-weight: 500; text-decoration: none; }
        .signup-link a:hover { text-decoration: underline; color: var(--asikzade-dark-green); }

        .footer { background-color: var(--asikzade-content-bg); padding: 60px 0 30px; position: relative; z-index: 20; color: var(--asikzade-dark-text); border-top: none; margin-top: auto; }
        .footer-content { max-width: 1200px; margin: 0 auto; padding: 0 50px; }
        .footer-social-row { display: flex; justify-content: center; margin-bottom: 40px; }
        .social-icons { display: flex; gap: 25px; }
        .social-icons a { width: 48px; height: 48px; background-color: var(--asikzade-green); border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; border: none; box-shadow: 0 3px 6px rgba(0,0,0,0.12); }
        .social-icons a:hover { background-color: var(--asikzade-dark-green); transform: translateY(-2px); }
        .social-icons svg { width: 22px; height: 22px; fill: var(--asikzade-light-text); }
        .footer-bottom { display: flex; justify-content: space-between; align-items: center; padding-top: 25px; border-top: 1px solid var(--asikzade-border); }
        .footer-links ul { list-style: none; display: flex; gap: 25px; margin: 0; padding: 0; }
        .footer-links a { color: var(--asikzade-gray); text-decoration: none; font-size: 14px; font-weight: 400; transition: color 0.3s ease; }
        .footer-links a:hover { color: var(--asikzade-dark-text); }
        .copyright { font-size: 14px; color: var(--asikzade-gray); font-weight: 400; text-align: left; margin: 0; }

        @media (max-width: 768px) {
            .header { padding: 12px 20px; } .logo-container img { height: 40px; } .logo-text { font-size: 18px; } .nav-user-icon, .nav-cart-icon { width: 32px; height: 32px; }
            .login-page-wrapper { padding-top: 100px; padding-bottom: 60px; } .login-form-container { max-width: 450px; } .login-form-container h1 { font-size: 30px; margin-bottom: 30px; }
            .footer-content { padding: 0 20px; } .footer-bottom { flex-direction: column; gap: 15px; text-align: center; padding-top: 20px; } .footer-links ul { justify-content: center; flex-wrap: wrap; gap: 10px 20px; } .copyright { text-align: center; } .footer-social-row { margin-bottom: 30px; } .social-icons a { width: 44px; height: 44px; } .social-icons svg { width: 20px; height: 20px; } .footer { padding: 40px 0 20px; }
        }
        @media (max-width: 480px) {
            .header { padding: 10px 15px; } .logo-container img { height: 36px; } .logo-text { font-size: 17px; } .logo-container { gap: 8px; } .user-actions-group { gap: 10px; } .nav-user-icon, .nav-cart-icon { width: 30px; height: 30px; }
            .login-page-wrapper { padding-top: 90px; padding-bottom: 40px; } .login-form-container { max-width: 100%; padding: 15px; } .login-form-container h1 { font-size: 26px; } .input-group input { padding: 11px 14px; font-size: 13px;} .login-btn { padding: 14px; font-size: 15px;}
        }
    </style>
</head>
<body>
     <div id="sayfa-gecis-katmani"></div>
      <div id="sayfa-kapanis-katmani"></div>
    <header class="header" id="mainHeader">
        <div class="logo-container">
            <img src="https://i.imgur.com/rdZuONP.png" alt="Aşıkzade Logo" id="headerLogoImage">
            <span class="logo-text" id="siteLogoTextMawa"></span>
        </div>
        <nav class="main-nav">
            <div class="user-actions-group">
                <a href="login.php" class="nav-user-icon" aria-label="Kullanıcı Girişi">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </a>
                <a href="sepet.php" class="nav-cart-icon" aria-label="Sepetim">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    <?php if ($cart_item_count > 0): ?>
                        <span class="cart-badge"><?php echo htmlspecialchars($cart_item_count); ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </nav>
    </header>

    <main class="login-page-wrapper">
        <div class="login-form-container">
            <h1>GİRİŞ YAP</h1>

            <?php if (!empty($error_message_display)): ?>
                <div class="message-box message-error"><?php echo htmlspecialchars($error_message_display); ?></div>
            <?php endif; ?>
            <?php if (!empty($success_message_display)): ?>
                <div class="message-box message-success"><?php echo htmlspecialchars($success_message_display); ?></div>
            <?php endif; ?>

            <form action="login_process.php" method="POST" class="login-form">
                <div class="input-group">
                    <label for="email">E-posta Adresiniz</label>
                    <input type="email" id="email" name="email" placeholder="ornek@eposta.com" required value="ornek@eposta.com">
                </div>
                
                <div class="input-group">
                    <label for="sifre">Şifreniz</label>
                    <input type="password" id="sifre" name="sifre" placeholder="••••••••" Value="123456" required>
                    <a href="forgot_password.php" class="forgot-password">Şifrenizi mi unuttunuz?</a>
                </div>
                
                <button type="submit" class="login-btn">Giriş Yap</button>
            </form>
            <p class="signup-link">
                Hesabınız yok mu? <a href="register.php">Hemen Kaydolun</a> <br> <br>
                  <a href="admin_login.php">Admin Girişi</a>
            </p>
            <?php 
            // Eğer $_SESSION['form_data'] özellikle bu sayfada veya register.php gibi
            // bir yerden gelip burada temizlenmesi gerekiyorsa bu satır kalabilir.
            // Sadece email için $_GET kullanılıyorsa bu satıra ihtiyaç olmayabilir.
            // Orijinal kodda olduğu için bırakıyorum.
            if (isset($_SESSION['form_data'])) {
                unset($_SESSION['form_data']); 
            }
            ?>
        </div>
    </main>

    <footer class="footer">
         <div class="footer-content">
            <div class="footer-social-row">
                <div class="social-icons">
                    <a href="https://facebook.com/asikzadenatural" target="_blank" aria-label="Facebook"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 2.039c-5.514 0-9.961 4.448-9.961 9.961s4.447 9.961 9.961 9.961c5.515 0 9.961-4.448 9.961-9.961s-4.446-9.961-9.961-9.961zm3.621 9.561h-2.2v7.3h-3.22v-7.3h-1.56v-2.68h1.56v-1.93c0-1.301.63-3.35 3.35-3.35h2.37v2.67h-1.45c-.47 0-.72.24-.72.72v1.31h2.24l-.24 2.68z"/></svg></a>
                    <a href="https://linkedin.com/company/asikzadenatural" target="_blank" aria-label="LinkedIn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14zm-11.383 7.125H5.121v6.75h2.496v-6.75zm-1.248-2.302a1.49 1.49 0 1 0 0-2.979 1.49 1.49 0 0 0 0 2.979zm9.016 2.302c-2.016 0-2.848 1.081-3.312 2.04h-.048v-1.788H9.573v6.75h2.496v-3.375c0-.891.171-1.755 1.26-1.755.972 0 1.088.687 1.088 1.809v3.321h2.496v-3.828c0-2.203-1.088-3.852-3.288-3.852z"/></svg></a>
                    <a href="https://instagram.com/asikzadenatural" target="_blank" aria-label="Instagram"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 2c2.717 0 3.056.01 4.122.06 1.065.05 1.79.217 2.428.465.66.254 1.217.598 1.77.96.582.386.96.826 1.344 1.344.385.517.778 1.074 1.032 1.734.272.712.436 1.436.488 2.498.052 1.066.063 1.405.063 4.122s-.01 3.056-.061 4.122c-.053 1.065-.218 1.79-.487 2.428-.254.66-.598 1.217-.96 1.77-.386.582-.826.96-1.344 1.344-.517.385-1.074.778-1.734 1.032-.712.272-1.436.436-2.498.488-1.066.052-1.405.063-4.122.063s-3.056-.01-4.122-.061c-1.065-.053-1.79-.218-2.428-.487-.66-.254-1.217-.598-1.77-.96-.582-.386-.96-.826-1.344-1.344-.385-.517-.778-1.074-1.032-1.734-.272-.712-.436-1.436-.488-2.498C2.012 15.056 2 14.717 2 12s.01-3.056.061-4.122c.053-1.065.218-1.79.487-2.428.254.66.598-1.217.96-1.77.386-.582.826.96 1.344-1.344.517-.385 1.074-.778 1.734-1.032.712-.272 1.436.436 2.498-.488C8.944 2.01 9.283 2 12 2zm0 1.802c-2.67 0-2.987.01-4.042.058-.975.045-1.505.207-1.857.344-.466.182-.795.396-1.15.748-.354.354-.566.684-.748 1.15-.137.352-.3.882-.344 1.857-.048 1.054-.058 1.373-.058 4.042s.01 2.987.058 4.042c.045.975.207 1.505.344 1.857.182.466.396.795.748 1.15.354.354.684.566 1.15.748.352.137.882.3 1.857.344 1.054.048 1.373.058 4.042.058s2.987-.01 4.042-.058c.975-.045 1.505-.207 1.857-.344.466-.182.795.396 1.15-.748.354.354-.566-.684.748 1.15.137-.352-.3-.882-.344-1.857.048-1.054.058-1.373.058-4.042s-.01-2.987-.058-4.042c-.045-.975-.207-1.505-.344-1.857-.182-.466-.396-.795-.748-1.15-.354-.354-.684-.566-1.15-.748-.352-.137-.882-.3-1.857-.344C14.987 3.812 14.67 3.802 12 3.802zm0 2.903c-2.836 0-5.135 2.299-5.135 5.135s2.299 5.135 5.135 5.135 5.135-2.299 5.135-5.135-2.299-5.135-5.135-5.135zm0 8.468c-1.837 0-3.333-1.496-3.333-3.333s1.496-3.333 3.333-3.333 3.333 1.496 3.333 3.333-1.496 3.333-3.333 3.333zm4.333-8.572a1.2 1.2 0 1 0 0-2.4 1.2 1.2 0 0 0 0 2.4z"/></svg></a>
                </div>
            </div>
            <div class="footer-bottom">
                <p class="copyright">© <?php echo date("Y"); ?> Aşıkzade. Tüm hakları saklıdır.</p>
                <div class="footer-links">
                    <ul>
                        <li><a href="#!">İade Politikası</a></li>
                        <li><a href="#!">Ödemeler</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
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
<?php
ob_end_flush(); // Send buffer content
?>