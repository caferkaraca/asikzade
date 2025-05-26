<?php
session_start();
include 'products_data.php'; // Ürün verilerini ve fonksiyonları dahil et
$cart_item_count = get_cart_count(); // Sepetteki ürün sayısını al
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AŞIKZADE - Doğal Lezzetler</title>
    <link rel="stylesheet" href="gecis_animasyonlari.css">
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
            --asikzade-nav-button-bg: #add18c;
            --asikzade-nav-button-text: #333333;
            --asikzade-nav-button-hover-bg: #9cc17c;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            overflow-x: hidden;
            position: relative;
            color: var(--asikzade-dark-text);
            line-height: 1.6;
            transition: background 0.8s ease;
            background-color: var(--asikzade-content-bg);
        }

        /* Dynamic background classes for Hero */
        .bg-product1-custom { background: #c24f3e; }
        .bg-product2-custom { background: #f4eddb; }
        .bg-product3-custom { background: #473345; }
        .bg-product4-custom { background: #ffd054; }

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
        .header.scrolled.content-bg-active { background: rgba(254, 246, 230, 0.95); }
        .header.scrolled.contact-bg-active { background: rgba(248, 200, 220, 0.95); }

        .logo-container { display: flex; align-items: center; gap: 10px; }
        .logo-container img { height: 60px; transition: height 0.3s ease, filter 0.3s ease; }
        .logo-container img.logo-inverted { filter: invert(1) brightness(1.5) drop-shadow(0 1px 2px rgba(0,0,0,0.3)); }
        .header.scrolled.contact-bg-active .logo-container img.logo-inverted,
        .header.scrolled.contact-bg-active .logo-container img { filter: none; }
        .header.scrolled .logo-container img { height: 48px; }
        .logo-text { font-size: 28px; font-weight: 600; letter-spacing: 1.5px; transition: all 0.3s ease; color: var(--asikzade-light-text); }
        .header:not(.scrolled) .logo-text.dark-theme-text { color: var(--asikzade-dark-text); }
        .header.scrolled .logo-text { font-size: 22px; color: var(--asikzade-dark-text); }

        .main-nav { display: flex; align-items: center; gap: 25px; }
        .nav-page-links { display: flex; align-items: center; gap: 12px; }
        .nav-page-link-button { padding: 10px 25px; background-color: var(--asikzade-nav-button-bg); color: var(--asikzade-nav-button-text); font-size: 14px; font-weight: 500; text-decoration: none; border-radius: 30px; transition: background-color 0.3s ease, transform 0.2s ease; white-space: nowrap; border: none; }
        .nav-page-link-button:hover { background-color: var(--asikzade-nav-button-hover-bg); transform: translateY(-1px); }

        .user-actions-group { display: flex; align-items: center; gap: 15px; }
        .nav-user-icon, .nav-cart-icon { display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; border: 1.5px solid var(--asikzade-light-text); color: var(--asikzade-light-text); transition: all 0.3s ease; position: relative; text-decoration: none; }
        .nav-user-icon svg, .nav-cart-icon svg { width: 18px; height: 18px; stroke: currentColor; }
        .header:not(.scrolled) .nav-user-icon.dark-theme-text,
        .header:not(.scrolled) .nav-cart-icon.dark-theme-text { border-color: var(--asikzade-dark-text); color: var(--asikzade-dark-text); }
        .header.scrolled .nav-user-icon,
        .header.scrolled .nav-cart-icon { border-color: var(--asikzade-dark-text); color: var(--asikzade-dark-text); width: 36px; height: 36px; }
        .nav-user-icon:hover, .nav-cart-icon:hover { background-color: rgba(255,255,255,0.1); }
        .header.scrolled .nav-user-icon:hover,
        .header.scrolled .nav-cart-icon:hover { background-color: rgba(0,0,0,0.05); }
        .cart-badge { position: absolute; top: -5px; right: -8px; background-color: var(--asikzade-green); color: var(--asikzade-light-text); border-radius: 50%; width: 20px; height: 20px; font-size: 12px; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 1px solid var(--asikzade-light-text); }
        .header:not(.scrolled) .nav-cart-icon.dark-theme-text .cart-badge { border-color: var(--asikzade-dark-text); }
        .header.scrolled .cart-badge { background-color: var(--asikzade-dark-green); border-color: var(--asikzade-dark-text); }
        .header.scrolled.contact-bg-active .nav-cart-icon { border-color: var(--asikzade-dark-text); color: var(--asikzade-dark-text); }
        .header.scrolled.contact-bg-active .cart-badge { background-color: var(--asikzade-dark-green); color: var(--asikzade-light-text); border-color: var(--asikzade-dark-text); }

        .hero-product-section { min-height: 100vh; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; padding-top: 80px; }
        .wave-transition { position: absolute; bottom: -50px; left: 0; width: 100%; height: 120px; z-index: 100; pointer-events: none; }
        .wave-transition svg { width: 100%; height: 100%; }
        .wave-transition path { transition: fill 0.8s ease; }
        .product-showcase-mawa { position: relative; z-index: 100; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; max-width: 500px; padding: 20px; }
        .product-name-background-mawa { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: clamp(30px, 9vw, 130px); font-weight: 900; z-index: 1; pointer-events: none; text-transform: uppercase; white-space: normal; max-width: 90vw; line-height: 1.0; opacity: 0; transition: opacity 0.5s ease 0.2s, transform 0.5s ease 0.2s, color 0.5s ease; text-align: center; overflow-wrap: break-word; }
        .product-image-container-mawa { position: relative; width: clamp(200px, 60vw, 300px); height: clamp(280px, 80vw, 400px); perspective: 1200px; margin-bottom: 30px; cursor: grab; z-index: 2; }
        .product-image-mawa { width: 100%; height: 100%; object-fit: contain; transition: transform 0.05s linear; transform-style: preserve-3d; filter: drop-shadow(0 25px 50px rgba(0,0,0,0.25)); will-change: transform; }
        .product-info-mawa { padding: clamp(10px, 2vw, 15px) clamp(20px, 5vw, 40px); background: rgba(255, 255, 255, 0.9); color: #333; border-radius: 30px; font-size: clamp(18px, 4vw, 22px); font-weight: 600; box-shadow: 0 8px 25px rgba(0,0,0,0.08); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); transition: opacity 0.4s ease 0.1s, transform 0.4s ease 0.1s; position: relative; z-index: 3; opacity: 0; transform: translateY(15px); }
        .arrow-mawa { position: absolute; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; background: transparent; border: 1.5px solid rgba(255, 255, 255, 0.3); border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease; z-index: 200; }
        .arrow-mawa:hover { background: rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.6); transform: translateY(-50%) scale(1.05); }
        .arrow-mawa.left { left: clamp(20px, 4vw, 60px); } .arrow-mawa.right { right: clamp(20px, 4vw, 60px); }
        .arrow-mawa::after { content: ''; width: 8px; height: 8px; border-top: 2px solid rgba(255, 255, 255, 0.8); border-right: 2px solid rgba(255, 255, 255, 0.8); }
        .arrow-mawa.left::after { transform: rotate(-135deg); margin-left: 2px; } .arrow-mawa.right::after { transform: rotate(45deg); margin-right: 2px; }
        .bg-element-mawa { position: absolute; pointer-events: none; will-change: transform, border-radius, opacity; z-index: 0; }
        .blob-mawa { border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%; background: rgba(255, 255, 255, 0.05); filter: blur(80px); animation: morphBlob 30s ease-in-out infinite alternate; }
        .blob-mawa.b1 { width: 40vw; height: 40vw; max-width: 500px; top: -20%; left: -20%; animation-duration: 35s; }
        .blob-mawa.b2 { width: 35vw; height: 35vw; max-width: 450px; bottom: -20%; right: -20%; animation-duration: 40s; }

        .dual-image-container { display: flex; width: 100%; gap: 0; padding: 0; background-color: transparent; }
        .full-screen-image-section { width: 50%; height: 70vh; overflow: hidden; position: relative; border-radius: 0; box-shadow: none; }
        .full-screen-image-section img { width: 100%; height: 100%; object-fit: cover; display: block; }

        .asikzade-content-wrapper { background-color: var(--asikzade-content-bg); color: var(--asikzade-dark-text); position: relative; z-index: 10; padding-top: 60px; }
        .section { padding: 100px 0; max-width: 1200px; margin: 0 auto; padding-left: 50px; padding-right: 50px; }
        .section-title { font-size: 36px; color: var(--asikzade-dark-text); text-align: center; margin-bottom: 60px; font-weight: 400; letter-spacing: -0.5px; }

        /* === ANA SAYFA TÜM ÜRÜN DETAYLARI LİSTESİ === */
        #homepage-product-details-list.section {
            padding-top: 60px; /* Hero'dan sonraki ilk bölüm için üst boşluk */
            padding-bottom: 0px; /* Sonraki bölümden önceki alt boşluk azaltıldı, arada section title olacak */
        }
        .product-detail-block {
            background-color: transparent; /* Arka planı ana content bg ile aynı yap */
            border-radius: 0; /* Köşeleri kaldır */
            /* box-shadow: none; */ /* Gölgeyi kaldır */
            padding: 0; /* İç padding'i kaldır, section padding'i kullanılacak */
            margin-bottom: 80px; /* Ürün blokları arası boşluk artırıldı */
            overflow: hidden;
        }
        .product-detail-block:last-child { margin-bottom: 0; }
        .product-detail-block-title {
            font-size: clamp(2rem, 5vw, 3.2rem); /* product_detail.php deki gibi */
            font-weight: 700;
            color: var(--asikzade-dark-text);
            text-align: left;
            margin-bottom: 30px;
        }
        .product-detail-block-layout {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr; /* product_detail.php deki gibi */
            gap: 50px;
            align-items: flex-start;
        }
        .product-detail-block-image-wrapper {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .product-detail-block-image-wrapper img.main-product-image {
            width: 100%;
            max-width: 450px; /* product_detail.php deki gibi */
            height: auto;
            display: block;
            object-fit: contain;
            border-radius: 0; /* Köşeleri kaldır */
        }
        .product-detail-block-badge {
            position: absolute;
            bottom: 20px;
            right: 20px;
            width: 80px;
            height: 80px;
            z-index: 5;
        }
        .product-detail-block-badge img { width: 100%; height: 100%; object-fit: contain; }
        .product-detail-block-info-wrapper { padding-top: 0; }
        .product-detail-block-description p {
            font-size: 1rem; /* product_detail.php deki gibi */
            line-height: 1.8;
            color: var(--asikzade-gray);
            margin-bottom: 25px;
        }
        .product-detail-block-price-stock { margin-bottom: 25px; }
        .product-detail-block-price {
            font-size: 1.8rem; /* product_detail.php deki gibi */
            font-weight: 600;
            color: var(--asikzade-dark-text);
            margin-bottom: 5px;
        }
        .add-to-cart-form { }
        .quantity-control-block { display: flex; align-items: center; border: 1px solid #D1D1D1; border-radius: 8px; overflow: hidden; margin-bottom: 20px; max-width: 150px; }
        .quantity-control-block .quantity-btn { background-color: transparent; border: none; color: #585858; font-size: 1.2rem; padding: 12px 18px; cursor: pointer; line-height: 1; }
        .quantity-control-block .quantity-btn.minus { border-right: 1px solid #D1D1D1; }
        .quantity-control-block .quantity-btn.plus { border-left: 1px solid #D1D1D1; }
        .quantity-control-block .quantity-btn:hover { background-color: #f7f7f7; }
        .quantity-control-block .quantity-input { width: 50px; text-align: center; font-size: 1.1rem; font-weight: 500; color: var(--asikzade-dark-text); border: none; padding: 12px 0; -moz-appearance: textfield; }
        .quantity-control-block .quantity-input::-webkit-outer-spin-button, .quantity-control-block .quantity-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .product-detail-block-add-to-cart-btn {
            background-color: var(--asikzade-green);
            color: var(--asikzade-light-text);
            padding: 14px 35px; /* product_detail.php deki gibi */
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
            max-width: 280px; /* product_detail.php deki gibi */
        }
        .product-detail-block-add-to-cart-btn:hover { background-color: var(--asikzade-dark-green); }
        /* === === */

        /* === ÖNE ÇIKAN ÜRÜNLER KART LİSTESİ (FP-GRID) === */
        #asikzade-products.section { /* Bu bölüm artık diğer ürünler listesi olacak */
            padding-top: 60px; /* Üstteki detaylı listeden sonra boşluk */
            padding-left: 0; padding-right: 0; max-width: none;
        }
        #asikzade-products .section-title-wrapper { max-width: 1200px; margin: 0 auto 60px auto; padding: 0 50px; }
        .fp-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0; }
        .fp-card { position: relative; overflow: hidden; display: block; aspect-ratio: 0.85; background-color: var(--asikzade-light-gray); }
        .fp-card img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
        .fp-card:hover img { transform: scale(1.08); }
        .fp-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(to top, rgba(20, 20, 20, 0.85) 0%, rgba(20, 20, 20, 0.5) 50%, rgba(20, 20, 20, 0) 100%); display: flex; flex-direction: column; justify-content: flex-end; align-items: center; padding: 25px 20px; opacity: 0; transition: opacity 0.4s ease-in-out; text-align: center; color: var(--asikzade-light-text); }
        .fp-card:hover .fp-overlay { opacity: 1; }
        .fp-overlay-name { font-size: clamp(1rem, 1.5vw, 1.25rem); font-weight: 600; line-height: 1.3; margin-bottom: 8px; transform: translateY(20px); transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.1s, opacity 0.4s ease 0.1s; opacity: 0; }
        .fp-overlay-buttons { display: flex; flex-direction: column; align-items: center; gap: 8px; }
        .fp-overlay-btn, .fp-add-to-cart-btn { background-color: var(--asikzade-green); color: var(--asikzade-light-text); padding: 10px 22px; border-radius: 30px; font-size: clamp(0.8rem, 1vw, 0.9rem); font-weight: 500; text-decoration: none; border: none; cursor: pointer; display: inline-block; transform: translateY(20px); opacity: 0; white-space: nowrap; }
        .fp-overlay-btn { transition: background-color 0.3s ease, transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.2s, opacity 0.4s ease 0.2s; }
        .fp-add-to-cart-btn { transition: background-color 0.3s ease, transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.25s, opacity 0.4s ease 0.25s; }
        .fp-card:hover .fp-overlay-name,
        .fp-card:hover .fp-overlay-btn,
        .fp-card:hover .fp-add-to-cart-btn { transform: translateY(0); opacity: 1; }
        .fp-overlay-btn:hover, .fp-add-to-cart-btn:hover { background-color: var(--asikzade-dark-green); }
        /* === === */

        .about-section-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 100px; align-items: center; }
        .about-image img { width: 100%; height: 450px; object-fit: cover; display: block; filter: grayscale(10%); border-radius: 8px; }
        .about-text h3 { font-size: 28px; margin-bottom: 30px; font-weight: 500; line-height: 1.4; }
        .about-text p { font-size: 16px; margin-bottom: 25px; color: var(--asikzade-gray); line-height: 1.9; font-weight: 300; }

        .benefits-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 60px; }
        .benefit-icon { width: 48px; height: 48px; border: 1.5px solid var(--asikzade-green); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 0 25px 0; }
        .benefit-icon svg { width: 22px; height: 22px; fill: var(--asikzade-green); }
        .benefit-item h4 { font-size: 20px; margin-bottom: 15px; font-weight: 500; }
        .benefit-item p { font-size: 15px; color: var(--asikzade-gray); line-height: 1.8; font-weight: 300; }

        #asikzade-contact { background-color: var(--asikzade-contact-bg); padding: 100px 0; color: var(--asikzade-dark-text); }
        .contact-container { max-width: 900px; margin: 0 auto; padding: 0 30px; }
        .contact-title { font-size: clamp(2.5rem, 8vw, 5rem); font-weight: 800; text-align: center; margin-bottom: 60px; color: var(--asikzade-dark-text); line-height: 1; letter-spacing: -1px; }
        .contact-layout { display: grid; grid-template-columns: 1fr; gap: 40px 60px; align-items: flex-start; }
        .contact-brand-aside { font-size: clamp(1.2rem, 2.5vw, 1.8rem); font-weight: 600; color: var(--asikzade-dark-text); opacity: 0.8; }
        .contact-form { display: flex; flex-direction: column; gap: 20px; }
        .contact-form input[type="text"], .contact-form input[type="email"], .contact-form input[type="tel"], .contact-form textarea { width: 100%; padding: 18px 25px; border: none; background-color: var(--asikzade-contact-input-bg); border-radius: 30px; font-size: 1rem; color: var(--asikzade-dark-text); font-family: inherit; box-shadow: none; }
        .contact-form input::placeholder, .contact-form textarea::placeholder { color: #888; opacity: 0.7; }
        .contact-form textarea { min-height: 150px; resize: vertical; }
        .contact-form button { background-color: var(--asikzade-green); color: var(--asikzade-light-text); padding: 18px 30px; border: none; border-radius: 30px; font-size: 1.05rem; font-weight: 500; cursor: pointer; transition: background-color 0.3s ease, transform 0.3s ease; align-self: flex-start; }
        .contact-form button:hover { background-color: var(--asikzade-dark-green); transform: translateY(-2px); }

        .insta-promo-section { position: relative; background-color: var(--asikzade-promo-bg); padding: clamp(60px, 10vw, 120px) 20px; overflow: hidden; min-height: 60vh; display: flex; align-items: center; justify-content: center; }
        .insta-promo-content { position: relative; z-index: 10; text-align: center; max-width: 700px; padding: 20px; }
        .insta-promo-handle { font-size: clamp(0.8rem, 1.5vw, 1rem); font-weight: 500; color: var(--asikzade-dark-text); margin-bottom: 10px; letter-spacing: 0.5px; }
        .insta-promo-title { font-size: clamp(1.8rem, 5vw, 3.2rem); font-weight: 700; color: var(--asikzade-dark-text); line-height: 1.2; text-transform: uppercase; margin-bottom: 30px; }
        .insta-promo-button { display: inline-flex; align-items: center; gap: 10px; background-color: var(--asikzade-green); color: var(--asikzade-light-text); padding: 12px 28px; border-radius: 50px; text-decoration: none; font-size: clamp(0.9rem, 2vw, 1.1rem); font-weight: 500; transition: background-color 0.3s ease, transform 0.3s ease; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .insta-promo-button:hover { background-color: var(--asikzade-dark-green); transform: translateY(-2px); }
        .insta-promo-button svg { width: clamp(18px, 3vw, 22px); height: clamp(18px, 3vw, 22px); stroke: var(--asikzade-light-text); }
        .promo-image { position: absolute; width: clamp(100px, 16vw, 200px); height: auto; aspect-ratio: 3/4; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); z-index: 5; object-fit: cover; }
        .promo-image img { width: 100%; height: 100%; object-fit: cover; border-radius: 12px; display: block; }
        .promo-image-1 { top: clamp(10px, 3%, 50px); left: clamp(10px, 4%, 60px); transform: rotate(-12deg); } .promo-image-2 { top: clamp(15px, 4%, 60px); right: clamp(10px, 3%, 50px); transform: rotate(10deg); }
        .promo-image-3 { bottom: clamp(10px, 3%, 50px); left: clamp(15px, 5%, 70px); transform: rotate(8deg); } .promo-image-4 { bottom: clamp(15px, 4%, 60px); right: clamp(15px, 5%, 70px); transform: rotate(-15deg); }

        .footer { background-color: var(--asikzade-content-bg); padding: 60px 0 30px; position: relative; z-index: 20; color: var(--asikzade-dark-text); border-top: none; }
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

        @keyframes morphBlob { 0%, 100% { transform: translate(0, 0) scale(1); } 50% { transform: translate(30px, -30px) scale(1.1); } }
        .product-transition-out { animation: fadeOut 0.3s ease forwards; } .product-transition-in { animation: fadeIn 0.3s ease forwards; }
        @keyframes fadeOut { to { opacity: 0; } } @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .product-name-bg-transition-out { opacity: 0; } .product-name-bg-transition-in { opacity: 1; }

        /* RESPONSIVE STYLES */
        @media (max-width: 1024px) {
            .header { padding: 20px 30px; } .header.scrolled { padding: 12px 30px; } .logo-container img { height: 54px; } .header.scrolled .logo-container img { height: 44px; } .logo-text { font-size: 24px; } .header.scrolled .logo-text { font-size: 20px; }
            .main-nav { gap: 20px; } .nav-page-links { gap: 10px; } .nav-page-link-button { padding: 9px 18px; font-size: 13px; }
            .nav-user-icon, .nav-cart-icon { width: 38px; height: 38px; } .header.scrolled .nav-user-icon, .header.scrolled .nav-cart-icon { width: 34px; height: 34px; }
            .section { padding: 80px 0; padding-left: 30px; padding-right: 30px; }
            .about-section-layout { gap: 50px; } .asikzade-content-wrapper { padding-top: 50px; }
            .dual-image-container { flex-direction: column; gap: 0; } .full-screen-image-section { width: 100%; height: 50vh; }
            .promo-image { width: clamp(90px, 14vw, 160px); } .promo-image-1 { left: clamp(10px, 2%, 40px); top: clamp(10px, 2%, 30px); } .promo-image-2 { right: clamp(10px, 1.5%, 35px); top: clamp(15px, 3%, 45px); } .promo-image-3 { left: clamp(10px, 3%, 45px); bottom: clamp(10px, 2%, 30px); } .promo-image-4 { right: clamp(10px, 2.5%, 40px); bottom: clamp(15px, 3%, 45px); }
            .contact-layout { grid-template-columns: 200px 1fr; align-items: center; } .contact-brand-aside { text-align: right; padding-right: 30px; }
            /* Homepage product details list tablet */
            .product-detail-block-layout { gap: 30px; grid-template-columns: 0.7fr 1.3fr; }
             /* FP Grid Tablet */
            #asikzade-products.section { padding-left: 30px; padding-right: 30px; max-width: 1200px; margin-left: auto; margin-right: auto; }
            #asikzade-products .section-title-wrapper { padding-left: 0; padding-right: 0; max-width: none; }
            .fp-grid { grid-template-columns: repeat(2, 1fr); gap: 25px; } .fp-card { aspect-ratio: 1; border-radius: 8px; }
        }
        @media (max-width: 992px) {
            .user-actions-group { gap: 20px; } .benefits-grid { grid-template-columns: 1fr; gap: 50px; }
            .nav-page-link-button { padding: 8px 15px; font-size: 12px; } .nav-page-links { gap: 8px; } .main-nav { gap: 15px; }
            /* Homepage product details list smaller tablet */
            .product-detail-block-layout { grid-template-columns: 1fr; text-align: center; }
            .product-detail-block-image-wrapper { margin: 0 auto 20px auto; }
            .add-to-cart-form { align-items: center; }
            .quantity-control-block { margin-left: auto; margin-right: auto; }
            .product-detail-block-title { text-align: center;}
        }
        @media (max-width: 768px) {
            .header { padding: 20px 20px; } .header.scrolled { padding: 12px 20px; } .logo-container img { height: 48px; } .header.scrolled .logo-container img { height: 40px; } .logo-text { font-size: 22px; } .header.scrolled .logo-text { font-size: 18px; }
            .main-nav { gap: 10px; } .nav-page-links { gap: 6px; } .nav-page-link-button { padding: 7px 12px; font-size: 11px; }
            .nav-user-icon, .nav-cart-icon { width: 36px; height: 36px; } .header.scrolled .nav-user-icon, .header.scrolled .nav-cart-icon { width: 32px; height: 32px; }
            .section-title { font-size: 28px; margin-bottom: 40px; }
            .about-section-layout { grid-template-columns: 1fr; gap: 50px; } .about-image img { height: 300px; } .wave-transition { height: 80px; bottom: -30px; }
            .benefits-grid { grid-template-columns: 1fr; } .asikzade-content-wrapper { padding-top: 40px; } .full-screen-image-section { height: 40vh; }
            .section { padding: 80px 0; padding-left: 20px; padding-right: 20px; }
            .insta-promo-section { padding-top: clamp(40px, 8vw, 80px); padding-bottom: clamp(40px, 8vw, 80px); } .promo-image { width: clamp(70px, 20vw, 110px); } .promo-image-1 { top: clamp(10px, 3%, 25px); left: clamp(10px, 1.5%, 20px); transform: rotate(-10deg); } .promo-image-2 { top: clamp(15px, 4%, 35px); right: clamp(10px, 1.5%, 20px); transform: rotate(8deg); } .promo-image-3 { display: none;  } .promo-image-4 { bottom: clamp(15px, 4%, 35px); right: clamp(10px, 1.5%, 20px); transform: rotate(-12deg); } .insta-promo-title { font-size: clamp(1.5rem, 6vw, 2.5rem); }
            #asikzade-contact { padding: 60px 0; } .contact-title { font-size: clamp(2rem, 10vw, 3.5rem); margin-bottom: 40px; } .contact-layout { grid-template-columns: 1fr; gap: 20px; } .contact-brand-aside { text-align: center; padding-right: 0; font-size: clamp(1rem, 2vw, 1.5rem); } .contact-form input[type="text"], .contact-form input[type="email"], .contact-form input[type="tel"], .contact-form textarea { padding: 16px 20px; font-size: 0.95rem; } .contact-form button { padding: 16px 25px; font-size: 1rem; align-self: center;}
            .footer-content { padding: 0 20px; } .footer-bottom { flex-direction: column; gap: 15px; text-align: center; padding-top: 20px; } .footer-links ul { justify-content: center; flex-wrap: wrap; gap: 10px 20px; } .copyright { text-align: center; } .footer-social-row { margin-bottom: 30px; } .social-icons a { width: 44px; height: 44px; } .social-icons svg { width: 20px; height: 20px; } .footer { padding: 40px 0 20px; }
            /* Homepage product details list mobile */
            .product-detail-block { padding: 20px; margin-bottom: 40px; }
            .product-detail-block-title { font-size: clamp(1.8rem, 6vw, 2.5rem); /* product_detail.php deki mobil başlık gibi */ text-align: center; margin-bottom: 20px; }
            /* FP Grid Mobile */
            #asikzade-products.section { padding-left: 20px; padding-right: 20px; }
            #asikzade-products .section-title-wrapper { padding-left: 0; padding-right: 0; }
            .fp-grid { grid-template-columns: 1fr; gap: 20px; } .fp-card { aspect-ratio: 4/3; border-radius: 8px; }
            .fp-overlay-name { font-size: 1.3rem; } .fp-overlay-btn, .fp-add-to-cart-btn { font-size: 0.95rem; padding: 12px 24px; }
        }
        @media (max-width: 480px) {
            .header { padding: 15px 15px; } .logo-container img { height: 42px; } .header.scrolled .logo-container img { height: 36px; } .logo-text { font-size: 20px; } .header.scrolled .logo-text { font-size: 17px; } .logo-container { gap: 8px; }
            .main-nav { gap: 8px; } .nav-page-links { gap: 5px; } .nav-page-link-button { padding: 6px 8px; font-size: 10px; } .user-actions-group { gap: 8px; }
            .nav-user-icon, .nav-cart-icon { width: 34px; height: 34px; } .header.scrolled .nav-user-icon, .header.scrolled .nav-cart-icon { width: 30px; height: 30px; }
            .arrow-mawa { width: 35px; height: 35px; } .arrow-mawa.left { left: 10px; } .arrow-mawa.right { right: 10px; } .section-title { font-size: 24px; } .wave-transition { height: 60px; bottom: -20px; } .asikzade-content-wrapper { padding-top: 30px; }
            .section { padding: 60px 0; padding-left: 15px; padding-right: 15px; }
            .promo-image { width: clamp(60px, 22vw, 90px); } .promo-image-1 { top: clamp(5px, 1.5%, 15px); left: clamp(5px, 1%, 10px); transform: rotate(-8deg); } .promo-image-2 { display: none;  } .promo-image-3 { display: none; } .promo-image-4 { top: clamp(5px, 1.5%, 15px); right: clamp(5px, 1%, 10px); bottom: auto; transform: rotate(8deg); } .insta-promo-content { padding-top: clamp(70px, 18vw, 100px); }
            .contact-container { padding: 0 20px; } .contact-title { font-size: clamp(1.8rem, 12vw, 2.8rem); }
        }
        @media (max-width: 380px) { /* .nav-page-links { display: none; } */ }
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
            <div class="nav-page-links">
                <a href="#asikzade-about" class="nav-page-link-button">HAKKIMIZDA</a>
                <a href="#asikzade-benefits" class="nav-page-link-button">FAYDALARINI İNCELE</a>
                <a href="#asikzade-products" class="nav-page-link-button">ÜRÜNLERİ KEŞFET</a> <!-- Bu link hala kart listesine gidebilir -->
            </div>
            <div class="user-actions-group">
                <a href="login.php" class="nav-user-icon" aria-label="Kullanıcı Girişi"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></a>
                <a href="sepet.php" class="nav-cart-icon" aria-label="Sepetim"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    <?php if ($cart_item_count > 0): ?><span class="cart-badge"><?php echo $cart_item_count; ?></span><?php endif; ?>
                </a>
            </div>
        </nav>
    </header>

    <section class="hero-product-section">
        <div class="arrow-mawa left" onclick="previousMawaProduct()"></div>
        <div class="product-showcase-mawa">
            <div class="product-name-background-mawa" id="productNameBackgroundMawa"></div>
            <div class="product-image-container-mawa" id="productContainerMawa"><img src="" alt="Aşıkzade Ürünü" class="product-image-mawa" id="productImageMawa"></div>
            <div class="product-info-mawa" id="productNameMawa">Ürün Adı</div>
        </div>
        <div class="arrow-mawa right" onclick="nextMawaProduct()"></div>
        <div class="bg-element-mawa blob-mawa b1"></div><div class="bg-element-mawa blob-mawa b2"></div>
        <div class="wave-transition"><svg viewBox="0 0 1440 120" preserveAspectRatio="none" id="waveToContent"><path d="M0,40 C480,120 960,0 1440,80 L1440,120 L0,120 Z" fill="#fef6e6"></path></svg></div>
    </section>

    <div style="width:100vw; height:0; margin:0; padding:0; overflow:hidden; visibility:hidden; position:absolute; pointer-events:none;"></div>

    <main class="asikzade-content-wrapper">
                <section class="section" id="asikzade-about">
            <h2 class="section-title">Hakkımızda</h2>
            <div class="about-section-layout">
                <div class="about-image"><img src="https://i.imgur.com/e7I7JoY.jpeg" alt="Aşıkzade Üretim Alanı"></div>
                <div class="about-text">
                    <p>2007’den beri Malatya’nın bereketli topraklarında organik tarım yapan Aşıkzade Natural, Şiraz üzümü ve geleneksel meyveleri doğal yöntemlerle işleyerek sağlığı sofralarınıza getiriyor. Doğaya saygılı, katkısız ve yerli üretimle gelenekten geleceğe bir yolculuk sunuyor.</p>
                    <h3>Kuruluş Hikayemiz</h3>
                    <p>Aşıkzade Natural, 2007 yılında Malatya'nın Battalgazi ilçesinde, nesilden nesile aktarılan bağcılık geleneğini yaşatmak amacıyla kuruldu. Toprağa duyulan saygı ve doğal üretime olan inançla yola çıkan markamız, ilk olarak Şiraz ve Arapgir Köhnüsü üzüm çeşitleriyle organik bağcılığa adım attı. Aile mirası bu topraklarda başlayan yolculuğumuz, kısa sürede doğal ürünlere duyulan güvenle büyüyerek bugünkü halini aldı.</p>
                </div>
            </div>
        </section>
        <section class="section" id="asikzade-about-cont">
            <div class="about-section-layout">
                <div class="about-text">
                    <h3>Aile Çiftliği Ruhu</h3>
                    <p>Aşıkzade Natural, bir aile markasıdır. Üretim sürecimizin her aşamasında aile bireylerimiz aktif rol alır. Bu sayede hem kalite kontrolünü kendi içimizde yapar, hem de üretime sevgi ve sadakat katmış oluruz. Toprağı tanıyan, mevsimi bilen ve her ürünün doğasında ne olduğunu anlayan ellerden çıkan ürünlerimiz, sofranıza bu özveriyle ulaşır.</p>
                    <h3>Organik Tarım Politikamız</h3>
                    <p>Bizim için organik olmak sadece bir etiket değil, bir yaşam biçimidir. Ürünlerimizi doğal dengeyi bozmadan, toprağın canlılığını koruyarak ve biyolojik çeşitliliği gözeterek üretiriz. Yerel tohumlara sahip çıkar, sürdürülebilir tarımı esas alırız. Her adımımız kayıtlı, şeffaf ve denetlenebilir şekilde ilerler.</p>
                </div>
                <div class="about-image"><img src="https://i.imgur.com/Ysk6QsD.png" alt="Aşıkzade Üretim Alanı"></div>
            </div>
        </section>
        <div class="dual-image-container">
            <section class="full-screen-image-section"><img src="https://i.imgur.com/mhaEN1W.jpeg" alt="Doğal Ürünler Tanıtım 1"></section>
            <section class="full-screen-image-section"><img src="https://i.imgur.com/WbqIJfj.jpeg" alt="Aşıkzade Üretim Süreci Tanıtım 2"></section>
        </div>

        <section class="section" id="homepage-product-details-list">
            <div id="asikzade-benefits"> </div>
            <!-- Bu bölümün genel bir başlığı olmayacak, her ürün kendi başlığına sahip olacak -->
            <?php
            if (isset($products) && is_array($products) && !empty($products)):
                foreach ($products as $product_id => $product_data):
                    if (empty($product_data['name']) || empty($product_data['image']) || !isset($product_data['price']) || empty($product_data['description'])) {
                        continue;
                    }
            ?>
                <div class="product-detail-block" id="product-detail-<?php echo $product_id; ?>"> <!-- ID güncellendi -->
                    <h3 class="product-detail-block-title"><?php echo htmlspecialchars($product_data['name']); ?></h3>
                    <div class="product-detail-block-layout">
                        <div class="product-detail-block-image-wrapper">
                            <img src="<?php echo htmlspecialchars($product_data['image']); ?>" alt="<?php echo htmlspecialchars($product_data['name']); ?>" class="main-product-image">
                            <?php if (!empty($product_data['badge_image'])): ?>
                            <div class="product-detail-block-badge">
                                <img src="https://i.imgur.com/RChLL2F.png" alt="Ürün Rozeti" style="width:110px; height:110px; max-width:none; max-height:none;">
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-detail-block-info-wrapper">
                            <div class="product-detail-block-description">
                                <p><?php echo nl2br(htmlspecialchars($product_data['description'])); ?></p>
                            </div>
                            <div class="product-detail-block-price-stock">
                                <p class="product-detail-block-price"><?php echo number_format($product_data['price'], 2); ?> TL</p>
                            </div>
                            <form action="cart_action.php" method="post" class="add-to-cart-form">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_data['id']); ?>">
                                <div class="quantity-control-block">
                                    <button type="button" class="quantity-btn minus" aria-label="Azalt">-</button>
                                    <input type="number" name="quantity" value="1" min="1" class="quantity-input" aria-label="Miktar">
                                    <button type="button" class="quantity-btn plus" aria-label="Artır">+</button>
                                </div>
                                <button type="submit" class="product-detail-block-add-to-cart-btn">SEPETE EKLE</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php
                endforeach;
            else:
                 echo "<p style='text-align:center;'>Ürün bulunamadı.</p>";
            endif;
            ?>
        </section>
    <section class="insta-promo-section">
        <div class="promo-image promo-image-1"><img src="https://i.imgur.com/fIyzlOi.png" alt="Aşıkzade Doğal Ürün 1"></div>
        <div class="promo-image promo-image-2"><img src="https://i.imgur.com/KY4PF0E.png" alt="Aşıkzade Yaşam Tarzı 1"></div>
        <div class="promo-image promo-image-3"><img src="https://i.imgur.com/rnpICDG.png" alt="Aşıkzade Doğal Ürün 2"></div>
        <div class="promo-image promo-image-4"><img src="https://i.imgur.com/Mufz5KT.png" alt="Aşıkzade Yaşam Tarzı 2"></div>
        <div class="insta-promo-content"><h2 class="insta-promo-title">SOFRANA İLHAM, <br>AKIŞINA RENK KAT!</h2></div>
    </section>
        <!-- Önceki 4'lü Grid Ürün Listesi (fp-grid) olduğu gibi kalacak -->
        <section class="section" id="asikzade-products">
            <div class="section-title-wrapper">
                <h2 class="section-title">Ürünlerimiz</h2> <!-- Başlık isteğe bağlı olarak değiştirilebilir -->
            </div>
            <div class="fp-grid">
                <?php
                if (isset($products) && is_array($products) && !empty($products)):
                    foreach ($products as $product_id => $product_data):
                        if (empty($product_data['image'])) {
                            continue;
                        }
                ?>
                    <div class="fp-card">
                        <img src="<?php echo htmlspecialchars($product_data['image']); ?>" alt="<?php echo htmlspecialchars($product_data['name']); ?>">
                        <div class="fp-overlay">
                            <h3 class="fp-overlay-name"><?php echo htmlspecialchars($product_data['name']); ?></h3>
                            <div class="fp-overlay-buttons">
                                <form action="cart_action.php" method="post" style="margin:0; padding:0; display:inline;">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    
                                </form>
                            </div>
                        </div>
                    </div>
                <?php
                    endforeach;
                endif;
                ?>
            </div>
        </section>




        <section class="section" >
            <h2 class="section-title">Neden Aşıkzade?</h2>
            <div class="benefits-grid">
                <div class="benefit-item"><div class="benefit-icon"><svg viewBox="0 0 24 24"><path d="M12 2L3 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-9-5z"></path></svg></div><h4>%100 Organik</h4><p>Tüm ürünlerimiz sertifikalı organik tarım yöntemleriyle yetiştirilir.</p></div>
                <div class="benefit-item"><div class="benefit-icon"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"></path></svg></div><h4>Katkısız & Doğal</h4><p>Koruyucu, renklendirici, yapay aroma veya tatlandırıcı içermez.</p></div>
                <div class="benefit-item"><div class="benefit-icon"><svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg></div><h4>Geleneksel Yöntemler</h4><p>Yöresel ve geleneksel üretim teknikleriyle eşsiz lezzetler sunarız.</p></div>
            </div>
        </section>
    </main>

    <section id="asikzade-contact">
        <div class="contact-container">
            <h2 class="contact-title">İLETİŞİM</h2>
            <div class="contact-layout">
                <form action="#" method="POST" class="contact-form">
                    <input type="text" name="name" placeholder="İsim Soyisim" required><input type="tel" name="phone" placeholder="Telefon"><input type="email" name="email" placeholder="E-mail Adresiniz" required><textarea name="message" placeholder="Sorularınız / Mesajınız" required></textarea><button type="submit">Gönder</button>
                </form>
            </div>
        </div>
        <div class="wave-transition" style="bottom: -1px; top:auto; transform: scaleY(-1);"><svg viewBox="0 0 1440 120" preserveAspectRatio="none" id="waveToInstaPromo"><path d="M0,40 C480,120 960,0 1440,80 L1440,120 L0,120 Z" fill="var(--asikzade-promo-bg)"></path></svg></div>
    </section>
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-social-row">
                <div class="social-icons">
                    <a href="https://facebook.com/asikzadenatural" target="_blank" aria-label="Facebook"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 2.039c-5.514 0-9.961 4.448-9.961 9.961s4.447 9.961 9.961 9.961c5.515 0 9.961-4.448 9.961-9.961s-4.446-9.961-9.961-9.961zm3.621 9.561h-2.2v7.3h-3.22v-7.3h-1.56v-2.68h1.56v-1.93c0-1.301.63-3.35 3.35-3.35h2.37v2.67h-1.45c-.47 0-.72.24-.72.72v1.31h2.24l-.24 2.68z"/></svg></a>
                    <a href="https://linkedin.com/company/asikzadenatural" target="_blank" aria-label="LinkedIn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14zm-11.383 7.125H5.121v6.75h2.496v-6.75zm-1.248-2.302a1.49 1.49 0 1 0 0-2.979 1.49 1.49 0 0 0 0 2.979zm9.016 2.302c-2.016 0-2.848 1.081-3.312 2.04h-.048v-1.788H9.573v6.75h2.496v-3.375c0-.891.171-1.755 1.26-1.755.972 0 1.088.687 1.088 1.809v3.321h2.496v-3.828c0-2.203-1.088-3.852-3.288-3.852z"/></svg></a>
                    <a href="https://instagram.com/asikzadenatural" target="_blank" aria-label="Instagram"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 2c2.717 0 3.056.01 4.122.06 1.065.05 1.79.217 2.428.465.66.254 1.217.598 1.77.96.582.386.96.826 1.344 1.344.385.517.778 1.074 1.032 1.734.272.712.436 1.436.488 2.498.052 1.066.063 1.405.063 4.122s-.01 3.056-.061 4.122c-.053 1.065-.218 1.79-.487 2.428-.254.66-.598 1.217-.96 1.77-.386.582-.826.96-1.344 1.344-.517.385-1.074.778-1.734 1.032-.712.272-1.436.436-2.498.488-1.066.052-1.405.063-4.122.063s-3.056-.01-4.122-.061c-1.065-.053-1.79-.218-2.428-.487-.66-.254-1.217-.598-1.77-.96-.582-.386-.96-.826-1.344-1.344-.385-.517-.778-1.074-1.032-1.734-.272-.712-.436-1.436-.488-2.498C2.012 15.056 2 14.717 2 12s.01-3.056.061-4.122c.053-1.065.218-1.79.487-2.428.254.66.598-1.217.96-1.77.386-.582.826.96 1.344-1.344.517-.385 1.074-.778 1.734-1.032.712-.272 1.436.436 2.498-.488C8.944 2.01 9.283 2 12 2zm0 1.802c-2.67 0-2.987.01-4.042.058-.975.045-1.505.207-1.857.344-.466.182-.795.396-1.15.748-.354.354-.566.684-.748 1.15-.137.352-.3.882-.344 1.857-.048 1.054-.058 1.373-.058 4.042s.01 2.987.058 4.042c.045.975.207 1.505.344 1.857.182.466.396.795.748 1.15.354.354.684.566 1.15.748.352.137.882.3 1.857.344 1.054.048 1.373.058 4.042.058s2.987-.01 4.042-.058c.975-.045 1.505-.207 1.857-.344.466-.182-.795.396 1.15-.748.354-.354-.566-.684.748-1.15.137-.352-.3-.882-.344-1.857.048-1.054.058-1.373.058-4.042s-.01-2.987-.058-4.042c-.045-.975-.207-1.505-.344-1.857-.182-.466-.396-.795-.748-1.15-.354-.354-.684-.566-1.15-.748-.352-.137-.882-.3-1.857-.344C14.987 3.812 14.67 3.802 12 3.802zm0 2.903c-2.836 0-5.135 2.299-5.135 5.135s2.299 5.135 5.135 5.135 5.135-2.299 5.135-5.135-2.299-5.135-5.135-5.135zm0 8.468c-1.837 0-3.333-1.496-3.333-3.333s1.496-3.333 3.333-3.333 3.333 1.496 3.333 3.333-1.496 3.333-3.333 3.333zm4.333-8.572a1.2 1.2 0 1 0 0-2.4 1.2 1.2 0 0 0 0 2.4z"/></svg></a>
                </div>
            </div>
            <div class="footer-bottom">
                <p class="copyright">© <?php echo date("Y"); ?> Aşıkzade. Tüm hakları saklıdır.</p>
                <div class="footer-links"><ul><li><a href="#!">İade Politikası</a></li><li><a href="#!">Ödemeler</a></li></ul></div>
            </div>
        </div>
    </footer>

    <script>
        // Miktar artırma/azaltma
        document.querySelectorAll('.quantity-btn.minus').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('.quantity-input');
                let currentValue = parseInt(input.value);
                if (currentValue > 1) input.value = currentValue - 1;
            });
        });
        document.querySelectorAll('.quantity-btn.plus').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('.quantity-input');
                let currentValue = parseInt(input.value);
                input.value = currentValue + 1;
            });
        });

        // Diğer JavaScript kodları (Header, Hero Slider, Scroll, Animations vb.)
        const mainHeader = document.getElementById('mainHeader');
        const headerLogoImage = document.getElementById('headerLogoImage');
        const siteLogoTextMawa = document.getElementById('siteLogoTextMawa');
        const navUserIcon = document.querySelector('.nav-user-icon');
        const navCartIcon = document.querySelector('.nav-cart-icon');
        const productNameBackgroundMawa = document.getElementById('productNameBackgroundMawa');
        const waveToContentPath = document.getElementById('waveToContent')?.querySelector('path');
        const waveToInstaPromoPath = document.getElementById('waveToInstaPromo')?.querySelector('path');

        function setMawaTextColors(textType) {
            if (!headerLogoImage || !siteLogoTextMawa || !navUserIcon || !navCartIcon) return;
            const isDarkTextThemeForHero = textType === "dark";
            if (productNameBackgroundMawa) {
                productNameBackgroundMawa.style.color = isDarkTextThemeForHero ? getComputedStyle(document.documentElement).getPropertyValue('--product-bg-text-dark').trim() : getComputedStyle(document.documentElement).getPropertyValue('--product-bg-text-light').trim();
            }
            if (isDarkTextThemeForHero) {
                headerLogoImage.classList.remove('logo-inverted');
                siteLogoTextMawa.classList.add('dark-theme-text');
                navUserIcon.classList.add('dark-theme-text');
                navCartIcon.classList.add('dark-theme-text');
            } else {
                headerLogoImage.classList.add('logo-inverted');
                siteLogoTextMawa.classList.remove('dark-theme-text');
                navUserIcon.classList.remove('dark-theme-text');
                navCartIcon.classList.remove('dark-theme-text');
            }
        }

        function updateHeaderStyles() {
            const heroSection = document.querySelector('.hero-product-section');
            const contactSection = document.getElementById('asikzade-contact');
            const mainContentWrapper = document.querySelector('.asikzade-content-wrapper');
            const scrollY = window.scrollY;
            const headerHeight = mainHeader.offsetHeight;

            if (scrollY > 50) mainHeader.classList.add('scrolled');
            else mainHeader.classList.remove('scrolled');

            let isOverHero = false;
            if (heroSection) isOverHero = heroSection.getBoundingClientRect().bottom > headerHeight;

            let currentHeroBgType = 'light';
            if (typeof currentMawaProductIndex !== 'undefined' && mawaProducts.length > 0 && mawaProducts[currentMawaProductIndex]) {
                currentHeroBgType = mawaProducts[currentMawaProductIndex].productNameBgTextType;
            }

            if (isOverHero) {
                mainHeader.style.background = 'transparent';
                mainHeader.classList.remove('content-bg-active', 'contact-bg-active');
                setMawaTextColors(currentHeroBgType);
            } else {
                mainHeader.style.background = '';
                if (headerLogoImage) headerLogoImage.classList.remove('logo-inverted');
                if (siteLogoTextMawa) siteLogoTextMawa.classList.remove('dark-theme-text');
                if (navUserIcon) navUserIcon.classList.remove('dark-theme-text');
                if (navCartIcon) navCartIcon.classList.remove('dark-theme-text');

                let isOverContactBg = false;
                if (contactSection) isOverContactBg = contactSection.getBoundingClientRect().top <= headerHeight && contactSection.getBoundingClientRect().bottom > headerHeight;
                
                let isOverContentBg = false;
                if (mainContentWrapper && !isOverContactBg) {
                    const contentWrapperRect = mainContentWrapper.getBoundingClientRect();
                    const heroBottom = heroSection ? heroSection.getBoundingClientRect().bottom : 0;
                     isOverContentBg = contentWrapperRect.top <= headerHeight && scrollY > (heroBottom - headerHeight);
                }

                if (isOverContactBg) {
                    mainHeader.classList.add('contact-bg-active');
                    mainHeader.classList.remove('content-bg-active');
                } else if (isOverContentBg) {
                    mainHeader.classList.add('content-bg-active');
                    mainHeader.classList.remove('contact-bg-active');
                } else {
                    mainHeader.classList.remove('content-bg-active', 'contact-bg-active');
                }
                 if (mainHeader.classList.contains('scrolled')) {
                    if (siteLogoTextMawa) siteLogoTextMawa.style.color = 'var(--asikzade-dark-text)';
                    if (navUserIcon) { navUserIcon.style.borderColor = 'var(--asikzade-dark-text)'; navUserIcon.style.color = 'var(--asikzade-dark-text)'; }
                    if (navCartIcon) { navCartIcon.style.borderColor = 'var(--asikzade-dark-text)'; navCartIcon.style.color = 'var(--asikzade-dark-text)'; }
                }
            }
        }
        window.addEventListener('scroll', updateHeaderStyles);

        document.querySelectorAll('.nav-page-link-button, .footer-links a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    let headerOffset = mainHeader.offsetHeight;
                     if (window.scrollY < 50 && !mainHeader.classList.contains('scrolled') && targetId !== 'hero-product-section') {
                        headerOffset = mainHeader.classList.contains('scrolled') ? mainHeader.offsetHeight : 70;
                    }
                    const elementPosition = targetElement.getBoundingClientRect().top + window.pageYOffset;
                    const offsetPosition = elementPosition - headerOffset - 20;
                    window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
                }
            });
        });

        const mawaProducts = <?php echo json_encode(array_values($mawaProductsJs)); ?>;
        let currentMawaProductIndex = 0;
        let isMawaAnimating = false;
        const bodyForMawaBg = document.body;
        const productImageMawa = document.getElementById('productImageMawa');
        const productNameMawa = document.getElementById('productNameMawa');
        const productContainerMawa = document.getElementById('productContainerMawa');
        let mawaMouseX = 0, mawaMouseY = 0;
        let mawaCurrentX = 0, mawaCurrentY = 0;
        const mawaFollowSpeed = 0.07;

        function animateMawaProductImage() {
            if (!productImageMawa) return;
            const distX = mawaMouseX - mawaCurrentX; const distY = mawaMouseY - mawaCurrentY;
            mawaCurrentX += distX * mawaFollowSpeed; mawaCurrentY += distY * mawaFollowSpeed;
            const rotateX = mawaCurrentY / 8; const rotateY = -mawaCurrentX / 8;
            const translateZ = Math.min(60, (Math.abs(mawaCurrentX) + Math.abs(mawaCurrentY)) / 3);
            const scale = 1 + (Math.abs(mawaCurrentX) + Math.abs(mawaCurrentY)) / 1000;
            productImageMawa.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(${translateZ}px) scale(${Math.min(scale, 1.12)})`;
            requestAnimationFrame(animateMawaProductImage);
        }
        if (productContainerMawa) {
            animateMawaProductImage();
            productContainerMawa.addEventListener('mousemove', (e) => { const rect = productContainerMawa.getBoundingClientRect(); mawaMouseX = (e.clientX - rect.left - rect.width / 2) / 4; mawaMouseY = (e.clientY - rect.top - rect.height / 2) / 4; });
            productContainerMawa.addEventListener('mouseleave', () => { mawaMouseX = 0; mawaMouseY = 0; });
        }

        function changeBodyMawaBackground(bgClass) {
            <?php $bgClassesToRemove = []; foreach ($mawaProductsJs as $prod) if (!empty($prod['dynamicBgClass'])) $bgClassesToRemove[] = $prod['dynamicBgClass']; echo "const mawaBgClasses = " . json_encode(array_unique($bgClassesToRemove)) . ";\n"; ?>
            mawaBgClasses.forEach(cls => bodyForMawaBg.classList.remove(cls));
            if (bgClass) bodyForMawaBg.classList.add(bgClass);
        }

        function showMawaProduct(index) {
            if (isMawaAnimating || !mawaProducts.length || !productImageMawa || index < 0 || index >= mawaProducts.length) return;
            isMawaAnimating = true;
            productImageMawa.classList.add('product-transition-out');
            if(productNameMawa) { productNameMawa.style.opacity = '0'; productNameMawa.style.transform = 'translateY(20px)'; }
            if(productNameBackgroundMawa) { productNameBackgroundMawa.classList.remove('product-name-bg-transition-in'); productNameBackgroundMawa.classList.add('product-name-bg-transition-out'); }
            setTimeout(() => {
                currentMawaProductIndex = index; const currentProductData = mawaProducts[index];
                changeBodyMawaBackground(currentProductData.dynamicBgClass); updateHeaderStyles();
                productImageMawa.src = currentProductData.image; productImageMawa.alt = `Aşıkzade ${currentProductData.name}`;
                if(productNameMawa) productNameMawa.textContent = currentProductData.name;
                if(productNameBackgroundMawa) { productNameBackgroundMawa.textContent = currentProductData.name; productNameBackgroundMawa.style.color = currentProductData.productNameBgTextType === "dark" ? getComputedStyle(document.documentElement).getPropertyValue('--product-bg-text-dark').trim() : getComputedStyle(document.documentElement).getPropertyValue('--product-bg-text-light').trim(); }
                productImageMawa.classList.remove('product-transition-out'); productImageMawa.classList.add('product-transition-in');
                setTimeout(() => { if(productNameMawa) { productNameMawa.style.opacity = '1'; productNameMawa.style.transform = 'translateY(0)'; } if(productNameBackgroundMawa) { productNameBackgroundMawa.classList.remove('product-name-bg-transition-out'); productNameBackgroundMawa.classList.add('product-name-bg-transition-in'); } }, 150);
                setTimeout(() => { productImageMawa.classList.remove('product-transition-in'); isMawaAnimating = false; }, 300);
            }, 300);
        }
        function nextMawaProduct() { if (!isMawaAnimating && mawaProducts.length > 0) { showMawaProduct((currentMawaProductIndex + 1) % mawaProducts.length); } }
        function previousMawaProduct() { if (!isMawaAnimating && mawaProducts.length > 0) { showMawaProduct((currentMawaProductIndex - 1 + mawaProducts.length) % mawaProducts.length); } }

        if (productContainerMawa) {
            document.addEventListener('keydown', (e) => { const heroSection = document.querySelector('.hero-product-section'); if (!heroSection) return; const heroRect = heroSection.getBoundingClientRect(); const isHeroVisible = heroRect.top < window.innerHeight && heroRect.bottom >= 0; if (isHeroVisible && (document.activeElement === document.body || heroSection.contains(document.activeElement) || document.activeElement === null)) { if (e.key === 'ArrowRight') nextMawaProduct(); if (e.key === 'ArrowLeft') previousMawaProduct(); } });
            let touchStartXmawa = 0; productContainerMawa.addEventListener('touchstart', (e) => { touchStartXmawa = e.changedTouches[0].screenX; }, { passive: true });
            productContainerMawa.addEventListener('touchend', (e) => { const diffX = touchStartXmawa - e.changedTouches[0].screenX; if (Math.abs(diffX) > 50) { if (diffX > 0) nextMawaProduct(); else previousMawaProduct(); } }, { passive: true });
        }
        let autoRotateInterval; function startAutoRotate() { if (autoRotateInterval) clearInterval(autoRotateInterval); if (mawaProducts.length > 1) autoRotateInterval = setInterval(nextMawaProduct, 5000); }
        function stopAutoRotate() { if (autoRotateInterval) clearInterval(autoRotateInterval); }
        window.addEventListener('load', () => { if (mawaProducts.length > 0) { showMawaProduct(0); startAutoRotate(); } updateHeaderStyles(); document.body.style.opacity = '1'; });
        if (productContainerMawa) { productContainerMawa.addEventListener('mouseenter', stopAutoRotate); productContainerMawa.addEventListener('mouseleave', startAutoRotate); productContainerMawa.addEventListener('touchstart', stopAutoRotate, { passive: true }); }
        document.querySelectorAll('.arrow-mawa').forEach(arrow => { arrow.addEventListener('click', () => { stopAutoRotate(); setTimeout(startAutoRotate, 10000); }); });

        const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -50px 0px' };
        const fadeInObserver = new IntersectionObserver((entries) => { entries.forEach(entry => { if (entry.isIntersecting) { entry.target.style.opacity = '1'; if(entry.target.style.transform.includes('translateY')) entry.target.style.transform = 'translateY(0)'; fadeInObserver.unobserve(entry.target); } }); }, observerOptions);
        document.addEventListener('DOMContentLoaded', () => {
             handleScroll();
             if (waveToContentPath) waveToContentPath.setAttribute('fill', getComputedStyle(document.documentElement).getPropertyValue('--asikzade-content-bg').trim());
             if (waveToInstaPromoPath) waveToInstaPromoPath.setAttribute('fill', getComputedStyle(document.documentElement).getPropertyValue('--asikzade-promo-bg').trim());
             updateHeaderStyles();
            const elementsToFadeIn = document.querySelectorAll(
                '.section:not(.hero-product-section) .about-section-layout, .section:not(.hero-product-section) .about-text, .section:not(.hero-product-section) .about-image, ' +
                '.benefit-item, .dual-image-container, ' +
                '#asikzade-products .fp-card, .insta-promo-content > *, ' + // fp-card hala listede
                '.contact-title, .contact-brand-aside, .contact-form input, .contact-form textarea, .contact-form button,' +
                '.product-detail-block .product-detail-block-layout > div' // Yeni ürün detay blokları için
            );
            elementsToFadeIn.forEach((el, index) => {
                let baseDelay = 0, initialY = '30px', transitionProps = `opacity 0.8s ease ${baseDelay}s, transform 0.8s ease ${baseDelay}s`;
                if (el.classList.contains('benefit-item')) { baseDelay = Array.from(document.querySelectorAll('.benefit-item')).indexOf(el) * 0.1; initialY = '20px'; }
                else if (el.classList.contains('fp-card')) { baseDelay = Array.from(document.querySelectorAll('#asikzade-products .fp-card')).indexOf(el) * 0.05; initialY = '0'; transitionProps = `opacity 0.6s ease ${baseDelay}s`; } // #asikzade-products altındaki fp-card'lar hedefleniyor
                else if (el.classList.contains('dual-image-container')) { initialY = '0px'; baseDelay = 0.2; }
                else if (el.parentElement && el.parentElement.classList.contains('insta-promo-content')) { if (el.classList.contains('insta-promo-handle')) baseDelay = 0.1; else if (el.classList.contains('insta-promo-title')) baseDelay = 0.2; else if (el.classList.contains('insta-promo-button')) baseDelay = 0.3; initialY = '20px'; }
                else if (el.classList.contains('contact-title')) { baseDelay = 0.1; initialY = '20px'; } else if (el.classList.contains('contact-brand-aside')) { baseDelay = 0.15; initialY = '20px'; }
                else if (el.closest('.contact-form')) { baseDelay = 0.2 + Array.from(el.closest('.contact-form').children).indexOf(el) * 0.07; initialY = '15px'; }
                else if (el.closest('.product-detail-block-layout')) { if (el.classList.contains('product-detail-block-image-wrapper')) baseDelay = 0.1; if (el.classList.contains('product-detail-block-info-wrapper')) baseDelay = 0.2; initialY = '20px'; }
                else { baseDelay = index * 0.05; }
                el.style.opacity = '0'; if (initialY !== '0px' && initialY !== '0') el.style.transform = `translateY(${initialY})`;
                el.style.transition = transitionProps; fadeInObserver.observe(el);
            });
        });
        let ticking = false; function updateParallax() { const heroSection = document.querySelector('.hero-product-section'); if (heroSection) { const heroRect = heroSection.getBoundingClientRect(); if (heroRect.bottom < 0 || heroRect.top > window.innerHeight) { ticking = false; return; } } const scrolled = window.pageYOffset; document.querySelectorAll('.blob-mawa').forEach((blob, index) => { const speed = 0.3 + (index * 0.05); const yPos = -(scrolled * speed); const existingTransform = blob.style.transform.replace(/translateY\([^)]*\)/g, '').trim(); blob.style.transform = `translateY(${yPos}px) ${existingTransform}`; }); ticking = false; }
        function requestTick() { if (!ticking) { window.requestAnimationFrame(updateParallax); ticking = true; } }
        window.addEventListener('scroll', requestTick, { passive: true });
        document.addEventListener('DOMContentLoaded', () => { const images = document.querySelectorAll('img:not(.product-image-mawa):not(.full-screen-image-section img)'); images.forEach(img => img.loading = 'lazy'); if (productImageMawa && productNameMawa && productNameBackgroundMawa) { /* Initial setup if needed */ } });
        document.body.style.opacity = '0'; document.body.style.transition = 'opacity 0.5s ease';
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