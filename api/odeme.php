<?php
require_once 'config.php'; // Defines paths, Supabase keys, etc.
include 'products_data.php'; // Includes $products array and get_cart_count()

$user_cookie_name = 'asikzade_user_session';
$user_logged_in = false;
$user_data_from_cookie = [];

if (isset($_COOKIE[$user_cookie_name])) {
    $user_data_json = $_COOKIE[$user_cookie_name];
    $user_data_from_cookie = json_decode($user_data_json, true);
    if ($user_data_from_cookie && isset($user_data_from_cookie['user_id'])) {
        $user_logged_in = true;
    } else {
        $user_data_from_cookie = []; 
        // setcookie($user_cookie_name, '', time() - 3600, "/"); 
    }
}

if (!$user_logged_in) {
    $params = [
        'redirect_after_login' => 'odeme.php',
        'info_msg' => urlencode("Ödeme yapmak için lütfen giriş yapın.")
    ];
    header('Location: login.php?' . http_build_query($params));
    exit;
}

$user_id_field_value = $user_data_from_cookie['user_id'] ?? '';
$user_ad = $user_data_from_cookie['user_ad'] ?? '';
$user_soyad = $user_data_from_cookie['user_soyad'] ?? '';
$user_email = $user_data_from_cookie['user_email'] ?? '';
$user_telefon_ornek = $user_data_from_cookie['user_telefon'] ?? '0500 000 00 00';

$adres_satiri1_value = $user_data_from_cookie['adres_satiri1'] ?? '';
$apt_suite_value = $user_data_from_cookie['apt_suite'] ?? '';

if (empty($adres_satiri1_value) && empty($apt_suite_value) && isset($user_data_from_cookie['user_adres'])) {
    $user_adres_ornek_parts = explode("\n", $user_data_from_cookie['user_adres']);
    $adres_satiri1_value = $user_adres_ornek_parts[0] ?? '';
    if (count($user_adres_ornek_parts) > 1) {
        $apt_suite_value = $user_adres_ornek_parts[1] ?? '';
    }
} elseif (empty($adres_satiri1_value) && empty($apt_suite_value)) {
    $adres_satiri1_value = 'Örnek Mah. Atatürk Cad. No:1 Daire:2';
}

$cart_item_count = 0;
if (function_exists('get_cart_count')) {
    $cart_item_count = get_cart_count();
}

$cart_contents_summary = [];
$sub_total_summary = 0; // This is the sum of (price * quantity) for all cart items

