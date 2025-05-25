<?php
require_once 'admin_config.php'; // session_start(), admin_check_login(), supabase_api_request()
admin_check_login(); // Yetkisiz erişimi engelle

$admin_email_session = $_SESSION['admin_email'] ?? 'Admin';

// products_data.php'yi dahil et (modalda ürün resimleri için)
// Bu dosyanın var olduğundan ve $products dizisini tanımladığından emin olun.
if (file_exists('products_data.php')) {
    include 'products_data.php';
} else {
    $products = []; // Eğer dosya yoksa boş bir dizi tanımla, hataları önlemek için.
    error_log("admin_dashboard.php: products_data.php dosyası bulunamadı.");
}


// --- SİPARİŞ DURUMU GÜNCELLEME İŞLEMİ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'guncelle_siparis_durumu') {
    $siparis_id_guncelle = $_POST['siparis_id_guncelle'] ?? null;
    $yeni_durum = $_POST['yeni_siparis_durumu'] ?? null;
    $gecerli_durumlar = ['beklemede', 'hazırlanıyor', 'gönderildi', 'teslim edildi', 'iptal edildi'];

    if ($siparis_id_guncelle && $yeni_durum && in_array($yeni_durum, $gecerli_durumlar)) {
        $updateData = ['siparis_durumu' => $yeni_durum];

        $updateResult = supabase_api_request( // Fonksiyon adınız supabase_admin_api_request ise onu kullanın
            'PATCH',
            '/rest/v1/siparisler?id=eq.' . $siparis_id_guncelle,
            $updateData, // data
            [],          // custom_headers
            true         // use_service_key (admin_config.php'deki fonksiyonunuza göre ayarlayın)
        );

        if (!$updateResult['error'] && ($updateResult['http_code'] === 200 || $updateResult['http_code'] === 204)) {
            $_SESSION['admin_success_message'] = "Sipariş #" . substr($siparis_id_guncelle,0,8) . " durumu başarıyla '" . htmlspecialchars($yeni_durum) . "' olarak güncellendi.";
        } else {
            $api_error = $updateResult['error']['message'] ?? ($updateResult['data']['message'] ?? 'Bilinmeyen API hatası');
            $_SESSION['admin_error_message'] = "Sipariş durumu güncellenirken hata: " . htmlspecialchars($api_error);
            error_log("Admin Sipariş Durum Güncelleme Hatası: " . $api_error . " | Sipariş ID: " . $siparis_id_guncelle);
        }
    } else {
        $_SESSION['admin_error_message'] = "Geçersiz sipariş ID veya durum bilgisi.";
    }
    header('Location: admin_dashboard.php?' . http_build_query($_GET)); // Mevcut filtreleri ve sayfa numarasını koru
    exit;
}

// Filtreleme ve arama için değerleri al
$search_query = trim($_GET['q'] ?? '');
$filter_status = $_GET['status'] ?? '';
$current_page = max(1, intval($_GET['page'] ?? 1));
$items_per_page = 15; // Sayfa başına gösterilecek sipariş sayısı
$offset = ($current_page - 1) * $items_per_page;


// --- SİPARİŞLERİ ÇEKME (Tüm Siparişler - Admin Görünümü) ---
$base_api_path = '/rest/v1/siparisler';
$select_fields = 'id,kullanici_id,siparis_tarihi,toplam_tutar,siparis_durumu,teslimat_adresi,kullanicilar(email,ad,soyad)';
$api_params_array = [];
$count_api_params_array = []; // Sayım için sadece filtreler

if (!empty($search_query)) {
    if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $search_query)) {
        $api_params_array[] = 'id=eq.' . urlencode($search_query);
        $count_api_params_array[] = 'id=eq.' . urlencode($search_query);
    }
}
if (!empty($filter_status)) {
    $api_params_array[] = 'siparis_durumu=eq.' . urlencode($filter_status);
    $count_api_params_array[] = 'siparis_durumu=eq.' . urlencode($filter_status);
}

