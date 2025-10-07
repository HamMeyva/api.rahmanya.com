<?php

namespace App\Helpers;

use App\Models\ShortUrl;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class UrlShortener
{
    /**
     * URL kısaltma işlemi
     *
     * @param string $originalUrl Kısaltılacak orijinal URL
     * @param string $type URL tipi (video, user, vs.)
     * @param string|null $entityId İlgili varlığın ID'si (video_id, user_id, vs.)
     * @param array $metadata Ek meta veriler
     * @return ShortUrl
     */
    public static function shorten($originalUrl, $type = 'general', $entityId = null, $metadata = [])
    {
        // Aynı URL için daha önce oluşturulmuş bir kısa URL var mı kontrol et
        $existingUrl = ShortUrl::query()
            ->where('original_url', $originalUrl)
            ->where('type', $type)
            ->where('entity_id', $entityId)
            ->first();
            
        if ($existingUrl) {
            // Varsa istatistiklerini güncelle ve mevcut kaydı döndür
            $existingUrl->increment('request_count');
            return $existingUrl;
        }
        
        // Benzersiz bir kısa kod oluştur
        do {
            $shortCode = Str::random(8);
        } while (
            ShortUrl::query()
                ->where('short_code', $shortCode)
                ->exists());
        
        // Yeni kısa URL oluştur
        $shortUrl = ShortUrl::create([
            'short_code' => $shortCode,
            'original_url' => $originalUrl,
            'type' => $type,
            'entity_id' => $entityId,
            'metadata' => $metadata,
            'request_count' => 0,
            'redirect_count' => 0,
        ]);
        
        // Cache'e ekle (24 saat)
        Cache::put("short_url:{$shortCode}", $shortUrl, 86400);
        
        return $shortUrl;
    }
    
    /**
     * Kısa kodu çözerek orijinal URL'yi döndür
     *
     * @param string $shortCode Çözülecek kısa kod
     * @param bool $trackRedirect Yönlendirme sayısını artır
     * @return ShortUrl
     */
    public static function resolve($shortCode, $trackRedirect = true)
    {
        // Önce cache'den kontrol et
        $cachedUrl = Cache::get("short_url:{$shortCode}");
        
        if ($cachedUrl) {
            if ($trackRedirect) {
                // Cache'den geldiği için DB'yi ayrıca güncelle
                ShortUrl::where('short_code', $shortCode)->increment('redirect_count');
            }
            return $cachedUrl;
        }
        
        // Cache'de yoksa DB'den getir
        $shortUrl = ShortUrl::query()
            ->where('short_code', $shortCode)
            ->firstOrFail();
            
        if ($trackRedirect) {
            $shortUrl->increment('redirect_count');
        }
        
        // Cache'e ekle (24 saat)
        Cache::put("short_url:{$shortCode}", $shortUrl, 86400);
        
        return $shortUrl;
    }
    
    /**
     * Deeplink URL'si oluştur
     *
     * @param string $type Deeplink tipi (video, user, vs.)
     * @param string $id İlgili varlığın ID'si
     * @param array $params Ek parametreler
     * @return string Deeplink URL'si
     */
    public static function createDeeplink($type, $id, $params = [])
    {
        $baseUrl = Config::get('app.url');
        $appScheme = Config::get('app.mobile_scheme', 'shoot90');
        
        // Web URL'si oluştur (fallback için)
        $webUrl = "{$baseUrl}/{$type}/{$id}";
        
        if (!empty($params)) {
            $queryString = http_build_query($params);
            $webUrl .= "?{$queryString}";
        }
        
        // Deeplink için app URL'si oluştur
        $appUrl = "{$appScheme}://{$type}/{$id}";
        
        if (!empty($params)) {
            $queryString = http_build_query($params);
            $appUrl .= "?{$queryString}";
        }
        
        // Metadata oluştur
        $metadata = [
            'type' => $type,
            'id' => $id,
            'app_url' => $appUrl,
            'web_url' => $webUrl,
            'params' => $params
        ];
        
        // Kısa URL oluştur
        $shortUrl = self::shorten($webUrl, $type, $id, $metadata);
        
        return $shortUrl;
    }
    
    /**
     * Video için deeplink oluştur
     *
     * @param string $videoId Video ID'si
     * @param array $params Ek parametreler
     * @return ShortUrl
     */
    public static function createVideoDeeplink($videoId, $params = [])
    {
        return self::createDeeplink('video', $videoId, $params);
    }
    
    /**
     * Kullanıcı için deeplink oluştur
     *
     * @param string $userId Kullanıcı ID'si
     * @param array $params Ek parametreler
     * @return ShortUrl
     */
    public static function createUserDeeplink($userId, $params = [])
    {
        return self::createDeeplink('user', $userId, $params);
    }
    
    /**
     * Deeplink HTML sayfası oluştur
     * 
     * @param ShortUrl $shortUrl Kısa URL objesi
     * @return string HTML içeriği
     */
    public static function generateDeeplinkHtml(ShortUrl $shortUrl)
    {
        $metadata = $shortUrl->metadata;
        $appUrl = $metadata['app_url'] ?? '';
        $webUrl = $metadata['web_url'] ?? $shortUrl->original_url;
        $type = $metadata['type'] ?? 'general';
        $id = $metadata['id'] ?? '';
        
        $appName = Config::get('app.name', 'Shoot90');
        $appStoreUrl = Config::get('app.ios_store_url', 'https://apps.apple.com/app/shoot90');
        $playStoreUrl = Config::get('app.android_store_url', 'https://play.google.com/store/apps/details?id=com.shoot90.app');
        
        // Deeplink HTML şablonu
        $html = <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$appName}</title>
    <meta property="og:title" content="{$appName}">
    <meta property="og:description" content="Shoot90 uygulamasında içeriği görüntüleyin">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{$webUrl}">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            text-align: center;
        }
        .container {
            background-color: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            max-width: 90%;
            width: 400px;
        }
        .logo {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }
        p {
            color: #666;
            margin-bottom: 25px;
            font-size: 16px;
        }
        .button {
            display: block;
            background-color: #FF5722;
            color: white;
            text-decoration: none;
            padding: 14px 20px;
            border-radius: 8px;
            font-weight: bold;
            margin-bottom: 15px;
            transition: background-color 0.2s;
        }
        .button:hover {
            background-color: #E64A19;
        }
        .store-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .store-button {
            flex: 1;
            margin: 0 5px;
            padding: 10px;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            border: 1px solid #ddd;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .store-button img {
            width: 20px;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="/logo.png" alt="{$appName} Logo" class="logo">
        <h1>{$appName} Uygulamasına Yönlendiriliyorsunuz</h1>
        <p>Eğer otomatik olarak yönlendirilmezseniz, aşağıdaki butona tıklayın.</p>
        <a href="{$appUrl}" class="button">Uygulamayı Aç</a>
        <p>Uygulama yüklü değil mi?</p>
        <div class="store-buttons">
            <a href="{$appStoreUrl}" class="store-button">
                <img src="/apple-icon.png" alt="App Store">
                App Store
            </a>
            <a href="{$playStoreUrl}" class="store-button">
                <img src="/google-play-icon.png" alt="Google Play">
                Google Play
            </a>
        </div>
    </div>
    <script>
        // Sayfa yüklendiğinde uygulamayı açmayı dene
        window.onload = function() {
            // iOS ve Android için farklı davranış
            var userAgent = navigator.userAgent || navigator.vendor || window.opera;
            var isIOS = /iPad|iPhone|iPod/.test(userAgent) && !window.MSStream;
            var isAndroid = /android/i.test(userAgent);
            
            // Deeplink URL'si
            var appUrl = "{$appUrl}";
            var webUrl = "{$webUrl}";
            var appStoreUrl = "{$appStoreUrl}";
            var playStoreUrl = "{$playStoreUrl}";
            
            // Timeout süresi (ms)
            var timeout = 2000;
            
            // Yönlendirme fonksiyonu
            function redirect() {
                // Önce uygulamayı açmayı dene
                window.location.href = appUrl;
                
                // Timeout ile store'a yönlendirme
                setTimeout(function() {
                    if (isIOS) {
                        window.location.href = appStoreUrl;
                    } else if (isAndroid) {
                        window.location.href = playStoreUrl;
                    } else {
                        window.location.href = webUrl;
                    }
                }, timeout);
            }
            
            // Yönlendirmeyi başlat
            redirect();
        };
    </script>
</body>
</html>
HTML;

        return $html;
    }
}
