<?php
require_once 'admin_config.php'; // supabase_api_request() ve GÜNCELLENMİŞ admin_check_login() burada olmalı

// 1. YENİ admin_check_login() FONKSİYONU COOKIE'Yİ KONTROL ETMELİ
// admin_config.php içindeki admin_check_login() fonksiyonunu şu şekilde güncelleyin:
/*
function admin_check_login($redirect_if_not_logged_in = true) {
    $cookie_name = 'asikzade_admin_session'; // admin_login_process.php'de kullanılan cookie adı
    if (isset($_COOKIE[$cookie_name])) {
        // İdealde, cookie değeri bir token ise, bu token'ı veritabanında kontrol et.
        // Şimdilik sadece varlığını kontrol ediyoruz veya içindeki basit bilgileri okuyoruz.
        $admin_data_json = $_COOKIE[$cookie_name];
        $admin_data = json_decode($admin_data_json, true);

        if ($admin_data && isset($admin_data['admin_id'])) { // Basit bir kontrol
            // İsteğe bağlı: Cookie'deki bilgileri global bir değişkene veya bir sınıfa ata
            // global $current_admin_user;
            // $current_admin_user = $admin_data;
            return true; // Admin giriş yapmış
        }
    }

    if ($redirect_if_not_logged_in) {
        // Giriş yapılmamışsa, login sayfasına yönlendir (hata mesajı olmadan)
        header('Location: admin_login.php');
        exit;
    }
    return false; // Admin giriş yapmamış
}
*/
$current_admin_user = admin_check_login(); // Yetkisiz erişimi engelle, returns user data or redirects

// Admin bilgilerini $current_admin_user'dan al
$admin_email_display = 'Admin'; // Varsayılan
$admin_id_from_cookie = null;   // Varsayılan

if ($current_admin_user && isset($current_admin_user['admin_email'])) {
    $admin_email_display = $current_admin_user['admin_email'];
}
if ($current_admin_user && isset($current_admin_user['admin_id'])) {
    $admin_id_from_cookie = $current_admin_user['admin_id'];
}


if (file_exists('products_data.php')) {
    include 'products_data.php';
} else {
    $products = [];
    error_log("admin_dashboard.php: products_data.php dosyası bulunamadı.");
}

// 3. URL'den başarı/hata mesajlarını al
$success_message = null;
$error_message = null;
if (isset($_GET['success_msg'])) {
    $success_message = htmlspecialchars(urldecode($_GET['success_msg']));
}
if (isset($_GET['error_msg'])) {
    $error_message = htmlspecialchars(urldecode($_GET['error_msg']));
}
// Mevcut GET parametrelerini mesajlar olmadan sakla (yönlendirmeler için)
$current_get_params = $_GET;
unset($current_get_params['success_msg'], $current_get_params['error_msg']);


// --- SİPARİŞ DURUMU GÜNCELLEME İŞLEMİ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'guncelle_siparis_durumu') {
    $siparis_id_guncelle = $_POST['siparis_id_guncelle'] ?? null;
    $yeni_durum = $_POST['yeni_siparis_durumu'] ?? null;
    $gecerli_durumlar = ['beklemede', 'hazırlanıyor', 'gönderildi', 'teslim edildi', 'iptal edildi'];
    
    $redirect_params_after_update = $current_get_params; // Sayfalama ve filtreleri koru

    if ($siparis_id_guncelle && $yeni_durum && in_array($yeni_durum, $gecerli_durumlar)) {
        $updateData = ['siparis_durumu' => $yeni_durum];
        // service_role key ile güncelleme yapılıyorsa 4. parametre true olmalı
        $updateResult = supabase_api_request('PATCH', '/rest/v1/siparisler?id=eq.' . $siparis_id_guncelle, $updateData, [], true);
        if (!$updateResult['error'] && ($updateResult['http_code'] === 200 || $updateResult['http_code'] === 204)) {
            $redirect_params_after_update['success_msg'] = urlencode("Sipariş #" . substr($siparis_id_guncelle,0,8) . " durumu başarıyla '" . htmlspecialchars($yeni_durum) . "' olarak güncellendi.");
        } else {
            $api_error = $updateResult['error']['message'] ?? ($updateResult['data']['message'] ?? 'Bilinmeyen API hatası');
            $redirect_params_after_update['error_msg'] = urlencode("Sipariş durumu güncellenirken hata: " . htmlspecialchars($api_error));
        }
    } else {
        $redirect_params_after_update['error_msg'] = urlencode("Geçersiz sipariş ID veya durum bilgisi.");
    }
    header('Location: admin_dashboard.php?' . http_build_query($redirect_params_after_update));
    exit;
}

// Filtreleme ve arama için değerleri al
$search_query = trim($_GET['q'] ?? '');
$filter_status = $_GET['status'] ?? '';
$current_page = max(1, intval($_GET['page'] ?? 1));
$items_per_page = 10;
$offset = ($current_page - 1) * $items_per_page;

