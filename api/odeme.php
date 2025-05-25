<?php
// config.php'yi dahil et (Supabase sabitleri ve potansiyel olarak API fonksiyonu için)
// Bu dosyanın session_start() ÇAĞIRMAMASI veya session'sız çalışabilmesi gerekir.
require_once 'config.php';
include 'products_data.php'; // $products dizisini sağlar

// 1. KULLANICI GİRİŞ KONTROLÜ (Cookie ile)
$user_logged_in = false;
$user_id_from_cookie = null;
$user_ad = '';
$user_soyad = '';
$user_email = '';
$user_telefon_ornek = '0500 000 00 00'; // Varsayılan telefon
$user_adres_ornek = "Örnek Mah. Atatürk Cad. No:1 Daire:2\nEfeler / AYDIN"; // Varsayılan adres

$user_cookie_name = 'asikzade_user_session'; // login_process.php'de kullanıcı için tanımlanan cookie adı

if (isset($_COOKIE[$user_cookie_name])) {
    $user_data_json = $_COOKIE[$user_cookie_name];
    $user_data = json_decode($user_data_json, true);

    if ($user_data && isset($user_data['user_id'])) { // Temel kontrol: user_id var mı?
        $user_logged_in = true;
        $user_id_from_cookie = $user_data['user_id'];
        $user_ad = $user_data['ad'] ?? '';
        $user_soyad = $user_data['soyad'] ?? '';
        $user_email = $user_data['email'] ?? '';
        // Cookie'de adres ve telefon bilgileri de saklanıyorsa:
        $user_telefon_ornek = $user_data['telefon'] ?? $user_telefon_ornek;
        $user_adres_ornek = $user_data['adres'] ?? $user_adres_ornek;
        // Eğer bu bilgiler cookie'de yoksa ve formda gösterilmesi gerekiyorsa,
        // burada $user_id_from_cookie kullanarak Supabase'den çekmeniz gerekir.
    }
}

if (!$user_logged_in) {
    // Giriş yapılmamışsa, login.php'ye yönlendir ve URL'ye parametre ekle
    $login_redirect_params = [
        'redirect_after_login' => 'odeme.php',
        'info_msg' => urlencode("Ödeme yapmak için lütfen giriş yapın.")
    ];
    header('Location: login.php?' . http_build_query($login_redirect_params));
    exit;
}

// Sepet sayısını cookie'den hesapla
$cart_item_count = 0;
if (isset($_COOKIE['asikzade_cart'])) {
    $cart_cookie_data_for_count = json_decode($_COOKIE['asikzade_cart'], true);
    if (is_array($cart_cookie_data_for_count)) {
        foreach ($cart_cookie_data_for_count as $item_id => $item_data) {
            if (isset($item_data['quantity'])) {
                $cart_item_count += (int)$item_data['quantity'];
            }
        }
    }
}
// Eğer get_cart_count() fonksiyonunuz cookie'den okuyorsa:
/*
if (function_exists('get_cart_count')) {
    $cart_item_count = get_cart_count(); // Bu fonksiyonun $_COOKIE['asikzade_cart']'ı kullandığından emin olun
}
*/

$cart_contents_summary = [];
$sub_total_summary = 0;
$shipping_cost = 50.00; // Kargo ücreti
$estimated_taxes = 0;   // Tahmini vergiler (KDV)

if (isset($_COOKIE['asikzade_cart'])) {
    $cart_cookie_data = json_decode($_COOKIE['asikzade_cart'], true);
    if (is_array($cart_cookie_data)) {
        foreach ($cart_cookie_data as $item_id_from_cookie => $item_data_from_cookie) {
            if (isset($products[$item_id_from_cookie]) && isset($item_data_from_cookie['quantity'])) {
                $product = $products[$item_id_from_cookie];
                $quantity = max(1, (int)$item_data_from_cookie['quantity']);
                $item_subtotal = $product['price'] * $quantity;
                $sub_total_summary += $item_subtotal;

                $cart_contents_summary[$item_id_from_cookie] = [
                    'name' => $product['name'],
                    'image' => $product['image'] ?? $product['hero_image'] ?? 'https://via.placeholder.com/60',
                    'price' => $product['price'],
                    'quantity' => $quantity,
                    'subtotal' => $item_subtotal
                ];
            }
        }
    }
}

