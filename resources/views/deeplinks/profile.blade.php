<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $user['name'] ?? 'Shoot90 Kullanıcı Profili' }}</title>
    
    <!-- Meta tags for SEO and social sharing -->
    <meta name="description" content="{{ $user['bio'] ?? 'Shoot90 kullanıcı profili' }}">
    <meta property="og:title" content="{{ $user['name'] ?? 'Shoot90 Kullanıcı Profili' }}">
    <meta property="og:description" content="{{ $user['bio'] ?? 'Shoot90 kullanıcı profili' }}">
    <meta property="og:image" content="{{ $user['avatar'] ?? asset('images/default-avatar.jpg') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="profile">
    <meta property="og:site_name" content="Shoot90">
    
    <!-- Twitter Card data -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $user['name'] ?? 'Shoot90 Kullanıcı Profili' }}">
    <meta name="twitter:description" content="{{ $user['bio'] ?? 'Shoot90 kullanıcı profili' }}">
    <meta name="twitter:image" content="{{ $user['avatar'] ?? asset('images/default-avatar.jpg') }}">
    
    <!-- App deep linking -->
    <meta name="apple-itunes-app" content="app-id=123456789, app-argument={{ $user['app_url'] }}">
    <meta name="google-play-app" content="app-id=com.shoot90.app">
    
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
        }
        .container {
            max-width: 600px;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 20px;
        }
        .logo {
            width: 120px;
            height: auto;
            margin-bottom: 20px;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            object-fit: cover;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 5px;
            color: #333;
        }
        .nickname {
            font-size: 18px;
            color: #666;
            margin-bottom: 15px;
        }
        p {
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 20px;
            color: #666;
        }
        .stats {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .stat {
            margin: 0 15px;
            text-align: center;
        }
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        .btn {
            display: inline-block;
            background-color: #FF5722;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 4px;
            font-weight: bold;
            margin: 10px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #E64A19;
        }
        .app-store-buttons {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .app-store-btn {
            margin: 10px;
            height: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="{{ asset('images/logo.png') }}" alt="Shoot90 Logo" class="logo">
        
        <img src="{{ $user['avatar'] ?? asset('images/default-avatar.jpg') }}" alt="{{ $user['name'] }}" class="profile-avatar">
        
        <h1>{{ $user['name'] }}</h1>
        <div class="nickname">{{ $user['nickname'] }}</div>
        
        <p>{{ $user['bio'] }}</p>
        
        <div class="stats">
            <div class="stat">
                <div class="stat-value">{{ $user['video_count'] }}</div>
                <div class="stat-label">Video</div>
            </div>
            <div class="stat">
                <div class="stat-value">{{ $user['followers_count'] }}</div>
                <div class="stat-label">Takipçi</div>
            </div>
            <div class="stat">
                <div class="stat-value">{{ $user['following_count'] }}</div>
                <div class="stat-label">Takip</div>
            </div>
        </div>
        
        <div>
            <a href="{{ $user['app_url'] }}" class="btn">Profili Uygulamada Görüntüle</a>
        </div>
        
        <p>Shoot90 uygulaması yüklü değil mi?</p>
        
        <div class="app-store-buttons" id="app-stores">
            <a href="{{ $app_store_url }}">
                <img src="{{ asset('images/app-store.png') }}" alt="App Store" class="app-store-btn">
            </a>
            <a href="{{ $play_store_url }}">
                <img src="{{ asset('images/play-store.png') }}" alt="Play Store" class="app-store-btn">
            </a>
        </div>
    </div>
    
    <script>
        // Mobil cihazda mıyız?
        if (/Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            // Uygulamayı açmayı dene
            window.location.href = "{{ $user['app_url'] }}";
            
            // Uygulama açılmazsa, 2 saniye sonra mağaza sayfalarına yönlendirme seçeneği göster
            setTimeout(function() {
                document.getElementById('app-stores').style.display = 'block';
            }, 2000);
        }
    </script>
</body>
</html>
