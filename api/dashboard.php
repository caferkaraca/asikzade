<?php
// Hata raporlamayı en üste alalım (Geliştirme için)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start(); // Çıktı tamponlamasını başlat

// config.php'yi dahil et (Supabase fonksiyonları için)
// YOLUNU KONTROL EDİN! Eğer dashboard.php api/ içindeyse ve config.php kökteyse:
// require_once __DIR__ . '/../config.php';
require_once 'config.php'; // Eğer aynı dizindeyse

// --- COOKIE TABANLI OTURUM KONTROLÜ ---
$user_data_from_cookie = null;
if (isset($_COOKIE['asikzade_user_session'])) {
    $user_data_from_cookie = json_decode($_COOKIE['asikzade_user_session'], true);
    if (!$user_data_from_cookie || !isset($user_data_from_cookie['user_id'])) {
        setcookie("asikzade_user_session", "", time() - 3600, "/");
        header('Location: /login.php?error=invalid_cookie'); // YOLU / İLE BAŞLATIN
        exit;
    }
} else {
    header('Location: /login.php'); // YOLU / İLE BAŞLATIN
    exit;
}
// --- OTURUM KONTROLÜ SONU ---

$success_message_dashboard = isset($_GET['success_msg']) ? urldecode($_GET['success_msg']) : null;
$error_message_dashboard = isset($_GET['error_msg']) ? urldecode($_GET['error_msg']) : null;

// products_data.php'nin yolunu kontrol edin
$products_data_path = __DIR__ . '/products_data.php'; // dashboard.php ile aynı dizinde olduğunu varsayar
if (file_exists($products_data_path)) {
    include $products_data_path;
} else {
    $products = []; // products_data.php bulunamazsa $products'ı boş bir dizi olarak tanımla
    error_log("dashboard.php: products_data.php bulunamadı. Beklenen yol: " . $products_data_path);
}


$cart_item_count = 0;
// get_cart_count fonksiyonu varsa ve products_data.php'den geliyorsa sorun yok.
// Eğer config.php veya başka bir yerden geliyorsa, onun da dahil edildiğinden emin olun.
if (function_exists('get_cart_count')) {
    $cart_item_count = get_cart_count();
}

// Kullanıcı bilgilerini cookie'den al
$user_ad = $user_data_from_cookie['user_ad'] ?? '';
$user_soyad = $user_data_from_cookie['user_soyad'] ?? '';
$user_email = $user_data_from_cookie['user_email'] ?? 'Misafir Kullanıcı';
$user_id_for_query = $user_data_from_cookie['user_id'] ?? null;

$welcome_name = trim($user_ad . ' ' . $user_soyad);
if (empty($welcome_name)) {
    $welcome_name = $user_email;
}

$active_tab = $_GET['tab'] ?? 'siparislerim';

// --- SİPARİŞ İPTAL İŞLEMİ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'iptal_et_siparis' && isset($_POST['siparis_id_iptal'])) {
    $redirect_params_on_cancel = '?tab=siparislerim'; // Yönlendirme için temel parametre
    if ($user_id_for_query) {
        $siparis_id_to_cancel = trim($_POST['siparis_id_iptal']);
        $user_id_for_query_encoded = rawurlencode($user_id_for_query);
        $siparis_id_to_cancel_encoded = rawurlencode($siparis_id_to_cancel);

        $check_path = '/rest/v1/siparisler?id=eq.' . $siparis_id_to_cancel_encoded . '&kullanici_id=eq.' . $user_id_for_query_encoded . '&select=siparis_durumu';
        // supabase_api_request'in son parametresi (use_service_key) RLS politikalarınıza göre ayarlanmalı.
        // Genellikle bir kullanıcının kendi siparişini okuması için anonim (false) yeterli olabilir.
        $checkOrderStatusResult = supabase_api_request('GET', $check_path, [], [], false);

        if (isset($checkOrderStatusResult['data'][0]['siparis_durumu']) && empty($checkOrderStatusResult['error'])) {
            $current_status = $checkOrderStatusResult['data'][0]['siparis_durumu'];
            if ($current_status === 'beklemede' || $current_status === 'hazırlanıyor') { // Küçük harf kontrolü
                $updateData = ['siparis_durumu' => 'iptal edildi'];
                $cancel_path = '/rest/v1/siparisler?id=eq.' . $siparis_id_to_cancel_encoded . '&kullanici_id=eq.' . $user_id_for_query_encoded;
                // Siparişi güncellemek için genellikle service_key (true) gerekebilir veya RLS'de özel izin.
                $cancelResult = supabase_api_request('PATCH', $cancel_path, $updateData, [], true); // service_key true varsayıyorum

                if (empty($cancelResult['error']) && ($cancelResult['http_code'] === 200 || $cancelResult['http_code'] === 204)) {
                    $redirect_params_on_cancel .= '&success_msg=' . urlencode("#" . substr($siparis_id_to_cancel, 0, 8) . " numaralı sipariş başarıyla iptal edildi.");
                } else {
                    $api_error_msg = $cancelResult['error']['message'] ?? ($cancelResult['data']['message'] ?? 'Bilinmeyen API hatası');
                    $redirect_params_on_cancel .= '&error_msg=' . urlencode("Sipariş iptal edilirken bir hata oluştu: " . $api_error_msg);
                    error_log("Sipariş İptal Hatası (API): " . $api_error_msg . " | Sipariş ID: " . $siparis_id_to_cancel . " | Response: " . json_encode($cancelResult));
                }
            } else {
                $redirect_params_on_cancel .= '&error_msg=' . urlencode("Bu sipariş durumu ('" . $current_status . "') nedeniyle iptal edilemez.");
            }
        } else {
            $error_detail = $checkOrderStatusResult['error']['message'] ?? 'Sipariş bulunamadı veya API hatası.';
            $redirect_params_on_cancel .= '&error_msg=' . urlencode("Sipariş durumu kontrol edilirken bir hata oluştu: " . $error_detail);
            error_log("Sipariş İptal - Durum Kontrol Hatası: " . $error_detail . " | Sipariş ID: " . $siparis_id_to_cancel . " | Response: " . json_encode($checkOrderStatusResult));
        }
    } else {
         $redirect_params_on_cancel .= '&error_msg=' . urlencode("Kullanıcı ID bulunamadığı için sipariş iptal edilemedi.");
         error_log("Dashboard - Sipariş iptali: Kullanıcı ID bulunamadı.");
    }
    header('Location: /dashboard.php' . $redirect_params_on_cancel); // YOLU / İLE BAŞLATIN
    exit;
}

