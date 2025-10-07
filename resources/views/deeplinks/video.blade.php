<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isset($video['creator']) ? ($video['creator']['name'] . ' - ' . $video['description']) : 'Shoot90 Video' }}</title>

    <!-- Meta tags for SEO and social sharing -->
    <meta name="description" content="{{ $video['description'] ?? 'Shoot90 uygulamasında bir video' }}">
    <meta property="og:title" content="{{ isset($video['creator']) ? ($video['creator']['name'] . ' - ' . $video['description']) : 'Shoot90 Video' }}">
    <meta property="og:description" content="{{ $video['description'] ?? 'Shoot90 uygulamasında bir video' }}">
    <meta property="og:image" content="{{ $video['thumbnail'] ?? asset('images/default-thumbnail.jpg') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="video">
    <meta name="twitter:card" content="summary_large_image">

    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #000;
            color: #fff;
            font-family: Arial, sans-serif;
            width: 100%;
            height: 100%;
            min-height: 100vh;
            min-height: -webkit-fill-available;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: fixed;
            overflow: hidden;
            -webkit-overflow-scrolling: touch;
        }



        .video-container {
            position: relative;
            width: 100%;
            height: 100vh;
            height: -webkit-fill-available;
            max-width: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            padding-bottom: env(safe-area-inset-bottom, 0);
        }

        /* Desktop styles */
        @media (min-width: 769px) {
            .video-container {
                width: calc(56.25vh); /* 9/16 = 0.5625 */
                max-width: 100%;
                margin: 0 auto;
                position: relative;
                background-color: #000;
            }

            .video-thumbnail {
                max-width: 100%;
                max-height: 100%;
                width: auto;
                height: auto;
                object-fit: contain;
            }
        }

        /* Mobile styles */
        @media (max-width: 768px) {
            .video-container {
                width: 100%;
                height: 100vh;
                height: -webkit-fill-available;
            }

            .video-thumbnail {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .store-links {
                display: flex;
            }
        }

        .brand-logo {
            position: absolute;
            top: max(20px, env(safe-area-inset-top, 20px));
            left: 20px;
            width: 120px;
            height: auto;
            z-index: 10;
        }

        .store-links {
            display: flex;
            position: absolute;
            top: max(20px, env(safe-area-inset-top, 20px));
            right: 20px;
            z-index: 10;
            flex-direction: column;
            gap: 10px;
            align-items: flex-end;
        }

        .store-link {
            display: inline-block;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        @media (max-width: 768px) {
            .brand-logo {
                width: 100px;
                top: max(20px, env(safe-area-inset-top, 20px));
                left: 20px;
            }
            
            .store-links {
                top: max(20px, env(safe-area-inset-top, 20px));
                right: 20px;
            }
        }

        .video-thumbnail {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -khtml-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        .play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80px;
            height: 80px;
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            z-index: 10;
        }

        .play-button::after {
            content: '';
            width: 0;
            height: 0;
            border-top: 20px solid transparent;
            border-left: 30px solid white;
            border-bottom: 20px solid transparent;
            margin-left: 5px;
        }

        .user-info {
            position: fixed;
            bottom: calc(20px + env(safe-area-inset-bottom, 0));
            left: calc(20px + env(safe-area-inset-left, 0));
            display: flex;
            align-items: center;
            z-index: 10;
            background-color: rgba(0, 0, 0, 0.5);
            padding: 8px 12px;
            border-radius: 30px;
            max-width: calc(100% - 40px - env(safe-area-inset-left, 0) - env(safe-area-inset-right, 0));
        }

        .user-info.no-avatar {
            padding-left: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            border: 2px solid white;
            display: none; /* Hidden by default */
        }

        .user-avatar.visible {
            display: block; /* Show when has avatar */
        }

        @media (max-width: 768px) {
            .user-avatar {
                width: 32px;
                height: 32px;
            }
        }

        .user-name {
            font-size: 16px;
            font-weight: bold;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);
        }

        .open-app-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 15px 32px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 4px;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            animation: pulse 2s infinite;
        }

        .bottom-app-btn {
            position: fixed;
            bottom: calc(20px + env(safe-area-inset-bottom, 0));
            right: calc(20px + env(safe-area-inset-right, 0));
            z-index: 10;
        }

        @media (max-width: 768px) {
            .open-app-btn {
                padding: 10px 20px;
                font-size: 14px;
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            }
        }





        @media (max-width: 768px) {
            .store-links {
                bottom: 70px;
                right: 10px;
            }
        }


    </style>
