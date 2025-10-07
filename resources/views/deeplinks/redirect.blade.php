<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Shoot90</title>

    <!-- Meta tags for SEO and social sharing -->
    <meta name="description" content="{{ $description }}">
    <meta property="og:title" content="{{ $title }} - Shoot90">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:image" content="{{ $image }}">
    <meta property="og:url" content="{{ $fallback_url }}">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">

    <!-- App links for deep linking -->
    <meta property="al:ios:url" content="{{ $app_url }}">
    <meta property="al:ios:app_store_id" content="123456789">
    <meta property="al:ios:app_name" content="Shoot90">
    <meta property="al:android:url" content="{{ $app_url }}">
    <meta property="al:android:package" content="com.shoot90.app">
    <meta property="al:android:app_name" content="Shoot90">

    <!-- Apple specific meta tags -->
    <meta name="apple-itunes-app" content="app-id=123456789, app-argument={{ $app_url }}">

    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .logo {
            max-width: 150px;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
        }
        p {
            color: #666;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 10px;
        }
        .store-buttons {
            margin-top: 30px;
        }
        .store-btn {
            display: inline-block;
            margin: 10px;
        }
        .store-btn img {
            height: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="{{ asset('assets/images/logo.png') }}" alt="Shoot90 Logo" class="logo">
        <h1>Shoot90 Uygulamasına Yönlendiriliyorsunuz</h1>
        <p>Uygulama açılmıyorsa, aşağıdaki butonları kullanabilirsiniz:</p>

        <a href="{{ $app_url }}" class="btn">Uygulamayı Aç</a>

        <div class="store-buttons">
            <a href="{{ $ios_store_url }}" class="store-btn">
                <img src="{{ asset('assets/images/app-store-badge.png') }}" alt="App Store">
            </a>
            <a href="{{ $android_store_url }}" class="store-btn">
                <img src="{{ asset('assets/images/google-play-badge.png') }}" alt="Google Play">
            </a>
        </div>
    </div>

    <script>
        // Redirect to app if installed, otherwise show this page
        window.onload = function() {
            // Try to open the app
            window.location.href = "{{ $app_url }}";

            // Set a timeout to redirect to app store if the app isn't installed
            setTimeout(function() {
                // Check if we're on iOS or Android
                var userAgent = navigator.userAgent || navigator.vendor || window.opera;
                if (/iPad|iPhone|iPod/.test(userAgent) && !window.MSStream) {
                    window.location.href = "{{ $ios_store_url }}";
                } else if (/android/i.test(userAgent)) {
                    window.location.href = "{{ $android_store_url }}";
                }
            }, 2000);
        };
    </script>
</body>
</html>