// --- SİPARİŞLERİ ÇEKME ---
$base_api_path = '/rest/v1/siparisler';
$select_fields = 'id,kullanici_id,siparis_tarihi,toplam_tutar,siparis_durumu,teslimat_adresi,kullanicilar(email,ad,soyad)';
$api_params_array = [];
$count_api_params_array = [];

$search_error_occurred = false; // Arama hatası için flag

if (!empty($search_query)) {
    if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $search_query)) {
        $api_params_array[] = 'id=eq.' . urlencode($search_query);
        $count_api_params_array[] = 'id=eq.' . urlencode($search_query);
    } else {
        // Sadece ID ile arama hata mesajını burada set etmiyoruz, doğrudan $error_message'a atayacağız.
        // Ancak bu mesajın diğer error_message'ları ezmemesine dikkat etmeliyiz.
        if (!$error_message) { // Eğer başka bir hata mesajı yoksa bunu göster
            $error_message = "Arama şu anda sadece tam Sipariş ID (UUID formatında) ile çalışmaktadır.";
        }
        $search_error_occurred = true; // Arama yapılamadığı için flag set et
    }
}

if (!empty($filter_status)) {
    $api_params_array[] = 'siparis_durumu=eq.' . urlencode($filter_status);
    $count_api_params_array[] = 'siparis_durumu=eq.' . urlencode($filter_status);
}

$total_orders = 0;
if (!$search_error_occurred) { // Eğer arama hatası yoksa toplam sayıyı çek
    $count_query_string = '';
    if (!empty($count_api_params_array)) {
        $count_query_string = '&' . implode('&', $count_api_params_array);
    }
    $countResult = supabase_api_request('GET', $base_api_path . '?select=id' . $count_query_string, [], ['Prefer: count=exact'], true);

    if (!$countResult['error'] && isset($countResult['headers']['content-range'])) {
        $range = explode('/', $countResult['headers']['content-range']);
        if(isset($range[1])) $total_orders = intval($range[1]);
    } else {
        $tempCountResult = supabase_api_request('GET', $base_api_path . '?select=id' . $count_query_string, [], [], true);
        if (!$tempCountResult['error'] && isset($tempCountResult['data'])) {
            $total_orders = count($tempCountResult['data']);
        }
        if ($countResult['error'] || !isset($countResult['headers']['content-range'])) {
           // error_log("Admin Dashboard: Content-Range header alınamadı veya API hatası. Sipariş sayısı fallback ile hesaplandı. Error: " . ($countResult['error']['message'] ?? 'Content-Range missing'));
        }
    }
}


$total_pages = $items_per_page > 0 ? ceil($total_orders / $items_per_page) : 1;
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $items_per_page;
}


$tum_siparisler = [];
if (!$search_error_occurred) { // Eğer arama hatası yoksa siparişleri çek
    $api_path_paginated = $base_api_path . '?select=' . $select_fields;
    if (!empty($api_params_array)) {
        $api_path_paginated .= '&' . implode('&', $api_params_array);
    }
    $api_path_paginated .= '&order=siparis_tarihi.desc&offset=' . $offset . '&limit=' . $items_per_page;

    $siparislerResult = supabase_api_request('GET', $api_path_paginated, [], [], true);

    if (!$siparislerResult['error'] && !empty($siparislerResult['data'])) {
        $tum_siparisler = $siparislerResult['data'];
    } elseif ($siparislerResult['error'] && !$error_message) { // Eğer başka bir hata mesajı yoksa
        $error_message = "Siparişler yüklenirken hata: " . ($siparislerResult['error']['message'] ?? 'Bilinmeyen hata');
    }
}


// --- SİPARİŞ DURUM RAPORU İÇİN VERİ ÇEKME ---
$order_status_report = [];
$allOrdersForReportResult = supabase_api_request('GET', '/rest/v1/siparisler?select=siparis_durumu', [], [], true); // Tüm siparişleri çekiyoruz
if (!$allOrdersForReportResult['error'] && !empty($allOrdersForReportResult['data'])) {
    foreach ($allOrdersForReportResult['data'] as $order) {
        $status = $order['siparis_durumu'] ?? 'bilinmiyor';
        if (!isset($order_status_report[$status])) {
            $order_status_report[$status] = 0;
        }
        $order_status_report[$status]++;
    }
} else {
    // error_log("Admin Dashboard: Sipariş durum raporu için veri çekilemedi. Error: " . ($allOrdersForReportResult['error']['message'] ?? 'Unknown'));
}

$status_order = ['beklemede', 'hazırlanıyor', 'gönderildi', 'teslim edildi', 'iptal edildi', 'bilinmiyor'];
$ordered_status_report = [];
foreach($status_order as $status_key) {
    if(isset($order_status_report[$status_key])) {
        $ordered_status_report[$status_key] = $order_status_report[$status_key];
    }
}