// --- SİPARİŞLERİ ÇEKME (Supabase'den) ---
$kullanici_siparisleri = [];
if ($user_id_for_query) {
    $user_id_for_query_encoded = rawurlencode($user_id_for_query);
    $select_fields_orders = rawurlencode('id,siparis_tarihi,toplam_tutar,siparis_durumu,teslimat_adresi');
    $siparisler_path = '/rest/v1/siparisler?kullanici_id=eq.' . $user_id_for_query_encoded . '&select=' . $select_fields_orders . '&order=siparis_tarihi.desc';
    // Kullanıcının kendi siparişlerini okuması için anonim key (false) genellikle yeterlidir.
    $siparislerResult = supabase_api_request('GET', $siparisler_path, [], [], false);

    if (empty($siparislerResult['error']) && !empty($siparislerResult['data'])) {
        $kullanici_siparisleri = $siparislerResult['data'];
    } else if (!empty($siparislerResult['error'])) {
        // Kullanıcıya bu hatayı göstermek yerine sadece loglayabiliriz.
        error_log("Dashboard - Siparişler çekilirken hata: " . ($siparislerResult['error']['message'] ?? 'Bilinmeyen API hatası') . " | HTTP: " . ($siparislerResult['http_code'] ?? 'N/A') . " | Response: " . json_encode($siparislerResult));
    }
} else {
    error_log("Dashboard: user_id_for_query tanımsız, siparişler çekilemedi.");
}

