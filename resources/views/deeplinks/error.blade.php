<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Shoot90' }}</title>
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
            height: 100vh;
            text-align: center;
        }
        .container {
            max-width: 600px;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .logo {
            width: 120px;
            height: auto;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 15px;
            color: #333;
        }
        p {
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 20px;
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
        <h1>{{ $title ?? 'Bir Şeyler Yanlış Gitti' }}</h1>
        <p>{{ $message ?? 'İçeriğe erişmeye çalışırken bir sorun oluştu.' }}</p>
        
        <div>
            <a href="shoot90://" class="btn">Uygulamayı Aç</a>
        </div>
        
        <p>Shoot90 uygulaması yüklü değil mi?</p>
        
        <div class="app-store-buttons">
            <a href="https://apps.apple.com/app/shoot90/id123456789">
                <img src="{{ asset('images/app-store.png') }}" alt="App Store" class="app-store-btn">
            </a>
            <a href="https://play.google.com/store/apps/details?id=com.shoot90.app">
                <img src="{{ asset('images/play-store.png') }}" alt="Play Store" class="app-store-btn">
            </a>
        </div>
    </div>
</body>
</html>
