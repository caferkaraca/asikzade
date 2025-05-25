<?php
// cart_action.php
session_start(); // Gelecekteki özellikler için (CSRF vb.)
include 'products_data.php'; // Ürün verilerini ve get_cart_count fonksiyonunu dahil et

$action = $_POST['action'] ?? $_GET['action'] ?? null;
$product_id = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

// Miktar kontrolleri
if ($action === 'update' && $quantity < 0) { // Güncelleme için miktar negatif olamaz, 0 siler
    $quantity = 0;
} elseif ($quantity < 1 && $action !== 'update') { // Ekleme veya diğer durumlar için en az 1
    $quantity = 1;
}

$cart = [];
if (isset($_COOKIE['asikzade_cart'])) {
    $cart_data = json_decode($_COOKIE['asikzade_cart'], true);
    if (is_array($cart_data)) {
        $cart = $cart_data;
    }
}

$product_exists_in_db = isset($products[$product_id]);

if ($action === 'add' && $product_exists_in_db) {
    if (isset($cart[$product_id])) {
        $cart[$product_id]['quantity'] += $quantity;
    } else {
        $cart[$product_id] = ['id' => $product_id, 'quantity' => $quantity];
    }
} elseif ($action === 'remove' && isset($cart[$product_id])) {
    unset($cart[$product_id]);
} elseif ($action === 'update' && isset($cart[$product_id])) {
    if ($quantity > 0) {
        $cart[$product_id]['quantity'] = $quantity;
    } else { // Miktar 0 veya daha az ise ürünü kaldır
        unset($cart[$product_id]);
    }
} elseif ($action === 'clear') {
    $cart = [];
}

// Cookie'yi ayarla (1 ay geçerli)
// Son parametreler: path, domain, secure, httponly
// Örnek: setcookie('asikzade_cart', json_encode($cart), time() + (86400 * 30), "/", "", false, true); // httponly için
setcookie('asikzade_cart', json_encode($cart), time() + (86400 * 30), "/");

// Kullanıcıyı yönlendir
$redirect_url = 'index.php'; // Varsayılan yönlendirme
if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
    $redirect_url = $_SERVER['HTTP_REFERER'];
}

// Eğer işlem sepet sayfasından yapıldıysa veya sepeti temizleme ise sepet.php'ye yönlendir
if ($action === 'update' || $action === 'remove' || $action === 'clear') {
    $redirect_url = 'sepet.php';
}

// URL'den mevcut fragment'ı (#) temizle, çünkü yenisini ekleyeceğiz (eğer varsa)
if (strpos($redirect_url, '#') !== false) {
    $redirect_url = substr($redirect_url, 0, strpos($redirect_url, '#'));
}

// Eğer JS'den bir anchor geldiyse, onu kullan. Yoksa ve action 'add' ise varsayılan anchor'ı kullan.
$anchor = '';
if (isset($_POST['redirect_anchor']) && !empty($_POST['redirect_anchor']) && $_POST['redirect_anchor'][0] === '#') {
    $anchor = $_POST['redirect_anchor'];
} elseif ($action === 'add' && strpos($redirect_url, 'index.php') !== false) {
    // 'add' action'ı ise ve index.php'ye dönülüyorsa, varsayılan olarak ürünler bölümüne git
    $anchor = '#asikzade-products'; // Veya tıklanan ürünün ID'si (JS'den gelirse)
}

// Yönlendirme URL'sine anchor'ı ekle
header('Location: ' . $redirect_url . $anchor);
exit;
?>