// Toplam sipariş sayısını al
$total_orders = 0;
$count_query_string = '';
if (!empty($count_api_params_array)) {
    $count_query_string = '&' . implode('&', $count_api_params_array);
}
// Supabase'den count almak için Prefer: count=exact başlığını kullanıyoruz.
// supabase_api_request fonksiyonunuzun custom header alabilmesi gerekiyor.
// Beşinci parametre $use_service_key, altıncı $custom_headers olsun.
$countResult = supabase_api_request(
    'GET',
    $base_api_path . '?select=id' . $count_query_string, // Sadece ID seç, daha hızlı
    [], // data
    ['Prefer: count=exact'], // custom_headers
    true // use_service_key
);

if (!$countResult['error'] && isset($countResult['http_code']) && $countResult['http_code'] === 200) {
    // cURL'den Content-Range başlığını almak için supabase_api_request fonksiyonunu modifiye etmeniz gerekebilir.
    // Şimdilik, dönen veri sayısına güvenelim (eğer limit uygulanmıyorsa) veya manuel sayalım.
    // Basit bir yaklaşım: Tüm eşleşen kayıtları çekip saymak (küçük veri setleri için).
    // Daha iyi bir yol, RPC veya view ile Supabase tarafında sayım yapmak.
    // Bu örnekte, header'ı alamadığımızı varsayarak, eğer countResult['data'] tümünü içeriyorsa kullanacağız.
    // Ancak bu genellikle limitlenmiş sonucu verir. Content-Range'i parse etmek en doğrusu.
    // Geçici çözüm: Eğer fonksiyon header döndürmüyorsa, tümünü çekip say.
    // Not: Bu geçici çözüm büyük veri kümelerinde VERİMSİZDİR!
    if(isset($countResult['headers']['content-range'])) {
        $range = explode('/', $countResult['headers']['content-range']);
        if(isset($range[1])) $total_orders = intval($range[1]);
    } else { // Fallback: Eğer header okunamıyorsa, tümünü çekip say (verimsiz)
        $tempCountResult = supabase_api_request('GET', $base_api_path . '?select=id' . $count_query_string, [], [], true);
        if (!$tempCountResult['error'] && isset($tempCountResult['data'])) {
            $total_orders = count($tempCountResult['data']);
        }
    }
}
$total_pages = $items_per_page > 0 ? ceil($total_orders / $items_per_page) : 1;
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages; // Geçersiz sayfa numarasını düzelt
$offset = ($current_page - 1) * $items_per_page;


// Sayfalanmış siparişleri çek
$api_path_paginated = $base_api_path . '?select=' . $select_fields;
if (!empty($api_params_array)) {
    $api_path_paginated .= '&' . implode('&', $api_params_array);
}
$api_path_paginated .= '&order=siparis_tarihi.desc&offset=' . $offset . '&limit=' . $items_per_page;

$tum_siparisler = [];
$siparislerResult = supabase_api_request(
    'GET',
    $api_path_paginated,
    [],   // data
    [],   // custom_headers
    true  // use_service_key
);

if (!$siparislerResult['error'] && !empty($siparislerResult['data'])) {
    $tum_siparisler = $siparislerResult['data'];
} elseif ($siparislerResult['error']) {
    $_SESSION['admin_error_message'] = "Siparişler yüklenirken hata: " . ($siparislerResult['error']['message'] ?? 'Bilinmeyen hata');
    error_log("Admin Sipariş Listeleme Hatası: " . ($siparislerResult['error']['message'] ?? 'Bilinmeyen hata') . " | HTTP: " . ($siparislerResult['http_code'] ?? 'N/A') . " | Path: " . $api_path_paginated);
}