if (empty($cart_contents_summary)) {
    // Sepet boşsa, sepet.php'ye yönlendir ve URL'ye mesaj ekle
    $sepet_redirect_params = [
        'info_msg' => urlencode("Ödeme yapabilmek için sepetinizde ürün bulunmalıdır.")
    ];
    header('Location: sepet.php?' . http_build_query($sepet_redirect_params));
    exit;
}

// KDV oranı (Örnek: %20)
$kdv_orani = 0.20; // İhtiyacınıza göre güncelleyin
$estimated_taxes = $sub_total_summary * $kdv_orani;
$grand_total_summary = $sub_total_summary + $shipping_cost + $estimated_taxes;

// 3. URL'den ödeme işlemi sonrası hata mesajını al (odeme_process.php'den gelebilir)
$error_message_odeme = null;
if (isset($_GET['error_msg_odeme'])) {
    $error_message_odeme = htmlspecialchars(urldecode($_GET['error_msg_odeme']));
}
// Başarı mesajı da benzer şekilde alınabilir (eğer odeme_process.php'den başarı mesajı ile dönülüyorsa)
// if (isset($_GET['success_msg_odeme'])) { ... }

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme - AŞIKZADE</title>
    <style>
        /* STİL KODLARI DEĞİŞMEDEN AYNI KALACAK */
        :root {
            --asikzade-content-bg: #fef6e6;
            --asikzade-green: #8ba86d;
            --asikzade-dark-green: #6a8252;
            --asikzade-dark-text: #2d3e2a;
            --asikzade-light-text: #fdfcf8;
            --asikzade-gray: #7a7a7a;
            --asikzade-border: #e5e5e5;
            --input-bg: #fff;
            --input-border-color: #ccc;
            --input-focus-border-color: var(--asikzade-green);
            --button-primary-bg: var(--asikzade-green);
            --button-primary-text: var(--asikzade-light-text);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif; }
        body {
            background-color: #fff; /* Ödeme sayfası için beyaz arkaplan daha uygun olabilir */
            color: var(--asikzade-dark-text);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .header {
            /* position: sticky; */ /* Header'ı sabit tutmak isterseniz */
            top: 0; width: 100%; display: flex; justify-content: space-between; align-items: center;
            padding: 15px 50px; z-index: 1000; background: rgba(254, 246, 230, 0.95); /* Ana site header rengi */
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 1px 0 rgba(0,0,0,0.05);
        }
        .logo-container { display: flex; align-items: center; gap: 10px; }
        .logo-container img { height: 48px; }
        .logo-text { font-size: 22px; font-weight: 600; letter-spacing: 1.5px; color: var(--asikzade-dark-text); text-decoration: none;}
        .main-nav { display: flex; align-items: center; }
        .user-actions-group { display: flex; align-items: center; gap:15px; }
        .nav-user-icon, .nav-cart-icon {
            display: flex; align-items: center; justify-content: center;
            width: 36px; height: 36px; border-radius: 50%;
            border: 1.5px solid var(--asikzade-dark-text);
            color: var(--asikzade-dark-text);
            transition: all 0.3s ease; position: relative; text-decoration: none;
        }
        .nav-user-icon svg, .nav-cart-icon svg { width: 18px; height: 18px; stroke: currentColor; }
        .nav-user-icon:hover, .nav-cart-icon:hover { background-color: rgba(0,0,0,0.05); }
        .cart-badge {
            position: absolute; top: -5px; right: -8px;
            background-color: var(--asikzade-dark-green); color: var(--asikzade-light-text);
            border-radius: 50%; width: 20px; height: 20px; font-size: 12px;
            display: flex; align-items: center; justify-content: center; font-weight: bold;
            border: 1px solid var(--asikzade-dark-text);
        }

        .checkout-container {
            display: flex;
            flex-wrap: wrap;
            max-width: 1200px;
            margin: 20px auto; /* Header'dan sonra biraz boşluk */
            gap: 30px; /* Form ve özet arası boşluk */
        }
        .checkout-form-section {
            flex: 2; /* Form alanı daha geniş */
            padding: 0 20px; /* İç padding */
            min-width: 300px;
        }
        .checkout-summary-section {
            flex: 1; /* Özet alanı */
            background-color: var(--asikzade-content-bg); /* Açık renk arka plan */
            padding: 30px 25px;
            border-left: 1px solid var(--asikzade-border);
            min-width: 300px;
            align-self: flex-start; /* Yukarıda başlasın */
        }
        .section-title {
            font-size: 1.8rem; /* Biraz küçülttüm */
            font-weight: 500;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--asikzade-border);
        }
        .contact-info p { margin-bottom: 5px; font-size: 0.95rem; }
        .contact-info .label { color: var(--asikzade-gray); display: inline-block; width: 80px; }
        .contact-info .value { font-weight: 500; }
        .contact-info a.change-link { font-size: 0.85rem; color: var(--asikzade-green); text-decoration: none; margin-left: 15px; }
        .contact-info a.change-link:hover { text-decoration: underline; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 0.9rem; color: #555; }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group select,
        .form-group .input-wrapper { /* Kart no için input-wrapper'ı da ekledim */
            width: 100%; padding: 12px 15px; border: 1px solid var(--input-border-color);
            border-radius: 5px; font-size: 1rem; background-color: var(--input-bg); transition: border-color 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group .input-wrapper:focus-within { /* input-wrapper için focus */
            outline: none; border-color: var(--input-focus-border-color); box-shadow: 0 0 0 1px var(--input-focus-border-color);
        }
        .form-group .input-wrapper { display: flex; align-items: center; padding: 0; } /* Kart no için padding'i wrapper'dan al */
        .form-group .input-wrapper input { border: none; outline: none; flex-grow: 1; padding: 12px 15px; background-color: transparent; }
        .form-group .input-wrapper svg { margin-right: 10px; color: var(--asikzade-gray); }
        .input-icon-wrapper { position: relative; display: flex; align-items: center; }
        .input-icon-wrapper input { padding-right: 30px; /* İkon için yer aç */ }
        .input-icon-wrapper .info-icon { position: absolute; right: 10px; color: var(--asikzade-gray); cursor: help; }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
        .checkbox-group { display: flex; align-items: center; margin-bottom: 15px; font-size: 0.95rem; }
        .checkbox-group input[type="checkbox"] { margin-right: 10px; width: 18px; height: 18px; accent-color: var(--asikzade-green); }

        .payment-method { border: 1px solid var(--asikzade-border); border-radius: 5px; margin-bottom: 15px; }
        .payment-method-header {
            display: flex; align-items: center; padding: 15px; cursor: pointer; background-color: #f9f9f9; /* Açık gri */
        }
        .payment-method-header.selected { background-color: var(--asikzade-content-bg); border-bottom: 1px solid var(--asikzade-border); }
        .payment-method-header input[type="radio"] { margin-right: 12px; width: 18px; height: 18px; accent-color: var(--asikzade-green); }
        .payment-method-header label { font-weight: 500; flex-grow: 1; }
        .payment-method-icons img { height: 24px; margin-left: 8px; vertical-align: middle; }
        .payment-method-body { padding: 20px; border-top: 1px solid var(--asikzade-border); display: block; } /* Kredi kartı hep açık */
        
        .submit-button-container { margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--asikzade-border); text-align: right; }
        .submit-button-container .pay-now-btn {
            background-color: #ef4444; /* Kırmızı tonu */ color: white; padding: 15px 35px; border: none;
            border-radius: 5px; font-size: 1.1rem; font-weight: 500; cursor: pointer; transition: background-color 0.3s;
        }
        .submit-button-container .pay-now-btn:hover { background-color: #dc2626; /* Koyu kırmızı */ }
        .secure-info { font-size: 0.85rem; color: var(--asikzade-gray); margin-top: 10px; text-align: center; }
        .secure-info svg { vertical-align: middle; margin-right: 5px; }

        /* Sipariş Özeti Stilleri */
        .order-summary-item { display: flex; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--asikzade-border); }
        .order-summary-item:last-child { border-bottom: none; margin-bottom: 0; }
        .order-summary-item img { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; margin-right: 15px; border: 1px solid var(--asikzade-border); }
        .item-details { flex-grow: 1; }
        .item-name { font-weight: 500; font-size: 0.95rem;}
        .item-quantity { font-size: 0.85rem; color: var(--asikzade-gray); }
        .item-price-summary { font-weight: 500; }
        .discount-code-form { display: flex; margin-bottom: 20px; margin-top: 20px; }
        .discount-code-form input[type="text"] { flex-grow: 1; padding: 10px 12px; border: 1px solid var(--input-border-color); border-radius: 5px 0 0 5px; font-size: 0.95rem; }
        .discount-code-form button { padding: 10px 18px; border: 1px solid var(--asikzade-green); background-color: var(--asikzade-green); color: var(--button-primary-text); border-radius: 0 5px 5px 0; cursor: pointer; font-weight: 500; }
        .totals-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.95rem; }
        .totals-row.grand-total { font-size: 1.25rem; font-weight: bold; margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--asikzade-dark-text); }
        .totals-row .label { color: var(--asikzade-gray); }
        .totals-row .value { font-weight: 500; }
        .info-icon { margin-left: 5px; color: var(--asikzade-gray); cursor: help; }
        .message-box-odeme { padding: 10px 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; font-size: 0.9rem; background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .footer { background-color: var(--asikzade-content-bg); padding: 40px 0 20px; color: var(--asikzade-dark-text); margin-top: auto; border-top: 1px solid var(--asikzade-border); }
        .footer-content { max-width: 1200px; margin: 0 auto; padding: 0 50px; text-align: center; }
        .footer-content p { font-size: 0.9rem; color: var(--asikzade-gray); }

        @media (max-width: 992px) { /* Tablet ve altı */
            .checkout-container { flex-direction: column-reverse; } /* Özet üste gelir */
            .checkout-summary-section { border-left: none; border-bottom: 1px solid var(--asikzade-border); margin-bottom: 30px; }
        }
        @media (max-width: 768px) { /* Mobil */
            .header { padding: 12px 20px; } .logo-container img { height: 40px; } .logo-text { font-size: 18px; }
            .checkout-form-section, .checkout-summary-section { padding: 0 15px; }
            .form-row { flex-direction: column; gap: 0; } .form-row .form-group { margin-bottom: 20px; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo-container">
            <a href="index.php"><img src="https://i.imgur.com/rdZuONP.png" alt="Aşıkzade Logo"></a>
            <a href="index.php" class="logo-text">AŞIKZADE</a> <!-- Logo text eklendi -->
        </div>
        <nav class="main-nav">
            <div class="user-actions-group">
                <a href="<?php echo $user_logged_in ? 'dashboard.php' : 'login.php'; ?>" class="nav-user-icon" aria-label="Kullanıcı">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </a>
                <a href="sepet.php" class="nav-cart-icon" aria-label="Sepetim">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    <?php if ($cart_item_count > 0): ?>
                        <span class="cart-badge"><?php echo htmlspecialchars($cart_item_count); ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </nav>
    </header>

    <div class="checkout-container">
        <section class="checkout-form-section">
            <?php if ($error_message_odeme): ?>
                <div class="message-box-odeme"><?php echo $error_message_odeme; // Zaten htmlspecialchars yapıldı ?></div>
            <?php endif; ?>

            <form action="odeme_process.php" method="POST" id="payment-form">
                <input type="hidden" name="user_id_field" value="<?php echo htmlspecialchars($user_id_from_cookie); ?>"> <!-- Kullanıcı ID'sini forma ekle -->
                <input type="hidden" name="total_amount_field" value="<?php echo htmlspecialchars($grand_total_summary); ?>"> <!-- Toplam tutarı forma ekle -->
                
                <div class="contact-info section-block">
                    <h2 class="section-title">İletişim</h2>
                    <p><span class="label">Hesap:</span> <span class="value"><?php echo htmlspecialchars($user_email); ?></span></p>
                     <p style="margin-top: 5px; font-size: 0.9rem;"><a href="logout.php" style="color:var(--asikzade-green); text-decoration: none;">Çıkış Yap</a></p>
                </div>

                <div class="delivery-info section-block" style="margin-top: 30px;">
                    <h2 class="section-title">Teslimat Adresi</h2>
                    <div class="form-group">
                        <label for="country">Ülke/Bölge</label>
                        <select id="country" name="ulke"> <!-- name attribute'u eklendi -->
                            <option value="TR" selected>Türkiye</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">Ad</label>
                            <input type="text" id="first_name" name="ad" value="<?php echo htmlspecialchars($user_ad); ?>" required> <!-- name attribute'u eklendi -->
                        </div>
                        <div class="form-group">
                            <label for="last_name">Soyad</label>
                            <input type="text" id="last_name" name="soyad" value="<?php echo htmlspecialchars($user_soyad); ?>" required> <!-- name attribute'u eklendi -->
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="address">Adres</label>
                        <input type="text" id="address" name="adres_satiri1" placeholder="Mahalle, Cadde, Sokak ve No" value="<?php echo htmlspecialchars(explode("\n", $user_adres_ornek)[0] ?? ''); ?>" required> <!-- name attribute'u eklendi -->
                    </div>
                    <div class="form-group">
                        <label for="apt_suite">Bina No / Daire (İsteğe Bağlı)</label>
                        <input type="text" id="apt_suite" name="adres_satiri2" placeholder="Bina adı, kat, daire numarası" value="<?php echo htmlspecialchars(explode("\n", $user_adres_ornek)[1] ?? ''); ?>"> <!-- name attribute'u eklendi -->
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">Şehir</label>
                            <input type="text" id="city" name="sehir" value="Aydın" required> <!-- name attribute'u eklendi -->
                        </div>
                        <div class="form-group">
                            <label for="province">İlçe</label>
                             <input type="text" id="province" name="ilce" value="Efeler" required> <!-- name attribute'u eklendi -->
                        </div>
                        <div class="form-group">
                            <label for="postal_code">Posta Kodu</label>
                            <input type="text" id="postal_code" name="posta_kodu" value="09100" required> <!-- name attribute'u eklendi -->
                        </div>
                    </div>
                     <div class="form-group">
                        <label for="phone">Telefon</label>
                        <input type="tel" id="phone" name="telefon" placeholder="05XX XXX XX XX" value="<?php echo htmlspecialchars($user_telefon_ornek); ?>" required> <!-- name attribute'u eklendi -->
                    </div>
                </div>

                <div class="payment-section section-block" style="margin-top: 30px;">
                    <h2 class="section-title">Ödeme Yöntemi</h2>
                    <p style="font-size:0.9rem; color: var(--asikzade-gray); margin-bottom:15px;">Tüm işlemler güvenli ve şifrelidir.</p>

                    <div class="payment-method">
                        <div class="payment-method-header selected">
                            <input type="radio" id="credit_card_radio" name="payment_method_radio" value="credit_card" checked style="display:none;"> <!-- name değiştirildi karışmasın diye -->
                            <label for="credit_card_radio" style="cursor:default;">Kredi Kartı</label>
                            <div class="payment-method-icons">
                                <img src="https://www.freepnglogos.com/uploads/visa-and-mastercard-logo-26.png" alt="Visa Mastercard" style="height: 28px;">
                            </div>
                        </div>
                        <div class="payment-method-body">
                            <div class="form-group">
                                <label for="card_number" style="display:none;">Kart Numarası</label> <!-- Ekran okuyucular için gizli label -->
                                <div class="input-wrapper">
                                    <input type="text" id="card_number" name="card_number" placeholder="Kart numarası" value="4545 4545 4545 4545" required pattern="\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}" title="Lütfen geçerli bir 16 haneli kart numarası girin.">
                                    <svg class="icon-svg icon-svg--lock icon-svg--size-16 form__icon" width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M11 6V4.2C11 2.4 9.7 1 8 1S5 2.4 5 4.2V6H4v7h8V6h-1zm-1.5 0V4.2c0-.8.7-1.7 2.5-1.7S10 3.4 10 4.2V6h1.5z"></path></svg>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                     <label for="expiry_date" style="display:none;">Son Kullanma Tarihi</label>
                                     <div class="input-icon-wrapper">
                                        <input type="text" id="expiry_date" name="expiry_date" placeholder="MM / YY" value="12 / 28" required pattern="(0[1-9]|1[0-2])\s?\/\s?([0-9]{2})" title="Lütfen MM / YY formatında geçerli bir son kullanma tarihi girin.">
                                     </div>
                                </div>
                                <div class="form-group">
                                    <label for="cvv" style="display:none;">CVV</label>
                                    <div class="input-icon-wrapper">
                                        <input type="text" id="cvv" name="cvv" placeholder="CVV" value="123" required pattern="\d{3,4}" title="Lütfen 3 veya 4 haneli CVV kodunu girin.">
                                        <svg class="info-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" focusable="false" aria-hidden="true" title="Kartınızın arkasındaki 3 veya 4 haneli güvenlik kodu."><circle cx="8" cy="8" r="7.5" stroke="currentColor" stroke-opacity=".56" fill="none"></circle><path d="M7.86 4.3a.6.6 0 01.6.6v.08a.6.6 0 01-.6.6.6.6 0 01-.6-.6V4.9a.6.6 0 01.6-.6zm-.33 2.95c0-.1.03-.18.08-.25a.54.54 0 01.42-.1c.17 0 .3.03.4.1.1.06.15.14.15.25v3.12c0 .1-.03-.18-.08-.25a.54.54 0 01-.42.1c-.17 0-.3-.03-.4-.1a.36.36 0 01-.15-.25V7.25z" fill="currentColor"></path></svg>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="card_name" style="display:none;">Kart Üzerindeki İsim</label>
                                <input type="text" id="card_name" name="card_name" placeholder="Kart üzerindeki isim" value="<?php echo htmlspecialchars($user_ad . ' ' . $user_soyad); ?>" required>
                            </div>
                             <label class="checkbox-group">
                                <input type="checkbox" name="use_shipping_as_billing" checked> Fatura adresi olarak teslimat adresini kullan
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="submit-button-container">
                    <button type="submit" class="pay-now-btn">Şimdi Öde</button>
                </div>
                <p class="secure-info">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7.308.063A.583.583 0 007 .292.583.583 0 006.692.063L4.475.01A.583.583 0 003.91.583v2.617a5.223 5.223 0 00-2.1 3.733A5.25 5.25 0 007 14a5.25 5.25 0 005.192-7.007 5.248 5.248 0 00-2.1-3.733V.583a.583.583 0 00-.565-.573L7.308.063zM7 12.833A4.083 4.083 0 117 4.667a4.083 4.083 0 010 8.166z" fill="#737373"></path><path d="M7.253 6.14l-.008.006L6.06 7.332a.48.48 0 00-.013.679l.013.013 1.184 1.184a.48.48 0 00.679-.013l.013-.013L9.708 7.41a.48.48 0 00.013-.679l-.013-.013-1.184-1.184a.48.48 0 00-.679.013l-.013.013-.579.579z" fill="#737373"></path></svg>
                    Güvenli ve Şifreli Ödeme
                </p>
            </form>
        </section>

        <aside class="checkout-summary-section">
            <h2 class="section-title">Sipariş Özeti</h2>
            <?php foreach ($cart_contents_summary as $item_id => $item): ?>
            <div class="order-summary-item">
                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                <div class="item-details">
                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</div>
                    <div class="item-quantity">Birim Fiyat: <?php echo number_format($item['price'], 2, ',', '.'); ?> TL</div>
                </div>
                <div class="item-price-summary"><?php echo number_format($item['subtotal'], 2, ',', '.'); ?> TL</div>
            </div>
            <?php endforeach; ?>

            <form action="#" method="post" class="discount-code-form" onsubmit="return false;"> <!-- İndirim kodu formu şimdilik bir yere gitmiyor -->
                <input type="text" name="discount_code" placeholder="Hediye kartı veya indirim kodu">
                <button type="submit">Uygula</button>
            </form>

            <div class="totals-breakdown">
                <div class="totals-row">
                    <span class="label">Ara Toplam</span>
                    <span class="value"><?php echo number_format($sub_total_summary, 2, ',', '.'); ?> TL</span>
                </div>
                <div class="totals-row">
                    <span class="label">Kargo</span>
                    <span class="value"><?php echo number_format($shipping_cost, 2, ',', '.'); ?> TL</span>
                </div>
                <div class="totals-row">
                    <span class="label">Tahmini Vergiler (KDV %<?php echo $kdv_orani * 100; ?>) <svg class="info-icon" width="12" height="12" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" title="KDV Dahil Değildir."><circle cx="8" cy="8" r="7.5" stroke="currentColor" stroke-opacity=".56" fill="none"></circle><path d="M7.86 4.3a.6.6 0 01.6.6v.08a.6.6 0 01-.6.6.6.6 0 01-.6-.6V4.9a.6.6 0 01.6-.6zm-.33 2.95c0-.1.03-.18.08-.25a.54.54 0 01.42-.1c.17 0 .3.03.4.1.1.06.15.14.15.25v3.12c0 .1-.03-.18-.08-.25a.54.54 0 01-.42.1c-.17 0-.3-.03-.4-.1a.36.36 0 01-.15-.25V7.25z" fill="currentColor"></path></svg></span>
                    <span class="value"><?php echo number_format($estimated_taxes, 2, ',', '.'); ?> TL</span>
                </div>
                <div class="totals-row grand-total">
                    <span class="label">TOPLAM</span>
                    <span class="value">TL <?php echo number_format($grand_total_summary, 2, ',', '.'); ?></span>
                </div>
            </div>
        </aside>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <p>© <?php echo date("Y"); ?> Aşıkzade. Tüm hakları saklıdır.</p>
        </div>
    </footer>
    <script>
        // Kredi kartı girişi için basit formatlama ve doğrulama (isteğe bağlı)
        document.addEventListener('DOMContentLoaded', function () {
            const cardNumberInput = document.getElementById('card_number');
            const expiryDateInput = document.getElementById('expiry_date');
            const cvvInput = document.getElementById('cvv');

            if(cardNumberInput) {
                cardNumberInput.addEventListener('input', function (e) {
                    let value = e.target.value.replace(/\D/g, ''); // Sadece rakamları al
                    let formattedValue = '';
                    for (let i = 0; i < value.length; i++) {
                        if (i > 0 && i % 4 === 0) {
                            formattedValue += ' ';
                        }
                        formattedValue += value[i];
                    }
                    e.target.value = formattedValue.substring(0, 19); // Maksimum 16 rakam + 3 boşluk
                });
            }

            if(expiryDateInput) {
                expiryDateInput.addEventListener('input', function (e) {
                    let value = e.target.value.replace(/\D/g, '');
                    let formattedValue = '';
                    if (value.length > 2) {
                        formattedValue = value.substring(0, 2) + ' / ' + value.substring(2, 4);
                    } else {
                        formattedValue = value;
                    }
                    e.target.value = formattedValue;
                });
            }
            
            if(cvvInput) {
                 cvvInput.addEventListener('input', function(e) {
                    e.target.value = e.target.value.replace(/\D/g, '').substring(0, 4); // En fazla 4 rakam
                });
            }
        });
    </script>
</body>
</html>