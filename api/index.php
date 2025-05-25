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
            /* DEĞİŞTİRİLEN RENK */
            background-color: #fef6e6; /* Ana site içerik arka planı */
            
            clip-path: circle(0% at 50% 50%); 
            transition: clip-path 1.2s cubic-bezier(0.7, 0, 0.3, 1); 
            z-index: 10; 
            pointer-events: none; 
        }

        #paint-transition-overlay.active {
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
            const redirectTo = 'anasayfa.php'; // Yönlendirilecek sayfa
            const transitionDuration = 1200; // CSS ile aynı (milisaniye)

            if (video && paintOverlay) {
                video.onended = function() {
                    paintOverlay.classList.add('active');
                    setTimeout(() => {
                        window.location.href = redirectTo;
                    }, transitionDuration);
                };

                video.play().catch(error => {
                    console.warn("Video otomatik oynatma engellenmiş olabilir.", error);
                    let fallbackTimeout = setTimeout(() => {
                        if (video.paused && video.currentTime === 0 && !paintOverlay.classList.contains('active')) {
                             console.log("Video oynatılamadı, geçiş animasyonu ve yönlendirme tetikleniyor.");
                             paintOverlay.classList.add('active');
                             setTimeout(() => {
                                 window.location.href = redirectTo;
                             }, transitionDuration);
                        }
                    }, 5000); 

                    video.onplay = () => { 
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