<?php
session_start();
include 'products_data.php'; // products_data.php dosyasını dahil et

// URL'den ürün ID'sini al
$product_id_from_url = $_GET['id'] ?? null;
$product = null;

// Ürün ID'si varsa ve numerikse ürünü bul
if ($product_id_from_url && is_numeric($product_id_from_url)) {
    $product = get_product_by_id((int)$product_id_from_url); // get_product_by_id fonksiyonunu çağır
}

// Ürün bulunamazsa ana sayfaya yönlendir
if (!$product) {
    header("Location: index.php");
    exit;
}

$cart_item_count = get_cart_count(); // Sepetteki ürün sayısını al
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - AŞIKZADE</title>
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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            overflow-x: hidden;
            position: relative;
            color: var(--asikzade-dark-text);
            line-height: 1.6;
            background-color: var(--asikzade-content-bg);
            opacity: 0; /* For fade-in */
        }
        /* === MINIMALIST HEADER === */
        .header {
            position: fixed;
            top: 0;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 50px;
            z-index: 1000;
            background: transparent;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .header.scrolled {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 15px 50px;
            box-shadow: 0 1px 0 rgba(0,0,0,0.05);
        }
        .header.scrolled.content-bg-active {
             background: rgba(254, 246, 230, 0.95);
        }
        .header.scrolled.contact-bg-active {
            background: rgba(248, 200, 220, 0.95);
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-container img {
            height: 60px;
            transition: height 0.3s ease, filter 0.3s ease;
        }
         .header:not(.scrolled) .logo-container img.logo-inverted {
             filter: invert(1) brightness(1.5) drop-shadow(0 1px 2px rgba(0,0,0,0.3));
        }
        .header.scrolled.contact-bg-active .logo-container img.logo-inverted,
        .header.scrolled.contact-bg-active .logo-container img {
            filter: none;
        }

        .header.scrolled .logo-container img {
            height: 48px;
            filter: none;
        }

        .logo-text {
            font-size: 28px;
            font-weight: 600;
            letter-spacing: 1.5px;
            transition: all 0.3s ease;
            color: var(--asikzade-dark-text);
        }
        .header:not(.scrolled) .logo-text.dark-theme-text {
            color: var(--asikzade-dark-text);
        }
         .header.scrolled .logo-text {
            font-size: 22px;
            color: var(--asikzade-dark-text);
        }

        .main-nav {
            display: flex;
            align-items: center;
        }

        .user-actions-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-user-icon, .nav-cart-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1.5px solid var(--asikzade-dark-text);
            color: var(--asikzade-dark-text);
            transition: all 0.3s ease;
            position: relative;
            text-decoration: none;
        }
        .nav-user-icon svg, .nav-cart-icon svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
        }
        .header:not(.scrolled) .nav-user-icon.dark-theme-text,
        .header:not(.scrolled) .nav-cart-icon.dark-theme-text {
            border-color: var(--asikzade-dark-text);
            color: var(--asikzade-dark-text);
        }

        .header.scrolled .nav-user-icon,
        .header.scrolled .nav-cart-icon {
            border-color: var(--asikzade-dark-text);
            color: var(--asikzade-dark-text);
            width: 36px;
            height: 36px;
        }
        .nav-user-icon:hover, .nav-cart-icon:hover {
            background-color: rgba(0,0,0,0.05);
        }
        .header.scrolled .nav-user-icon:hover,
        .header.scrolled .nav-cart-icon:hover {
            background-color: rgba(0,0,0,0.05);
        }

        .cart-badge {
            position: absolute;
            top: -5px;
            right: -8px;
            background-color: var(--asikzade-green);
            color: var(--asikzade-light-text);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            border: 1px solid var(--asikzade-dark-text);
        }
        .header:not(.scrolled) .nav-cart-icon.dark-theme-text .cart-badge {
            border-color: var(--asikzade-dark-text);
        }
        .header.scrolled .cart-badge {
             background-color: var(--asikzade-dark-green);
            border-color: var(--asikzade-dark-text);
        }
        .header.scrolled.contact-bg-active .nav-cart-icon {
            border-color: var(--asikzade-dark-text);
            color: var(--asikzade-dark-text);
        }
        .header.scrolled.contact-bg-active .cart-badge {
            background-color: var(--asikzade-dark-green);
            color: var(--asikzade-light-text);
            border-color: var(--asikzade-dark-text);
        }

        /* === CONTENT WRAPPER === */
        .asikzade-content-wrapper {
            background-color: var(--asikzade-content-bg);
            color: var(--asikzade-dark-text);
            position: relative;
            z-index: 10;
            padding-top: 120px;
        }
        .section {
            padding: 60px 0;
            max-width: 1200px;
            margin: 0 auto;
            padding-left: 50px;
            padding-right: 50px;
        }

        /* === PRODUCT DETAIL STYLES === */
        .product-detail-section {
            background-color: var(--asikzade-content-bg);
        }
        .product-detail-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .product-main-title {
            font-size: clamp(2rem, 5vw, 3.2rem);
            font-weight: 700;
            color: var(--asikzade-dark-text);
            text-align: left;
            margin-bottom: 30px;
            padding-left: 10px;
        }
        .product-layout {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr; /* Resim sütunu biraz daha geniş */
            gap: 50px;
            align-items: flex-start;
        }
        .product-image-wrapper {
            position: relative;
            background-color: transparent; /* Çerçeveyi kaldırmak için */
            /* padding, border-radius, box-shadow kaldırıldı */
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .product-image-wrapper img.main-product-image {
            width: 100%;
            max-width: 450px; /* İsteğe bağlı, resmin maksimum genişliği */
            height: auto;
            display: block;
            object-fit: contain;
            /* border-radius kaldırıldı */
        }
        .product-badge {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 60px;
            height: 60px;
            z-index: 5;
        }
        .product-badge img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .product-info-wrapper {
            padding-top: 0;
        }
        .product-description p {
            font-size: 1rem;
            line-height: 1.8;
            color: var(--asikzade-gray);
            margin-bottom: 25px;
        }
        .product-page-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .product-page-actions .action-button {
            background-color: var(--asikzade-green);
            color: var(--asikzade-light-text);
            padding: 10px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
            transition: background-color 0.3s ease;
            border: none;
            cursor: pointer;
            text-align: center;
        }
        .product-page-actions .action-button:hover {
            background-color: var(--asikzade-dark-green);
        }

        .product-price-stock {
            margin-bottom: 25px;
        }
        .product-price {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--asikzade-dark-text);
            margin-bottom: 5px;
        }
        .product-stock-status {
            font-size: 0.9rem;
            color: var(--asikzade-green);
        }

        .add-to-cart-form {
            /* Quantity ve button için layout */
        }
        .quantity-control-block {
            display: flex;
            align-items: center;
            border: 1px solid #D1D1D1;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
            max-width: 150px;
        }
        .quantity-control-block .quantity-btn {
            background-color: transparent;
            border: none;
            color: #585858;
            font-size: 1.2rem;
            padding: 12px 18px;
            cursor: pointer;
            line-height: 1;
        }
        .quantity-control-block .quantity-btn.minus {
             border-right: 1px solid #D1D1D1;
        }
        .quantity-control-block .quantity-btn.plus {
             border-left: 1px solid #D1D1D1;
        }
        .quantity-control-block .quantity-btn:hover {
            background-color: #f7f7f7;
        }
        .quantity-control-block .quantity-input {
            width: 50px;
            text-align: center;
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--asikzade-dark-text);
            border: none;
            padding: 12px 0;
            -moz-appearance: textfield;
        }
        .quantity-control-block .quantity-input::-webkit-outer-spin-button,
        .quantity-control-block .quantity-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .product-add-to-cart-btn {
            background-color: var(--asikzade-green);
            color: var(--asikzade-light-text);
            padding: 14px 35px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
            max-width: 280px;
        }
        .product-add-to-cart-btn:hover {
            background-color: var(--asikzade-dark-green);
        }

        /* === FOOTER STYLES === */
        .footer {
            background-color: var(--asikzade-content-bg);
            padding: 60px 0 30px;
            position: relative;
            z-index: 20;
            color: var(--asikzade-dark-text);
            border-top: none;
        }
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 50px;
        }
        .footer-social-row {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }
        .social-icons {
            display: flex;
            gap: 25px;
        }
        .social-icons a {
            width: 48px;
            height: 48px;
            background-color: var(--asikzade-green);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 3px 6px rgba(0,0,0,0.12);
        }
        .social-icons a:hover {
            background-color: var(--asikzade-dark-green);
            transform: translateY(-2px);
        }
        .social-icons svg {
            width: 22px;
            height: 22px;
            fill: var(--asikzade-light-text);
        }
        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 25px;
            border-top: 1px solid var(--asikzade-border);
        }
        .footer-links ul {
            list-style: none;
            display: flex;
            gap: 25px;
            margin: 0;
            padding: 0;
        }
        .footer-links a {
            color: var(--asikzade-gray);
            text-decoration: none;
            font-size: 14px;
            font-weight: 400;
            transition: color 0.3s ease;
        }
        .footer-links a:hover {
            color: var(--asikzade-dark-text);
        }
        .copyright {
            font-size: 14px;
            color: var(--asikzade-gray);
            font-weight: 400;
            text-align: left;
            margin: 0;
        }

         /* --- RESPONSIVE STYLES --- */
        @media (max-width: 1024px) {
            .header { padding: 20px 30px; }
            .header.scrolled { padding: 12px 30px; }
            .logo-container img { height: 54px; }
            .header.scrolled .logo-container img { height: 44px; }
            .logo-text { font-size: 24px; }
            .header.scrolled .logo-text { font-size: 20px; }
            .nav-user-icon, .nav-cart-icon { width: 38px; height: 38px; }
            .header.scrolled .nav-user-icon, .header.scrolled .nav-cart-icon { width: 34px; height: 34px; }
            .section { padding-left: 30px; padding-right: 30px; }
        }
         @media (max-width: 992px) {
            .user-actions-group { gap: 20px; }
        }
        @media (max-width: 768px) {
            .asikzade-content-wrapper {
                padding-top: 90px;
            }
            .header { padding: 20px 20px; }
            .header.scrolled { padding: 12px 20px; }
            .logo-container img { height: 48px; }
            .header.scrolled .logo-container img { height: 40px; }
            .logo-text { font-size: 22px; }
            .header.scrolled .logo-text { font-size: 18px; }
            .nav-user-icon, .nav-cart-icon { width: 36px; height: 36px; }
            .header.scrolled .nav-user-icon, .header.scrolled .nav-cart-icon { width: 32px; height: 32px; }
            .section { padding-left: 20px; padding-right: 20px; }

            .product-main-title {
                font-size: clamp(1.8rem, 6vw, 2.5rem);
                margin-bottom: 20px;
                text-align: center;
                padding-left: 0;
            }
            .product-layout {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            .product-image-wrapper {
                margin: 0 auto;
                max-width: 320px; /* Mobil için resim boyutu */
            }
            .product-image-wrapper img.main-product-image {
                max-width: 100%; /* Mobil için kapsayıcısının tamamını kullanır */
            }
            .product-badge {
                width: 50px; height: 50px;
                bottom: 15px; right: 15px;
            }
            .product-info-wrapper {
                padding-top: 0;
                text-align: center;
            }
            .product-page-actions {
                justify-content: center;
                gap: 10px;
                margin-bottom: 25px;
            }
            .product-page-actions .action-button {
                padding: 10px 18px;
                font-size: 0.8rem;
            }
            .product-price-stock, .add-to-cart-form {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .quantity-control-block {
                margin: 0 auto 20px auto;
            }
            .product-add-to-cart-btn {
                max-width: 250px;
            }

            .footer-content { padding: 0 20px; }
            .footer-bottom { flex-direction: column; gap: 15px; text-align: center; padding-top: 20px; }
            .footer-links ul { justify-content: center; flex-wrap: wrap; gap: 10px 20px; }
            .copyright { text-align: center; }
            .footer-social-row { margin-bottom: 30px; }
            .social-icons a { width: 44px; height: 44px; }
            .social-icons svg { width: 20px; height: 20px; }
            .footer { padding: 40px 0 20px; }
        }
         @media (max-width: 480px) {
            .header { padding: 15px 15px; }
            .logo-container img { height: 42px; }
            .header.scrolled .logo-container img { height: 36px; }
            .logo-text { font-size: 20px; }
            .header.scrolled .logo-text { font-size: 17px; }
            .logo-container { gap: 8px; }
            .user-actions-group { gap: 10px; }
            .nav-user-icon, .nav-cart-icon { width: 34px; height: 34px; }
            .header.scrolled .nav-user-icon, .header.scrolled .nav-cart-icon { width: 30px; height: 30px; }
            .section { padding-left: 15px; padding-right: 15px; }
        }
    </style>
</head>
<body>
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
                        <span class="cart-badge"><?php echo $cart_item_count; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </nav>
    </header>

    <main class="asikzade-content-wrapper">
        <section class="section product-detail-section">
            <div class="product-detail-container">
                <h1 class="product-main-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="product-layout">
                    <div class="product-image-wrapper">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="main-product-image">
                        <?php if (!empty($product['badge_image'])): ?>
                        <div class="product-badge">
                            <img src="<?php echo htmlspecialchars($product['badge_image']); ?>" alt="Ürün Rozeti">
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info-wrapper">
                        <div class="product-description">
                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                        </div>

                        <div class="product-page-actions">
                            <a href="index.php#asikzade-about" class="action-button">HAKKIMIZDA</a>
                            <a href="index.php#asikzade-benefits" class="action-button">FAYDALARINI İNCELE</a>
                            <a href="index.php#asikzade-products" class="action-button">ÜRÜNLERİ KEŞFET</a>
                        </div>
                        
                        <div class="product-price-stock">
                            <p class="product-price"><?php echo number_format($product['price'], 2); ?> TL</p>
                            <!-- <p class="product-stock-status">Stokta Var</p> -->
                        </div>

                        <form action="cart_action.php" method="post" class="add-to-cart-form">
                            <input type="hidden" name="action" value="add">
                            <!-- $product_id_from_url, URL'den gelen ID'yi içerir. 
                                 Eğer sepetinize ürünün kendi array'indeki 'id' değerini göndermek istiyorsanız, 
                                 $product['id'] kullanmalısınız. products_data.php'deki $product array'inde 'id' anahtarı olmalı.
                            -->
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); // $product['id'] olarak değiştirildi ?>">
                            
                            <div class="quantity-control-block">
                                <button type="button" class="quantity-btn minus" aria-label="Azalt">-</button>
                                <input type="number" name="quantity" value="1" min="1" class="quantity-input" aria-label="Miktar">
                                <button type="button" class="quantity-btn plus" aria-label="Artır">+</button>
                            </div>
                            
                            <button type="submit" class="product-add-to-cart-btn">SEPETE EKLE</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-social-row">
                <div class="social-icons">
                    <a href="https://facebook.com/asikzadenatural" target="_blank" aria-label="Facebook"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 2.039c-5.514 0-9.961 4.448-9.961 9.961s4.447 9.961 9.961 9.961c5.515 0 9.961-4.448 9.961-9.961s-4.446-9.961-9.961-9.961zm3.621 9.561h-2.2v7.3h-3.22v-7.3h-1.56v-2.68h1.56v-1.93c0-1.301.63-3.35 3.35-3.35h2.37v2.67h-1.45c-.47 0-.72.24-.72.72v1.31h2.24l-.24 2.68z"/></svg></a>
                    <a href="https://linkedin.com/company/asikzadenatural" target="_blank" aria-label="LinkedIn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14zm-11.383 7.125H5.121v6.75h2.496v-6.75zm-1.248-2.302a1.49 1.49 0 1 0 0-2.979 1.49 1.49 0 0 0 0 2.979zm9.016 2.302c-2.016 0-2.848 1.081-3.312 2.04h-.048v-1.788H9.573v6.75h2.496v-3.375c0-.891.171-1.755 1.26-1.755.972 0 1.088.687 1.088 1.809v3.321h2.496v-3.828c0-2.203-1.088-3.852-3.288-3.852z"/></svg></a>
                    <a href="https://instagram.com/asikzadenatural" target="_blank" aria-label="Instagram"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 2c2.717 0 3.056.01 4.122.06 1.065.05 1.79.217 2.428.465.66.254 1.217.598 1.77.96.582.386.96.826 1.344 1.344.385.517.778 1.074 1.032 1.734.272.712.436 1.436.488 2.498.052 1.066.063 1.405.063 4.122s-.01 3.056-.061 4.122c-.053 1.065-.218 1.79-.487 2.428-.254.66-.598 1.217-.96 1.77-.386.582-.826.96-1.344 1.344-.517.385-1.074.778-1.734 1.032-.712.272-1.436.436-2.498.488-1.066.052-1.405.063-4.122.063s-3.056-.01-4.122-.061c-1.065-.053-1.79-.218-2.428-.487-.66-.254-1.217-.598-1.77-.96-.582-.386-.96-.826-1.344-1.344-.385-.517-.778-1.074-1.032-1.734-.272-.712-.436-1.436-.488-2.498C2.012 15.056 2 14.717 2 12s.01-3.056.061-4.122c.053-1.065.218-1.79.487-2.428.254-.66.598-1.217.96-1.77.386-.582.826.96 1.344-1.344.517-.385 1.074-.778 1.734-1.032.712-.272 1.436.436 2.498-.488C8.944 2.01 9.283 2 12 2zm0 1.802c-2.67 0-2.987.01-4.042.058-.975.045-1.505.207-1.857.344-.466.182-.795.396-1.15.748-.354.354-.566.684-.748 1.15-.137.352-.3.882-.344 1.857-.048 1.054-.058 1.373-.058 4.042s.01 2.987.058 4.042c.045.975.207 1.505.344 1.857.182.466.396.795.748 1.15.354.354.684.566 1.15.748.352.137.882.3 1.857.344 1.054.048 1.373.058 4.042.058s2.987-.01 4.042-.058c.975-.045 1.505-.207 1.857-.344.466-.182.795-.396 1.15-.748.354-.354-.566-.684.748-1.15.137-.352-.3-.882-.344-1.857.048-1.054.058-1.373.058-4.042s-.01-2.987-.058-4.042c-.045-.975-.207-1.505-.344-1.857-.182-.466-.396-.795-.748-1.15-.354-.354-.684-.566-1.15-.748-.352-.137-.882-.3-1.857-.344C14.987 3.812 14.67 3.802 12 3.802zm0 2.903c-2.836 0-5.135 2.299-5.135 5.135s2.299 5.135 5.135 5.135 5.135-2.299 5.135-5.135-2.299-5.135-5.135-5.135zm0 8.468c-1.837 0-3.333-1.496-3.333-3.333s1.496-3.333 3.333-3.333 3.333 1.496 3.333 3.333-1.496 3.333-3.333 3.333zm4.333-8.572a1.2 1.2 0 1 0 0-2.4 1.2 1.2 0 0 0 0 2.4z"/></svg></a>
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
        const mainHeader = document.getElementById('mainHeader');
        const headerLogoImage = document.getElementById('headerLogoImage');
        const siteLogoTextMawa = document.getElementById('siteLogoTextMawa');
        const navUserIcon = document.querySelector('.nav-user-icon');
        const navCartIcon = document.querySelector('.nav-cart-icon');

        function updateHeaderStyles() {
            const contactSection = document.getElementById('asikzade-contact');
            const mainContentWrapper = document.querySelector('.asikzade-content-wrapper');
            const scrollY = window.scrollY;
            const headerHeight = mainHeader.offsetHeight;

            if (scrollY > 50) {
                mainHeader.classList.add('scrolled');
                if(siteLogoTextMawa) siteLogoTextMawa.textContent = "AŞIKZADE";
                if(headerLogoImage) headerLogoImage.classList.remove('logo-inverted');
            } else {
                mainHeader.classList.remove('scrolled');
                if(siteLogoTextMawa) siteLogoTextMawa.textContent = "AŞIKZADE";
                if(headerLogoImage) headerLogoImage.classList.remove('logo-inverted');
            }
            
            mainHeader.style.background = '';
            if (headerLogoImage) headerLogoImage.classList.remove('logo-inverted');
            if (siteLogoTextMawa) siteLogoTextMawa.classList.remove('dark-theme-text');
            if (navUserIcon) navUserIcon.classList.remove('dark-theme-text');
            if (navCartIcon) navCartIcon.classList.remove('dark-theme-text');

            let isOverContactBg = false;
            if (contactSection) {
                const contactRect = contactSection.getBoundingClientRect();
                isOverContactBg = contactRect.top <= headerHeight && contactRect.bottom > headerHeight;
            }

            let isOverContentBg = false;
            if (mainContentWrapper && !isOverContactBg) {
                const contentWrapperRect = mainContentWrapper.getBoundingClientRect();
                isOverContentBg = contentWrapperRect.top <= headerHeight && scrollY > 0;
            }

            if (isOverContactBg) {
                mainHeader.classList.add('contact-bg-active');
                mainHeader.classList.remove('content-bg-active');
                if (siteLogoTextMawa) siteLogoTextMawa.style.color = 'var(--asikzade-dark-text)';
            } else if (isOverContentBg) {
                 mainHeader.classList.add('content-bg-active');
                 mainHeader.classList.remove('contact-bg-active');
                 if (siteLogoTextMawa) siteLogoTextMawa.style.color = '';
            } else {
                mainHeader.classList.remove('content-bg-active', 'contact-bg-active');
                if (siteLogoTextMawa) siteLogoTextMawa.style.color = '';
            }

            if (mainHeader.classList.contains('scrolled')) {
                if(siteLogoTextMawa) siteLogoTextMawa.style.color = 'var(--asikzade-dark-text)';
                if(navUserIcon) {
                    navUserIcon.style.borderColor = 'var(--asikzade-dark-text)';
                    navUserIcon.style.color = 'var(--asikzade-dark-text)';
                }
                if(navCartIcon) {
                    navCartIcon.style.borderColor = 'var(--asikzade-dark-text)';
                    navCartIcon.style.color = 'var(--asikzade-dark-text)';
                }
                 if(headerLogoImage) headerLogoImage.classList.remove('logo-inverted');
            } else {
                 if(siteLogoTextMawa) siteLogoTextMawa.style.color = 'var(--asikzade-dark-text)';
                 if(navUserIcon) {
                    navUserIcon.style.borderColor = 'var(--asikzade-dark-text)';
                    navUserIcon.style.color = 'var(--asikzade-dark-text)';
                 }
                 if(navCartIcon) {
                    navCartIcon.style.borderColor = 'var(--asikzade-dark-text)';
                    navCartIcon.style.color = 'var(--asikzade-dark-text)';
                 }
            }
        }

        function handleScroll() {
            updateHeaderStyles();
        }
        window.addEventListener('scroll', handleScroll);
        
        document.addEventListener('DOMContentLoaded', () => {
            if(siteLogoTextMawa) {
                siteLogoTextMawa.textContent = "AŞIKZADE";
            }
            updateHeaderStyles();
            document.body.style.opacity = '1';
        });

        document.querySelectorAll('.quantity-btn.minus').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('.quantity-input');
                let currentValue = parseInt(input.value);
                if (currentValue > 1) {
                    input.value = currentValue - 1;
                }
            });
        });

        document.querySelectorAll('.quantity-btn.plus').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('.quantity-input');
                let currentValue = parseInt(input.value);
                input.value = currentValue + 1;
            });
        });
        
        document.addEventListener('DOMContentLoaded', () => {
            const images = document.querySelectorAll('img:not(.product-image-mawa):not(.full-screen-image-section img)');
            images.forEach(img => {
                if(img.loading !== 'eager') {
                    img.loading = 'lazy';
                }
            });
        });
    </script>
</body>
</html>