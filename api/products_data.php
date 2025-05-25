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
        'description' => 'Aşıkzade Natural’ın özenle ürettiği organik Şiraz üzümü özü, doğal içeriği ve yüksek antioksidan değeriyle sağlıklı yaşamı destekleyen güçlü bir takviyedir. Şiraz üzümünde doğal olarak bulunan resveratrol ve flavonoidler, vücutta serbest radikallerle savaşarak hücre yenilenmesini destekler ve bağışıklık sistemini güçlendirir. Kalp ve damar sağlığına katkı sağladığı bilinen bu doğal öz, düzenli kullanımda enerji seviyesini artırabilir ve metabolizmayı dengede tutmaya yardımcı olabilir. Aynı zamanda sindirimi kolaylaştırıcı etkileriyle mide sağlığını desteklerken, cildin daha canlı ve sağlıklı görünmesine de katkıda bulunur. Katkı maddesi içermeyen saf formülüyle Aşıkzade Natural Şiraz üzümü özü, doğadan gelen şifayı günlük rutininize dahil etmenin en doğal yoludur.', // Added
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
        'description' => 'Aşıkzade’nin özenle formüle ettiği Alıçlı Şiraz Üzümü Özü, iki güçlü bitkisel içeriğin birleşimiyle kalp ve damar sağlığına doğal destek sunar. Şiraz üzümünün zengin antioksidan profili, özellikle resveratrol sayesinde hücre yenilenmesini desteklerken, alıç meyvesi kalp ritmini düzenlemeye ve tansiyonu dengelemeye yardımcı olur. Bu doğal öz, dolaşım sistemini güçlendirmek, bağışıklığı artırmak ve vücutta oluşan stresin etkilerini hafifletmek isteyenler için ideal bir tercihtir. Aynı zamanda sindirim sistemine dost olan bu karışım, yorgunluk ve halsizlik gibi günlük şikâyetlerin giderilmesinde de destekleyici rol oynar. Şeker ilavesiz, katkısız ve tamamen doğal içeriğiyle Aşıkzade Natural Alıçlı Şiraz Üzümü Özü, doğanın kalpten gelen iyiliğini sofralarınıza taşır.', // Added
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
        'description' => 'Aşıkzade’nin diyabetik bireyler için özel olarak geliştirdiği Şiraz üzümü özü, doğal içeriği ve düşük glisemik etkisiyle kan şekeri dengesini desteklemeye yardımcı olur. İçeriğindeki güçlü antioksidanlar, özellikle de resveratrol ve polifenoller, hücreleri oksidatif strese karşı korurken metabolizmayı dengeleyici etkiler sunar. Tatlandırıcı ya da rafine şeker içermeyen bu özel formül, hem bağışıklık sistemini destekler hem de enerji seviyelerini doğal yoldan artırır. Kalp sağlığını korumaya katkıda bulunan ve sindirim sistemini destekleyen yapısıyla, diyabetli bireylerin günlük yaşamlarında güvenle kullanabilecekleri doğal bir destektir. Aşıkzade Natural Diyabetik Şiraz Üzümü Özü, sağlığına dikkat edenler için doğadan gelen dengeli ve lezzetli bir çözümdür.', // Added
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
        'description' => 'Aşıkzade Kayısı Pekmezi, güneşte olgunlaşmış doğal kayısılardan üretilerek hem lezzeti hem de besin değeriyle öne çıkar. Yüksek lif içeriği sayesinde sindirim sistemini düzenlemeye yardımcı olurken, özellikle kabızlık sorunu yaşayanlar için doğal bir destek sunar. Potasyum, demir ve A vitamini bakımından zengin olan kayısı pekmezi, bağışıklık sistemini güçlendirir ve cilt sağlığını destekler. Enerji verici özelliğiyle çocuklardan yetişkinlere kadar herkesin günlük beslenmesinde yer alabilecek sağlıklı bir alternatiftir. Katkı maddesi ve ilave şeker içermeyen formülüyle Aşıkzade Natural Kayısı Pekmezi, doğallığı ön planda tutanlar için hem şifa hem de lezzet kaynağıdır.', // Added
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