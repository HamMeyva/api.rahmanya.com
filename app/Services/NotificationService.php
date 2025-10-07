<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\Relations\UserDevice;
use Illuminate\Support\Facades\Event;

class NotificationService
{
    /**
     * @var FirebaseNotificationService
     */
    protected $firebaseService;

    /**
     * @var NetGsmSmsService
     */
    protected $smsService;

    /**
     * NotificationService constructor.
     *
     * @param FirebaseNotificationService $firebaseService
     * @param NetGsmSmsService $smsService
     */
    public function __construct(
        FirebaseNotificationService $firebaseService,
        NetGsmSmsService $smsService
    ) {
        $this->firebaseService = $firebaseService;
        $this->smsService = $smsService;
    }

    /**
     * Kullanıcıya bildirim gönderir
     *
     * @param User|string $user User model veya user_id
     * @param string $template Bildirim şablonu
     * @param array $data Bildirim verisi
     * @param string|array $channels Bildirim kanalları ('APP', 'PUSH', 'SMS', 'EMAIL', 'REALTIME')
     * @return array
     */
    public function notifyUser($user, string $template, array $data = [], $channels = ['APP'])
    {
        if (!$user instanceof User) {
            $user = User::find($user);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
        }

        $result = [
            'app' => false,
            'push' => false,
            'sms' => false,
            'email' => false,
            'realtime' => false
        ];

        $channels = is_array($channels) ? $channels : [$channels];

        // Her kanal için bildirimi gönder
        foreach ($channels as $channel) {
            switch (strtoupper($channel)) {
                case 'APP':
                    $result['app'] = $this->sendAppNotification($user, $template, $data);
                    break;
                case 'PUSH':
                    $result['push'] = $this->sendPushNotification($user, $template, $data);
                    break;
                case 'REALTIME':
                    $result['realtime'] = $this->sendRealtimeNotification($user, $template, $data);
                    break;
                case 'SMS':
                    $result['sms'] = $this->sendSmsNotification($user, $template, $data);
                    break;
                case 'EMAIL':
                    // E-posta bildirimi entegrasyonu
                    break;
            }
        }

        return [
            'success' => in_array(true, $result),
            'channels' => $result
        ];
    }

    /**
     * Kullanıcının takipçilerine bildirim gönderir
     *
     * @param User|string $user Kullanıcı veya kullanıcı ID'si
     * @param string $template Bildirim şablonu
     * @param array $data Bildirim verisi
     * @param string|array $channels Bildirim kanalları
     * @param int $batchSize Toplu işlem boyutu
     * @return array
     */
    public function notifyFollowers($user, string $template, array $data = [], $channels = ['APP'], int $batchSize = 100)
    {
        if (!$user instanceof User) {
            $user = User::find($user);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
        }

        // Kullanıcı bilgilerini data içine ekleyelim
        $data = array_merge($data, [
            'user_id' => $user->id,
            'username' => $user->username,
            'nickname' => $user->nickname,
            'avatar' => $user->profile_photo_url,
            'streamer' => $user->nickname ?? $user->username
        ]);

        // Takipçileri bul - burada takip eden doğru kullanıcıları bulmanız gerekiyor
        $followers = \App\Models\Follow::where('followed_id', $user->id)
            ->where('status', 'approved')
            ->select('follower_id')
            ->get()
            ->pluck('follower_id')
            ->toArray();

        if (empty($followers)) {
            return [
                'success' => false,
                'message' => 'No followers to notify'
            ];
        }

        // Sonuç takibi için array
        $result = [
            'total' => count($followers),
            'success' => 0,
            'failures' => 0
        ];

        // Takipçileri batch'ler halinde işle
        foreach (array_chunk($followers, $batchSize) as $batch) {
            foreach ($batch as $followerId) {
                $notifyResult = $this->notifyUser($followerId, $template, $data, $channels);

                if ($notifyResult['success']) {
                    $result['success']++;
                } else {
                    $result['failures']++;
                }
            }
        }

        return [
            'success' => $result['success'] > 0,
            'result' => $result
        ];
    }

    /**
     * Uygulama içi bildirim gönderir
     *
     * @param User $user
     * @param string $template
     * @param array $data
     * @return bool
     */
    protected function sendAppNotification(User $user, string $template, array $data): bool
    {
        try {
            // Uygulama içi bildirim mantığı
            // Örnek: $notification = new \App\Models\Notification();
            return true;
        } catch (\Exception $e) {
            Log::error('App Notification Error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'template' => $template,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Firebase üzerinden push bildirim gönderir
     *
     * @param User $user
     * @param string $template
     * @param array $data
     * @return bool
     */
    protected function sendPushNotification(User $user, string $template, array $data): bool
    {
        try {
            // Kullanıcının cihaz token'larını al
            $tokens = UserDevice::where('user_id', $user->id)
                ->whereNotNull('fcm_token')
                ->where('is_active', true)
                ->pluck('fcm_token')
                ->toArray();

            if (empty($tokens)) {
                return false;
            }

            // Firebase kullanarak bildirim gönder
            $result = $this->firebaseService->sendTemplateNotification(
                $tokens,
                'tokens',
                $template,
                $data
            );

            return $result['success'];
        } catch (\Exception $e) {
            Log::error('Push Notification Error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'template' => $template,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Laravel Reverb ile gerçek zamanlı bildirim gönderir
     *
     * @param User $user
     * @param string $template
     * @param array $data
     * @return bool
     */
    protected function sendRealtimeNotification(User $user, string $template, array $data): bool
    {
        try {
            // Event ismi belirle
            $eventName = 'notification.' . $template;

            // Laravel Reverb (Broadcast) ile bildirim gönder
            Event::broadcast('private-user.' . $user->id, $eventName, $data);

            return true;
        } catch (\Exception $e) {
            Log::error('Realtime Notification Error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'template' => $template,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * NetGSM üzerinden SMS bildirimi gönderir
     *
     * @param User $user
     * @param string $template
     * @param array $data
     * @return bool
     */
    protected function sendSmsNotification(User $user, string $template, array $data): bool
    {
        try {
            // Kullanıcının telefon numarası var mı kontrol et
            if (empty($user->phone)) {
                return false;
            }

            // SMS mesajını hazırla
            $message = $this->prepareSmsContent($template, $data);

            // NetGSM servisi ile SMS gönder
            $result = $this->smsService->sendSms($user->phone, $message);

            return $result['success'] ?? false;
        } catch (\Exception $e) {
            Log::error('SMS Notification Error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'template' => $template,
                'phone' => $user->phone ?? 'N/A',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Şablona göre SMS içeriğini hazırlar
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    protected function prepareSmsContent(string $template, array $data): string
    {
        $templates = [
            'live_stream_started' => '{streamer} canlı yayına başladı! Hemen katıl: https://shoot90.com/live/{stream_id}',
            'live_stream_ended' => '{streamer} yayını sona erdi. Tekrar izlemek için: https://shoot90.com/replay/{stream_id}',
            'password_reset' => 'Shoot90 şifre sıfırlama kodunuz: {code}',
            'verification' => 'Shoot90 doğrulama kodunuz: {code}',
            'new_message' => '{sender} size mesaj gönderdi. Görüntülemek için: https://shoot90.com/messages',
        ];

        $message = $templates[$template] ?? 'Yeni Shoot90 bildirimi';

        // Placeholder'ları değerlerle değiştir
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $message = str_replace('{' . $key . '}', $value, $message);
            }
        }

        return $message;
    }
}