$success_message = $_SESSION['admin_success_message'] ?? null;
unset($_SESSION['admin_success_message']);
$error_message = $_SESSION['admin_error_message'] ?? null;
unset($_SESSION['admin_error_message']);

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - Sipariş Yönetimi</title>
    <style>
        /* === Önceki admin_dashboard.php'deki CSS stilleriniz buraya gelecek === */
        /* Stilleri kopyalayıp yapıştırın, sadece .dashboard-container ile ilgili olan */
        /* sekmeli yapı stillerini ve .dashboard-sidebar içindeki "Hesabım" başlığını kaldırabilirsiniz. */
        /* Onun yerine .admin-sidebar ve .admin-main-content-wrapper stillerini kullanın. */
        /* Kullanıcı dashboard.php'sinden kopyaladığınız header, footer stillerini de kaldırın. */

        :root {
            --asikzade-content-bg: #fef6e6;
            --asikzade-green: #8ba86d;
            --asikzade-dark-green: #6a8252;
            --asikzade-dark-text: #2d3e2a;
            --asikzade-light-text: #fdfcf8;
            --asikzade-gray: #7a7a7a;
            --asikzade-border: #e5e5e5;

            --admin-bg: #f4f6f9;
            --admin-sidebar-bg: #2c3e50;
            --admin-sidebar-text: #ecf0f1;
            --admin-sidebar-hover-bg: #34495e;
            --admin-sidebar-active-bg: var(--asikzade-green);
            --admin-header-bg: #ffffff;
            --admin-text-dark: #343a40;
            --admin-text-light: #6c757d;
            --admin-card-bg: #ffffff;
            --asikzade-red: #c0392b;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif; }
        body {
            background-color: var(--admin-bg); color: var(--admin-text-dark);
            line-height: 1.6; display: flex; min-height: 100vh;
        }

        .admin-sidebar {
            width: 260px; background-color: var(--admin-sidebar-bg); color: var(--admin-sidebar-text);
            padding-top: 20px; position: fixed; top:0; left:0; bottom:0; z-index:100;
            display: flex; flex-direction: column; box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .admin-sidebar .logo-container { text-align: center; margin-bottom: 25px; padding: 0 15px; }
        .admin-sidebar .logo-container img { max-height: 50px; }
        .admin-sidebar .logo-container .logo-text { display:block; color: var(--admin-sidebar-text); font-size: 1.3rem; margin-top:8px; font-weight: 500; }
        .admin-sidebar nav { flex-grow: 1; }
        .admin-sidebar nav ul { list-style: none; padding: 0; margin: 0; }
        .admin-sidebar nav ul li a {
            display: flex; align-items: center; padding: 14px 25px; text-decoration: none; color: var(--admin-sidebar-text);
            border-left: 4px solid transparent; transition: background-color 0.2s, border-left-color 0.2s; font-size: 0.95rem;
        }
        .admin-sidebar nav ul li a svg { margin-right: 12px; width:20px; height:20px; opacity: 0.8; }
        .admin-sidebar nav ul li a:hover { background-color: var(--admin-sidebar-hover-bg); }
        .admin-sidebar nav ul li a.active { background-color: var(--admin-sidebar-active-bg); border-left-color: #fff; font-weight: 500; }
        .admin-sidebar .sidebar-footer { padding: 20px 25px; border-top: 1px solid #3e5165; text-align: center; font-size: 0.8rem;}
        .admin-sidebar .sidebar-footer a { color: var(--asikzade-green); text-decoration: none; }

        .admin-main-content-wrapper { margin-left: 260px; flex-grow: 1; padding: 0; display: flex; flex-direction: column; }
        .admin-header {
            background-color: var(--admin-header-bg); padding: 15px 30px;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--asikzade-border); box-shadow: 0 1px 4px rgba(0,0,0,0.07);
            position: sticky; top: 0; z-index: 90;
        }
        .admin-header h1 { font-size: 1.6rem; margin: 0; font-weight: 600; color: var(--admin-text-dark); }
        .admin-user-info span { color: var(--admin-text-light); margin-right: 10px;}
        .admin-user-info a { color: var(--asikzade-red); text-decoration: none; font-weight:500; }

        .admin-page-content { padding: 30px; flex-grow: 1; }
        .content-card { background-color: var(--admin-card-bg); padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); margin-bottom: 30px; }
        .content-card h3 { font-size: 1.3rem; margin-top:0; margin-bottom: 20px; font-weight: 500; padding-bottom:10px; border-bottom:1px solid var(--asikzade-border);}
        
        .filters-form { display: flex; gap: 20px; margin-bottom: 25px; align-items: flex-end; flex-wrap: wrap; }
        .filters-form .form-group { flex: 1; min-width: 200px; }
        .filters-form label { font-size: 0.85rem; color: var(--admin-text-light); margin-bottom: 5px; display:block; }
        .filters-form input[type="text"], .filters-form select {
            width: 100%; padding: 10px 12px; border: 1px solid var(--asikzade-border); border-radius: 5px; font-size: 0.9rem;
        }
        .filters-form button[type="submit"] {
            padding: 10px 25px; background-color: var(--asikzade-green); color: white; border: none;
            border-radius: 5px; cursor: pointer; font-size: 0.9rem; font-weight:500;
            transition: background-color 0.2s; white-space: nowrap;
        }
        .filters-form button[type="submit"]:hover { background-color: var(--asikzade-dark-green); }

        .orders-admin-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; table-layout: auto; }
        .orders-admin-table th, .orders-admin-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--asikzade-border); vertical-align: middle;}
        .orders-admin-table th { background-color: #eef1f5; font-weight: 500; color: var(--admin-text-dark); }
        .orders-admin-table tbody tr:hover { background-color: #f9fafb; }
        .orders-admin-table td form { display: flex; align-items:center; gap: 8px; margin:0;}
        .orders-admin-table select.status-select {
            padding: 7px 10px; font-size:0.85rem; border-radius:4px; border:1px solid #ced4da; flex-grow:1; min-width: 130px;
        }
        .orders-admin-table button.update-btn, .orders-admin-table button.detail-btn {
            font-size:0.8rem; padding: 7px 12px; border:none; border-radius:4px; cursor:pointer; color:white; font-weight:500;
            transition: opacity 0.2s; white-space: nowrap;
        }
        .orders-admin-table button.update-btn { background-color: #007bff; }
        .orders-admin-table button.update-btn:hover { opacity:0.85; }
        .orders-admin-table button.detail-btn { background-color: #6c757d; }
        .orders-admin-table button.detail-btn:hover { opacity:0.85; }

        .orders-admin-table .user-email-link {color: var(--asikzade-green); text-decoration:none;}
        .orders-admin-table .user-email-link:hover {text-decoration:underline;}
        .orders-admin-table .order-id-link { color: var(--admin-text-dark); text-decoration:none; font-weight:500;}
        .orders-admin-table .order-id-link:hover { color: var(--asikzade-green); }
        .status-beklemede { color: #fd7e14; font-weight: bold; }
        .status-hazirlaniyor { color: #0dcaf0; font-weight: bold; }
        .status-gonderildi { color: #198754; font-weight: bold; }
        .status-teslim-edildi { color: var(--asikzade-green); font-weight: bold; }
        .status-iptal-edildi { color: #dc3545; font-weight: bold; }


        .message-box { padding: 12px 18px; margin-bottom: 20px; border-radius: 5px; font-size: 0.95rem; border: 1px solid transparent; }
        .message-success { background-color: #d1e7dd; color: #0f5132; border-color: #badbcc; }
        .message-error { background-color: #f8d7da; color: #842029; border-color: #f5c2c7; }

        .pagination { margin-top: 25px; text-align: center; }
        .pagination a, .pagination span {
            display: inline-block; padding: 8px 12px; margin: 0 3px; border: 1px solid var(--asikzade-border);
            text-decoration: none; color: var(--admin-text-dark); border-radius: 4px; font-size: 0.9rem;
        }
        .pagination a:hover { background-color: #e9ecef; }
        .pagination span.current { background-color: var(--asikzade-green); color: white; border-color: var(--asikzade-green); }
        .pagination span.disabled { color: #ccc; border-color: #eee; }

        .modal {
            display: none; position: fixed; z-index: 1050; left: 0; top: 0;
            width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);
            align-items: center; justify-content: center; animation: modalFadeIn 0.3s;
        }
        @keyframes modalFadeIn { from{opacity:0} to{opacity:1} }
        .modal-content {
            background-color: var(--admin-card-bg); margin: auto; padding: 25px 30px;
            border: 1px solid rgba(0,0,0,0.1); width: 90%; max-width: 800px;
            border-radius: 8px; position: relative; box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            animation: modalSlideIn 0.3s;
        }
        @keyframes modalSlideIn { from{transform: translateY(-50px); opacity:0} to{transform: translateY(0); opacity:1} }
        .modal-close {
            color: #777; float: right; font-size: 28px; font-weight: bold;
            position: absolute; top: 15px; right: 20px; line-height: 1;
        }
        .modal-close:hover, .modal-close:focus { color: #333; text-decoration: none; cursor: pointer; }
        .modal-title { margin-top:0; margin-bottom: 20px; font-size: 1.5rem; font-weight: 500; color: var(--admin-text-dark); }
        
        .order-details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px 25px; margin-bottom: 20px;}
        .order-detail-section { font-size: 0.95rem; }
        .order-detail-section strong { display: block; font-weight: 500; color: var(--admin-text-light); margin-bottom: 3px; font-size: 0.85rem;}
        .order-detail-section span { font-weight: 500; color: var(--admin-text-dark); }
        .order-detail-section span.status { padding: 3px 8px; border-radius: 4px; color: white !important; font-size:0.85rem; display:inline-block;}

        .modal-order-items-table { width: 100%; margin-top: 15px; border-collapse: collapse; font-size: 0.9rem;}
        .modal-order-items-table th, .modal-order-items-table td { padding: 10px; border-bottom: 1px solid var(--asikzade-border); text-align: left; vertical-align: middle;}
        .modal-order-items-table th { background-color: #f8f9fa; }
        .modal-order-items-table img.product-thumbnail { width: 45px; height: 45px; object-fit: cover; border-radius: 4px; margin-right: 10px;}
        
        .modal-footer { margin-top: 25px; text-align: right; }
        .modal-footer button {
            background-color: var(--asikzade-gray); color: white; border: none;
            padding: 9px 18px; border-radius: 5px; cursor: pointer; font-size:0.9rem;
        }
        .modal-footer button:hover { background-color: #555; }

        @media (max-width: 992px) { /* Sidebar daraltma */
            .admin-sidebar { width: 75px; }
            .admin-sidebar .logo-container .logo-text, .admin-sidebar nav ul li a span { display: none; }
            .admin-sidebar nav ul li a svg { margin-right: 0; }
            .admin-sidebar nav ul li a { justify-content: center; padding: 15px 0; }
            .admin-main-content-wrapper { margin-left: 75px; }
        }
        @media (max-width: 768px) { /* Mobil için daha fazla düzenleme */
            .admin-main-content-wrapper { margin-left: 0; }
            .admin-sidebar { position:fixed; transform: translateX(-100%); transition: transform 0.3s ease; width:260px; }
            .admin-sidebar.open { transform: translateX(0); }
            .admin-header { padding: 12px 20px;}
            .admin-header h1 { margin-bottom:0; font-size: 1.3rem; margin-left: 40px; /* Hamburger için yer */ }
            .admin-header .menu-toggle { display: block; position: absolute; left: 15px; top: 50%; transform: translateY(-50%); font-size:1.5rem; cursor:pointer; color:var(--admin-text-dark); z-index: 110;}
            .admin-page-content { padding: 20px; }
            .filters-form { flex-direction: column; align-items: stretch;}
            .filters-form .form-group { margin-bottom: 10px;}
            .orders-admin-table { font-size: 0.85rem; }
            .orders-admin-table th, .orders-admin-table td { padding: 8px 10px; }
            .orders-admin-table select.status-select, .orders-admin-table button { font-size:0.75rem; padding: 6px 8px;}
            .modal-content { padding: 20px;}
            .modal-title {font-size: 1.3rem;}
        }
        .menu-toggle { display: none; } /* Varsayılan olarak gizli */

    </style>
</head>
<body>
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="logo-container">
            <a href="admin_dashboard.php"><img src="https://i.imgur.com/rdZuONP.png" alt="Aşıkzade Logo"></a>
            <span class="logo-text">AŞIKZADE</span>
        </div>
        <nav>
            <ul>
                <!-- <li><a href="admin_dashboard.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    <span>Dashboard</span></a></li> -->
                <li><a href="admin_dashboard.php" class="active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                    <span>Siparişler</span></a></li>
                <li><a href="#"> <!-- admin_users.php (henüz oluşturulmadı) -->
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    <span>Kullanıcılar</span></a></li>
                <li><a href="#"> <!-- admin_products.php (henüz oluşturulmadı) -->
                     <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                    <span>Ürünler</span></a></li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <a href="admin_logout.php">Çıkış Yap</a>
        </div>
    </aside>

    <div class="admin-main-content-wrapper">
        <header class="admin-header">
            <span class="menu-toggle" id="menuToggle">☰</span> <!-- Hamburger Icon -->
            <h1>Sipariş Yönetimi</h1>
            <div class="admin-user-info">
                <span>Hoş geldiniz, <?php echo htmlspecialchars($admin_email_session); ?></span>
                <a href="admin_logout.php">Çıkış</a>
            </div>
        </header>

        <main class="admin-page-content">
            <?php if ($success_message): ?>
                <div class="message-box message-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="message-box message-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="content-card filters-card">
                <h3>Filtrele ve Ara</h3>
                <form action="admin_dashboard.php" method="GET" class="filters-form">
                    <input type="hidden" name="page" value="1"> <!-- Arama yapıldığında ilk sayfaya git -->
                    <div class="form-group">
                        <label for="q">Sipariş ID Ara</label>
                        <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Tam Sipariş ID'si...">
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
                    <button type="submit">Uygula</button>
                </form>
            </div>

            <div class="content-card">
                <h3>Tüm Siparişler (<?php echo $total_orders; ?> Adet)</h3>
                <?php if (!empty($tum_siparisler)): ?>
                    <div style="overflow-x:auto;">
                    <table class="orders-admin-table">
                        <thead>
                            <tr>
                                <th>Sip. ID</th>
                                <th>Kullanıcı</th>
                                <th>Tarih</th>
                                <th>Tutar</th>
                                <th>Mevcut Durum</th>
                                <th style="min-width:280px;">Durum Güncelle</th>
                                <th>Detay</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tum_siparisler as $siparis): ?>
                            <tr>
                                <td><a href="#" class="order-id-link admin-order-inspect-btn" data-order-id="<?php echo htmlspecialchars($siparis['id']); ?>">#<?php echo htmlspecialchars(substr($siparis['id'], 0, 8)); ?></a></td>
                                <td>
                                    <?php if (isset($siparis['kullanicilar']) && !empty($siparis['kullanicilar'])): ?>
                                        <?php echo htmlspecialchars(trim(($siparis['kullanicilar']['ad'] ?? '') . ' ' . ($siparis['kullanicilar']['soyad'] ?? ''))); ?><br>
                                        <small><a href="mailto:<?php echo htmlspecialchars($siparis['kullanicilar']['email'] ?? ''); ?>" class="user-email-link"><?php echo htmlspecialchars($siparis['kullanicilar']['email'] ?? 'E-posta yok'); ?></a></small>
                                    <?php else: ?>
                                        <small>Kullanıcı bilgisi yok<br>(ID: <?php echo htmlspecialchars(substr($siparis['kullanici_id'],0,8)); ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($siparis['siparis_tarihi']))); ?></td>
                                <td><?php echo htmlspecialchars(number_format($siparis['toplam_tutar'], 2, ',', '.')); ?> TL</td>
                                <td><span class="status-<?php echo str_replace(' ', '-', htmlspecialchars(strtolower($siparis['siparis_durumu']))); ?>"><?php echo htmlspecialchars(ucfirst($siparis['siparis_durumu'])); ?></span></td>
                                <td>
                                    <form action="admin_dashboard.php?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page])); ?>" method="POST">
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
                        // Sayfalama linkleri için temel URL
                        $pagination_params = $_GET; // Mevcut GET parametrelerini al
                        unset($pagination_params['page']); // 'page' parametresini kaldır, yeniden eklenecek
                        $base_pagination_url = 'admin_dashboard.php?' . http_build_query($pagination_params);
                        $separator = empty($pagination_params) ? '' : '&';
                        ?>
                        <?php if ($current_page > 1): ?>
                            <a href="<?php echo $base_pagination_url . $separator . 'page=' . ($current_page - 1); ?>">« Önceki</a>
                        <?php else: ?>
                            <span class="disabled">« Önceki</span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $current_page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="<?php echo $base_pagination_url . $separator . 'page=' . $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <a href="<?php echo $base_pagination_url . $separator . 'page=' . ($current_page + 1); ?>">Sonraki »</a>
                        <?php else: ?>
                            <span class="disabled">Sonraki »</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <p>Filtrelerinize uygun sipariş bulunamadı veya hiç sipariş yok.</p>
                <?php endif; ?>
            </div>
        </main>
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
document.addEventListener('DOMContentLoaded', function () {
    const inspectButtons = document.querySelectorAll('.admin-order-inspect-btn');
    const modal = document.getElementById('adminOrderDetailModal');
    const modalContentDiv = document.getElementById('adminModalOrderDetailContent');
    const closeButtons = document.querySelectorAll('.admin-modal-close');
    const menuToggle = document.getElementById('menuToggle');
    const adminSidebar = document.getElementById('adminSidebar');

    if (menuToggle && adminSidebar) {
        menuToggle.addEventListener('click', function() {
            adminSidebar.classList.toggle('open');
        });
    }

    function openModal() {
        if(modal) modal.style.display = 'flex';
    }
    function closeModal() {
        if(modal) modal.style.display = 'none';
        if(modalContentDiv) modalContentDiv.innerHTML = '<p>Yükleniyor...</p>';
    }

    closeButtons.forEach(btn => btn.addEventListener('click', closeModal));
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            closeModal();
        }
    });

    inspectButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const orderId = this.getAttribute('data-order-id');
            openModal();
            modalContentDiv.innerHTML = `<p><b>Sipariş No:</b> #${orderId.substring(0,8)}... detayları yükleniyor...</p>`;

            fetch(`get_order_details.php?order_id=${orderId}&admin_view=true`)
                .then(response => {
                    if (!response.ok) {
                        // Hata durumunda yanıtı metin olarak alıp loglayalım ve JSON parse hatasını önleyelim
                        return response.text().then(text => {
                           throw new Error('Network response was not ok. Status: ' + response.status + '. Response: ' + text);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        modalContentDiv.innerHTML = `<p style="color:red;">Hata: ${data.error}</p>`;
                    } else {
                        let detailsHtml = '<div class="order-details-grid">';
                        if(data.order_info) {
                            detailsHtml += `<div class="order-detail-section"><strong>Sipariş No:</strong> <span>#${data.order_info.id ? data.order_info.id.substring(0,8) : 'N/A'}</span></div>`;
                            detailsHtml += `<div class="order-detail-section"><strong>Sipariş Tarihi:</strong> <span>${data.order_info.siparis_tarihi ? new Date(data.order_info.siparis_tarihi).toLocaleString('tr-TR') : 'N/A'}</span></div>`;
                            detailsHtml += `<div class="order-detail-section"><strong>Toplam Tutar:</strong> <span>${data.order_info.toplam_tutar ? parseFloat(data.order_info.toplam_tutar).toFixed(2).replace('.', ',') : '0,00'} TL</span></div>`;
                            let statusClass = data.order_info.siparis_durumu ? data.order_info.siparis_durumu.toLowerCase().replace(/\s+/g, '-') : 'bilinmiyor';
                            detailsHtml += `<div class="order-detail-section"><strong>Sipariş Durumu:</strong> <span class="status status-${statusClass}">${data.order_info.siparis_durumu || 'Bilinmiyor'}</span></div>`;
                            
                            if(data.order_info.kullanicilar) {
                                const ku = data.order_info.kullanicilar;
                                const musteriAdi = ku.ad || ku.soyad ? `${(ku.ad || '')} ${(ku.soyad || '')}`.trim() : 'Misafir';
                                detailsHtml += `<div class="order-detail-section"><strong>Müşteri:</strong> <span>${musteriAdi}</span></div>`;
                                detailsHtml += `<div class="order-detail-section"><strong>E-posta:</strong> <span><a href="mailto:${ku.email || ''}">${ku.email || 'N/A'}</a></span></div>`;
                            } else {
                                detailsHtml += `<div class="order-detail-section"><strong>Müşteri ID:</strong> <span>${data.order_info.kullanici_id ? data.order_info.kullanici_id.substring(0,8) + '...' : 'N/A'}</span></div>`;
                            }
                        }
                        detailsHtml += `</div>`; // order-details-grid kapanışı
                        
                        if(data.order_info && data.order_info.teslimat_adresi) {
                             detailsHtml += `<div class="order-detail-section" style="grid-column: 1 / -1; margin-top:15px;"><strong>Teslimat Adresi:</strong><br><span>${(data.order_info.teslimat_adresi).replace(/\n/g, '<br>')}</span></div>`;
                        }


                        if (data.items && data.items.length > 0) {
                            detailsHtml += '<h5 style="margin-top:20px; margin-bottom:10px; font-size:1.1rem; grid-column: 1 / -1;">Sipariş İçeriği:</h5>';
                            detailsHtml += '<div style="grid-column: 1 / -1; overflow-x:auto;"><table class="modal-order-items-table"><thead><tr><th>Resim</th><th>Ürün Adı</th><th>Miktar</th><th>Birim Fiyat</th><th>Ara Toplam</th></tr></thead><tbody>';
                            
                            const productsData = <?php echo isset($products) && is_array($products) ? json_encode($products) : '{}'; ?>;

                            data.items.forEach(item => {
                                let imageUrl = 'https://via.placeholder.com/45/CCCCCC/FFFFFF?text=ResimYok';
                                if (productsData && item.urun_adi) {
                                    for (const key in productsData) {
                                        if (productsData.hasOwnProperty(key) && productsData[key].name === item.urun_adi) {
                                            imageUrl = productsData[key].image || productsData[key].hero_image || imageUrl;
                                            break;
                                        }
                                    }
                                }

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
                            detailsHtml += '<p style="margin-top:15px; grid-column: 1 / -1;">Bu siparişe ait ürün detayı bulunamadı.</p>';
                        }
                        modalContentDiv.innerHTML = detailsHtml;
                    }
                })
                .catch(error => {
                    modalContentDiv.innerHTML = `<p style="color:red;">Sipariş detayları yüklenirken bir hata oluştu. Lütfen konsolu kontrol edin.</p>`;
                    console.error('Admin Sipariş detayları çekilirken hata:', error);
                });
        });
    });
});
</script>
</body>
</html>