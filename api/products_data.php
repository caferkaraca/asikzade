<?php
// products_data.php

$products = [
    1 => [
        'id' => 1, // Keep your numerical ID
        'name' => "Organik Şiraz Özü",
        'image' => "https://i.imgur.com/S5jhEyx.png", // Main image for grid and detail
        'hero_image' => "https://i.imgur.com/fIyzlOi.png", // Image for hero slider
        'price' => 120.00,
        'dynamicBgClass' => "bg-product1-custom",
        'productNameBgTextType' => "light",
        'description' => 'Tamamen organik Şiraz üzümlerinden elde edilen Aşıkzade Organik Şıraz Özü, doğal tatlılığı ve zengin besin içeriğiyle öne çıkar. Antioksidanlar açısından zengin olan şıraz özü, enerji verir ve bağışıklık sistemini destekler. Kahvaltılarda, tatlılarda veya sağlıklı içeceklerde kullanabileceğiniz bu özel ürün, katkısız ve şeker ilavesizdir.', // Added
        'badge_image' => 'https://i.imgur.com/vLKmF3N.png' // Added - generic badge, change if needed
    ],
    2 => [
        'id' => 2,
        'name' => "Şiraz Özlü Alıç Özü",
        'image' => "https://i.imgur.com/LOEDyCw.png",
        'hero_image' => "https://i.imgur.com/FtV0KWE.png",
        'price' => 135.00,
        'dynamicBgClass' => "bg-product2-custom",
        'productNameBgTextType' => "dark",
        'description' => 'Aşıkzade Şiraz Özlü Alıç Özü, şifalı alıç meyvesinin faydalarını geleneksel şıraz özü ile birleştirir. Kalp ve damar sağlığını destekleyici özellikleriyle bilinen alıç, bu özel formülasyonla lezzetli ve sağlıklı bir alternatif sunar. Doğal yöntemlerle hazırlanmış, katkısız bir üründür.', // Added
        'badge_image' => 'https://i.imgur.com/vLKmF3N.png' // Added
    ],
    3 => [
        'id' => 3,
        'name' => "Diyabetik Şiraz Özü",
        'image' => "https://i.imgur.com/VYQJSgh.png",
        'hero_image' => "https://i.imgur.com/1GZKtHt.png",
        'price' => 150.00,
        'dynamicBgClass' => "bg-product3-custom",
        'productNameBgTextType' => "light",
        'description' => 'Özel olarak formüle edilmiş Aşıkzade Diyabetik Şiraz Özü, şeker hastalarının da güvenle tüketebileceği doğal bir tatlandırıcı ve enerji kaynağıdır. Düşük glisemik indeksi ile kan şekerini dengede tutmaya yardımcı olurken, şıraz üzümünün tüm faydalarını sunar.', // Added
        'badge_image' => 'https://i.imgur.com/vLKmF3N.png' // Added
    ],
    4 => [
        'id' => 4,
        'name' => "Kayısı Pekmezi",
        'image' => "https://i.imgur.com/pQIXgmW.png",
        'hero_image' => "https://i.imgur.com/rnpICDG.png",
        'price' => 90.00,
        'dynamicBgClass' => "bg-product4-custom",
        'productNameBgTextType' => "dark",
        'description' => 'Aşıkzade Kayısı Pekmezi, güneşte olgunlaşmış doğal kayısılardan üretilerek hem lezzeti hem de besin değeriyle öne çıkar. Yüksek lif içeriği sayesinde sindirim sistemini düzenlemeye yardımcı olurken, özellikle kabızlık sorunu yaşayanlar için doğal bir destek sunar. Potasyum, demir ve A vitamini bakımından zengindir.', // Added
        'badge_image' => 'https://i.imgur.com/vLKmF3N.png' // Added
    ],
    // You can add other products here with all the fields:
    // id, name, image, hero_image, price, dynamicBgClass, productNameBgTextType, description, badge_image
];

// Hero slider için JS'ye ürün verisi aktarımı
$mawaProductsJs = [];
foreach ($products as $id => $product) { // $id will be 1, 2, 3, 4...
    if (!empty($product['hero_image'])) {
        $mawaProductsJs[] = [ // Using [] ensures numerically indexed array for JS
            'name' => $product['name'],
            'image' => $product['hero_image'],
            'dynamicBgClass' => $product['dynamicBgClass'],
            'productNameBgTextType' => $product['productNameBgTextType']
        ];
    }
}

// Function to get a single product by its numerical ID
function get_product_by_id($id) {
    global $products; // Access the global $products array
    if (isset($products[$id])) {
        return $products[$id];
    }
    return null; // Return null if product with that ID is not found
}

// Your existing cookie-based cart count function
function get_cart_count() {
    $cart_items_count = 0;
    if (isset($_COOKIE['asikzade_cart'])) {
        $cart = json_decode($_COOKIE['asikzade_cart'], true);
        if (is_array($cart)) {
            foreach ($cart as $item_id => $item_data) {
                // Ensure item_data is an array and quantity key exists
                if (is_array($item_data) && isset($item_data['quantity'])) {
                    $cart_items_count += (int)$item_data['quantity'];
                }
                // If your cart structure is simpler, like $cart[product_id] = quantity
                // else if (is_numeric($item_data)) {
                //    $cart_items_count += (int)$item_data;
                // }
            }
        }
    }
    return $cart_items_count;
}
?>