if (isset($_COOKIE['asikzade_cart'])) {
    $cart_cookie_data = json_decode($_COOKIE['asikzade_cart'], true);
    if (is_array($cart_cookie_data)) {
        foreach ($cart_cookie_data as $item_id_from_cookie => $item_data_from_cookie) {
            if (isset($products[$item_id_from_cookie]) && isset($item_data_from_cookie['quantity'])) {
                $product = $products[$item_id_from_cookie];
                $quantity = max(1, (int)$item_data_from_cookie['quantity']);
                $item_subtotal = $product['price'] * $quantity;
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
    header('Location: sepet.php?info_msg=' . urlencode("Ödeme yapabilmek için sepetinizde ürün bulunmalıdır."));
    exit;
} else {
    foreach ($cart_contents_summary as $item) {
        $sub_total_summary += $item['subtotal'];
    }
}

// --- DISCOUNT CODE LOGIC ---
$shipping_cost_default = 50.00;
$current_shipping_cost = $shipping_cost_default;

$discount_codes_available = [
    // code => [type, value, description]
    "WELCOME10" => ["type" => "percentage", "value" => 10, "description" => "10% Hoşgeldin İndirimi"],
    "SAVE25FIXED" => ["type" => "fixed", "value" => 25, "description" => "25 TL Sabit İndirim"],
    "HOLIDAY50" => ["type" => "fixed", "value" => 50, "description" => "50 TL Bayram İndirimi"],
    "FREESHIPPINGMAY" => ["type" => "free_shipping", "value" => $shipping_cost_default, "description" => "Ücretsiz Kargo (Mayıs Promosyonu)"],
    "YUZDE20PROMO" => ["type" => "percentage", "value" => 20, "description" => "Özel Promosyon: 20% İndirim"]
];

$selected_coupon_code = null;
$item_level_discount_amount = 0; // Total monetary discount on items
$coupon_description_for_display = ""; // Description of the applied coupon

// Check if discount form was submitted (page reloaded with POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['discount_code_select_submit'])) {
    $posted_coupon_code = $_POST['discount_code_select'] ?? '';
    if (!empty($posted_coupon_code) && isset($discount_codes_available[$posted_coupon_code])) {
        $selected_coupon_code = $posted_coupon_code;
        $coupon_details = $discount_codes_available[$selected_coupon_code];
        $coupon_description_for_display = $coupon_details['description'];

        if ($coupon_details['type'] == 'percentage') {
            $item_level_discount_amount = ($sub_total_summary * $coupon_details['value']) / 100;
        } elseif ($coupon_details['type'] == 'fixed') {
            $item_level_discount_amount = $coupon_details['value'];
            // Ensure discount doesn't exceed subtotal
            if ($item_level_discount_amount > $sub_total_summary) {
                $item_level_discount_amount = $sub_total_summary;
            }
        } elseif ($coupon_details['type'] == 'free_shipping') {
            $current_shipping_cost = 0;
            // item_level_discount_amount remains 0 for free shipping type, its effect is on shipping cost
        }
    } else {
        // No valid coupon selected or "Seçin" was chosen, so reset any potential previous selection
        $selected_coupon_code = null;
        $item_level_discount_amount = 0;
        $current_shipping_cost = $shipping_cost_default; // Reset to default shipping
        $coupon_description_for_display = "";
    }
}

// --- Calculate final totals ---
$sub_total_after_item_discount = $sub_total_summary - $item_level_discount_amount;
$estimated_taxes = $sub_total_after_item_discount * 0.18; // Tax is calculated on the subtotal *after* item discounts
$grand_total_summary = $sub_total_after_item_discount + $current_shipping_cost + $estimated_taxes;
// --- END DISCOUNT CODE LOGIC & FINAL TOTALS ---


$error_message_odeme = null;
if(isset($_GET['error_msg_odeme'])) {
    $error_message_odeme = htmlspecialchars(urldecode($_GET['error_msg_odeme']));
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme - AŞIKZADE</title>
    <link rel="stylesheet" href="gecis_animasyonlari.css">
    <style>
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
            --discount-color: #e53e3e; /* For discount text if needed */
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif; }
        body { background-color: #fff; color: var(--asikzade-dark-text); line-height: 1.6; display: flex; flex-direction: column; min-height: 100vh; }
        .header { top: 0; width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 15px 50px; z-index: 1000; background: rgba(254, 246, 230, 0.95); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); box-shadow: 0 1px 0 rgba(0,0,0,0.05); }
        .logo-container { display: flex; align-items: center; gap: 10px; }
        .logo-container img { height: 48px; }
        .logo-text { font-size: 22px; font-weight: 600; letter-spacing: 1.5px; color: var(--asikzade-dark-text); text-decoration: none;}
        .main-nav { display: flex; align-items: center; }
        .user-actions-group { display: flex; align-items: center; gap:15px; }
        .nav-user-icon, .nav-cart-icon { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; border: 1.5px solid var(--asikzade-dark-text); color: var(--asikzade-dark-text); transition: all 0.3s ease; position: relative; text-decoration: none; }
        .nav-user-icon svg, .nav-cart-icon svg { width: 18px; height: 18px; stroke: currentColor; }
        .nav-user-icon:hover, .nav-cart-icon:hover { background-color: rgba(0,0,0,0.05); }
        .cart-badge { position: absolute; top: -5px; right: -8px; background-color: var(--asikzade-dark-green); color: var(--asikzade-light-text); border-radius: 50%; width: 20px; height: 20px; font-size: 12px; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 1px solid var(--asikzade-dark-text); }
        .checkout-container { display: flex; flex-wrap: wrap; max-width: 1200px; margin: 20px auto; gap: 30px; }
        .checkout-form-section { flex: 2; padding: 0 20px; min-width: 300px; }
        .checkout-summary-section { flex: 1; background-color: var(--asikzade-content-bg); padding: 30px 25px; border-left: 1px solid var(--asikzade-border); min-width: 300px; align-self: flex-start; }
        .section-title { font-size: 1.8rem; font-weight: 500; margin-bottom: 25px; padding-bottom: 10px; border-bottom: 1px solid var(--asikzade-border); }
        .contact-info p { margin-bottom: 5px; font-size: 0.95rem; } .contact-info .label { color: var(--asikzade-gray); display: inline-block; width: 80px; } .contact-info .value { font-weight: 500; } .contact-info a.change-link { font-size: 0.85rem; color: var(--asikzade-green); text-decoration: none; margin-left: 15px; } .contact-info a.change-link:hover { text-decoration: underline; }
        .form-group { margin-bottom: 20px; } .form-group label { display: block; margin-bottom: 6px; font-size: 0.9rem; color: #555; } .form-group input[type="text"], .form-group input[type="email"], .form-group input[type="tel"], .form-group select, .form-group .input-wrapper { width: 100%; padding: 12px 15px; border: 1px solid var(--input-border-color); border-radius: 5px; font-size: 1rem; background-color: var(--input-bg); transition: border-color 0.2s; }
        .form-group input:focus, .form-group select:focus, .form-group .input-wrapper:focus-within { outline: none; border-color: var(--input-focus-border-color); box-shadow: 0 0 0 1px var(--input-focus-border-color); }
        .form-group .input-wrapper { display: flex; align-items: center; padding: 0; } .form-group .input-wrapper input { border: none; outline: none; flex-grow: 1; padding: 12px 15px; background-color: transparent; } .form-group .input-wrapper svg { margin-right: 10px; color: var(--asikzade-gray); }
        .input-icon-wrapper { position: relative; display: flex; align-items: center; } .input-icon-wrapper input { padding-right: 30px; } .input-icon-wrapper .info-icon { position: absolute; right: 10px; color: var(--asikzade-gray); cursor: help; }
        .form-row { display: flex; gap: 15px; } .form-row .form-group { flex: 1; }
        .checkbox-group { display: flex; align-items: center; margin-bottom: 15px; font-size: 0.95rem; } .checkbox-group input[type="checkbox"] { margin-right: 10px; width: 18px; height: 18px; accent-color: var(--asikzade-green); }
        .payment-method { border: 1px solid var(--asikzade-border); border-radius: 5px; margin-bottom: 15px; } .payment-method-header { display: flex; align-items: center; padding: 15px; cursor: pointer; background-color: #f9f9f9; } .payment-method-header.selected { background-color: var(--asikzade-content-bg); border-bottom: 1px solid var(--asikzade-border); } .payment-method-header input[type="radio"] { margin-right: 12px; width: 18px; height: 18px; accent-color: var(--asikzade-green); } .payment-method-header label { font-weight: 500; flex-grow: 1; } .payment-method-icons img { height: 24px; margin-left: 8px; vertical-align: middle; }
        .payment-method-body { padding: 20px; border-top: 1px solid var(--asikzade-border); display: block; }
        .submit-button-container { margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--asikzade-border); text-align: right; } .submit-button-container .pay-now-btn { background-color: #ef4444; color: white; padding: 15px 35px; border: none; border-radius: 5px; font-size: 1.1rem; font-weight: 500; cursor: pointer; transition: background-color 0.3s; } .submit-button-container .pay-now-btn:hover { background-color: #dc2626; }
        .secure-info { font-size: 0.85rem; color: var(--asikzade-gray); margin-top: 10px; text-align: center; } .secure-info svg { vertical-align: middle; margin-right: 5px; }
        .order-summary-item { display: flex; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--asikzade-border); } .order-summary-item:last-child { border-bottom: none; margin-bottom: 0; } .order-summary-item img { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; margin-right: 15px; border: 1px solid var(--asikzade-border); } .item-details { flex-grow: 1; } .item-name { font-weight: 500; font-size: 0.95rem;} .item-quantity { font-size: 0.85rem; color: var(--asikzade-gray); } .item-price-summary { font-weight: 500; }
        .discount-code-form { display: flex; margin-bottom: 20px; margin-top: 20px; } 
        .discount-code-form select {
            flex-grow: 1;
            min-width: 0; /* EKLENEN/DEĞİŞTİRİLEN SATIR */
            padding: 10px 12px;
            border: 1px solid var(--input-border-color);
            border-radius: 5px 0 0 5px;
            font-size: 0.95rem;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%237a7a7a' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
        }
        .discount-code-form button { padding: 10px 18px; border: 1px solid var(--asikzade-green); background-color: var(--asikzade-green); color: var(--button-primary-text); border-radius: 0 5px 5px 0; cursor: pointer; font-weight: 500; white-space: nowrap; }
        .totals-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.95rem; } .totals-row.grand-total { font-size: 1.25rem; font-weight: bold; margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--asikzade-dark-text); } .totals-row .label { color: var(--asikzade-gray); } .totals-row .value { font-weight: 500; }
        .totals-row.discount-applied-row .label, .totals-row.discount-applied-row .value { color: var(--asikzade-green); }
        .info-icon { margin-left: 5px; color: var(--asikzade-gray); cursor: help; }
        .message-box-odeme { padding: 10px 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; font-size: 0.9rem; background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .footer { background-color: var(--asikzade-content-bg); padding: 40px 0 20px; color: var(--asikzade-dark-text); margin-top: auto; border-top: 1px solid var(--asikzade-border); }
        .footer-content { max-width: 1200px; margin: 0 auto; padding: 0 50px; text-align: center; } .footer-content p { font-size: 0.9rem; color: var(--asikzade-gray); }
        @media (max-width: 992px) { .checkout-container { flex-direction: column-reverse; } .checkout-summary-section { border-left: none; border-bottom: 1px solid var(--asikzade-border); margin-bottom: 30px; } }
        @media (max-width: 768px) { .header { padding: 12px 20px; } .logo-container img { height: 40px; } .logo-text { font-size: 18px; } .checkout-form-section, .checkout-summary-section { padding: 0 15px; } .form-row { flex-direction: column; gap: 0; } .form-row .form-group { margin-bottom: 20px; } }
    </style>
</head>
<body>
     <div id="sayfa-gecis-katmani"></div>
      <div id="sayfa-kapanis-katmani"></div>
    <header class="header">
        <div class="logo-container">
            <a href="index.php"><img src="https://i.imgur.com/rdZuONP.png" alt="Aşıkzade Logo"></a>
            <a href="index.php" class="logo-text"></a>
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
                <div class="message-box-odeme"><?php echo htmlspecialchars($error_message_odeme); ?></div>
            <?php endif; ?>

            <form action="odeme_process.php" method="POST" id="payment-form">
                <input type="hidden" name="user_id_field" value="<?php echo htmlspecialchars($user_id_field_value); ?>">
                <?php if ($selected_coupon_code): ?>
                    <input type="hidden" name="applied_coupon_code" value="<?php echo htmlspecialchars($selected_coupon_code); ?>">
                    <input type="hidden" name="item_level_discount_amount" value="<?php echo htmlspecialchars($item_level_discount_amount); ?>">
                    <input type="hidden" name="final_shipping_cost" value="<?php echo htmlspecialchars($current_shipping_cost); ?>">
                    <input type="hidden" name="final_sub_total_after_discount" value="<?php echo htmlspecialchars($sub_total_after_item_discount); ?>">
                    <input type="hidden" name="final_taxes" value="<?php echo htmlspecialchars($estimated_taxes); ?>">
                    <input type="hidden" name="final_grand_total" value="<?php echo htmlspecialchars($grand_total_summary); ?>">
                <?php endif; ?>


                <div class="contact-info section-block">
                    <h2 class="section-title">İletişim</h2>
                    <p><span class="label">Hesap:</span> <span class="value"><?php echo htmlspecialchars($user_email); ?></span></p>
                    <p style="margin-top: 5px; font-size: 0.9rem;"><a href="logout.php" style="color:var(--asikzade-green); text-decoration: none;">Çıkış Yap</a></p>
                </div>

                <div class="delivery-info section-block" style="margin-top: 30px;">
                    <h2 class="section-title">Teslimat</h2>
                    <div class="form-group">
                        <label for="country">Ülke/Bölge</label>
                        <select id="country" name="ulke">
                            <option value="TR" selected>Türkiye</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">Ad</label>
                            <input type="text" id="first_name" name="ad" value="<?php echo htmlspecialchars($user_ad); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Soyad</label>
                            <input type="text" id="last_name" name="soyad" value="<?php echo htmlspecialchars($user_soyad); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="address">Adres</label>
                        <input type="text" id="address" name="adres_satiri1" placeholder="Mahalle, Cadde, Sokak ve No" value="<?php echo htmlspecialchars($adres_satiri1_value); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="apt_suite">Bina No / Daire (İsteğe Bağlı)</label>
                        <input type="text" id="apt_suite" name="adres_satiri2" placeholder="Bina adı, kat, daire numarası" value="<?php echo htmlspecialchars($apt_suite_value); ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">Şehir</label>
                            <input type="text" id="city" name="sehir" value="Aydın" required>
                        </div>
                        <div class="form-group">
                            <label for="province">İlçe</label>
                             <input type="text" id="province" name="ilce" value="Efeler" required>
                        </div>
                        <div class="form-group">
                            <label for="postal_code">Posta Kodu</label>
                            <input type="text" id="postal_code" name="posta_kodu" value="09100" required>
                        </div>
                    </div>
                     <div class="form-group">
                        <label for="phone">Telefon</label>
                        <input type="tel" id="phone" name="telefon" placeholder="05XX XXX XX XX" value="<?php echo htmlspecialchars($user_telefon_ornek); ?>" required>
                    </div>
                </div>

                <div class="payment-section section-block" style="margin-top: 30px;">
                    <h2 class="section-title">Ödeme Yöntemi</h2>
                    <p style="font-size:0.9rem; color: var(--asikzade-gray); margin-bottom:15px;">Tüm işlemler güvenli ve şifrelidir.</p>
                    <div class="payment-method">
                        <div class="payment-method-header selected">
                            <input type="radio" id="credit_card" name="payment_method_radio" value="credit_card" checked style="display:none;">
                            <label for="credit_card" style="cursor:default;">Kredi Kartı</label>
                            <div class="payment-method-icons">
                                <img src="https://www.freepnglogos.com/uploads/visa-and-mastercard-logo-26.png" alt="Visa Mastercard" style="height: 28px;">
                            </div>
                        </div>
                        <div class="payment-method-body">
                            <div class="form-group">
                                <div class="input-wrapper">
                                    <input type="text" id="card_number" name="card_number" placeholder="Kart numarası" value="4545 4545 4545 4545">
                                    <svg class="icon-svg icon-svg--lock icon-svg--size-16 form__icon" width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M11 6V4.2C11 2.4 9.7 1 8 1S5 2.4 5 4.2V6H4v7h8V6h-1zm-1.5 0V4.2c0-.8.7-1.7 2.5-1.7S10 3.4 10 4.2V6h1.5z"></path></svg>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                     <div class="input-icon-wrapper">
                                        <input type="text" id="expiry_date" name="expiry_date" placeholder="MM / YY" value="12 / 28">
                                     </div>
                                </div>
                                <div class="form-group">
                                    <div class="input-icon-wrapper">
                                        <input type="text" id="cvv" name="cvv" placeholder="CVV" value="123">
                                        <svg class="info-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" focusable="false" aria-hidden="true"><circle cx="8" cy="8" r="7.5" stroke="currentColor" stroke-opacity=".56" fill="none"></circle><path d="M7.86 4.3a.6.6 0 01.6.6v.08a.6.6 0 01-.6.6.6.6 0 01-.6-.6V4.9a.6.6 0 01.6-.6zm-.33 2.95c0-.1.03-.18.08-.25a.54.54 0 01.42-.1c.17 0 .3.03.4.1.1.06.15.14.15.25v3.12c0 .1-.03-.18-.08-.25a.54.54 0 01-.42.1c-.17 0-.3-.03-.4-.1a.36.36 0 01-.15-.25V7.25z" fill="currentColor"></path></svg>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <input type="text" id="card_name" name="card_name" placeholder="Kart üzerindeki isim" value="<?php echo htmlspecialchars($user_ad . ' ' . $user_soyad); ?>">
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

        <aside class="checkout-summary-section" id="order-summary">
            <h2 class="section-title">Sipariş Özeti</h2>
            <?php if (!empty($cart_contents_summary)): ?>
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

                <form action="odeme.php#order-summary" method="POST" class="discount-code-form" id="discount-apply-form">
                    <select name="discount_code_select" id="discount_code_select">
                        <option value="">İndirim Kodu Seçin...</option>
                        <?php foreach ($discount_codes_available as $code => $details): ?>
                            <option value="<?php echo htmlspecialchars($code); ?>" <?php if ($selected_coupon_code == $code) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($details['description']); ?>
                                (<?php
                                    if ($details['type'] == 'percentage') echo $details['value'] . '%';
                                    elseif ($details['type'] == 'fixed') echo number_format($details['value'], 0, '', '') . ' TL';
                                    elseif ($details['type'] == 'free_shipping') echo 'Ücretsiz Kargo';
                                ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="discount_code_select_submit">Uygula</button>
                </form>

                <div class="totals-breakdown">
                    <div class="totals-row">
                        <span class="label">Ara Toplam</span>
                        <span class="value"><?php echo number_format($sub_total_summary, 2, ',', '.'); ?> TL</span>
                    </div>

                    <?php if ($selected_coupon_code && $item_level_discount_amount > 0 && $discount_codes_available[$selected_coupon_code]['type'] != 'free_shipping'): ?>
                    <div class="totals-row discount-applied-row">
                        <span class="label">İndirim (<?php echo htmlspecialchars($coupon_description_for_display); ?>)</span>
                        <span class="value">- <?php echo number_format($item_level_discount_amount, 2, ',', '.'); ?> TL</span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="totals-row">
                        <span class="label">Kargo</span>
                        <span class="value"><?php echo ($current_shipping_cost == 0 && $shipping_cost_default > 0) ? 'Ücretsiz' : number_format($current_shipping_cost, 2, ',', '.').' TL'; ?></span>
                    </div>
                    <?php if ($selected_coupon_code && isset($discount_codes_available[$selected_coupon_code]) && $discount_codes_available[$selected_coupon_code]['type'] == 'free_shipping' && $shipping_cost_default > 0): ?>
                    <div class="totals-row discount-applied-row" style="font-size: 0.85rem;">
                        <span class="label" colspan="2" style="width:100%; text-align:left;">(<?php echo htmlspecialchars($coupon_description_for_display); ?> uygulandı)</span>
                    </div>
                    <?php endif; ?>

                    <div class="totals-row">
                        <span class="label">Tahmini Vergiler (%18) <svg class="info-icon" width="12" height="12" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="8" cy="8" r="7.5" stroke="currentColor" stroke-opacity=".56" fill="none"></circle><path d="M7.86 4.3a.6.6 0 01.6.6v.08a.6.6 0 01-.6.6.6.6 0 01-.6-.6V4.9a.6.6 0 01.6-.6zm-.33 2.95c0-.1.03-.18.08-.25a.54.54 0 01.42-.1c.17 0 .3.03.4.1.1.06.15.14.15.25v3.12c0 .1-.03-.18-.08-.25a.54.54 0 01-.42.1c-.17 0-.3-.03-.4-.1a.36.36 0 01-.15-.25V7.25z" fill="currentColor"></path></svg></span>
                        <span class="value"><?php echo number_format($estimated_taxes, 2, ',', '.'); ?> TL</span>
                    </div>
                    <div class="totals-row grand-total">
                        <span class="label">TOPLAM</span>
                        <span class="value">TL <?php echo number_format($grand_total_summary, 2, ',', '.'); ?></span>
                    </div>
                </div>
            <?php else: ?>
                <p>Sipariş özetiniz için sepette ürün bulunmamaktadır.</p>
            <?php endif; ?>
        </aside>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <p>© <?php echo date("Y"); ?> Aşıkzade. Tüm hakları saklıdır.</p>
        </div>
    </footer>
<script> document.addEventListener('DOMContentLoaded', () => {
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
});  </script>
</body>
</html>