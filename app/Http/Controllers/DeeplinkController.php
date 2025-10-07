<?php

namespace App\Http\Controllers;

use App\Helpers\UrlShortener;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class DeeplinkController extends Controller
{
    /**
     * Handle video deeplink
     *
     * @param string $videoId
     * @return \Illuminate\View\View
     */
    public function handleVideoDeeplink($videoId)
    {
        try {
            // Find the video by video_guid
            $video = Video::where('video_guid', $videoId)->first();
            
            // Log for debugging
            Log::info("Searching for video with guid: $videoId", [
                'found' => $video ? 'yes' : 'no'
            ]);
            
            if (!$video) {
                Log::warning("Video not found for deeplink: $videoId");
                return $this->renderErrorPage('Video bulunamadı', 'Aradığınız video mevcut değil veya kaldırılmış olabilir.');
            }
            
            // Get video details
            $videoData = [
                'id' => $video->video_guid, // Use video_guid as ID
                'video_guid' => $video->video_guid,
                'title' => $video->title ?? 'Shoot90 Video',
                'description' => $video->description ?? 'Shoot90 uygulamasında bir video',
                'thumbnail' => $video->thumbnail_url ?? config('app.url') . '/assets/images/default-thumbnail.jpg',
                'url' => route('deeplink.video', ['videoId' => $videoId]),
                'app_url' => "shoot90://v/$videoId",
                'creator' => null
            ];
            
            // Log successful video fetch
            Log::info("Video found for deeplink", [
                'video_guid' => $video->video_guid,
                'title' => $video->title
            ]);
            
            // Get user data if available
            if ($video->user_id) {
                $user = User::find($video->user_id);
                if ($user) {
                    $videoData['creator'] = [
                        'id' => $user->id,
                        'name' => $user->name . ' ' . $user->surname,
                        'nickname' => $user->nickname,
                        'avatar' => $user->avatar
                    ];
                } elseif (isset($video->user_data) && !empty($video->user_data)) {
                    // Use embedded user data if available
                    $userData = $video->user_data;
                    $videoData['creator'] = [
                        'id' => $userData['id'] ?? null,
                        'name' => ($userData['name'] ?? '') . ' ' . ($userData['surname'] ?? ''),
                        'nickname' => $userData['nickname'] ?? null,
                        'avatar' => $userData['avatar'] ?? null
                    ];
                }
            }
            
            // Track this view for analytics (devre dışı bırakıldı)
            // TODO: Implement deeplink tracking
            // UrlShortener::trackDeeplinkVisit('video', $videoId);
            
            // Render the deeplink page
            return view('deeplinks.video', [
                'video' => $videoData,
                'app_store_url' => config('app.ios_app_store_url', 'https://apps.apple.com/app/shoot90/id123456789'),
                'play_store_url' => config('app.android_play_store_url', 'https://play.google.com/store/apps/details?id=com.shoot90.app')
            ]);
        } catch (\Exception $e) {
            Log::error("Error handling video deeplink: " . $e->getMessage(), [
                'video_id' => $videoId,
                'exception' => $e
            ]);
            
            return $this->renderErrorPage('Bir hata oluştu', 'Video yüklenirken bir sorun oluştu. Lütfen daha sonra tekrar deneyin.');
        }
    }
    
    /**
     * Handle profile deeplink
     *
     * @param string $userId
     * @return \Illuminate\View\View
     */
    public function handleProfileDeeplink($userId)
    {
        try {
            // Find the user by id or guid
            $user = User::where('id', $userId)->orWhere('guid', $userId)->first();
            
            // Log for debugging
            Log::info("Searching for user with id/guid: $userId", [
                'found' => $user ? 'yes' : 'no'
            ]);
            
            if (!$user) {
                Log::warning("User not found for deeplink: $userId");
                return $this->renderErrorPage('Kullanıcı bulunamadı', 'Aradığınız kullanıcı mevcut değil veya hesabını kapatmış olabilir.');
            }
            
            // Get user details
            $userData = [
                'id' => $user->id,
                'name' => $user->name . ' ' . $user->surname,
                'nickname' => $user->nickname ?? '@' . strtolower(str_replace(' ', '', $user->name . $user->surname)),
                'bio' => $user->bio ?? 'Shoot90 kullanıcısı',
                'avatar' => $user->avatar ?? config('app.url') . '/assets/images/default-avatar.jpg',
                'url' => route('deeplink.user', ['userId' => $userId]),
                'app_url' => "shoot90://u/$userId",
                'video_count' => $user->video_count ?? 0,
                'followers_count' => $user->followers_count ?? 0,
                'following_count' => $user->following_count ?? 0
            ];
            
            // Track this view for analytics (devre dışı bırakıldı)
            // TODO: Implement deeplink tracking
            // UrlShortener::trackDeeplinkVisit('profile', $userId);
            
            // Render the deeplink page
            return view('deeplinks.profile', [
                'user' => $userData,
                'app_store_url' => config('app.ios_app_store_url', 'https://apps.apple.com/app/shoot90/id123456789'),
                'play_store_url' => config('app.android_play_store_url', 'https://play.google.com/store/apps/details?id=com.shoot90.app')
            ]);
        } catch (\Exception $e) {
            Log::error("Error handling profile deeplink: " . $e->getMessage(), [
                'user_id' => $userId,
                'exception' => $e
            ]);
            
            return $this->renderErrorPage('Bir hata oluştu', 'Profil yüklenirken bir sorun oluştu. Lütfen daha sonra tekrar deneyin.');
        }
    }
    
    /**
     * Handle share page
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function handleSharePage(Request $request)
    {
        try {
            $link = $request->query('link');
            $message = $request->query('message');
            
            if (!$link) {
                return $this->renderErrorPage('Geçersiz bağlantı', 'Paylaşım bağlantısı geçersiz veya eksik.');
            }
            
            // Parse the link to determine what's being shared
            $parsedUrl = parse_url($link);
            $path = $parsedUrl['path'] ?? '';
            $segments = explode('/', trim($path, '/'));
            
            $type = $segments[0] ?? '';
            $id = $segments[1] ?? '';
            
            $shareData = [
                'title' => 'Shoot90 Paylaşımı',
                'description' => $message ?? 'Shoot90 uygulamasından bir paylaşım',
                'image' => config('app.url') . '/assets/images/share-default.jpg',
                'url' => $link,
                'app_url' => "shoot90://$type/$id",
                'original_link' => $link
            ];
            
            // If it's a video link, try to get video details
            if ($type === 'v' && $id) {
                $video = Video::find($id);
                if ($video) {
                    $shareData['title'] = $video->title ?? 'Shoot90 Video';
                    $shareData['description'] = $video->description ?? ($message ?? 'Shoot90 uygulamasından bir video');
                    $shareData['image'] = $video->thumbnail_url ?? $shareData['image'];
                }
            }
            
            // If it's a profile link, try to get user details
            if ($type === 'u' && $id) {
                $user = User::find($id);
                if ($user) {
                    $shareData['title'] = $user->name . ' ' . $user->surname;
                    $shareData['description'] = $user->bio ?? ($message ?? 'Shoot90 kullanıcı profili');
                    $shareData['image'] = $user->avatar ?? $shareData['image'];
                }
            }
            
            // Track this share for analytics
            UrlShortener::trackDeeplinkVisit('share', $id ?: 'unknown', [
                'link' => $link,
                'message' => $message
            ]);
            
            // Render the share page
            return view('deeplinks.share', [
                'share' => $shareData,
                'app_store_url' => config('app.ios_app_store_url', 'https://apps.apple.com/app/shoot90/id123456789'),
                'play_store_url' => config('app.android_play_store_url', 'https://play.google.com/store/apps/details?id=com.shoot90.app')
            ]);
        } catch (\Exception $e) {
            Log::error("Error handling share page: " . $e->getMessage(), [
                'link' => $request->query('link'),
                'exception' => $e
            ]);
            
            return $this->renderErrorPage('Bir hata oluştu', 'Paylaşım sayfası yüklenirken bir sorun oluştu. Lütfen daha sonra tekrar deneyin.');
        }
    }
    
    /**
     * Render error page
     *
     * @param string $title
     * @param string $message
     * @return \Illuminate\View\View
     */
    private function renderErrorPage($title, $message)
    {
        // Log error for debugging
        Log::info('Rendering error page', [
            'title' => $title,
            'message' => $message
        ]);
        
        return view('deeplinks.error', [
            'title' => $title,
            'message' => $message,
            'app_store_url' => config('app.ios_app_store_url', 'https://apps.apple.com/app/shoot90/id123456789'),
            'play_store_url' => config('app.android_play_store_url', 'https://play.google.com/store/apps/details?id=com.shoot90.app')
        ]);
    }
}