// --- PROFİL BİLGİLERİNİ ÇEKME (Supabase'den - Telefon ve Adres için) ---
$profil_telefon = '';
$profil_adres = '';
if ($user_id_for_query) {
    $select_fields_profile = rawurlencode('telefon,adres'); // 'kullanicilar' tablosundaki sütun adları
    $profil_path = '/rest/v1/kullanicilar?id=eq.' . rawurlencode($user_id_for_query) . '&select=' . $select_fields_profile;
    // Kullanıcının kendi profilini okuması için anonim key (false) genellikle yeterlidir.
    $profilResult = supabase_api_request('GET', $profil_path, [], [], false);

    if (empty($profilResult['error']) && !empty($profilResult['data']) && isset($profilResult['data'][0])) {
        $profil_telefon = $profilResult['data'][0]['telefon'] ?? '';
        $profil_adres = $profilResult['data'][0]['adres'] ?? '';
    } else if (!empty($profilResult['error'])) {
         error_log("Dashboard - Profil bilgileri çekilirken hata: " . ($profilResult['error']['message'] ?? 'Bilinmeyen API hatası') . " | HTTP: " . ($profilResult['http_code'] ?? 'N/A') . " | Response: " . json_encode($profilResult));
    }
} else {
    error_log("Dashboard: user_id_for_query tanımsız, profil bilgileri çekilemedi.");
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrol Paneli - AŞIKZADE</title>
    <style>
        /* CSS Stilleriniz (önceki mesajdaki gibi) buraya gelecek */
        /* ... */
        :root {
            --asikzade-content-bg: #fef6e6;
            --asikzade-green: #8ba86d;
            --asikzade-dark-green: #6a8252;
            --asikzade-dark-text: #2d3e2a;
            --asikzade-light-text: #fdfcf8;
            --asikzade-gray: #7a7a7a;
            --asikzade-border: #e5e5e5;
            --input-bg: #fff;
            --message-success-bg: #d4edda;
            --message-success-text: #155724;
            --message-success-border: #c3e6cb;
            --message-error-bg: #f8d7da;
            --message-error-text: #721c24;
            --message-error-border: #f5c6cb;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif; }
        body {
            background-color: var(--asikzade-content-bg); color: var(--asikzade-dark-text);
            line-height: 1.6; display: flex; flex-direction: column; min-height: 100vh;
        }
        .header {
            position: fixed; top: 0; width: 100%; display: flex; justify-content: space-between; align-items: center;
            padding: 15px 50px; z-index: 1000; background: rgba(254, 246, 230, 0.95);
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); box-shadow: 0 1px 0 rgba(0,0,0,0.05);
        }
        .logo-container { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .logo-container img { height: 48px; }
        .logo-text { font-size: 22px; font-weight: 600; letter-spacing: 1.5px; color: var(--asikzade-dark-text); text-decoration: none; }
        .main-nav { display: flex; align-items: center; }
        .user-actions-group { display: flex; align-items: center; gap:15px; }
        .nav-user-icon, .nav-cart-icon {
            display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%;
            border: 1.5px solid var(--asikzade-dark-text); color: var(--asikzade-dark-text);
            transition: all 0.3s ease; position: relative; text-decoration: none; cursor: pointer;
        }
        .nav-user-icon svg, .nav-cart-icon svg { width: 18px; height: 18px; stroke: currentColor; }
        .nav-user-icon:hover, .nav-cart-icon:hover { background-color: rgba(0,0,0,0.05); }
        .cart-badge {
            position: absolute; top: -5px; right: -8px; background-color: var(--asikzade-dark-green); color: var(--asikzade-light-text);
            border-radius: 50%; width: 20px; height: 20px; font-size: 12px;
            display: flex; align-items: center; justify-content: center; font-weight: bold; border: 1px solid var(--asikzade-dark-text);
        }
        .profile-dropdown { position: relative; }
        .dropdown-menu {
            display: none; position: absolute; top: calc(100% + 10px); right: 0; /* Biraz boşluk ekledim */
            background-color: white; border: 1px solid var(--asikzade-border);
            border-radius: 6px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); /* Gölgeyi yumuşattım */
            z-index: 1001; min-width: 220px; padding: 8px 0; /* Padding ayarı */
        }
        .profile-dropdown:hover .dropdown-menu, /* Hover'da aç */
        .profile-dropdown .nav-user-icon:focus + .dropdown-menu, /* Focus'ta aç (klavye navigasyonu için) */
        .dropdown-menu:hover { display: block; } /* Menü üzerindeyken açık kal */

        .dropdown-menu a {
            display: block; padding: 10px 20px; text-decoration: none; /* Padding ayarı */
            color: var(--asikzade-dark-text); font-size: 0.95rem; white-space: nowrap;
            transition: background-color 0.2s ease; /* Geçiş efekti */
        }
        .dropdown-menu a:hover { background-color: #f0f0f0; }
        .dropdown-menu .dropdown-user-info {
            padding: 12px 20px; border-bottom: 1px solid var(--asikzade-border); margin-bottom: 8px; /* Padding ve margin ayarı */
        }
        .dropdown-user-info .user-name { font-weight: 600; display: block; font-size: 1rem; margin-bottom: 2px;} /* Font ağırlığı ve margin */
        .dropdown-user-info .user-email { font-size: 0.85rem; color: var(--asikzade-gray); display: block;}
        .dropdown-menu a.logout-link { color: #c0392b; font-weight: 500;} /* Çıkış linki için özel stil */
        .dropdown-menu a.logout-link:hover { background-color: #f8d7da; }


        .dashboard-page-wrapper { flex-grow: 1; padding-top: 100px; padding-bottom: 60px; width: 100%; }
        .dashboard-container {
            max-width: 1000px; margin: 0 auto; padding: 0;
            background-color: #fff; border-radius: 8px; box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            display: flex;
        }
        .dashboard-sidebar {
            flex: 0 0 240px; background-color: #f8f9fa; padding: 25px 0;
            border-right: 1px solid var(--asikzade-border); border-radius: 8px 0 0 8px;
        }
        .dashboard-sidebar h2 {
            font-size: 1.4rem; padding: 0 25px 15px 25px; margin-bottom: 15px;
            border-bottom: 1px solid var(--asikzade-border); font-weight: 600; color: var(--asikzade-dark-text);
        }
        .dashboard-sidebar ul { list-style: none; padding: 0; margin: 0; }
        .dashboard-sidebar ul li a {
            display: block; padding: 13px 25px; text-decoration: none; color: var(--asikzade-dark-text);
            font-size: 1rem; border-left: 4px solid transparent; transition: background-color 0.2s, border-left-color 0.2s;
        }
        .dashboard-sidebar ul li a:hover { background-color: var(--asikzade-content-bg); }
        .dashboard-sidebar ul li a.active {
            background-color: var(--asikzade-content-bg); border-left-color: var(--asikzade-green); font-weight: 500;
        }
        .dashboard-content { flex-grow: 1; padding: 30px 35px; }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .tab-content h3 {
            font-size: 1.8rem; margin-bottom: 25px; color: var(--asikzade-dark-text);
            font-weight: 600; padding-bottom: 15px; border-bottom: 1px solid var(--asikzade-border);
        }
        .tab-content p { margin-bottom: 15px; font-size: 1rem; color: #444; }
        .orders-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9rem; }
        .orders-table th, .orders-table td {
            padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--asikzade-border);
        }
        .orders-table th { background-color: #f1f1f1; font-weight: 500; }
        .orders-table td.actions-cell { text-align: center; white-space: nowrap; }
        .orders-table .order-inspect-btn, .orders-table .order-cancel-btn {
            background-color: var(--asikzade-green); color: white; border: none;
            padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; margin: 0 3px;
            transition: background-color 0.2s ease;
        }
        .orders-table .order-inspect-btn:hover { background-color: var(--asikzade-dark-green); }
        .orders-table .order-cancel-btn { background-color: #e74c3c; }
        .orders-table .order-cancel-btn:hover { background-color: #c0392b; }
        .orders-table .order-cancel-btn:disabled { background-color: #ccc; cursor: not-allowed; opacity: 0.7;}
        .status-beklemede { color: #e67e22; font-weight: bold; }
        .status-hazirlaniyor, .status-hazırlanıyor { color: #3498db; font-weight: bold; } /* Türkçe karakter için eklendi */
        .status-gonderildi, .status-gönderildi { color: #27ae60; font-weight: bold; } /* Türkçe karakter için eklendi */
        .status-teslim-edildi { color: var(--asikzade-green); font-weight: bold; }
        .status-iptal-edildi { color: #c0392b; font-weight: bold; }
        .profile-info-group { margin-bottom: 18px; }
        .profile-info-group strong {
            display: inline-block; min-width: 120px; font-weight: 500; color: var(--asikzade-gray);
        }
        .profile-info-group span { color: var(--asikzade-dark-text); }
        .message-box {
            padding: 12px 15px; margin-bottom: 20px; border: 1px solid transparent;
            border-radius: 4px; font-size: 0.95rem;
        }
        .message-success { color: var(--message-success-text); background-color: var(--message-success-bg); border-color: var(--message-success-border); }
        .message-error { color: var(--message-error-text); background-color: var(--message-error-bg); border-color: var(--message-error-border); }
        .modal {
            display: none; position: fixed; z-index: 2000; left: 0; top: 0;
            width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6);
            align-items: center; justify-content: center; animation: modalFadeIn 0.3s ease-out;
        }
        @keyframes modalFadeIn { from { opacity: 0; } to { opacity: 1; } }
        .modal-content {
            background-color: #fff; margin: auto; padding: 25px 30px;
            border: none; width: 90%; max-width: 750px; /* Border kaldırıldı */
            border-radius: 8px; position: relative; box-shadow: 0 5px 20px rgba(0,0,0,0.2); /* Gölge yumuşatıldı */
            animation: modalSlideIn 0.3s ease-out;
        }
        @keyframes modalSlideIn { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-close {
            color: #aaa; float: right; font-size: 30px; font-weight: bold;
            position: absolute; top: 10px; right: 20px; line-height: 1;
        }
        .modal-close:hover, .modal-close:focus { color: black; text-decoration: none; cursor: pointer; }
        .modal-title { margin-top:0; margin-bottom: 20px; font-size: 1.6rem; font-weight: 500; }
        .order-details-table { width: 100%; margin-top: 15px; border-collapse: collapse; font-size: 0.9rem;}
        .order-details-table th, .order-details-table td { padding: 10px; border-bottom: 1px solid var(--asikzade-border); text-align: left;}
        .order-details-table th { background-color: #f8f8f8; }
        .order-details-table img.product-thumbnail { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; margin-right: 10px; vertical-align: middle;}
        .order-detail-section { margin-bottom: 15px; font-size: 0.95rem; }
        .order-detail-section strong { display: inline-block; min-width: 150px; color: var(--asikzade-gray); font-weight: 500; } /* Ağırlık eklendi */
        .order-detail-section span { font-weight: 500; }
        .modal-footer { margin-top: 25px; text-align: right; padding-top: 15px; border-top: 1px solid var(--asikzade-border); } /* Padding ve border eklendi */
        .modal-footer button {
            background-color: var(--asikzade-gray); color: white; border: none;
            padding: 8px 18px; border-radius: 5px; cursor: pointer; /* Padding ve radius ayarı */
            transition: background-color 0.2s ease;
        }
        .modal-footer button:hover { background-color: #555; }
        .footer {
            background-color: var(--asikzade-content-bg); padding: 40px 0 20px; color: var(--asikzade-dark-text);
            margin-top: auto; border-top: 1px solid var(--asikzade-border);
        }
        .footer-content { max-width: 1200px; margin: 0 auto; padding: 0 50px; text-align: center; }
        .footer-content p { font-size: 0.9rem; color: var(--asikzade-gray); }

        /* Responsive Ayarlamalar */
        @media (max-width: 992px) {
             .dashboard-container { flex-direction: column; }
             .dashboard-sidebar { flex: 0 0 auto; width:100%; border-radius: 8px 8px 0 0; border-right: none; border-bottom: 1px solid var(--asikzade-border);}
             .dashboard-sidebar ul { display: flex; flex-wrap: wrap; justify-content: center; }
             .dashboard-sidebar ul li a { border-left: none; border-bottom: 3px solid transparent; padding: 10px 15px;}
             .dashboard-sidebar ul li a.active { border-bottom-color: var(--asikzade-green); border-left-color:transparent;}
             .dashboard-sidebar h2 { text-align: center; padding-bottom: 10px; margin-bottom: 10px;}
        }
        @media (max-width: 768px) {
            .header { padding: 12px 20px; }
            .logo-container img { height: 40px; } .logo-text { font-size: 18px; }
            .nav-user-icon, .nav-cart-icon { width: 32px; height: 32px; }
            .dashboard-page-wrapper { padding-top: 90px; padding-bottom: 40px; }
            .dashboard-content { padding: 20px; }
            .tab-content h3 { font-size: 1.5rem; }
            .orders-table { font-size: 0.85rem; }
            .orders-table th, .orders-table td { padding: 8px; }
            .orders-table .order-inspect-btn, .orders-table .order-cancel-btn { font-size: 0.8rem; padding: 5px 8px; }
            .modal-content { width: 95%; padding: 20px;}
            .modal-title {font-size: 1.3rem;}
        }
         @media (max-width: 576px) { /* Daha küçük ekranlar için ek ayarlar */
            .header { padding: 10px 15px; }
            .logo-container { gap: 5px;}
            .logo-container img { height: 36px; }
            .logo-text { font-size: 17px; }
            .user-actions-group { gap: 10px; }
            .dashboard-sidebar ul li a { font-size: 0.9rem; padding: 8px 12px;}
            .dashboard-content { padding: 15px; }
            .tab-content h3 { font-size: 1.3rem; margin-bottom: 20px;}
            .orders-table { font-size: 0.8rem; }
            .actions-cell form, .actions-cell button { display: block; width: 100%; margin-bottom: 5px;}
            .actions-cell button { margin-left:0; margin-right: 0;}
        }
    </style>
</head>
<body>
    <header class="header" id="mainHeader">
        <a href="/index.php" class="logo-container"> <!-- YOLU / İLE BAŞLATIN -->
            <img src="https://i.imgur.com/rdZuONP.png" alt="Aşıkzade Logo">
            <span class="logo-text"></span> <!-- Metni ekledim -->
        </a>
        <nav class="main-nav">
            <div class="user-actions-group">
                <div class="profile-dropdown">
                    <span class="nav-user-icon" tabindex="0" aria-label="Hesabım Menüsü">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </span>
                    <div class="dropdown-menu">
                        <div class="dropdown-user-info">
                            <span class="user-name"><?php echo htmlspecialchars($welcome_name); ?></span>
                            <span class="user-email"><?php echo htmlspecialchars($user_email); ?></span>
                        </div>
                        <a href="/dashboard.php?tab=profilim" data-tab-link="profilim">Profilim</a>
                        <a href="/dashboard.php?tab=siparislerim" data-tab-link="siparislerim">Siparişlerim</a>
                        <a href="/logout.php" class="logout-link">Çıkış Yap</a>  <!-- YOLU / İLE BAŞLATIN -->
                    </div>
                </div>
                <a href="/sepet.php" class="nav-cart-icon" aria-label="Sepetim"> <!-- YOLU / İLE BAŞLATIN -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    <?php if ($cart_item_count > 0): ?>
                        <span class="cart-badge"><?php echo htmlspecialchars($cart_item_count); ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </nav>
    </header>

    <main class="dashboard-page-wrapper">
        <div class="dashboard-container">
            <aside class="dashboard-sidebar">
                <h2>Hesabım</h2>
                <ul>
                    <li><a href="/dashboard.php?tab=siparislerim" class="<?php echo ($active_tab === 'siparislerim' ? 'active' : ''); ?>" data-tab="siparislerim">Siparişlerim</a></li>
                    <li><a href="/dashboard.php?tab=profilim" class="<?php echo ($active_tab === 'profilim' ? 'active' : ''); ?>" data-tab="profilim">Profil Bilgilerim</a></li>
                    <li><a href="/logout.php">Çıkış Yap</a></li> <!-- YOLU / İLE BAŞLATIN -->
                </ul>
            </aside>
            <section class="dashboard-content">
                <?php if ($success_message_dashboard): ?>
                    <div class="message-box message-success">
                        <?php echo htmlspecialchars($success_message_dashboard); ?>
                    </div>
                <?php endif; ?>
                <?php if ($error_message_dashboard): ?>
                    <div class="message-box message-error">
                        <?php echo htmlspecialchars($error_message_dashboard); ?>
                    </div>
                <?php endif; ?>

                <div id="siparislerim" class="tab-content <?php echo ($active_tab === 'siparislerim' ? 'active' : ''); ?>">
                    <h3>Siparişlerim</h3>
                    <?php if (!empty($kullanici_siparisleri)): ?>
                        <div style="overflow-x:auto;"> <!-- Mobil için tablo kaydırma -->
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Sipariş No</th>
                                    <th>Tarih</th>
                                    <th>Toplam Tutar</th>
                                    <th>Durum</th>
                                    <th style="text-align:center;">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kullanici_siparisleri as $siparis): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars(substr($siparis['id'], 0, 8)); ?>...</td>
                                    <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($siparis['siparis_tarihi']))); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($siparis['toplam_tutar'], 2, ',', '.')); ?> TL</td>
                                    <td>
                                        <?php
                                            $status_class_raw = strtolower($siparis['siparis_durumu']);
                                            // Türkçe karakterleri ve boşlukları tireye çevir
                                            $status_class = str_replace(['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', ' '], ['i', 'g', 'u', 's', 'o', 'c', '-'], $status_class_raw);
                                            $status_class = preg_replace('/[^a-z0-9-]+/', '', $status_class); // Sadece harf, rakam ve tire kalsın
                                        ?>
                                        <span class="status-<?php echo htmlspecialchars($status_class); ?>">
                                            <?php echo htmlspecialchars(ucfirst($siparis['siparis_durumu'])); ?>
                                        </span>
                                    </td>
                                    <td class="actions-cell">
                                        <button class="order-inspect-btn" data-order-id="<?php echo htmlspecialchars($siparis['id']); ?>">İncele</button>
                                        <?php if ($siparis['siparis_durumu'] === 'beklemede' || $siparis['siparis_durumu'] === 'hazırlanıyor'): ?>
                                            <form action="/dashboard.php?tab=siparislerim" method="POST" style="display:inline;" onsubmit="return confirm('Bu siparişi iptal etmek istediğinizden emin misiniz?');">
                                                <input type="hidden" name="action" value="iptal_et_siparis">
                                                <input type="hidden" name="siparis_id_iptal" value="<?php echo htmlspecialchars($siparis['id']); ?>">
                                                <button type="submit" class="order-cancel-btn">İptal Et</button>
                                            </form>
                                        <?php else: ?>
                                             <button class="order-cancel-btn" disabled>İptal Et</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    <?php else: ?>
                        <p>Henüz kayıtlı bir siparişiniz bulunmamaktadır.</p>
                        <p><a href="/index.php" style="color:var(--asikzade-green); text-decoration:none; font-weight:500;">Hemen Alışverişe Başla!</a></p> <!-- YOLU / İLE BAŞLATIN -->
                    <?php endif; ?>
                </div>

                <div id="profilim" class="tab-content <?php echo ($active_tab === 'profilim' ? 'active' : ''); ?>">
                    <h3>Profil Bilgilerim</h3>
                     <div class="profile-info-group">
                        <strong>Ad Soyad:</strong>
                        <span><?php echo htmlspecialchars($welcome_name); ?></span>
                    </div>
                    <div class="profile-info-group">
                        <strong>E-posta:</strong>
                        <span><?php echo htmlspecialchars($user_email); ?></span>
                    </div>
                    <div class="profile-info-group">
                        <strong>Telefon:</strong>
                        <span><?php echo htmlspecialchars(!empty($profil_telefon) ? $profil_telefon : 'Belirtilmemiş'); ?></span>
                    </div>
                    <div class="profile-info-group">
                        <strong>Varsayılan Adres:</strong>
                        <span><?php echo nl2br(htmlspecialchars(!empty($profil_adres) ? $profil_adres : 'Belirtilmemiş')); ?></span>
                    </div>
                    <p style="margin-top:25px; font-size:0.9rem; color: var(--asikzade-gray);">
                        Profil bilgilerinizi güncellemek veya şifrenizi değiştirmek için lütfen
                        <a href="mailto:destek@asikzade.com.tr" style="color:var(--asikzade-green);">destek ekibimizle</a> iletişime geçin.
                    </p>
                </div>
            </section>
        </div>
    </main>

    <div id="orderDetailModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">×</span>
            <h4 class="modal-title">Sipariş Detayları</h4>
            <div id="modalOrderDetailContent">
                <p>Yükleniyor...</p>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal()">Kapat</button> <!-- type="button" eklendi -->
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <p>© <?php echo date("Y"); ?> Aşıkzade. Tüm hakları saklıdır.</p>
        </div>
    </footer>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebarLinks = document.querySelectorAll('.dashboard-sidebar ul li a[data-tab]');
        const tabContents = document.querySelectorAll('.dashboard-content .tab-content');
        const orderInspectButtons = document.querySelectorAll('.order-inspect-btn');
        const modal = document.getElementById('orderDetailModal');
        const modalContentDiv = document.getElementById('modalOrderDetailContent');
        const closeModalBtns = document.querySelectorAll('.modal-close, .modal-footer button'); // Kapatma butonlarını seç
        const dropdownTabLinks = document.querySelectorAll('.dropdown-menu a[data-tab-link]');

        function showTab(tabId) {
            tabContents.forEach(content => content.classList.remove('active'));
            sidebarLinks.forEach(link => link.classList.remove('active'));
            const activeContent = document.getElementById(tabId);
            const activeLink = document.querySelector(`.dashboard-sidebar ul li a[data-tab="${tabId}"]`);
            if (activeContent) activeContent.classList.add('active');
            if (activeLink) activeLink.classList.add('active');

            // Dropdown menüdeki linklerin aktif durumunu da senkronize et
            dropdownTabLinks.forEach(ddLink => {
                ddLink.classList.toggle('active', ddLink.getAttribute('data-tab-link') === tabId);
            });
        }

        function handleTabNavigation(event, linkElement) {
            // Eğer logout linkiyse, normal davranışına izin ver
            if (linkElement.getAttribute('href') === '/logout.php') return true; // YOLU / İLE BAŞLATIN

            event.preventDefault(); // Diğer linkler için varsayılan davranışı engelle
            const tabId = linkElement.getAttribute('data-tab') || linkElement.getAttribute('data-tab-link');
            if (tabId) {
                const url = new URL(window.location);
                url.searchParams.set('tab', tabId);
                window.history.pushState({}, '', url); // URL'yi güncelle (sayfa yenilenmeden)
                showTab(tabId);

                // Eğer dropdown içinden tıklandıysa, dropdown'ı kapat
                const openDropdown = linkElement.closest('.dropdown-menu');
                if (openDropdown) {
                    // Gerekirse dropdown'ı gizleme mantığını buraya ekleyin
                    // Örneğin: openDropdown.style.display = 'none';
                    // Veya hover ile çalışıyorsa, focus'u kaldırabilirsiniz.
                    linkElement.blur(); // Focus'u kaldırarak hover efektinin bitmesini sağlayabilir
                }
            }
            return false;
        }

        sidebarLinks.forEach(link => {
            link.addEventListener('click', function (event) {
                handleTabNavigation(event, this);
            });
        });
        dropdownTabLinks.forEach(link => {
             link.addEventListener('click', function (event) {
                handleTabNavigation(event, this);
            });
        });

        // Sayfa yüklendiğinde URL'deki tab'ı kontrol et ve göster
        const initialTab = new URLSearchParams(window.location.search).get('tab');
        if (initialTab && document.getElementById(initialTab)) {
            showTab(initialTab);
        } else {
            // Varsayılan olarak 'siparislerim' tabını göster
            const defaultTabLink = document.querySelector('.dashboard-sidebar ul li a[data-tab="siparislerim"]');
            if (defaultTabLink) showTab(defaultTabLink.getAttribute('data-tab'));
            else { // Eğer 'siparislerim' yoksa ilk bulunan tab'ı göster
                 const firstTabLink = document.querySelector('.dashboard-sidebar ul li a[data-tab]');
                 if(firstTabLink) showTab(firstTabLink.getAttribute('data-tab'));
            }
        }

        // Modal işlemleri
        function openModal() {
            if(modal) modal.style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Arka plan kaymasını engelle
        }
        window.closeModal = function() { // Fonksiyonu global yap
            if(modal) modal.style.display = 'none';
            if(modalContentDiv) modalContentDiv.innerHTML = '<p>Yükleniyor...</p>'; // İçeriği sıfırla
            document.body.style.overflow = ''; // Kaymayı geri aç
        }

        closeModalBtns.forEach(btn => btn.onclick = window.closeModal); // Tüm kapatma butonlarına ata

        window.addEventListener('click', function(event) { // Dışa tıklayınca kapat
            if (event.target == modal) {
                window.closeModal();
            }
        });
         document.addEventListener('keydown', function(event) { // Esc ile kapat
            if (event.key === "Escape" && modal && modal.style.display === 'flex') {
                window.closeModal();
            }
        });

        orderInspectButtons.forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                openModal();
                modalContentDiv.innerHTML = `<p style="text-align:center; padding:20px;"><b>Sipariş No:</b> #${orderId.substring(0,8)}... detayları yükleniyor...</p>`;

                // get_order_details.php'nin yolunu projenizin yapısına göre ayarlayın (örn: /api/get_order_details.php)
                fetch(`/get_order_details.php?order_id=${orderId}`) // YOLU / İLE BAŞLATIN (Eğer kök dizindeyse)
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => {
                                throw new Error(`Sunucu hatası (${response.status}). Yanıt: ${text.substring(0, 200)}`);
                            });
                        }
                        return response.text().then(text => {
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                console.error("JSON parse edilemedi. get_order_details.php'den gelen ham yanıt:", text);
                                throw new Error(`Sunucudan beklenmedik yanıt formatı. Hata: ${e.message}.`);
                            }
                        });
                    })
                    .then(data => {
                        if (data.error) {
                            modalContentDiv.innerHTML = `<p style="color:var(--message-error-text); padding:20px; text-align:center;">Hata: ${data.error}</p>`;
                        } else {
                            let detailsHtml = '';
                            if(data.order_info) {
                                detailsHtml += `<div class="order-detail-section"><strong>Sipariş No:</strong> <span>#${String(data.order_info.id || '').substring(0,8)}...</span></div>`;
                                detailsHtml += `<div class="order-detail-section"><strong>Sipariş Tarihi:</strong> <span>${data.order_info.siparis_tarihi ? new Date(data.order_info.siparis_tarihi).toLocaleString('tr-TR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'N/A'}</span></div>`;
                                detailsHtml += `<div class="order-detail-section" style="margin-bottom: 10px;"><strong>Teslimat Adresi:</strong><br><div style="margin-top:5px; padding:8px; background-color:#f9f9f9; border-radius:4px; font-size:0.9em;">${(data.order_info.teslimat_adresi || 'Belirtilmemiş').replace(/\n/g, '<br>')}</div></div>`;
                                detailsHtml += `<div class="order-detail-section"><strong>Toplam Tutar:</strong> <span>${data.order_info.toplam_tutar ? parseFloat(data.order_info.toplam_tutar).toFixed(2).replace('.',',') : '0,00'} TL</span></div>`;

                                let statusClassModal = 'bilinmiyor';
                                if (data.order_info.siparis_durumu) {
                                    let status_raw_modal = String(data.order_info.siparis_durumu).toLowerCase();
                                    statusClassModal = status_raw_modal.replace(/[^a-z0-9]+/g, '-').replace('ı','i').replace('ğ','g').replace('ü','u').replace('ş','s').replace('ö','o').replace('ç','c');
                                }
                                detailsHtml += `<div class="order-detail-section"><strong>Sipariş Durumu:</strong> <span class="status-${statusClassModal}">${data.order_info.siparis_durumu || 'Bilinmiyor'}</span></div>`;
                            }

                            if (data.items && data.items.length > 0) {
                                detailsHtml += '<h5 style="margin-top:25px; margin-bottom:15px; font-size:1.2rem; color:var(--asikzade-dark-text); padding-bottom:10px; border-bottom:1px solid var(--asikzade-border);">Sipariş İçeriği</h5>';
                                detailsHtml += '<div style="overflow-x:auto;"><table class="order-details-table"><thead><tr><th></th><th>Ürün Adı</th><th>Miktar</th><th>Birim Fiyat</th><th>Ara Toplam</th></tr></thead><tbody>';
                                
                                const productsDataPHP = <?php echo json_encode(isset($products) && is_array($products) ? array_values($products) : []); ?>;

                                data.items.forEach(item => {
                                    let imageUrl = 'https://via.placeholder.com/50x50.png?text=?';
                                    const itemNameFromOrder = item.urun_adi ? String(item.urun_adi).toLowerCase().trim() : null;
                                    
                                    if (itemNameFromOrder && Array.isArray(productsDataPHP)) {
                                        const foundProduct = productsDataPHP.find(p => p && p.name && String(p.name).toLowerCase().trim() === itemNameFromOrder);
                                        if (foundProduct) {
                                            imageUrl = foundProduct.image || foundProduct.hero_image || foundProduct.thumbnail || imageUrl;
                                        }
                                    }
                                    
                                    detailsHtml += `<tr>
                                        <td><img src="${imageUrl}" alt="${item.urun_adi ? String(item.urun_adi).substring(0,50) : 'Ürün'}" class="product-thumbnail"></td>
                                        <td>${item.urun_adi || 'Bilinmeyen Ürün'}</td>
                                        <td>${item.miktar || 0}</td>
                                        <td>${item.birim_fiyat ? parseFloat(item.birim_fiyat).toFixed(2).replace('.', ',') : '0,00'} TL</td>
                                        <td>${item.ara_toplam ? parseFloat(item.ara_toplam).toFixed(2).replace('.', ',') : '0,00'} TL</td>
                                    </tr>`;
                                });
                                detailsHtml += '</tbody></table></div>';
                            } else {
                                detailsHtml += '<p style="margin-top:20px; text-align:center; color:var(--asikzade-gray);">Bu siparişe ait ürün detayı bulunamadı.</p>';
                            }
                            modalContentDiv.innerHTML = detailsHtml;
                        }
                    })
                    .catch(error => {
                        modalContentDiv.innerHTML = `<p style="color:var(--message-error-text); padding:20px; text-align:center;">Sipariş detayları yüklenirken bir hata oluştu. Lütfen konsolu kontrol edin.</p>`;
                        console.error('Dashboard - Sipariş detayları çekilirken hata:', error);
                    });
            });
        });
    });
</script>
</body>
</html>
<?php
ob_end_flush(); // Çıktı tamponunu gönder
?>