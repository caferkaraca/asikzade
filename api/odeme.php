<?php
require_once 'config.php'; // Defines paths, Supabase keys etc.
// products_data.php dosyasını dahil et
if (file_exists('products_data.php')) {
    include 'products_data.php'; // $products array and get_cart_count()
} else {
    $products = []; // products_data.php bulunamazsa, boş bir products array oluştur
    error_log("odeme.php: products_data.php dosyası bulunamadı.");
}

$user_cookie_name = 'asikzade_user_session';
$user_logged_in = false;
$user_data_from_cookie = [];

if (isset($_COOKIE[$user_cookie_name])) {
    $user_data_json = $_COOKIE[$user_cookie_name];
    $user_data_from_cookie = json_decode($user_data_json, true);
    if ($user_data_from_cookie && isset($user_data_from_cookie['user_id'])) {
        $user_logged_in = true;
    } else {
        $user_data_from_cookie = []; // Malformed cookie, treat as not logged in
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

// User is logged in, extract data with defaults
$user_id_field_value = $user_data_from_cookie['user_id'] ?? '';
$user_ad = $user_data_from_cookie['user_ad'] ?? '';
$user_soyad = $user_data_from_cookie['user_soyad'] ?? '';
$user_email = $user_data_from_cookie['user_email'] ?? '';
$user_telefon = $user_data_from_cookie['user_telefon'] ?? ''; // Name changed to user_telefon for clarity

// Address handling:
$adres_satiri1_value = $user_data_from_cookie['adres_satiri1'] ?? '';
$apt_suite_value = $user_data_from_cookie['apt_suite'] ?? ''; // This corresponds to adres_satiri2

// If adres_satiri1 and apt_suite are not directly in cookie, try to use 'user_adres' if it exists
if (empty($adres_satiri1_value) && isset($user_data_from_cookie['user_adres'])) {
    $user_adres_parts = explode("\n", $user_data_from_cookie['user_adres']);
    $adres_satiri1_value = $user_adres_parts[0] ?? '';
    if (count($user_adres_parts) > 1) {
        // If user_adres has multiple lines, concatenate them for apt_suite_value or take the second line
        $apt_suite_value = implode(" ", array_slice($user_adres_parts, 1)); // Or just $user_adres_parts[1] ?? '';
    }
} elseif (empty($adres_satiri1_value) && empty($apt_suite_value)) {
    // Fallback to generic placeholders if nothing is in the cookie
    $adres_satiri1_value = 'Örnek Mah. Atatürk Cad. No:1 Daire:2';
    $apt_suite_value = 'Efeler / AYDIN'; // Or can be empty by default
}


// Cart data and summary calculation
$cart_contents_summary = [];
$sub_total_summary = 0;
$shipping_cost = 50.00; // Fixed shipping

if (isset($_COOKIE['asikzade_cart'])) {
    $cart_cookie_data = json_decode($_COOKIE['asikzade_cart'], true);
    if (is_array($cart_cookie_data) && !empty($cart_cookie_data)) {
        foreach ($cart_cookie_data as $item_id_from_cookie => $item_data_from_cookie) {
            // Ensure $item_id_from_cookie is treated as string if your $products keys are strings
            $item_id_str = (string) $item_id_from_cookie;
            if (isset($products[$item_id_str]) && isset($item_data_from_cookie['quantity'])) {
                $product = $products[$item_id_str];
                $quantity = max(1, (int)$item_data_from_cookie['quantity']);
                $item_subtotal = $product['price'] * $quantity;
                $sub_total_summary += $item_subtotal;
                $cart_contents_summary[$item_id_str] = [
                    'name' => $product['name'],
                    'image' => $product['image'] ?? $product['hero_image'] ?? 'https://via.placeholder.com/60',
                    'price' => $product['price'],
                    'quantity' => $quantity,
                    'total' => $item_subtotal // Changed 'subtotal' to 'total' to match HTML template
                ];
            }
        }
    }
}

// Check if cart is empty after processing cookie
if (empty($cart_contents_summary)) {
    header('Location: sepet.php?info_msg=' . urlencode("Ödeme yapabilmek için sepetinizde ürün bulunmalıdır."));
    exit;
}

$estimated_taxes = $sub_total_summary * 0.18; // Example tax rate
$grand_total_summary = $sub_total_summary + $shipping_cost + $estimated_taxes;

$error_message_odeme = null;
if(isset($_GET['error_msg_odeme'])) {
    $error_message_odeme = htmlspecialchars(urldecode($_GET['error_msg_odeme']));
}

// Cart item count for header
$header_cart_item_count = 0;
if (function_exists('get_cart_count')) {
    $header_cart_item_count = get_cart_count();
} else { // Fallback if get_cart_count is not available
    foreach ($cart_contents_summary as $item) {
        $header_cart_item_count += $item['quantity'];
    }
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme Sayfası - Aşıkzade</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/odeme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <a href="index.php" class="logo-container">
            <img src="https://i.imgur.com/rdZuONP.png" alt="Aşıkzade Logo">
            <span class="logo-text">AŞIKZADE</span>
        </a>
        <nav class="navigation">
            <ul class="nav-links">
                <li><a href="index.php">Ana Sayfa</a></li>
                <li><a href="index.php#asikzade-products">Ürünler</a></li>
                <li><a href="hakkimizda.php">Hakkımızda</a></li>
                <li><a href="iletisim.php">İletişim</a></li>
            </ul>
        </nav>
        <div class="header-actions">
            <a href="sepet.php" class="action-link nav-cart-icon">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-item-count" id="cart-item-count-header">
                    <?php echo $header_cart_item_count; ?>
                </span>
            </a>
            <a href="<?php echo $user_logged_in ? 'dashboard.php' : 'login.php'; ?>" class="action-link nav-user-icon">
                <i class="fas fa-user"></i>
            </a>
            <button class="menu-toggle"><i class="fas fa-bars"></i></button>
        </div>
    </header>

    <main class="odeme-main-content">
        <div class="checkout-container">
            <div class="billing-details">
                <h2>Fatura Bilgileri</h2>
                <?php if ($error_message_odeme): ?>
                    <div class="error-message-odeme"><?php echo $error_message_odeme; ?></div>
                <?php endif; ?>

                <form action="odeme_process.php" method="POST" id="payment-form">
                    <input type="hidden" name="user_id_field" value="<?php echo htmlspecialchars($user_id_field_value); ?>">
                    
                    <div class="form-row">
                        <div class="form-group half-width">
                            <label for="ad">Ad</label>
                            <input type="text" id="ad" name="ad" value="<?php echo htmlspecialchars($user_ad); ?>" required>
                        </div>
                        <div class="form-group half-width">
                            <label for="soyad">Soyad</label>
                            <input type="text" id="soyad" name="soyad" value="<?php echo htmlspecialchars($user_soyad); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">E-posta</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_email); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="telefon">Telefon</label>
                        <input type="tel" id="telefon" name="telefon" value="<?php echo htmlspecialchars($user_telefon); ?>" placeholder="05XX XXX XX XX" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="country">Ülke</label>
                        <select id="country" name="country" required>
                            <option value="TR" selected>Türkiye</option>
                            {/* Diğer ülkeler eklenebilir */}
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="adres_satiri1">Adres Satırı 1 (Cadde, Sokak, No)</label>
                        <input type="text" id="adres_satiri1" name="adres_satiri1" value="<?php echo htmlspecialchars($adres_satiri1_value); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="adres_satiri2">Adres Satırı 2 (Apartman, Daire No, İlçe / Şehir vb.)</label>
                        <input type="text" id="adres_satiri2" name="adres_satiri2" value="<?php echo htmlspecialchars($apt_suite_value); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group third-width">
                            <label for="sehir">Şehir</label>
                            <input type="text" id="sehir" name="sehir" value="<?php echo htmlspecialchars($user_data_from_cookie['sehir'] ?? 'Aydın'); ?>" required>
                        </div>
                        <div class="form-group third-width">
                            <label for="ilce">İlçe</label> {/* odeme_process.php 'ilce' bekliyor */}
                            <input type="text" id="ilce" name="ilce" value="<?php echo htmlspecialchars($user_data_from_cookie['ilce'] ?? 'Efeler'); ?>" required>
                        </div>
                        <div class="form-group third-width">
                            <label for="posta_kodu">Posta Kodu</label>
                            <input type="text" id="posta_kodu" name="posta_kodu" value="<?php echo htmlspecialchars($user_data_from_cookie['posta_kodu'] ?? '09100'); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="order_notes">Sipariş Notları (İsteğe Bağlı)</label>
                        <textarea id="order_notes" name="order_notes" rows="3" placeholder="Siparişinizle ilgili özel notlarınız varsa buraya yazabilirsiniz."></textarea>
                    </div>

                    <div class="payment-methods">
                        <h3>Ödeme Yöntemi</h3>
                        <div class="payment-option">
                            <input type="radio" id="havale_eft" name="payment_method" value="havale_eft" checked>
                            <label for="havale_eft">Banka Havalesi / EFT</label>
                            <div class="payment-info" id="havale_eft_info">
                                <p>Lütfen ödemenizi aşağıdaki banka hesabımıza yapınız ve açıklama kısmına sipariş numaranızı yazınız. Siparişiniz ödeme onaylandıktan sonra işleme alınacaktır.</p>
                                <p><strong>Banka Adı:</strong> XYZ Bankası</p>
                                <p><strong>Hesap Sahibi:</strong> AŞIKZADE ÇAY VE KAHVE LTD.</p>
                                <p><strong>IBAN:</strong> TR00 0000 0000 0000 0000 0000</p>
                            </div>
                        </div>
                        <div class="payment-option">
                            <input type="radio" id="kapida_odeme" name="payment_method" value="kapida_odeme">
                            <label for="kapida_odeme">Kapıda Ödeme</label>
                             <div class="payment-info" id="kapida_odeme_info" style="display:none;">
                                <p>Kapıda nakit veya kredi kartı ile ödeme yapabilirsiniz. Kapıda ödeme hizmet bedeli <strong>25.00 TL</strong>'dir. Bu tutar sipariş toplamınıza eklenecektir.</p>
                            </div>
                        </div>
                        {/* 
                        <div class="payment-option">
                            <input type="radio" id="credit_card" name="payment_method" value="credit_card">
                            <label for="credit_card">Kredi Kartı (Yakında)</label>
                            <div class="payment-info" id="credit_card_info" style="display:none;">
                                <p>Kredi kartı ile güvenli ödeme altyapımız yakında hizmetinizde olacaktır.</p>
                            </div>
                        </div>
                        */}
                    </div>
                    
                    <div class="form-group terms-agreement">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms"><a href="gizlilik-politikasi.php#mesafeli-satis" target="_blank">Mesafeli satış sözleşmesini</a> ve <a href="gizlilik-politikasi.php#on-bilgilendirme" target="_blank">ön bilgilendirme formunu</a> okudum, kabul ediyorum.</label>
                    </div>

                    <button type="submit" class="btn-submit-order">Siparişi Tamamla</button>
                </form>
            </div>

            <div class="order-summary">
                <h2>Sipariş Özeti</h2>
                <div class="cart-items-summary">
                    <?php if (!empty($cart_contents_summary)): ?>
                        <?php foreach ($cart_contents_summary as $item_id => $item): ?>
                            <div class="cart-item-summary">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image-summary">
                                <div class="item-details-summary">
                                    <span class="item-name-summary"><?php echo htmlspecialchars($item['name']); ?></span>
                                    <span class="item-quantity-price-summary"><?php echo htmlspecialchars($item['quantity']); ?> x <?php echo number_format($item['price'], 2, ',', '.'); ?> TL</span>
                                </div>
                                <span class="item-total-summary"><?php echo number_format($item['total'], 2, ',', '.'); ?> TL</span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Sepetinizde ürün bulunmamaktadır.</p>
                    <?php endif; ?>
                </div>
                <div class="totals-summary">
                    <div class="total-line">
                        <span>Ara Toplam:</span>
                        <span id="subtotal-value"><?php echo number_format($sub_total_summary, 2, ',', '.'); ?> TL</span>
                    </div>
                    <div class="total-line">
                        <span>Kargo Ücreti:</span>
                        <span id="shipping-cost-value"><?php echo number_format($shipping_cost, 2, ',', '.'); ?> TL</span>
                    </div>
                    <div class="total-line">
                        <span>Tahmini Vergiler (%18):</span>
                        <span id="taxes-value"><?php echo number_format($estimated_taxes, 2, ',', '.'); ?> TL</span>
                    </div>
                    <div class="total-line kapida-odeme-ek-ucret" style="display:none;">
                        <span>Kapıda Ödeme Hizmet Bedeli:</span>
                        <span id="kapida-odeme-fee-value">25.00 TL</span>
                    </div>
                    <div class="total-line grand-total">
                        <span>Genel Toplam:</span>
                        <span id="grand-total-value"><?php echo number_format($grand_total_summary, 2, ',', '.'); ?> TL</span>
                    </div>
                </div>
                 <div class="continue-shopping">
                    <a href="index.php#asikzade-products" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Alışverişe Devam Et
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-logo-social">
                <a href="index.php" class="footer-logo">
                    <img src="https://i.imgur.com/rdZuONP.png" alt="Aşıkzade Footer Logo">
                    <span>AŞIKZADE</span>
                </a>
                <div class="social-icons">
                    <a href="#" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" target="_blank"><i class="fab fa-twitter"></i></a>
                    <a href="#" target="_blank"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-links">
                <h4>Hızlı Erişim</h4>
                <ul>
                    <li><a href="index.php">Ana Sayfa</a></li>
                    <li><a href="index.php#asikzade-products">Ürünler</a></li>
                    <li><a href="hakkimizda.php">Hakkımızda</a></li>
                    <li><a href="iletisim.php">İletişim</a></li>
                    <li><a href="dashboard.php">Hesabım</a></li> 
                </ul>
            </div>
            <div class="footer-contact">
                <h4>İletişim</h4>
                <p><i class="fas fa-map-marker-alt"></i> Adres: Örnek Mah. Örnek Cad. No:123, Aydın</p>
                <p><i class="fas fa-phone"></i> Telefon: +90 555 123 4567</p>
                <p><i class="fas fa-envelope"></i> E-posta: bilgi@asikzade.com</p>
            </div>
            <div class="footer-legal">
                <h4>Yasal</h4>
                <ul>
                    <li><a href="gizlilik-politikasi.php">Gizlilik Politikası</a></li>
                    <li><a href="gizlilik-politikasi.php#mesafeli-satis">Mesafeli Satış Sözleşmesi</a></li>
                    <li><a href="gizlilik-politikasi.php#iptal-iade">İptal ve İade Koşulları</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date("Y"); ?> Aşıkzade. Tüm hakları saklıdır.</p>
        </div>
    </footer>

    <script src="assets/js/odeme.js"></script>
    <script src="assets/js/main.js"></script> 
</body>
</html>
