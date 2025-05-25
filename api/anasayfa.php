<?php
session_start(); // CSRF token veya diğer session işlemleri için gerekebilir.
include 'products_data.php'; // Ürün verilerini ve fonksiyonları dahil et
$cart_item_count = get_cart_count(); // Sepetteki ürün sayısını al
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AŞIKZADE - Doğal Lezzetler</title>
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
            --asikzade-contact-bg: #F8C8DC; /* New contact section background */
            --asikzade-contact-input-bg: #ECECEC; /* Contact form input background */
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            overflow-x: hidden;
            position: relative;
            color: var(--asikzade-dark-text);
            line-height: 1.6;
            transition: background 0.8s ease;
            background-color: var(--asikzade-content-bg); /* Ensure body also has this bg if not set by hero */
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
        .header.scrolled.content-bg-active {
             background: rgba(254, 246, 230, 0.95); /* var(--asikzade-content-bg) with transparency */
        }
        .header.scrolled.contact-bg-active { /* For contact section */
            background: rgba(248, 200, 220, 0.95); /* Match contact bg with transparency */
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
        .logo-container img.logo-inverted {
             filter: invert(1) brightness(1.5) drop-shadow(0 1px 2px rgba(0,0,0,0.3));
        }
         /* Ensure logo is not inverted on pink bg if scrolled */
        .header.scrolled.contact-bg-active .logo-container img.logo-inverted,
        .header.scrolled.contact-bg-active .logo-container img {
            filter: none;
        }


        .header.scrolled .logo-container img {
            height: 48px;
        }

        .logo-text {
            font-size: 28px;
            font-weight: 600;
            letter-spacing: 1.5px;
            transition: all 0.3s ease;
            color: var(--asikzade-light-text); /* Default light for hero */
        }
        .header:not(.scrolled) .logo-text.dark-theme-text {
            color: var(--asikzade-dark-text);
        }
         .header.scrolled .logo-text {
            font-size: 22px;
            color: var(--asikzade-dark-text); /* Dark when scrolled */
        }

        /* Minimalist Navigation */
        .main-nav {
            display: flex;
            align-items: center; /* İkonları dikeyde ortala */
        }

        /* Kullanıcı ve Sepet ikonlarını yan yana getirmek için */
        .user-actions-group {
            display: flex;
            align-items: center;
            gap: 15px; /* İkonlar arası boşluk */
        }

        .nav-user-icon, .nav-cart-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1.5px solid var(--asikzade-light-text);
            color: var(--asikzade-light-text);
            transition: all 0.3s ease;
            position: relative; /* Badge için */
            text-decoration: none; /* Link alt çizgisini kaldır */
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
            width: 36px; /* Slightly smaller when scrolled */
            height: 36px;
        }
        .nav-user-icon:hover, .nav-cart-icon:hover {
            background-color: rgba(255,255,255,0.1);
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
            border: 1px solid var(--asikzade-light-text); /* Hero üzerindeykenki border */
        }
        /* Scrolled ve dark tema için badge ayarları */
        .header:not(.scrolled) .nav-cart-icon.dark-theme-text .cart-badge {
            border-color: var(--asikzade-dark-text);
            /* İsteğe bağlı: background-color: var(--asikzade-dark-green); */
        }
        .header.scrolled .cart-badge {
             background-color: var(--asikzade-dark-green); /* Scrolled için daha koyu yeşil */
            border-color: var(--asikzade-dark-text); /* Scrolled için border */
        }
         /* Pembe arka plan üzerinde sepet ikonu ve badge (header scrolled ve contact-bg-active iken) */
        .header.scrolled.contact-bg-active .nav-cart-icon {
            border-color: var(--asikzade-dark-text); /* Zaten böyle olmalı */
            color: var(--asikzade-dark-text);
        }
        .header.scrolled.contact-bg-active .cart-badge {
            background-color: var(--asikzade-dark-green);
            color: var(--asikzade-light-text);
            border-color: var(--asikzade-dark-text); /* Veya var(--asikzade-content-bg) gibi bir renk */
        }


        /* === 3D HERO SECTION === */
        .hero-product-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding-top: 80px;
        }

        /* === WAVE TRANSITION === */
        .wave-transition {
            position: absolute;
            bottom: -50px; /* Adjusted to better connect with content */
            left: 0;
            width: 100%;
            height: 120px; /* Increased height for smoother curve */
            z-index: 100; /* Ensure it's above hero bg but below content */
            pointer-events: none;
        }
        .wave-transition svg {
            width: 100%;
            height: 100%;
        }
        .wave-transition path {
             transition: fill 0.8s ease; /* Smooth transition for fill color if dynamic */
        }


        .product-showcase-mawa {
            position: relative;
            z-index: 100;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }

        .product-name-background-mawa {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: clamp(30px, 9vw, 130px); /* UPDATED: Increased from 20px, 6vw, 90px */
            font-weight: 900;
            z-index: 1;
            pointer-events: none;
            text-transform: uppercase;
            white-space: normal;
            max-width: 90vw; /* UPDATED: Increased from 80vw */
            line-height: 1.0; /* UPDATED: Tightened line height for larger text */
            opacity: 0;
            transition: opacity 0.5s ease 0.2s, transform 0.5s ease 0.2s, color 0.5s ease;
            text-align: center;
            overflow-wrap: break-word;
        }


        .product-image-container-mawa {
            position: relative;
            width: clamp(200px, 60vw, 300px);
            height: clamp(280px, 80vw, 400px);
            perspective: 1200px;
            margin-bottom: 30px;
            cursor: grab;
            z-index: 2;
        }

        .product-image-mawa {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.05s linear;
            transform-style: preserve-3d;
            filter: drop-shadow(0 25px 50px rgba(0,0,0,0.25));
            will-change: transform;
        }

        .product-info-mawa {
            padding: clamp(10px, 2vw, 15px) clamp(20px, 5vw, 40px);
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            border-radius: 30px;
            font-size: clamp(18px, 4vw, 22px);
            font-weight: 600;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transition: opacity 0.4s ease 0.1s, transform 0.4s ease 0.1s;
            position: relative;
            z-index: 3;
            opacity: 0;
            transform: translateY(15px);
        }

        .arrow-mawa {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: transparent;
            border: 1.5px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 200;
        }
        .arrow-mawa:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.6);
            transform: translateY(-50%) scale(1.05);
        }
        .arrow-mawa.left { left: clamp(20px, 4vw, 60px); }
        .arrow-mawa.right { right: clamp(20px, 4vw, 60px); }
        .arrow-mawa::after {
            content: '';
            width: 8px;
            height: 8px;
            border-top: 2px solid rgba(255, 255, 255, 0.8);
            border-right: 2px solid rgba(255, 255, 255, 0.8);
        }
        .arrow-mawa.left::after { transform: rotate(-135deg); margin-left: 2px; }
        .arrow-mawa.right::after { transform: rotate(45deg); margin-right: 2px; }

        .bg-element-mawa { position: absolute; pointer-events: none; will-change: transform, border-radius, opacity; z-index: 0; }
        .blob-mawa {
            border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%;
            background: rgba(255, 255, 255, 0.05);
            filter: blur(80px);
            animation: morphBlob 30s ease-in-out infinite alternate;
        }
        .blob-mawa.b1 { width: 40vw; height: 40vw; max-width: 500px; top: -20%; left: -20%; animation-duration: 35s; }
        .blob-mawa.b2 { width: 35vw; height: 35vw; max-width: 450px; bottom: -20%; right: -20%; animation-duration: 40s; }

        /* === DUAL IMAGE CONTAINER === */
        .dual-image-container {
            display: flex;
            width: 100%;
            gap: 0;
            padding: 0;
            background-color: transparent;
        }

        .full-screen-image-section {
            width: 50%;
            height: 70vh;
            overflow: hidden;
            position: relative;
            border-radius: 0;
            box-shadow: none;
        }
        .full-screen-image-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .asikzade-content-wrapper {
            background-color: var(--asikzade-content-bg);
            color: var(--asikzade-dark-text);
            position: relative;
            z-index: 10;
            padding-top: 60px;
        }

        .section {
            padding: 100px 0;
            max-width: 1200px;
            margin: 0 auto;
            padding-left: 50px;
            padding-right: 50px;
        }

        .section-title {
            font-size: 36px;
            color: var(--asikzade-dark-text);
            text-align: center;
            margin-bottom: 60px;
            font-weight: 400;
            letter-spacing: -0.5px;
        }

        /* === FEATURED PRODUCTS SECTION === */
        #asikzade-products.section {
            padding-left: 0;
            padding-right: 0;
            max-width: none;
        }

        #asikzade-products .section-title-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 50px;
        }

        .fp-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
        }

        .fp-card { /* Bu artık a tag'i değil, bir div olacak */
            position: relative;
            overflow: hidden;
            /* text-decoration: none; */ /* Div için gereksiz */
            display: block;
            aspect-ratio: 0.85;
            background-color: var(--asikzade-light-gray);
        }

        .fp-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .fp-card:hover img {
            transform: scale(1.08);
        }

        .fp-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to top, rgba(20, 20, 20, 0.85) 0%, rgba(20, 20, 20, 0.5) 50%, rgba(20, 20, 20, 0) 100%);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: center;
            padding: 25px 20px;
            opacity: 0;
            transition: opacity 0.4s ease-in-out;
            text-align: center;
            color: var(--asikzade-light-text);
        }

        .fp-card:hover .fp-overlay {
            opacity: 1;
        }

        .fp-overlay-name {
            font-size: clamp(1rem, 1.5vw, 1.25rem);
            font-weight: 600;
            line-height: 1.3;
            margin-bottom: 8px;
            transform: translateY(20px);
            transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.1s, opacity 0.4s ease 0.1s;
            opacity: 0;
        }

        /* .fp-overlay-price { ... } - Kullanılmıyorsa kaldırılabilir veya ürün fiyatı için eklenebilir */

        .fp-overlay-buttons { /* Butonları sarmalamak için yeni bir div */
            display: flex;
            flex-direction: column; /* Butonları alt alta sırala */
            align-items: center; /* Butonları ortala */
            gap: 8px; /* Butonlar arası boşluk */
        }

        .fp-overlay-btn, .fp-add-to-cart-btn {
            background-color: var(--asikzade-green);
            color: var(--asikzade-light-text);
            padding: 10px 22px;
            border-radius: 30px;
            font-size: clamp(0.8rem, 1vw, 0.9rem);
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            display: inline-block;
            transform: translateY(20px);
            opacity: 0;
            white-space: nowrap;
        }
        .fp-overlay-btn { /* Detay butonu için geçiş */
            transition: background-color 0.3s ease, transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.2s, opacity 0.4s ease 0.2s;
        }
        .fp-add-to-cart-btn { /* Sepete ekle butonu için geçiş (biraz daha geç) */
             transition: background-color 0.3s ease, transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.25s, opacity 0.4s ease 0.25s;
        }


        .fp-card:hover .fp-overlay-name,
        .fp-card:hover .fp-overlay-btn,
        .fp-card:hover .fp-add-to-cart-btn {
            transform: translateY(0);
            opacity: 1;
        }

        .fp-overlay-btn:hover, .fp-add-to-cart-btn:hover {
            background-color: var(--asikzade-dark-green);
        }


        /* === ABOUT SECTION === */
        .about-section-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 100px;
            align-items: center;
        }
        .about-image img {
            width: 100%;
            height: 450px;
            object-fit: cover;
            display: block;
            filter: grayscale(10%);
            border-radius: 8px;
        }
        .about-text h3 {
            font-size: 28px;
            margin-bottom: 30px;
            font-weight: 500;
            line-height: 1.4;
        }
        .about-text p {
            font-size: 16px;
            margin-bottom: 25px;
            color: var(--asikzade-gray);
            line-height: 1.9;
            font-weight: 300;
        }


        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 60px;
        }
        .benefit-icon {
            width: 48px;
            height: 48px;
            border: 1.5px solid var(--asikzade-green);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 0 25px 0;
        }
        .benefit-icon svg {
            width: 22px;
            height: 22px;
            fill: var(--asikzade-green);
        }
        .benefit-item h4 {
            font-size: 20px;
            margin-bottom: 15px;
            font-weight: 500;
        }
        .benefit-item p {
            font-size: 15px;
            color: var(--asikzade-gray);
            line-height: 1.8;
            font-weight: 300;
        }

        /* === CONTACT SECTION === */
        #asikzade-contact {
            background-color: var(--asikzade-contact-bg);
            padding: 100px 0;
            color: var(--asikzade-dark-text); /* Ensure text is dark on light pink */
        }
        .contact-container {
            max-width: 900px; /* Max width for form area */
            margin: 0 auto;
            padding: 0 30px;
        }
        .contact-title {
            font-size: clamp(2.5rem, 8vw, 5rem); /* Large, responsive title */
            font-weight: 800;
            text-align: center;
            margin-bottom: 60px;
            color: var(--asikzade-dark-text);
            line-height: 1;
            letter-spacing: -1px;
        }
        .contact-layout {
            display: grid;
            grid-template-columns: 1fr; /* Default single column */
            gap: 40px 60px;
            align-items: flex-start; /* Align brand text to top of form */
        }
        .contact-brand-aside {
            font-size: clamp(1.2rem, 2.5vw, 1.8rem);
            font-weight: 600;
            color: var(--asikzade-dark-text);
            opacity: 0.8;
            /* On larger screens, this could be styled to be more prominent if next to form */
        }
        .contact-form {
            display: flex;
            flex-direction: column;
            gap: 20px; /* Space between form elements */
        }
        .contact-form input[type="text"],
        .contact-form input[type="email"],
        .contact-form input[type="tel"],
        .contact-form textarea {
            width: 100%;
            padding: 18px 25px;
            border: none; /* No border as per image */
            background-color: var(--asikzade-contact-input-bg);
            border-radius: 30px; /* Pill shape */
            font-size: 1rem;
            color: var(--asikzade-dark-text);
            font-family: inherit;
            box-shadow: none; /* No shadow as per image */
        }
        .contact-form input::placeholder,
        .contact-form textarea::placeholder {
            color: #888; /* Placeholder color */
            opacity: 0.7;
        }
        .contact-form textarea {
            min-height: 150px;
            resize: vertical;
        }
        .contact-form button {
            background-color: var(--asikzade-green);
            color: var(--asikzade-light-text);
            padding: 18px 30px;
            border: none;
            border-radius: 30px;
            font-size: 1.05rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
            align-self: flex-start; /* Button not full width */
        }
        .contact-form button:hover {
            background-color: var(--asikzade-dark-green);
            transform: translateY(-2px);
        }

        /* === INSTA PROMO SECTION === */
        .insta-promo-section {
            position: relative;
            background-color: var(--asikzade-promo-bg);
            padding: clamp(60px, 10vw, 120px) 20px;
            overflow: hidden;
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .insta-promo-content {
            position: relative;
            z-index: 10;
            text-align: center;
            max-width: 700px;
            padding: 20px;
        }

        .insta-promo-handle {
            font-size: clamp(0.8rem, 1.5vw, 1rem);
            font-weight: 500;
            color: var(--asikzade-dark-text);
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        .insta-promo-title {
            font-size: clamp(1.8rem, 5vw, 3.2rem);
            font-weight: 700;
            color: var(--asikzade-dark-text);
            line-height: 1.2;
            text-transform: uppercase;
            margin-bottom: 30px;
        }

        .insta-promo-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background-color: var(--asikzade-green);
            color: var(--asikzade-light-text);
            padding: 12px 28px;
            border-radius: 50px;
            text-decoration: none;
            font-size: clamp(0.9rem, 2vw, 1.1rem);
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.3s ease;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .insta-promo-button:hover {
            background-color: var(--asikzade-dark-green);
            transform: translateY(-2px);
        }
        .insta-promo-button svg {
            width: clamp(18px, 3vw, 22px);
            height: clamp(18px, 3vw, 22px);
            stroke: var(--asikzade-light-text);
        }

        .promo-image {
            position: absolute;
            width: clamp(100px, 16vw, 200px); /* UPDATED: Slightly smaller for better spacing */
            height: auto;
            aspect-ratio: 3/4;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            z-index: 5;
            object-fit: cover;
        }
        .promo-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
            display: block;
        }

        .promo-image-1 {
            top: clamp(10px, 3%, 50px);    /* UPDATED */
            left: clamp(10px, 4%, 60px);   /* UPDATED */
            transform: rotate(-12deg);
        }
        .promo-image-2 {
            top: clamp(15px, 4%, 60px);    /* UPDATED */
            right: clamp(10px, 3%, 50px);  /* UPDATED */
            transform: rotate(10deg);
        }
        .promo-image-3 {
            bottom: clamp(10px, 3%, 50px); /* UPDATED */
            left: clamp(15px, 5%, 70px);   /* UPDATED */
            transform: rotate(8deg);
        }
        .promo-image-4 {
            bottom: clamp(15px, 4%, 60px); /* UPDATED */
            right: clamp(15px, 5%, 70px);  /* UPDATED */
            transform: rotate(-15deg);
        }


        /* === UPDATED FOOTER STYLES === */
        .footer {
            background-color: var(--asikzade-content-bg); /* Footer background same as body/content */
            padding: 60px 0 30px;
            position: relative;
            z-index: 20;
            color: var(--asikzade-dark-text); /* Ensure text color consistency */
            border-top: none; /* Removed top border for a cleaner look as per image hint */
        }
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 50px;
        }

        /* Removed .footer-main and .footer-brand as they are not in the new design */

        .footer-social-row {
            display: flex;
            justify-content: center;
            margin-bottom: 40px; /* Space between icons and bottom line */
        }

        .social-icons {
            display: flex;
            gap: 25px; /* Adjusted gap for new icon size */
        }
        .social-icons a {
            width: 48px;  /* Slightly larger icons */
            height: 48px;
            background-color: var(--asikzade-green); /* Green background */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            border: none; /* No border for green background icons */
            box-shadow: 0 3px 6px rgba(0,0,0,0.12); /* Subtle shadow for depth */
        }
        .social-icons a:hover {
            background-color: var(--asikzade-dark-green); /* Darken on hover */
            transform: translateY(-2px); /* Lift effect */
        }
        .social-icons svg {
            width: 22px; /* Adjusted SVG size */
            height: 22px;
            fill: var(--asikzade-light-text); /* Light icon color for contrast with green */
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 25px; /* Space above this line */
            border-top: 1px solid var(--asikzade-border); /* Subtle separator line */
        }

        .footer-links ul { /* Was .footer-section ul */
            list-style: none;
            display: flex;
            gap: 25px; /* Adjusted gap */
            margin: 0;
            padding: 0;
        }
        .footer-links a { /* Was .footer-section a */
            color: var(--asikzade-gray);
            text-decoration: none;
            font-size: 14px;
            font-weight: 400; /* Slightly bolder to match image */
            transition: color 0.3s ease;
        }
        .footer-links a:hover {
            color: var(--asikzade-dark-text);
        }

        .copyright {
            font-size: 14px; /* Adjusted to match links and image */
            color: var(--asikzade-gray);
            font-weight: 400; /* Adjusted to match links and image */
            text-align: left; /* As per image */
            margin: 0;
        }

        @keyframes morphBlob {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, -30px) scale(1.1); }
        }
        .product-transition-out { animation: fadeOut 0.3s ease forwards; }
        .product-transition-in { animation: fadeIn 0.3s ease forwards; }
        @keyframes fadeOut { to { opacity: 0; } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .product-name-bg-transition-out { opacity: 0; }
        .product-name-bg-transition-in { opacity: 1; }

        /* RESPONSIVE STYLES */
        @media (max-width: 1024px) { /* Tablet */
            .header { padding: 20px 30px; }
            .header.scrolled { padding: 12px 30px; }
            .logo-container img { height: 54px; }
            .header.scrolled .logo-container img { height: 44px; }
            .logo-text { font-size: 24px; }
            .header.scrolled .logo-text { font-size: 20px; }
            .nav-user-icon, .nav-cart-icon { width: 38px; height: 38px; }
            .header.scrolled .nav-user-icon, .header.scrolled .nav-cart-icon { width: 34px; height: 34px; }


            #asikzade-products.section {
                padding-left: 30px;
                padding-right: 30px;
                max-width: 1200px;
                margin-left: auto;
                margin-right: auto;
            }
            #asikzade-products .section-title-wrapper {
                padding-left: 0;
                padding-right: 0;
                max-width: none;
            }
            .fp-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 25px;
            }
            .fp-card {
                aspect-ratio: 1;
                border-radius: 8px;
            }
            .section {
                padding: 80px 0;
                padding-left: 30px;
                padding-right: 30px;
            }
             .about-section-layout {
                gap: 50px;
            }
            .asikzade-content-wrapper {
                padding-top: 50px;
            }
            .dual-image-container {
                flex-direction: column;
                gap: 0;
            }
            .full-screen-image-section {
                width: 100%;
                height: 50vh;
            }

            .promo-image {
                width: clamp(90px, 14vw, 160px); /* UPDATED */
            }
            .promo-image-1 {
                left: clamp(10px, 2%, 40px);   /* UPDATED */
                top: clamp(10px, 2%, 30px);    /* UPDATED */
            }
            .promo-image-2 {
                right: clamp(10px, 1.5%, 35px);/* UPDATED */
                top: clamp(15px, 3%, 45px);    /* UPDATED */
            }
            .promo-image-3 {
                left: clamp(10px, 3%, 45px);   /* UPDATED */
                bottom: clamp(10px, 2%, 30px); /* UPDATED */
            }
            .promo-image-4 {
                right: clamp(10px, 2.5%, 40px);/* UPDATED */
                bottom: clamp(15px, 3%, 45px); /* UPDATED */
            }

            /* Contact Section Tablet */
            .contact-layout {
                 grid-template-columns: 200px 1fr; /* Brand aside, form main */
                 align-items: center; /* Center vertically brand and form */
            }
             .contact-brand-aside {
                text-align: right; /* Align brand text right if it's on the left */
                padding-right: 30px;
            }
        }

        @media (max-width: 992px) {
            /* .logo-container img { height: 54px; } */
            /* .header.scrolled .logo-container img { height: 44px; } */
            .user-actions-group { gap: 20px; } /* Tablet için ikon arası boşluk */
            .benefits-grid { grid-template-columns: 1fr; gap: 50px; }
        }

        @media (max-width: 768px) { /* Mobile */
            .header { padding: 20px 20px; }
            .header.scrolled { padding: 12px 20px; }
            .logo-container img { height: 48px; }
            .header.scrolled .logo-container img { height: 40px; }
            .logo-text { font-size: 22px; }
            .header.scrolled .logo-text { font-size: 18px; }
            .nav-user-icon, .nav-cart-icon { width: 36px; height: 36px; }
            .header.scrolled .nav-user-icon, .header.scrolled .nav-cart-icon { width: 32px; height: 32px; }


            .section-title { font-size: 28px; margin-bottom: 40px; }
            .about-section-layout { grid-template-columns: 1fr; gap: 50px; }
            .about-image img { height: 300px; }
            .wave-transition { height: 80px; bottom: -30px; }

            #asikzade-products.section {
                padding-left: 20px;
                padding-right: 20px;
            }
             #asikzade-products .section-title-wrapper {
                padding-left: 0;
                padding-right: 0;
            }
            .fp-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .fp-card {
                aspect-ratio: 4/3;
                border-radius: 8px;
            }
            .fp-overlay-name { font-size: 1.3rem; }
            /* .fp-overlay-price { font-size: 1.1rem; } */
            .fp-overlay-btn, .fp-add-to-cart-btn { font-size: 0.95rem; padding: 12px 24px; }


             .benefits-grid {
                grid-template-columns: 1fr;
            }
            .asikzade-content-wrapper {
                padding-top: 40px;
            }
            .full-screen-image-section {
                height: 40vh;
            }
            .section {
                padding: 80px 0;
                padding-left: 20px;
                padding-right: 20px;
            }

            .insta-promo-section {
                padding-top: clamp(40px, 8vw, 80px);
                padding-bottom: clamp(40px, 8vw, 80px);
            }
            .promo-image {
                 width: clamp(70px, 20vw, 110px); /* UPDATED */
            }
            .promo-image-1 {
                top: clamp(10px, 3%, 25px);    /* UPDATED */
                left: clamp(10px, 1.5%, 20px); /* UPDATED */
                transform: rotate(-10deg);
            }
            .promo-image-2 {
                top: clamp(15px, 4%, 35px);   /* UPDATED */
                right: clamp(10px, 1.5%, 20px);/* UPDATED */
                transform: rotate(8deg);
            }
            .promo-image-3 { display: none;  }
            .promo-image-4 {
                bottom: clamp(15px, 4%, 35px);/* UPDATED */
                right: clamp(10px, 1.5%, 20px);/* UPDATED */
                transform: rotate(-12deg);
            }
            .insta-promo-title { font-size: clamp(1.5rem, 6vw, 2.5rem); }

            /* Contact Section Mobile */
            #asikzade-contact { padding: 60px 0; }
            .contact-title { font-size: clamp(2rem, 10vw, 3.5rem); margin-bottom: 40px; }
            .contact-layout {
                 grid-template-columns: 1fr; /* Stack brand and form */
                 gap: 20px; /* Reduced gap */
            }
             .contact-brand-aside {
                text-align: center; /* Center brand text when stacked */
                padding-right: 0;
                font-size: clamp(1rem, 2vw, 1.5rem);
            }
            .contact-form input[type="text"],
            .contact-form input[type="email"],
            .contact-form input[type="tel"],
            .contact-form textarea { padding: 16px 20px; font-size: 0.95rem; }
            .contact-form button { padding: 16px 25px; font-size: 1rem; align-self: center;}

            /* Footer Mobile */
            .footer-content {
                padding: 0 20px;
            }
            .footer-bottom {
                flex-direction: column;
                gap: 15px;
                text-align: center;
                padding-top: 20px;
            }
            .footer-links ul {
                justify-content: center;
                flex-wrap: wrap;
                gap: 10px 20px; /* Smaller gap for wrapped items */
            }
            .copyright {
                text-align: center;
            }
            .footer-social-row {
                margin-bottom: 30px;
            }
            .social-icons a {
                width: 44px;
                height: 44px;
            }
            .social-icons svg {
                width: 20px;
                height: 20px;
            }
             .footer {
                padding: 40px 0 20px;
            }
        }

        @media (max-width: 480px) { /* Small Mobile */
            .header { padding: 15px 15px; }
            .logo-container img { height: 42px; }
            .header.scrolled .logo-container img { height: 36px; }
            .logo-text { font-size: 20px; }
            .header.scrolled .logo-text { font-size: 17px; }
            .logo-container { gap: 8px; } /* Reduce gap for small screens */
            .user-actions-group { gap: 10px; } /* Küçük mobil için ikon arası boşluk */
            .nav-user-icon, .nav-cart-icon { width: 34px; height: 34px; }
            .header.scrolled .nav-user-icon, .header.scrolled .nav-cart-icon { width: 30px; height: 30px; }


            .arrow-mawa { width: 35px; height: 35px; }
            .arrow-mawa.left { left: 10px; }
            .arrow-mawa.right { right: 10px; }
            .section-title { font-size: 24px; }
            .wave-transition { height: 60px; bottom: -20px; }
            .asikzade-content-wrapper {
                padding-top: 30px;
            }
             .section {
                padding: 60px 0;
                padding-left: 15px;
                padding-right: 15px;
            }
            .fp-overlay-name { font-size: 1.2rem; }
            /* .fp-overlay-price { font-size: 1rem; } */
            .fp-overlay-btn, .fp-add-to-cart-btn { font-size: 0.9rem; padding: 10px 20px; }

            .promo-image {
                 width: clamp(60px, 22vw, 90px); /* UPDATED */
            }
             .promo-image-1 {
                 top: clamp(5px, 1.5%, 15px);  /* UPDATED */
                 left: clamp(5px, 1%, 10px);   /* UPDATED */
                 transform: rotate(-8deg);
             }
             .promo-image-2 { display: none;  }
             .promo-image-3 { display: none; }
             .promo-image-4 {
                 top: clamp(5px, 1.5%, 15px);  /* UPDATED */
                 right: clamp(5px, 1%, 10px);  /* UPDATED */
                 bottom: auto;
                 transform: rotate(8deg);
             }
             .insta-promo-content { padding-top: clamp(70px, 18vw, 100px);  /* UPDATED */ }

            .contact-container { padding: 0 20px; }
            .contact-title { font-size: clamp(1.8rem, 12vw, 2.8rem); }

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

    <section class="hero-product-section">
        <div class="arrow-mawa left" onclick="previousMawaProduct()"></div>
        <div class="product-showcase-mawa">
            <div class="product-name-background-mawa" id="productNameBackgroundMawa"></div>
            <div class="product-image-container-mawa" id="productContainerMawa">
                <img src="" alt="Aşıkzade Ürünü" class="product-image-mawa" id="productImageMawa">
            </div>
            <div class="product-info-mawa" id="productNameMawa">Ürün Adı</div>
        </div>
        <div class="arrow-mawa right" onclick="nextMawaProduct()"></div>
        <div class="bg-element-mawa blob-mawa b1"></div>
        <div class="bg-element-mawa blob-mawa b2"></div>

        <div class="wave-transition">
            <svg viewBox="0 0 1440 120" preserveAspectRatio="none" id="waveToContent">
                <path d="M0,40 C480,120 960,0 1440,80 L1440,120 L0,120 Z" fill="#fef6e6"></path>
            </svg>
        </div>
    </section>

    <div style="width:100vw; height:0; margin:0; padding:0; overflow:hidden; visibility:hidden; position:absolute; pointer-events:none;"></div>

    <main class="asikzade-content-wrapper">
        <section class="section" id="asikzade-about">
            <h2 class="section-title">Hakkımızda</h2>
            <div class="about-section-layout">
                <div class="about-image">
                    <img src="https://i.imgur.com/e7I7JoY.jpeg" alt="Aşıkzade Üretim Alanı">
                </div>
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
                <div class="about-image">
                    <img src="https://i.imgur.com/Ysk6QsD.png" alt="Aşıkzade Üretim Alanı">
                </div>
            </div>
        </section>
        <div class="dual-image-container">
            <section class="full-screen-image-section">
                <img src="https://i.imgur.com/mhaEN1W.jpeg" alt="Doğal Ürünler Tanıtım 1">
            </section>
            <section class="full-screen-image-section">
                <img src="https://i.imgur.com/WbqIJfj.jpeg" alt="Aşıkzade Üretim Süreci Tanıtım 2">
            </section>
        </div>

        <section class="section" id="asikzade-products">
            <div class="section-title-wrapper">
                <h2 class="section-title">Öne Çıkan Ürünlerimiz</h2>
            </div>
            <div class="fp-grid">
                <?php foreach ($products as $product_id => $product_data): ?>
                    <?php
                        // Sadece 'image' anahtarı olan ve 'hero_image' olmayan ürünleri listele
                        // Veya farklı bir mantıkla öne çıkan ürünleri seçebilirsiniz.
                        // Şimdilik 'image' olan tüm ürünleri listeliyoruz.
                        if (empty($product_data['image'])) continue;
                    ?>
                    <div class="fp-card">
                        <img src="<?php echo htmlspecialchars($product_data['image']); ?>" alt="<?php echo htmlspecialchars($product_data['name']); ?>">
                        <div class="fp-overlay">
                            <h3 class="fp-overlay-name"><?php echo htmlspecialchars($product_data['name']); ?></h3>
                            <!-- Fiyatı isterseniz burada gösterebilirsiniz: -->
                            <!-- <span class="fp-overlay-price"><?php // echo number_format($product_data['price'], 2); ?> TL</span> -->
                            <div class="fp-overlay-buttons">
                                <a href="product_detail.php?id=<?php echo $product_id; ?>" class="fp-overlay-btn">Detayları İncele</a>
                                <form action="cart_action.php" method="post" style="margin:0; padding:0; display:inline;">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                    <input type="hidden" name="quantity" value="1"> <!-- Varsayılan olarak 1 adet ekle -->
                                    <button type="submit" class="fp-add-to-cart-btn">Sepete Ekle</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section" id="asikzade-benefits">
            <h2 class="section-title">Neden Aşıkzade?</h2>
            <div class="benefits-grid">
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <svg viewBox="0 0 24 24"><path d="M12 2L3 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-9-5z"></path></svg>
                    </div>
                    <h4>%100 Organik</h4>
                    <p>Tüm ürünlerimiz sertifikalı organik tarım yöntemleriyle yetiştirilir.</p>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"></path></svg>
                    </div>
                    <h4>Katkısız & Doğal</h4>
                    <p>Koruyucu, renklendirici, yapay aroma veya tatlandırıcı içermez.</p>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                    </div>
                    <h4>Geleneksel Yöntemler</h4>
                    <p>Yöresel ve geleneksel üretim teknikleriyle eşsiz lezzetler sunarız.</p>
                </div>
            </div>
        </section>
    </main>
        <section class="insta-promo-section">
        <div class="promo-image promo-image-1">
            <img src="https://i.imgur.com/fIyzlOi.png" alt="Aşıkzade Doğal Ürün 1">
        </div>

        <div class="promo-image promo-image-2">
            <img src="https://i.imgur.com/KY4PF0E.png" alt="Aşıkzade Yaşam Tarzı 1">
        </div>
        <div class="promo-image promo-image-3">
            <img src="https://i.imgur.com/rnpICDG.png" alt="Aşıkzade Doğal Ürün 2">
        </div>

        <div class="promo-image promo-image-4">
            <img src="https://i.imgur.com/Mufz5KT.png" alt="Aşıkzade Yaşam Tarzı 2">
        </div>

        <div class="insta-promo-content">

            <h2 class="insta-promo-title">SOFRANA İLHAM, <br>AKIŞINA RENK KAT!</h2>

        </div>
    </section>

    <section id="asikzade-contact">
        <div class="contact-container">
            <h2 class="contact-title">İLETİŞİM</h2>
            <div class="contact-layout">

                <form action="#" method="POST" class="contact-form">
                    <input type="text" name="name" placeholder="İsim Soyisim" required>
                    <input type="tel" name="phone" placeholder="Telefon">
                    <input type="email" name="email" placeholder="E-mail Adresiniz" required>
                    <textarea name="message" placeholder="Sorularınız / Mesajınız" required></textarea>
                    <button type="submit">Gönder</button>
                </form>
            </div>
        </div>
         <div class="wave-transition" style="bottom: -1px; top:auto; transform: scaleY(-1);"> <!-- Flipped wave for bottom of contact -->
            <svg viewBox="0 0 1440 120" preserveAspectRatio="none" id="waveToInstaPromo">
                <path d="M0,40 C480,120 960,0 1440,80 L1440,120 L0,120 Z" fill="var(--asikzade-promo-bg)"></path>
            </svg>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-social-row">
                <div class="social-icons">
                    <!-- Facebook -->
                    <a href="https://facebook.com/asikzadenatural" target="_blank" aria-label="Facebook">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 2.039c-5.514 0-9.961 4.448-9.961 9.961s4.447 9.961 9.961 9.961c5.515 0 9.961-4.448 9.961-9.961s-4.446-9.961-9.961-9.961zm3.621 9.561h-2.2v7.3h-3.22v-7.3h-1.56v-2.68h1.56v-1.93c0-1.301.63-3.35 3.35-3.35h2.37v2.67h-1.45c-.47 0-.72.24-.72.72v1.31h2.24l-.24 2.68z"/></svg>
                    </a>
                    <!-- LinkedIn -->
                    <a href="https://linkedin.com/company/asikzadenatural" target="_blank" aria-label="LinkedIn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14zm-11.383 7.125H5.121v6.75h2.496v-6.75zm-1.248-2.302a1.49 1.49 0 1 0 0-2.979 1.49 1.49 0 0 0 0 2.979zm9.016 2.302c-2.016 0-2.848 1.081-3.312 2.04h-.048v-1.788H9.573v6.75h2.496v-3.375c0-.891.171-1.755 1.26-1.755.972 0 1.088.687 1.088 1.809v3.321h2.496v-3.828c0-2.203-1.088-3.852-3.288-3.852z"/></svg>
                    </a>
                    <!-- Instagram -->
                    <a href="https://instagram.com/asikzadenatural" target="_blank" aria-label="Instagram">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 2c2.717 0 3.056.01 4.122.06 1.065.05 1.79.217 2.428.465.66.254 1.217.598 1.77.96.582.386.96.826 1.344 1.344.385.517.778 1.074 1.032 1.734.272.712.436 1.436.488 2.498.052 1.066.063 1.405.063 4.122s-.01 3.056-.061 4.122c-.053 1.065-.218 1.79-.487 2.428-.254.66-.598 1.217-.96 1.77-.386.582-.826.96-1.344 1.344-.517.385-1.074.778-1.734 1.032-.712.272-1.436.436-2.498.488-1.066.052-1.405.063-4.122.063s-3.056-.01-4.122-.061c-1.065-.053-1.79-.218-2.428-.487-.66-.254-1.217-.598-1.77-.96-.582-.386-.96-.826-1.344-1.344-.385-.517-.778-1.074-1.032-1.734-.272-.712-.436-1.436-.488-2.498C2.012 15.056 2 14.717 2 12s.01-3.056.061-4.122c.053-1.065.218-1.79.487-2.428.254-.66.598-1.217.96-1.77.386-.582.826.96 1.344-1.344.517-.385 1.074-.778 1.734-1.032.712-.272 1.436.436 2.498-.488C8.944 2.01 9.283 2 12 2zm0 1.802c-2.67 0-2.987.01-4.042.058-.975.045-1.505.207-1.857.344-.466.182-.795.396-1.15.748-.354.354-.566.684-.748 1.15-.137.352-.3.882-.344 1.857-.048 1.054-.058 1.373-.058 4.042s.01 2.987.058 4.042c.045.975.207 1.505.344 1.857.182.466.396.795.748 1.15.354.354.684.566 1.15.748.352.137.882.3 1.857.344 1.054.048 1.373.058 4.042.058s2.987-.01 4.042-.058c.975-.045 1.505-.207 1.857-.344.466-.182.795-.396 1.15-.748.354-.354-.566-.684.748-1.15.137-.352-.3-.882-.344-1.857.048-1.054.058-1.373.058-4.042s-.01-2.987-.058-4.042c-.045-.975-.207-1.505-.344-1.857-.182-.466-.396-.795-.748-1.15-.354-.354-.684-.566-1.15-.748-.352-.137-.882-.3-1.857-.344C14.987 3.812 14.67 3.802 12 3.802zm0 2.903c-2.836 0-5.135 2.299-5.135 5.135s2.299 5.135 5.135 5.135 5.135-2.299 5.135-5.135-2.299-5.135-5.135-5.135zm0 8.468c-1.837 0-3.333-1.496-3.333-3.333s1.496-3.333 3.333-3.333 3.333 1.496 3.333 3.333-1.496 3.333-3.333 3.333zm4.333-8.572a1.2 1.2 0 1 0 0-2.4 1.2 1.2 0 0 0 0 2.4z"/></svg>
                    </a>
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
        const navCartIcon = document.querySelector('.nav-cart-icon'); // Sepet ikonunu da seçelim
        const productNameBackgroundMawa = document.getElementById('productNameBackgroundMawa');

        const waveToContentPath = document.getElementById('waveToContent')?.querySelector('path');
        const waveToInstaPromoPath = document.getElementById('waveToInstaPromo')?.querySelector('path');

        function setMawaTextColors(textType) { // textType can be "light" or "dark"
            if (!headerLogoImage || !siteLogoTextMawa || !navUserIcon || !navCartIcon) return;

            const isDarkTextThemeForHero = textType === "dark";

            if (productNameBackgroundMawa) {
                productNameBackgroundMawa.style.color = isDarkTextThemeForHero ?
                    getComputedStyle(document.documentElement).getPropertyValue('--product-bg-text-dark').trim() :
                    getComputedStyle(document.documentElement).getPropertyValue('--product-bg-text-light').trim();
            }

            if (isDarkTextThemeForHero) { // Dark text on light hero background
                headerLogoImage.classList.remove('logo-inverted');
                siteLogoTextMawa.classList.add('dark-theme-text');
                navUserIcon.classList.add('dark-theme-text');
                navCartIcon.classList.add('dark-theme-text'); // Sepet ikonu için de
            } else { // Light text on dark hero background
                headerLogoImage.classList.add('logo-inverted');
                siteLogoTextMawa.classList.remove('dark-theme-text');
                navUserIcon.classList.remove('dark-theme-text');
                navCartIcon.classList.remove('dark-theme-text'); // Sepet ikonu için de
            }
        }

        function updateHeaderStyles() {
            const heroSection = document.querySelector('.hero-product-section');
            const contactSection = document.getElementById('asikzade-contact');
            const mainContentWrapper = document.querySelector('.asikzade-content-wrapper');

            const scrollY = window.scrollY;
            const headerHeight = mainHeader.offsetHeight;

            if (scrollY > 50) {
                mainHeader.classList.add('scrolled');
            } else {
                mainHeader.classList.remove('scrolled');
            }

            let isOverHero = false;
            if (heroSection) {
                const heroRect = heroSection.getBoundingClientRect();
                isOverHero = heroRect.bottom > headerHeight;
            }

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
                if (navCartIcon) navCartIcon.classList.remove('dark-theme-text'); // Sepet ikonu için

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
                    if (navUserIcon) {
                        navUserIcon.style.borderColor = 'var(--asikzade-dark-text)';
                        navUserIcon.style.color = 'var(--asikzade-dark-text)';
                    }
                    if (navCartIcon) { // Sepet ikonu için
                        navCartIcon.style.borderColor = 'var(--asikzade-dark-text)';
                        navCartIcon.style.color = 'var(--asikzade-dark-text)';
                    }
                } else if (isOverContentBg) {
                    mainHeader.classList.add('content-bg-active');
                    mainHeader.classList.remove('contact-bg-active');
                    if (siteLogoTextMawa) siteLogoTextMawa.style.color = '';
                    if (navUserIcon) {
                        navUserIcon.style.borderColor = '';
                        navUserIcon.style.color = '';
                    }
                     if (navCartIcon) { // Sepet ikonu için
                        navCartIcon.style.borderColor = '';
                        navCartIcon.style.color = '';
                    }
                } else {
                    mainHeader.classList.remove('content-bg-active', 'contact-bg-active');
                    if (siteLogoTextMawa) siteLogoTextMawa.style.color = '';
                    if (navUserIcon) {
                        navUserIcon.style.borderColor = '';
                        navUserIcon.style.color = '';
                    }
                     if (navCartIcon) { // Sepet ikonu için
                        navCartIcon.style.borderColor = '';
                        navCartIcon.style.color = '';
                    }
                }
            }
        }

        function handleScroll() {
            updateHeaderStyles();
        }

        window.addEventListener('scroll', handleScroll);

        const contactLinkFooter = document.querySelector('.footer-links a[href="#asikzade-contact"]');
        if (contactLinkFooter) {
            contactLinkFooter.addEventListener('click', (e) => {
                e.preventDefault();
                const targetSection = document.getElementById('asikzade-contact');
                if (targetSection) {
                    let headerOffset = mainHeader.offsetHeight;
                    const elementPosition = targetSection.getBoundingClientRect().top + window.pageYOffset;
                    const offsetPosition = elementPosition - headerOffset - 20;
                    window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
                }
            });
        }

        // Hero slider için ürün verilerini PHP'den alıyoruz
        const mawaProducts = <?php echo json_encode(array_values($mawaProductsJs)); ?>;
        // array_values() PHP dizisinin anahtarlarını sıfırdan başlayarak yeniden düzenler, JS için daha iyi.

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
            const distX = mawaMouseX - mawaCurrentX;
            const distY = mawaMouseY - mawaCurrentY;
            mawaCurrentX += distX * mawaFollowSpeed;
            mawaCurrentY += distY * mawaFollowSpeed;
            const rotateX = mawaCurrentY / 8;
            const rotateY = -mawaCurrentX / 8;
            const translateZ = Math.min(60, (Math.abs(mawaCurrentX) + Math.abs(mawaCurrentY)) / 3);
            const scale = 1 + (Math.abs(mawaCurrentX) + Math.abs(mawaCurrentY)) / 1000;
            productImageMawa.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(${translateZ}px) scale(${Math.min(scale, 1.12)})`;
            requestAnimationFrame(animateMawaProductImage);
        }

        if (productContainerMawa) {
            animateMawaProductImage();
            productContainerMawa.addEventListener('mousemove', (e) => {
                const rect = productContainerMawa.getBoundingClientRect();
                mawaMouseX = (e.clientX - rect.left - rect.width / 2) / 4;
                mawaMouseY = (e.clientY - rect.top - rect.height / 2) / 4;
            });
            productContainerMawa.addEventListener('mouseleave', () => {
                mawaMouseX = 0;
                mawaMouseY = 0;
            });
        }

        function changeBodyMawaBackground(bgClass) {
            // Önceki mawaProducts JS dizisindeki tüm sınıfları temizle
            <?php
                // PHP tarafında $mawaProductsJs içindeki tüm dynamicBgClass'ları bir JS dizisine yazdıralım
                $bgClassesToRemove = [];
                foreach ($mawaProductsJs as $prod) {
                    if (!empty($prod['dynamicBgClass'])) {
                        $bgClassesToRemove[] = $prod['dynamicBgClass'];
                    }
                }
                echo "const mawaBgClasses = " . json_encode(array_unique($bgClassesToRemove)) . ";\n";
            ?>
            mawaBgClasses.forEach(cls => bodyForMawaBg.classList.remove(cls));
            if (bgClass) bodyForMawaBg.classList.add(bgClass);
        }

        function showMawaProduct(index) {
            if (isMawaAnimating || !mawaProducts.length || !productImageMawa || index < 0 || index >= mawaProducts.length) return;
            isMawaAnimating = true;
            productImageMawa.classList.add('product-transition-out');
            if(productNameMawa) productNameMawa.style.opacity = '0';
            if(productNameMawa) productNameMawa.style.transform = 'translateY(20px)';
            if(productNameBackgroundMawa) productNameBackgroundMawa.classList.remove('product-name-bg-transition-in');
            if(productNameBackgroundMawa) productNameBackgroundMawa.classList.add('product-name-bg-transition-out');

            setTimeout(() => {
                currentMawaProductIndex = index;
                const currentProductData = mawaProducts[index];
                changeBodyMawaBackground(currentProductData.dynamicBgClass);
                updateHeaderStyles();

                productImageMawa.src = currentProductData.image;
                productImageMawa.alt = `Aşıkzade ${currentProductData.name}`;
                if(productNameMawa) productNameMawa.textContent = currentProductData.name;
                if(productNameBackgroundMawa) productNameBackgroundMawa.textContent = currentProductData.name;
                 if (productNameBackgroundMawa) {
                    productNameBackgroundMawa.style.color = currentProductData.productNameBgTextType === "dark" ?
                        getComputedStyle(document.documentElement).getPropertyValue('--product-bg-text-dark').trim() :
                        getComputedStyle(document.documentElement).getPropertyValue('--product-bg-text-light').trim();
                }


                productImageMawa.classList.remove('product-transition-out');
                productImageMawa.classList.add('product-transition-in');

                setTimeout(() => {
                    if(productNameMawa) productNameMawa.style.opacity = '1';
                    if(productNameMawa) productNameMawa.style.transform = 'translateY(0)';
                    if(productNameBackgroundMawa) productNameBackgroundMawa.classList.remove('product-name-bg-transition-out');
                    if(productNameBackgroundMawa) productNameBackgroundMawa.classList.add('product-name-bg-transition-in');
                }, 150);

                setTimeout(() => {
                    productImageMawa.classList.remove('product-transition-in');
                    isMawaAnimating = false;
                }, 300);
            }, 300);
        }

        function nextMawaProduct() {
            if (!isMawaAnimating && mawaProducts.length > 0) {
                const nextI = (currentMawaProductIndex + 1) % mawaProducts.length;
                showMawaProduct(nextI);
            }
        }

        function previousMawaProduct() {
            if (!isMawaAnimating && mawaProducts.length > 0) {
                const prevI = (currentMawaProductIndex - 1 + mawaProducts.length) % mawaProducts.length;
                showMawaProduct(prevI);
            }
        }

        if (productContainerMawa) {
            document.addEventListener('keydown', (e) => {
                const heroSection = document.querySelector('.hero-product-section');
                if (!heroSection) return;
                const heroRect = heroSection.getBoundingClientRect();
                const isHeroVisible = heroRect.top < window.innerHeight && heroRect.bottom >= 0;

                if (isHeroVisible && (document.activeElement === document.body || heroSection.contains(document.activeElement) || document.activeElement === null)) {
                    if (e.key === 'ArrowRight') nextMawaProduct();
                    if (e.key === 'ArrowLeft') previousMawaProduct();
                }
            });

            let touchStartXmawa = 0;
            productContainerMawa.addEventListener('touchstart', (e) => {
                touchStartXmawa = e.changedTouches[0].screenX;
            }, { passive: true });

            productContainerMawa.addEventListener('touchend', (e) => {
                const touchEndXmawa = e.changedTouches[0].screenX;
                const diffX = touchStartXmawa - touchEndXmawa;
                if (Math.abs(diffX) > 50) {
                    if (diffX > 0) nextMawaProduct();
                    else previousMawaProduct();
                }
            }, { passive: true });
        }

        let autoRotateInterval;
        function startAutoRotate() {
            if (autoRotateInterval) clearInterval(autoRotateInterval);
            if (mawaProducts.length > 1) { // Sadece 1'den fazla ürün varsa otomatik döndür
                autoRotateInterval = setInterval(nextMawaProduct, 5000);
            }
        }

        function stopAutoRotate() {
            if (autoRotateInterval) clearInterval(autoRotateInterval);
        }

        window.addEventListener('load', () => {
            if (mawaProducts.length > 0) {
                showMawaProduct(0);
                startAutoRotate();
            }
            updateHeaderStyles();
            document.body.style.opacity = '1';
        });


        if (productContainerMawa) {
            productContainerMawa.addEventListener('mouseenter', stopAutoRotate);
            productContainerMawa.addEventListener('mouseleave', startAutoRotate);
            productContainerMawa.addEventListener('touchstart', stopAutoRotate, { passive: true });
        }

        document.querySelectorAll('.arrow-mawa').forEach(arrow => {
            arrow.addEventListener('click', () => {
                stopAutoRotate();
                setTimeout(startAutoRotate, 10000);
            });
        });

        const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -50px 0px' };
        const fadeInObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    if(entry.target.style.transform.includes('translateY')) {
                        entry.target.style.transform = 'translateY(0)';
                    }
                    fadeInObserver.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.addEventListener('DOMContentLoaded', () => {
             handleScroll();
             if (waveToContentPath) {
                 waveToContentPath.setAttribute('fill', getComputedStyle(document.documentElement).getPropertyValue('--asikzade-content-bg').trim());
            }
            if (waveToInstaPromoPath) {
                waveToInstaPromoPath.setAttribute('fill', getComputedStyle(document.documentElement).getPropertyValue('--asikzade-promo-bg').trim());
            }
            updateHeaderStyles();


            const elementsToFadeIn = document.querySelectorAll(
                '.section:not(#asikzade-products) .about-section-layout, ' +
                '.section:not(#asikzade-products) .about-text, ' +
                '.section:not(#asikzade-products) .about-image, ' +
                '.benefit-item, .dual-image-container, ' +
                '#asikzade-products .fp-card, .insta-promo-content > *, ' +
                '.contact-title, .contact-brand-aside, .contact-form input, .contact-form textarea, .contact-form button'
            );

            elementsToFadeIn.forEach((el, index) => {
                let baseDelay = 0;
                let initialY = '30px';
                let transitionProps = `opacity 0.8s ease ${baseDelay}s, transform 0.8s ease ${baseDelay}s`;


                if (el.classList.contains('benefit-item')) {
                    const benefitItems = Array.from(document.querySelectorAll('.benefit-item'));
                    baseDelay = benefitItems.indexOf(el) * 0.1;
                    initialY = '20px';
                } else if (el.classList.contains('fp-card')) {
                    const productCards = Array.from(document.querySelectorAll('.fp-card'));
                    baseDelay = productCards.indexOf(el) * 0.05;
                    initialY = '0';
                     transitionProps = `opacity 0.6s ease ${baseDelay}s`;
                } else if (el.classList.contains('dual-image-container')) {
                    initialY = '0px';
                    baseDelay = 0.2;
                } else if (el.parentElement && el.parentElement.classList.contains('insta-promo-content')) {
                    if (el.classList.contains('insta-promo-handle')) baseDelay = 0.1;
                    else if (el.classList.contains('insta-promo-title')) baseDelay = 0.2;
                    else if (el.classList.contains('insta-promo-button')) baseDelay = 0.3;
                    initialY = '20px';
                } else if (el.classList.contains('contact-title')) {
                    baseDelay = 0.1; initialY = '20px';
                } else if (el.classList.contains('contact-brand-aside')) {
                    baseDelay = 0.15; initialY = '20px';
                } else if (el.closest('.contact-form')) {
                    const formElements = Array.from(el.closest('.contact-form').children);
                    baseDelay = 0.2 + formElements.indexOf(el) * 0.07;
                    initialY = '15px';
                }
                else {
                    baseDelay = index * 0.05;
                }

                el.style.opacity = '0';
                if (initialY !== '0px' && initialY !== '0') {
                     el.style.transform = `translateY(${initialY})`;
                }
                el.style.transition = transitionProps;
                fadeInObserver.observe(el);
            });
        });


        let ticking = false;
        function updateParallax() {
            const heroSection = document.querySelector('.hero-product-section');
            if (heroSection) {
                const heroRect = heroSection.getBoundingClientRect();
                if (heroRect.bottom < 0 || heroRect.top > window.innerHeight) {
                    ticking = false;
                    return;
                }
            }

            const scrolled = window.pageYOffset;
            const blobs = document.querySelectorAll('.blob-mawa');
            blobs.forEach((blob, index) => {
                const speed = 0.3 + (index * 0.05);
                const yPos = -(scrolled * speed);
                const existingTransform = blob.style.transform.replace(/translateY\([^)]*\)/g, '').trim();
                blob.style.transform = `translateY(${yPos}px) ${existingTransform}`;
            });
            ticking = false;
        }

        function requestTick() {
            if (!ticking) {
                window.requestAnimationFrame(updateParallax);
                ticking = true;
            }
        }
        window.addEventListener('scroll', requestTick, { passive: true });

        const mobileMenuToggle = () => {};
        window.addEventListener('resize', mobileMenuToggle);
        mobileMenuToggle();

        document.addEventListener('DOMContentLoaded', () => {
            const images = document.querySelectorAll('img:not(.product-image-mawa):not(.full-screen-image-section img)');
            images.forEach(img => img.loading = 'lazy');
        });

        document.body.style.opacity = '0';
        document.body.style.transition = 'opacity 0.5s ease';
    </script>
</body>
</html>