</head>
<body>

    <div class="video-container">
        <img src="{{ $video['thumbnail'] ?? asset('images/default-thumbnail.jpg') }}" alt="Video Thumbnail" class="video-thumbnail">
        <img src="https://api.shoot90.com/assets_admin/images/brand.svg" alt="Shoot90 Brand" class="brand-logo">
        <div class="play-button"></div>

        @if(isset($video['creator']) && !empty($video['creator']['name']))
        <div class="user-info {{ empty($video['creator']['avatar']) ? 'no-avatar' : '' }}">
            @if(!empty($video['creator']['avatar']))
            <img src="{{ $video['creator']['avatar'] }}" alt="User Avatar" class="user-avatar visible">
            @else
            <img src="" alt="" class="user-avatar">
            @endif
            <div class="user-name">{{ $video['creator']['name'] }}</div>
        </div>
        @endif

        <a href="shoot90://v/{{ $video['video_guid'] ?? $video['id'] }}" class="open-app-btn bottom-app-btn" id="bottom-open-app-btn">Uygulamada Aç</a>

        <div class="store-links" id="store-links">
            <a href="{{ $app_store_url }}" class="store-link">App Store'dan İndir</a>
            <a href="{{ $play_store_url }}" class="store-link">Google Play'den İndir</a>
        </div>
    </div>

    <script>
        // Sayfa yüklendiğinde hemen uygulamaya yönlendir
        window.onload = function() {
            var appUrl = "shoot90://v/{{ $video['video_guid'] ?? $video['id'] }}";
            console.log('Trying to open app with URL:', appUrl);

            // iOS için
            if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
                window.location.href = appUrl;

                // Uygulama yüklü değilse, store linklerini göster
                setTimeout(function() {
                    if (document.hasFocus()) {
                        document.getElementById('store-links').style.display = 'block';
                        document.getElementById('top-store-links').style.display = 'block';
                    }
                }, 2000);
            }
            // Android için
            else if (/Android/i.test(navigator.userAgent)) {
                var intentUrl = "intent://v/{{ $video['video_guid'] ?? $video['id'] }}#Intent;scheme=shoot90;package=com.shoot90.app;end";
                window.location.href = intentUrl;

                // Uygulama yüklü değilse, store linklerini göster
                setTimeout(function() {
                    if (document.hasFocus()) {
                        document.getElementById('store-links').style.display = 'block';
                        document.getElementById('top-store-links').style.display = 'block';
                    }
                }, 2000);
            }
        };

        // Alt butona tıklandığında uygulamayı aç
        document.getElementById('bottom-open-app-btn').addEventListener('click', function(e) {
            openApp();
        });

        // Play butonuna tıklandığında uygulamayı aç
        document.querySelector('.play-button').addEventListener('click', function(e) {
            openApp();
        });

        // Uygulamayı açma fonksiyonu
        function openApp() {
            var appUrl = "shoot90://v/{{ $video['video_guid'] ?? $video['id'] }}";
            console.log('Button clicked, opening app with URL:', appUrl);

            if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
                window.location.href = appUrl;

                // Uygulama yüklü değilse, store linklerini göster
                setTimeout(function() {
                    if (document.hasFocus()) {
                        document.getElementById('store-links').style.display = 'block';
                        document.getElementById('top-store-links').style.display = 'block';
                    }
                }, 2000);
            } else if (/Android/i.test(navigator.userAgent)) {
                var intentUrl = "intent://v/{{ $video['video_guid'] ?? $video['id'] }}#Intent;scheme=shoot90;package=com.shoot90.app;end";
                window.location.href = intentUrl;

                // Uygulama yüklü değilse, store linklerini göster
                setTimeout(function() {
                    if (document.hasFocus()) {
                        document.getElementById('store-links').style.display = 'block';
                        document.getElementById('top-store-links').style.display = 'block';
                    }
                }, 2000);
            }
        }
    </script>
</body>
</html>
