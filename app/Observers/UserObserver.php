<?php

namespace App\Observers;

use Exception;
use JsonException;
use App\Models\User;
use App\Services\BunnyCdnService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\UpdateEmbeddedUserData;
use Illuminate\Support\Facades\Cache;
use App\Services\User\UserService;

class UserObserver
{
    public function created(User $user): void
    {
        $bunny = new BunnyCdnService();

        // Kullanıcı oluşturma işlemi tamamlandıktan sonra çalışacak şekilde ayarlıyoruz.
        DB::afterCommit(function () use ($user, $bunny) {
            try {
                $collectionUuid = $bunny->createCollection($user->id);
                $user->update([
                    'collection_uuid' => $collectionUuid,
                    'last_seen_at' => now()
                ]);
            } catch (Exception $e) {
                Log::error("BunnyCdnService createCollection hatası: " . $e->getMessage());
            }
        });

        // Update user cache
        app(UserService::class)->updateUserCache($user);
        
        Cache::forget('dashboard:registered-user-count-chart');
    }

    public function updated(User $user): void
    {
        // Update user cache whenever a user is updated
        app(UserService::class)->updateUserCache($user);
        
        // Değişen alanları kontrol et - sadece embedded user data ile ilgili alanlar değiştiyse güncelle
        $relevantFields = [
            'name', 'surname', 'nickname', 'avatar', 'is_private', 'is_frozen',
            'collection_uuid', 'email', 'phone'
        ];
        
        $hasRelevantChanges = false;
        foreach ($relevantFields as $field) {
            if ($user->isDirty($field)) {
                $hasRelevantChanges = true;
                break;
            }
        }
        
        // Eğer ilgili alanlarda değişiklik yoksa, işlemi atla
        if (!$hasRelevantChanges) {
            return;
        }
        
        // MongoDB'de kullanıcının videolarını bul ve embedded user bilgilerini güncelle
        DB::afterCommit(function () use ($user) {
            try {
                // Kullanıcı verilerini hazırla
                $userData = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'surname' => $user->surname,
                    'nickname' => $user->nickname,
                    'avatar' => $user->avatar,
                    'is_private' => $user->is_private,
                    'is_frozen' => $user->is_frozen,
                    'collection_uuid' => $user->collection_uuid,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ];
                
                // UpdateEmbeddedUserData job'u kuyruğa ekle
                UpdateEmbeddedUserData::dispatch($user->id, $userData, 100)
                    ->onQueue('video-updates')
                    ->delay(now()->addSeconds(5)); // 5 saniye gecikme ile işlemi başlat
                
                Log::info("User {$user->id} için embedded user data güncelleme job'u kuyruğa eklendi");
                
            } catch (Exception $e) {
                Log::error("MongoDB embedded user data güncelleme hatası: " . $e->getMessage(), [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        });
    }
}
