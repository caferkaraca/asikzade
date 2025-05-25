<?php
session_start();
include 'products_data.php'; // Ürün verilerini ve fonksiyonları dahil et
$cart_item_count = get_cart_count();

$cart_contents = [];
$grand_total = 0;

if (isset($_COOKIE['asikzade_cart'])) {
    $cart_cookie_data = json_decode($_COOKIE['asikzade_cart'], true);
    if (is_array($cart_cookie_data)) {
        foreach ($cart_cookie_data as $item_id_from_cookie => $item_data_from_cookie) {
            // Cookie'deki product_id'nin $products dizimizde olup olmadığını kontrol et
            if (isset($products[$item_id_from_cookie]) && isset($item_data_from_cookie['quantity'])) {
                $product = $products[$item_id_from_cookie];
                $quantity = max(1, (int)$item_data_from_cookie['quantity']); // Miktar en az 1 olsun
                $subtotal = $product['price'] * $quantity;
                $grand_total += $subtotal;

                $cart_contents[$item_id_from_cookie] = [
                    'id' => $item_id_from_cookie, // Doğru ID'yi kullandığımızdan emin olalım
                    'name' => $product['name'],
                    'image' => $product['image'] ?? $product['hero_image'] ?? 'https://via.placeholder.com/80', // Uygun bir resim seç veya varsayılan
                    'price' => $product['price'],
                    'quantity' => $quantity,
                    'subtotal' => $subtotal
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AŞIKZADE - Sepetim</title>
    <!-- index.php'deki temel CSS değişkenlerini ve genel stilleri buraya alabilir veya ayrı bir CSS'e taşıyabilirsiniz -->
    <style>
        :root {
            --asikzade-content-bg: #fef6e6;
            --asikzade-green: #8ba86d;
            --asikzade-dark-green: #6a8252;
            --asikzade-dark-text: #2d3e2a;
            --asikzade-light-text: #fdfcf8;
            --asikzade-gray: #7a7a7a;
            --asikzade-border: #e5e5e5;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body {
            background-color: var(--asikzade-content-bg);
            color: var(--asikzade-dark-text);
            line-height: 1.6;
        }
        /* Header Stilleri (index.php'den kopyalanabilir veya sadeleştirilebilir) */
        .header {
            /* position: sticky; /* Sayfa kaydırıldığında yapışkan kalabilir */
            top: 0;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 50px; /* Scrolled versiyonunu kullanalım */
            z-index: 1000;
            background: rgba(254, 246, 230, 0.95); /* var(--asikzade-content-bg) with transparency */
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 1px 0 rgba(0,0,0,0.05);
            margin-bottom: 30px; /* İçerikle arasında boşluk */
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

        /* Sepet Sayfası Stilleri */
        .cart-page-container {
            max-width: 950px;
            margin: 0 auto 40px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.07);
        }
        .cart-page-container h1 {
            text-align: center;
            margin-bottom: 30px;
            color: var(--asikzade-dark-text);
            font-weight: 500;
            font-size: 2rem;
        }
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .cart-table th, .cart-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--asikzade-border);
        }
        .cart-table th {
            background-color: #f8f8f8;
            font-weight: 500;
            font-size: 0.9rem;
            text-transform: uppercase;
            color: var(--asikzade-gray);
        }
        .cart-table td.product-info-cell {
            display: flex;
            align-items: center;
        }
        .cart-table img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
            border: 1px solid var(--asikzade-border);
        }
        .cart-item-name {
            font-weight: 500;
            color: var(--asikzade-dark-text);
            font-size: 1rem;
        }
        .cart-item-price {
            font-size: 0.9rem;
            color: var(--asikzade-gray);
        }
        .quantity-form input[type="number"] {
            width: 60px;
            padding: 8px;
            text-align: center;
            border: 1px solid var(--asikzade-border);
            border-radius: 4px;
            font-size: 0.95rem;
        }
         .quantity-form button[type="submit"] { /* Güncelle butonu için */
            background-color: var(--asikzade-green);
            color: white;
            border: none;
            padding: 6px 10px;
            font-size: 0.8rem;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 5px;
            opacity: 0.7; /* Başlangıçta soluk */
            transition: opacity 0.3s;
        }
        .quantity-form input[type="number"]:focus + button,
        .quantity-form input[type="number"]:valid + button { /* Değişiklik olduğunda belirginleş */
            opacity: 1;
        }


        .remove-item-btn {
            background: none;
            border: none;
            color: #c0392b; /* Kırmızı */
            cursor: pointer;
            font-size: 1.3rem; /* Biraz büyük bir çarpı */
            padding: 5px;
            line-height: 1;
        }
        .remove-item-btn:hover {
            color: #a93226;
        }
        .cart-actions-summary {
            display: flex;
            justify-content: space-between;
            align-items: flex-start; /* Üstten hizala */
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid var(--asikzade-dark-text);
        }
        .cart-actions .clear-cart-btn {
            background-color: var(--asikzade-gray);
            color: var(--asikzade-light-text);
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        .cart-actions .clear-cart-btn:hover {
            background-color: #555;
        }
        .cart-summary {
            text-align: right;
        }
        .cart-summary h3 {
            font-size: 1.4rem;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .cart-summary .checkout-btn {
            background-color: var(--asikzade-green);
            color: var(--asikzade-light-text);
            padding: 12px 30px;
            border: none;
            border-radius: 30px;
            font-size: 1.05rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
        }
        .cart-summary .checkout-btn:hover {
            background-color: var(--asikzade-dark-green);
        }
        .empty-cart-message {
            text-align: center;
            font-size: 1.3rem;
            color: var(--asikzade-gray);
            padding: 60px 20px;
            background-color: #fff;
            border-radius: 8px;
            margin: 40px auto;
            max-width: 600px;
        }
        .empty-cart-message a {
            color: var(--asikzade-green);
            text-decoration: none;
            font-weight: 500;
        }
        .empty-cart-message a:hover {
            text-decoration: underline;
        }

        /* Responsive Ayarlamalar */
        @media (max-width: 768px) {
            .header { padding: 12px 20px; }
            .logo-container img { height: 40px; }
            .logo-text { font-size: 18px; }
            .cart-page-container { padding: 15px; margin: 0 10px 20px 10px; }
            .cart-table thead { display: none; } /* Başlıkları mobilde gizle */
            .cart-table, .cart-table tbody, .cart-table tr, .cart-table td { display: block; width: 100%; }
            .cart-table tr { margin-bottom: 20px; border: 1px solid var(--asikzade-border); border-radius: 5px; padding: 10px; }
            .cart-table td {
                padding: 8px;
                border: none;
                display: flex; /* İçeriği yan yana getir */
                justify-content: space-between; /* Aralarına boşluk bırak */
                align-items: center;
            }
            .cart-table td::before { /* Mobilde etiketleri ekle */
                content: attr(data-label);
                font-weight: bold;
                margin-right: 10px;
                color: var(--asikzade-gray);
                min-width: 80px; /* Etiket için minimum genişlik */
            }
            .cart-table td.product-info-cell { flex-direction: row; /* Resim ve adı yan yana tut */ }
            .cart-table td.product-info-cell::before { display: none; /* Resim hücresinde etikete gerek yok */ }
            .cart-table img { width: 60px; height: 60px; margin-right: 10px;}
            .cart-item-name { font-size: 0.95rem; }
            .cart-actions-summary { flex-direction: column; align-items: center; gap: 20px;}
            .cart-actions { width: 100%; text-align: center; }
            .cart-summary { width: 100%; text-align: center; }
            .cart-summary .checkout-btn { width: 100%; }
            .quantity-form { display:flex; align-items:center;} /* Miktar formu için */
        }
         @media (max-width: 480px) {
            .cart-table td { flex-direction: column; align-items: flex-start; }
            .cart-table td::before { margin-bottom: 5px; }
            .quantity-form input[type="number"] { width: 50px; }
        }


    </style>
</head>
<body>
    <header class="header">
        <div class="logo-container">
            <a href="index.php"><img src="https://i.imgur.com/rdZuONP.png" alt="Aşıkzade Logo"></a>
            <a href="index.php" class="logo-text"></a>
        </div>
        <nav class="main-nav">
            <div class="user-actions-group">
                <a href="login.php" class="nav-user-icon" aria-label="Kullanıcı Girişi">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </a>
                <a href="sepet.php" class="nav-cart-icon" aria-label="Sepetim">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    <?php if ($cart_item_count > 0): ?>
                        <span class="cart-badge"><?php echo $cart_item_count; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </nav>
    </header>

    <main class="cart-page-container">
        <h1>Sepetim</h1>
        <?php if (empty($cart_contents)): ?>
            <p class="empty-cart-message">Sepetiniz şu anda boş. <br><a href="index.php#asikzade-products">Alışverişe Başlayın!</a></p>
        <?php else: ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th colspan="2">Ürün</th>
                        <th>Fiyat</th>
                        <th>Miktar</th>
                        <th>Ara Toplam</th>
                        <th>Kaldır</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_contents as $item_id => $item): ?>
                    <tr>
                        <td data-label="Ürün Resmi">
                             <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        </td>
                        <td data-label="Ürün Adı" class="product-info-cell-details"> <!-- Mobil için özel class -->
                            <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        </td>
                        <td data-label="Fiyat"><?php echo number_format($item['price'], 2); ?> TL</td>
                        <td data-label="Miktar">
                            <form action="cart_action.php" method="post" class="quantity-form">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="0" required>
                                <!-- Miktar 0 girilirse ürün silinecek (cart_action.php'de handle ediliyor) -->
                                <button type="submit">Güncelle</button>
                            </form>
                        </td>
                        <td data-label="Ara Toplam"><?php echo number_format($item['subtotal'], 2); ?> TL</td>
                        <td data-label="Kaldır">
                            <form action="cart_action.php" method="post">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="remove-item-btn" title="Ürünü Kaldır">✕</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="cart-actions-summary">
                <div class="cart-actions">
                    <form action="cart_action.php" method="post" style="display:inline;">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="clear-cart-btn">Sepeti Boşalt</button>
                    </form>
                </div>
                <div class="cart-summary">
                    <h3>Genel Toplam: <?php echo number_format($grand_total, 2); ?> TL</h3>
                    <a href="odeme.php" class="checkout-btn">Siparişi Tamamla</a> <!-- odeme.php'ye yönlendir -->
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer (index.php'den kopyalayabilirsiniz) -->
    <footer class="footer" style="margin-top: 50px; padding-top:30px; border-top:1px solid var(--asikzade-border); background-color:var(--asikzade-content-bg);">
        <div class="footer-content" style="max-width: 1200px; margin: 0 auto; padding: 0 50px;">
             <div class="footer-social-row" style="display: flex; justify-content: center; margin-bottom: 20px;">
                <!-- Sosyal medya ikonlarınızı buraya ekleyebilirsiniz -->
            </div>
            <div class="footer-bottom" style="display: flex; justify-content: space-between; align-items: center; padding-top: 20px; font-size:14px; color: var(--asikzade-gray); border-top: 1px solid var(--asikzade-border);">
                <p class="copyright">© <?php echo date("Y"); ?> Aşıkzade. Tüm hakları saklıdır.</p>
                 <div class="footer-links">
                    <ul style="list-style: none; display: flex; gap: 20px; margin: 0; padding: 0;">
                        <li><a href="#!" style="color: var(--asikzade-gray); text-decoration: none;">İade Politikası</a></li>
                        <li><a href="#!" style="color: var(--asikzade-gray); text-decoration: none;">Ödemeler</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    <script>
        // Miktar input alanı değiştiğinde "Güncelle" butonunu göster/gizle
        document.querySelectorAll('.quantity-form input[type="number"]').forEach(input => {
            const originalValue = input.value;
            const updateButton = input.nextElementSibling; // Güncelle butonu input'tan sonra gelmeli
            if (updateButton && updateButton.type === 'submit') {
                updateButton.style.opacity = '0.3'; // Başlangıçta soluk
                updateButton.style.pointerEvents = 'none'; // Tıklanamaz

                input.addEventListener('input', function() {
                    if (this.value !== originalValue) {
                        updateButton.style.opacity = '1';
                        updateButton.style.pointerEvents = 'auto';
                    } else {
                        updateButton.style.opacity = '0.3';
                        updateButton.style.pointerEvents = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>