<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Açılış - Boya Efekti</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            overflow: hidden;
            background-color: #000; /* Video altındaki varsayılan arka plan */
        }

        #video-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1; /* Video en altta */
        }

        #introVideo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        #paint-transition-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /* BURAYA İSTEDİĞİNİZ BOYA RENGİNİ GİRİN */
            background-color: #4A0D66; /* Örnek: Koyu Mor bir renk */
            /* background-color: #D93B26; */ /* Örnek: Koyu Turuncu/Kırmızı bir renk */
            /* background-color: #006470; */ /* Örnek: Koyu Teal bir renk */
            
            /* Başlangıçta tamamen görünmez ve ekranın dışında gibi */
            clip-path: circle(0% at 50% 50%); /* Merkezden %0 büyüklüğünde bir daire */
            /* Veya farklı bir başlangıç noktası: */
            /* clip-path: circle(0% at 0% 100%); */ /* Sol alttan */
            /* clip-path: circle(0% at 100% 0%); */ /* Sağ üstten */
            
            transition: clip-path 1.2s cubic-bezier(0.7, 0, 0.3, 1); /* Yumuşak ve dinamik bir yayılma */
            z-index: 10; /* Videonun üzerinde, her şeyin üzerinde */
            pointer-events: none; /* Üzerindeki tıklamaları engellemesin (gerekirse) */
        }

        #paint-transition-overlay.active {
            /* Ekranı tamamen kaplayacak kadar büyük bir daire */
            /* %150, köşegenleri de kaplamayı garanti eder */
            clip-path: circle(150% at 50% 50%); 
        }
    </style>
</head>
<body>
    <div id="video-container">
        <video id="introVideo" autoplay muted playsinline>
            <source src="https://i.imgur.com/KT0EDTZ.mp4" type="video/mp4">
            Tarayıcınız video etiketini desteklemiyor.
        </video>
    </div>

    <div id="paint-transition-overlay"></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const video = document.getElementById('introVideo');
            const paintOverlay = document.getElementById('paint-transition-overlay');
            const redirectTo = 'anasayfa.php';
            // CSS'teki transition süresiyle aynı olmalı (milisaniye)
            const transitionDuration = 1200; 

            if (video && paintOverlay) {
                video.onended = function() {
                    // Boya yayılma animasyonunu başlat
                    paintOverlay.classList.add('active');

                    // Yayılma animasyonu bittikten sonra yönlendir
                    setTimeout(() => {
                        window.location.href = redirectTo;
                    }, transitionDuration);
                };

                video.play().catch(error => {
                    console.warn("Video otomatik oynatma engellenmiş olabilir.", error);
                    // Oynatma başlamazsa diye güvenlik önlemi:
                    // Belirli bir süre sonra yine de geçişi tetikle
                    let fallbackTimeout = setTimeout(() => {
                        if (video.paused && video.currentTime === 0 && !paintOverlay.classList.contains('active')) {
                             console.log("Video oynatılamadı, geçiş animasyonu ve yönlendirme tetikleniyor.");
                             paintOverlay.classList.add('active');
                             setTimeout(() => {
                                 window.location.href = redirectTo;
                             }, transitionDuration);
                        }
                    }, 5000); // 5 saniye sonra kontrol et

                    video.onplay = () => { // Video oynamaya başlarsa fallback'i iptal et
                        clearTimeout(fallbackTimeout);
                    };
                });

            } else {
                console.error("Gerekli elementler (video veya overlay) bulunamadı. Doğrudan yönlendiriliyor.");
                window.location.href = redirectTo;
            }
        });
    </script>
</body>
</html>