// $success_message ve $error_message zaten yukarıda URL'den alındı.
// Session'dan unset etmeye gerek yok.

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - Sipariş Yönetimi</title>
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
            --asikzade-red: #c0392b;
            --admin-header-height: 70px; /* Header yüksekliği */
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif; }
        body {
            background-color: var(--asikzade-content-bg); /* Ana site arkaplanı */
            color: var(--asikzade-dark-text);
            line-height: 1.6;
            display: flex;
            flex-direction: column; /* Header ve main'i alt alta sırala */
            min-height: 100vh;
        }

        /* ANA SİTE HEADER STİLLERİ (login.php'den alınabilir) */
        .header {
            position: fixed; /* Sabit header */
            top: 0;
            left: 0;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px; /* Padding ayarı */
            z-index: 1000;
            background: rgba(254, 246, 230, 0.97); /* Hafif transparan ana site bg */
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            height: var(--admin-header-height);
        }
        .logo-container { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .logo-container img { height: 45px; /* Biraz küçülttüm */ }
        .logo-text { font-size: 20px; font-weight: 600; color: var(--asikzade-dark-text); }

        .admin-header-nav { display: flex; align-items: center; gap: 25px; }
        .admin-header-nav .welcome-text { font-size: 0.9rem; color: var(--asikzade-gray); }
        .admin-header-nav .logout-link {
            color: var(--asikzade-red); text-decoration: none; font-weight: 500; font-size: 0.9rem;
            padding: 6px 12px; border: 1px solid var(--asikzade-red); border-radius: 20px; transition: all 0.3s ease;
        }
        .admin-header-nav .logout-link:hover { background-color: var(--asikzade-red); color: var(--asikzade-light-text); }

        /* ADMİN İÇERİK ALANI */
        .admin-main-content-wrapper {
            padding-top: var(--admin-header-height); /* Header için boşluk */
            width: 100%;
            max-width: 1300px; /* İçerik genişliği */
            margin: 0 auto; /* Ortala */
            padding-left: 25px;
            padding-right: 25px;
            padding-bottom: 40px; /* Footer için boşluk */
            flex-grow: 1;
        }
        .admin-page-title {
            font-size: 2.2rem; font-weight: 700; color: var(--asikzade-dark-text);
            margin-top: 30px; margin-bottom: 30px; text-align: center;
        }

        /* Rapor Kartları */
        .status-report-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }
        .report-card {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.07);
            text-align: center;
            border-left: 5px solid var(--asikzade-green); /* Varsayılan renk */
        }
        .report-card h4 { font-size: 1rem; color: var(--asikzade-gray); margin-bottom: 8px; font-weight: 500; text-transform: capitalize;}
        .report-card .count { font-size: 2.2rem; font-weight: 700; color: var(--asikzade-dark-text); display: block; }
        .report-card.status-beklemede { border-left-color: #fd7e14; }
        .report-card.status-hazirlaniyor { border-left-color: #0dcaf0; }
        .report-card.status-gonderildi { border-left-color: #198754; }
        .report-card.status-teslim-edildi { border-left-color: var(--asikzade-green); }
        .report-card.status-iptal-edildi { border-left-color: var(--asikzade-red); }
        .report-card.status-bilinmiyor { border-left-color: var(--asikzade-gray); }


        .content-card {
            background-color: #fff; padding: 25px; border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 30px;
        }
        .content-card h3 {
            font-size: 1.4rem; margin-top:0; margin-bottom: 20px; font-weight: 600;
            padding-bottom:12px; border-bottom:1px solid var(--asikzade-border);
        }
        
        .filters-form { display: flex; gap: 20px; margin-bottom: 25px; align-items: flex-end; flex-wrap: wrap; }
        .filters-form .form-group { flex: 1; min-width: 220px; }
        .filters-form label { font-size: 0.9rem; color: var(--asikzade-gray); margin-bottom: 6px; display:block; font-weight:500; }
        .filters-form input[type="text"], .filters-form select {
            width: 100%; padding: 11px 14px; border: 1px solid #ccc; border-radius: 6px; font-size: 0.95rem;
            background-color: #fdfdfd;
        }
        .filters-form input:focus, .filters-form select:focus { border-color: var(--asikzade-green); outline:none; box-shadow: 0 0 0 2px rgba(139,168,109,0.2); }
        .filters-form button[type="submit"] {
            padding: 11px 28px; background-color: var(--asikzade-green); color: white; border: none;
            border-radius: 6px; cursor: pointer; font-size: 0.95rem; font-weight:500;
            transition: background-color 0.2s; white-space: nowrap;
        }
        .filters-form button[type="submit"]:hover { background-color: var(--asikzade-dark-green); }

        .orders-admin-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .orders-admin-table th, .orders-admin-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--asikzade-border); vertical-align: middle;}
        .orders-admin-table th { background-color: #f8f9fa; font-weight: 600; color: var(--asikzade-dark-text); }
        .orders-admin-table tbody tr:hover { background-color: #fdfaf2; }
        .orders-admin-table td form { display: flex; align-items:center; gap: 8px; margin:0;}
        .orders-admin-table select.status-select {
            padding: 8px 10px; font-size:0.85rem; border-radius:5px; border:1px solid #ccc; flex-grow:1; min-width: 140px;
        }
        .orders-admin-table button.update-btn, .orders-admin-table button.detail-btn {
            font-size:0.8rem; padding: 8px 14px; border:none; border-radius:5px; cursor:pointer; color:white; font-weight:500;
            transition: opacity 0.2s; white-space: nowrap;
        }
        .orders-admin-table button.update-btn { background-color: var(--asikzade-green); }
        .orders-admin-table button.update-btn:hover { background-color: var(--asikzade-dark-green); }
        .orders-admin-table button.detail-btn { background-color: var(--asikzade-gray); }
        .orders-admin-table button.detail-btn:hover { background-color: #555; }

        .orders-admin-table .user-email-link {color: var(--asikzade-green); text-decoration:none;}
        .orders-admin-table .user-email-link:hover {text-decoration:underline;}
        .orders-admin-table .order-id-link { color: var(--asikzade-dark-text); text-decoration:none; font-weight:500;}
        .orders-admin-table .order-id-link:hover { color: var(--asikzade-green); }
        .status-beklemede { color: #fd7e14; font-weight: bold; }
        .status-hazirlaniyor { color: #0dcaf0; font-weight: bold; }
        .status-gonderildi { color: #198754; font-weight: bold; }
        .status-teslim-edildi { color: var(--asikzade-green); font-weight: bold; }
        .status-iptal-edildi { color: var(--asikzade-red); font-weight: bold; }


        .message-box { padding: 12px 18px; margin-bottom: 20px; border-radius: 6px; font-size: 0.95rem; border: 1px solid transparent; }
        .message-success { background-color: #d1e7dd; color: #0f5132; border-color: #badbcc; }
        .message-error { background-color: #f8d7da; color: #842029; border-color: #f5c2c7; }

        .pagination { margin-top: 30px; text-align: center; }
        .pagination a, .pagination span {
            display: inline-block; padding: 8px 14px; margin: 0 4px; border: 1px solid var(--asikzade-border);
            text-decoration: none; color: var(--asikzade-dark-text); border-radius: 5px; font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        .pagination a:hover { background-color: #f0eada; border-color: var(--asikzade-dark-green); color: var(--asikzade-dark-text); }
        .pagination span.current { background-color: var(--asikzade-green); color: white; border-color: var(--asikzade-green); }
        .pagination span.disabled { color: #aaa; border-color: #eee; cursor: not-allowed; }

        .modal {
            display: none; position: fixed; z-index: 1050; left: 0; top: 0;
            width: 100%; height: 100%; overflow: auto; background-color: rgba(45,62,42,0.6); /* Koyu yeşil transparan */
            align-items: center; justify-content: center; animation: modalFadeIn 0.3s;
        }
        @keyframes modalFadeIn { from{opacity:0} to{opacity:1} }
        .modal-content {
            background-color: #fff; margin: auto; padding: 25px 30px;
            border: none; width: 90%; max-width: 800px;
            border-radius: 10px; position: relative; box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            animation: modalSlideIn 0.3s;
        }
        @keyframes modalSlideIn { from{transform: translateY(-50px); opacity:0} to{transform: translateY(0); opacity:1} }
        .modal-close {
            color: var(--asikzade-gray); float: right; font-size: 32px; font-weight: bold;
            position: absolute; top: 12px; right: 18px; line-height: 1; cursor: pointer;
            transition: color 0.2s ease;
        }
        .modal-close:hover, .modal-close:focus { color: var(--asikzade-dark-text); }
        .modal-title { margin-top:0; margin-bottom: 25px; font-size: 1.6rem; font-weight: 600; color: var(--asikzade-dark-text); }
        
        .order-details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 15px 25px; margin-bottom: 20px;}
        .order-detail-section { font-size: 0.95rem; }
        .order-detail-section strong { display: block; font-weight: 500; color: var(--asikzade-gray); margin-bottom: 4px; font-size: 0.85rem; text-transform: uppercase;}
        .order-detail-section span { font-weight: 500; color: var(--asikzade-dark-text); }
        .order-detail-section span.status { padding: 4px 10px; border-radius: 20px; color: white !important; font-size:0.8rem; display:inline-block; font-weight: bold; text-transform: capitalize;}

        .modal-order-items-table { width: 100%; margin-top: 20px; border-collapse: collapse; font-size: 0.9rem;}
        .modal-order-items-table th, .modal-order-items-table td { padding: 10px 12px; border-bottom: 1px solid var(--asikzade-border); text-align: left; vertical-align: middle;}
        .modal-order-items-table th { background-color: #f8f9fa; font-weight: 500; }
        .modal-order-items-table img.product-thumbnail { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; margin-right: 12px;}
        
        .modal-footer { margin-top: 30px; text-align: right; }
        .modal-footer button {
            background-color: var(--asikzade-gray); color: white; border: none;
            padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size:0.9rem;
            transition: background-color 0.2s ease;
        }
        .modal-footer button:hover { background-color: #505050; } /* Biraz daha koyu gri */

        @media (max-width: 768px) {
            .admin-header { padding: 10px 20px; height: 60px;}
            .admin-main-content-wrapper { padding-top: 60px; padding-left: 15px; padding-right: 15px; }
            .admin-page-title { font-size: 1.8rem; margin-top: 20px; margin-bottom: 20px;}
            .status-report-cards { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; }
            .report-card { padding: 15px; }
            .report-card .count { font-size: 1.8rem; }
            .filters-form { flex-direction: column; align-items: stretch; gap: 15px;}
            .filters-form .form-group { margin-bottom: 0;}
            .orders-admin-table { font-size: 0.85rem; }
            .orders-admin-table th, .orders-admin-table td { padding: 10px 8px; }
            .orders-admin-table select.status-select, .orders-admin-table button { font-size:0.75rem; padding: 7px 10px;}
            .modal-content { padding: 20px 15px;}
            .modal-title {font-size: 1.4rem;}
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="index.php" class="logo-container">
            <img src="https://i.imgur.com/rdZuONP.png" alt="Aşıkzade Logo">
            <span class="logo-text">AŞIKZADE</span> <!-- Logo text eklendi -->
        </a>
        <nav class="admin-header-nav">
            <span class="welcome-text">Hoş geldiniz, <?php echo htmlspecialchars($admin_email_display); ?>!</span>
            <a href="admin_logout.php" class="logout-link">Çıkış Yap</a>
        </nav>
    </header>

    <div class="admin-main-content-wrapper">
        <h1 class="admin-page-title">Admin Paneli - Sipariş Yönetimi</h1>

        <?php if ($success_message): ?>
            <div class="message-box message-success"><?php echo $success_message; // Zaten htmlspecialchars yapıldı ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message-box message-error"><?php echo $error_message; // Zaten htmlspecialchars yapıldı ?></div>
        <?php endif; ?>

        <!-- Sipariş Durum Raporu -->
        <div class="status-report-cards">
            <?php $total_all_orders_for_report = array_sum($ordered_status_report); ?>
            <div class="report-card" style="border-left-color: var(--asikzade-dark-text);">
                <h4>Toplam Sipariş</h4>
                <span class="count"><?php echo $total_all_orders_for_report; ?></span>
            </div>
            <?php foreach ($ordered_status_report as $status => $count):
                  $status_class = str_replace(' ', '-', htmlspecialchars(strtolower($status)));
            ?>
                <div class="report-card status-<?php echo $status_class; ?>">
                    <h4><?php echo htmlspecialchars(ucfirst($status)); ?></h4>
                    <span class="count"><?php echo $count; ?></span>
                </div>
            <?php endforeach; ?>
            <?php if (empty($ordered_status_report) && $total_all_orders_for_report == 0): ?>
                 <div class="report-card status-bilinmiyor">
                    <h4>Veri Yok</h4>
                    <span class="count">0</span>
                </div>
            <?php endif; ?>
        </div>


        <div class="content-card filters-card">
            <h3>Filtrele ve Sipariş Ara</h3>
            <form action="admin_dashboard.php" method="GET" class="filters-form">
                <input type="hidden" name="page" value="1">
                <div class="form-group">
                    <label for="q">Sipariş ID Ara (UUID)</label>
                    <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                </div>
                <div class="form-group">
                    <label for="status">Sipariş Durumu</label>
                    <select id="status" name="status">
                        <option value="">Tümü</option>
                        <option value="beklemede" <?php echo ($filter_status === 'beklemede' ? 'selected' : ''); ?>>Beklemede</option>
                        <option value="hazırlanıyor" <?php echo ($filter_status === 'hazırlanıyor' ? 'selected' : ''); ?>>Hazırlanıyor</option>
                        <option value="gönderildi" <?php echo ($filter_status === 'gönderildi' ? 'selected' : ''); ?>>Gönderildi</option>
                        <option value="teslim edildi" <?php echo ($filter_status === 'teslim edildi' ? 'selected' : ''); ?>>Teslim Edildi</option>
                        <option value="iptal edildi" <?php echo ($filter_status === 'iptal edildi' ? 'selected' : ''); ?>>İptal Edildi</option>
                    </select>
                </div>
                <button type="submit">Filtrele / Ara</button>
            </form>
        </div>

        <div class="content-card">
            <h3>Sipariş Listesi (Filtrelenmiş: <?php echo $total_orders; ?> Adet)</h3>
            <?php if (!empty($tum_siparisler)): ?>
                <div style="overflow-x:auto;">
                <table class="orders-admin-table">
                    <thead>
                        <tr>
                            <th>Sip. ID</th>
                            <th>Kullanıcı</th>
                            <th>Tarih</th>
                            <th>Tutar</th>
                            <th>Durum</th>
                            <th style="min-width:290px;">Durum Güncelle</th>
                            <th>Detay</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tum_siparisler as $siparis): ?>
                        <tr>
                            <td><a href="#" class="order-id-link admin-order-inspect-btn" data-order-id="<?php echo htmlspecialchars($siparis['id']); ?>">#<?php echo htmlspecialchars(substr($siparis['id'], 0, 8)); ?>..</a></td>
                            <td>
                                <?php
                                $kullanici_adi_soyadi = 'Misafir';
                                $kullanici_email = 'N/A';
                                if (isset($siparis['kullanicilar']) && !empty($siparis['kullanicilar'])) {
                                    $k_ad = $siparis['kullanicilar']['ad'] ?? '';
                                    $k_soyad = $siparis['kullanicilar']['soyad'] ?? '';
                                    if(trim($k_ad . $k_soyad) !== '') {
                                        $kullanici_adi_soyadi = trim(htmlspecialchars($k_ad) . ' ' . htmlspecialchars($k_soyad));
                                    }
                                    $kullanici_email = $siparis['kullanicilar']['email'] ?? 'E-posta yok';
                                }
                                echo $kullanici_adi_soyadi; // Zaten htmlspecialchars yapıldı
                                ?>
                                <br>
                                <small><a href="mailto:<?php echo htmlspecialchars($kullanici_email); ?>" class="user-email-link"><?php echo htmlspecialchars($kullanici_email); ?></a></small>
                            </td>
                            <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($siparis['siparis_tarihi']))); ?></td>
                            <td><?php echo htmlspecialchars(number_format($siparis['toplam_tutar'], 2, ',', '.')); ?> TL</td>
                            <td><span class="status-<?php echo str_replace(' ', '-', htmlspecialchars(strtolower($siparis['siparis_durumu']))); ?>"><?php echo htmlspecialchars(ucfirst($siparis['siparis_durumu'])); ?></span></td>
                            <td>
                                <form action="admin_dashboard.php?<?php echo http_build_query(array_merge($current_get_params, ['page' => $current_page])); ?>" method="POST">
                                    <input type="hidden" name="action" value="guncelle_siparis_durumu">
                                    <input type="hidden" name="siparis_id_guncelle" value="<?php echo htmlspecialchars($siparis['id']); ?>">
                                    <select name="yeni_siparis_durumu" class="status-select">
                                        <option value="beklemede" <?php echo ($siparis['siparis_durumu'] === 'beklemede' ? 'selected' : ''); ?>>Beklemede</option>
                                        <option value="hazırlanıyor" <?php echo ($siparis['siparis_durumu'] === 'hazırlanıyor' ? 'selected' : ''); ?>>Hazırlanıyor</option>
                                        <option value="gönderildi" <?php echo ($siparis['siparis_durumu'] === 'gönderildi' ? 'selected' : ''); ?>>Gönderildi</option>
                                        <option value="teslim edildi" <?php echo ($siparis['siparis_durumu'] === 'teslim edildi' ? 'selected' : ''); ?>>Teslim Edildi</option>
                                        <option value="iptal edildi" <?php echo ($siparis['siparis_durumu'] === 'iptal edildi' ? 'selected' : ''); ?>>İptal Edildi</option>
                                    </select>
                                    <button type="submit" class="update-btn">Güncelle</button>
                                </form>
                            </td>
                            <td>
                                <button class="detail-btn admin-order-inspect-btn" data-order-id="<?php echo htmlspecialchars($siparis['id']); ?>">İncele</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>

                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php
                    // $pagination_params zaten $current_get_params olarak yukarıda tanımlandı (mesajlar hariç)
                    $base_pagination_url = 'admin_dashboard.php?' . http_build_query($current_get_params);
                    $separator = empty($current_get_params) ? '' : '&';
                    if (empty($current_get_params) && $total_pages > 0 && $current_page == 1) $separator = "?"; // Hiç GET yoksa ve sayfa 1 ise ? ile başla
                    if (!empty($current_get_params)) $separator = '&'; else $separator = '?';


                    ?>
                    <?php if ($current_page > 1): ?>
                        <a href="<?php echo 'admin_dashboard.php?' . http_build_query(array_merge($current_get_params, ['page' => ($current_page - 1)])); ?>">« Önceki</a>
                    <?php else: ?>
                        <span class="disabled">« Önceki</span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $current_page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="<?php echo 'admin_dashboard.php?' . http_build_query(array_merge($current_get_params, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="<?php echo 'admin_dashboard.php?' . http_build_query(array_merge($current_get_params, ['page' => ($current_page + 1)])); ?>">Sonraki »</a>
                    <?php else: ?>
                        <span class="disabled">Sonraki »</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            <?php else: ?>
                 <?php if ($search_error_occurred && !empty($search_query) && !$error_message) : ?>
                    <p>"'<?php echo htmlspecialchars($search_query); ?>'" için sonuç bulunamadı. Lütfen geçerli bir Sipariş ID (UUID formatında) girin.</p>
                <?php elseif (!$error_message) : // Başka bir hata yoksa genel mesajı göster ?>
                    <p>Filtrelerinize uygun sipariş bulunamadı veya hiç sipariş yok.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="adminOrderDetailModal" class="modal">
        <div class="modal-content">
            <span class="modal-close admin-modal-close">×</span>
            <h4 class="modal-title">Sipariş Detayları</h4>
            <div id="adminModalOrderDetailContent">
                <p>Yükleniyor...</p>
            </div>
             <div class="modal-footer">
                <button type="button" class="admin-modal-close">Kapat</button>
            </div>
        </div>
    </div>

<script>
// JAVASCRIPT KODLARI DEĞİŞMEDEN AYNI KALACAK
document.addEventListener('DOMContentLoaded', function () {
    const inspectButtons = document.querySelectorAll('.admin-order-inspect-btn');
    const modal = document.getElementById('adminOrderDetailModal');
    const modalContentDiv = document.getElementById('adminModalOrderDetailContent');
    const closeButtons = document.querySelectorAll('.admin-modal-close');

    function openModal() {
        if(modal) modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeModal() {
        if(modal) modal.style.display = 'none';
        if(modalContentDiv) modalContentDiv.innerHTML = '<p>Yükleniyor...</p>';
        document.body.style.overflow = '';
    }

    closeButtons.forEach(btn => btn.addEventListener('click', closeModal));
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            closeModal();
        }
    });
     document.addEventListener('keydown', function(event) {
        if (event.key === "Escape" && modal && modal.style.display === 'flex') {
            closeModal();
        }
    });

    inspectButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const orderId = this.getAttribute('data-order-id');
            openModal();
            modalContentDiv.innerHTML = `<p style="text-align:center; padding:20px;">Sipariş #${orderId.substring(0,8)}... detayları yükleniyor...</p>`;

            fetch(`get_order_details.php?order_id=${orderId}&admin_view=true`)
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                           throw new Error('Network response error. Status: ' + response.status + '. Response: ' + text);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        modalContentDiv.innerHTML = `<p style="color:var(--asikzade-red); padding:20px; text-align:center;">Hata: ${data.error}</p>`;
                    } else {
                        let detailsHtml = '<div class="order-details-grid">';
                        if(data.order_info) {
                            detailsHtml += `<div class="order-detail-section"><strong>Sipariş No:</strong> <span>#${data.order_info.id ? data.order_info.id.substring(0,8) : 'N/A'}</span></div>`;
                            detailsHtml += `<div class="order-detail-section"><strong>Sipariş Tarihi:</strong> <span>${data.order_info.siparis_tarihi ? new Date(data.order_info.siparis_tarihi).toLocaleString('tr-TR', {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'}) : 'N/A'}</span></div>`;
                            detailsHtml += `<div class="order-detail-section"><strong>Toplam Tutar:</strong> <span>${data.order_info.toplam_tutar ? parseFloat(data.order_info.toplam_tutar).toFixed(2).replace('.', ',') : '0,00'} TL</span></div>`;
                            let statusClass = data.order_info.siparis_durumu ? 'status-' + data.order_info.siparis_durumu.toLowerCase().replace(/\s+/g, '-') : 'status-bilinmiyor';
                            detailsHtml += `<div class="order-detail-section"><strong>Sipariş Durumu:</strong> <span class="status ${statusClass}">${data.order_info.siparis_durumu || 'Bilinmiyor'}</span></div>`;
                            
                            let musteriAdi = 'Misafir';
                            let musteriEmail = 'N/A';
                            if(data.order_info.kullanicilar) {
                                const ku = data.order_info.kullanicilar;
                                if (ku.ad || ku.soyad) musteriAdi = `${(ku.ad || '')} ${(ku.soyad || '')}`.trim();
                                if (ku.email) musteriEmail = ku.email;
                            }
                            detailsHtml += `<div class="order-detail-section"><strong>Müşteri:</strong> <span>${musteriAdi}</span></div>`;
                            detailsHtml += `<div class="order-detail-section"><strong>E-posta:</strong> <span><a href="mailto:${musteriEmail}" style="color:var(--asikzade-green); text-decoration:none;">${musteriEmail}</a></span></div>`;
                        }
                        detailsHtml += `</div>`;
                        
                        if(data.order_info && data.order_info.teslimat_adresi) {
                             detailsHtml += `<div class="order-detail-section" style="grid-column: 1 / -1; margin-top:15px; padding-top:15px; border-top:1px solid var(--asikzade-border);"><strong>Teslimat Adresi:</strong><br><div style="margin-top:5px; padding:10px; background-color:#f9f9f9; border-radius:5px;">${(data.order_info.teslimat_adresi).replace(/\n/g, '<br>')}</div></div>`;
                        }

                        if (data.items && data.items.length > 0) {
                            detailsHtml += '<h5 style="margin-top:25px; margin-bottom:15px; font-size:1.2rem; color:var(--asikzade-dark-text); padding-bottom:10px; border-bottom:1px solid var(--asikzade-border);">Sipariş İçeriği</h5>';
                            detailsHtml += '<div style="overflow-x:auto;"><table class="modal-order-items-table"><thead><tr><th></th><th>Ürün Adı</th><th>Miktar</th><th>Birim Fiyat</th><th>Ara Toplam</th></tr></thead><tbody>';
                            
                            const productsData = <?php echo isset($products) && is_array($products) ? json_encode($products) : '{}'; ?>;
// ... fetch(...).then(data => { ... if (data.items && data.items.length > 0) { ...
data.items.forEach(item => {
    let imageUrl = 'https://via.placeholder.com/50/e9ecef/6c757d?text=?';
    const itemNameFromOrder = item.urun_adi ? String(item.urun_adi).trim() : null;

    // --- HATA AYIKLAMA KODLARI BAŞLANGIÇ ---
    console.log("-------------------------------------");
    console.log("SİPARİŞTEN GELEN ÜRÜN ADI:", itemNameFromOrder);
    // productsData'nın kendisini veya ilk birkaç anahtarını loglayarak yapısını kontrol edebilirsiniz
    // console.log("productsData NESNESİ (ilk birkaç anahtar):", Object.keys(productsData).slice(0,5));
    // console.log("productsData TAMAMI:", productsData); // Çok büyük olabilir, dikkatli kullanın
    // --- HATA AYIKLAMA KODLARI SONU ---

    if (productsData && itemNameFromOrder) {
         for (const key in productsData) { // productsData bir nesne { "1":{...}, "2":{...} }
            if (productsData.hasOwnProperty(key)) {
                const productNameFromData = String(productsData[key].name).trim();
                
                // --- HATA AYIKLAMA KODLARI BAŞLANGIÇ ---
                // Eşleşme denemesini logla (çok fazla log olmaması için bir koşul eklenebilir veya sadece eşleşme varsa logla)
                // İsimlerin birbirini içerip içermediğini kontrol etmek için küçük harfe çevirerek karşılaştırma:
                if (productNameFromData.toLowerCase().includes(itemNameFromOrder.toLowerCase()) || itemNameFromOrder.toLowerCase().includes(productNameFromData.toLowerCase())) {
                    console.log(`  Karşılaştırılıyor: '${productNameFromData}' (productsData) vs '${itemNameFromOrder}' (sipariş). Tam Eşleşme: ${productNameFromData === itemNameFromOrder}`);
                }
                // --- HATA AYIKLAMA KODLARI SONU ---

                if (productNameFromData === itemNameFromOrder) {
                    imageUrl = productsData[key].image || productsData[key].hero_image || imageUrl;
                    // --- HATA AYIKLAMA KODLARI BAŞLANGIÇ ---
                    console.log("+++ EŞLEŞME BULUNDU! Resim URL:", imageUrl);
                    // --- HATA AYIKLAMA KODLARI SONU ---
                    break;
                }
            }
        }
    } else {
        // --- HATA AYIKLAMA KODLARI BAŞLANGIÇ ---
        console.log("productsData veya itemNameFromOrder tanımsız/boş, resim arama atlandı.");
        // --- HATA AYIKLAMA KODLARI SONU ---
    }
    
    // HTML oluşturma kısmı (gerisi aynı kalacak)
    detailsHtml += `<tr>
        <td><img src="${imageUrl}" alt="${item.urun_adi || 'Ürün'}" class="product-thumbnail"></td>
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
                    modalContentDiv.innerHTML = `<p style="color:var(--asikzade-red); padding:20px; text-align:center;">Sipariş detayları yüklenirken bir hata oluştu. Lütfen konsolu kontrol edin.</p>`;
                    console.error('Admin Sipariş detayları çekilirken hata:', error);
                });
        });
    });
});
</script>
</body>
</html>