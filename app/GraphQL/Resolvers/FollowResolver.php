<?php

namespace App\GraphQL\Resolvers;

use App\Events\UserFollowed;
use App\Models\User;
use App\Models\Follow;
use App\Models\UserStats;
use Illuminate\Support\Facades\Auth;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class FollowResolver
{
    /**
     * Tek bir endpoint ile follow ve follow request işlemlerini yönetir
     * Kullanıcı gizli hesapsa otomatik olarak follow request gönderir
     * Açık hesapsa direkt takip eder
     */
    public function followUser($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Get the authenticated user
        $user = Auth::user();
        if (!$user) {
            \Log::error('followUser: Authentication failed');
            return [
                'success' => false,
                'message' => 'Kullanıcı kimliği doğrulanamadı',
                'follow' => null,
                'status' => 'error'
            ];
        }
        
        $targetUserId = $args['user_id'];
        \Log::info("followUser: Processing follow for follower={$user->id}, followed={$targetUserId}");

        // Prevent self-follow
        if ($user->id === $targetUserId) {
            \Log::warning('followUser: User tried to follow themselves');
            return [
                'success' => false,
                'message' => 'Kendinizi takip edemezsiniz',
                'follow' => null,
                'status' => 'self_follow'
            ];
        }
        
        // Find target user
        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            \Log::error('followUser: Target user not found');
            return [
                'success' => false,
                'message' => 'Kullanıcı bulunamadı',
                'follow' => null,
                'status' => 'user_not_found'
            ];
        }

        try {
            // Önce mevcut takip durumunu kontrol et (silinmiş kayıtlar dahil)
            $existingFollow = DB::table('follows')
                ->where('follower_id', $user->id)
                ->where('followed_id', $targetUserId)
                ->first(); // Silinmiş kayıtları da kontrol etmek için deleted_at filtresini eklemiyoruz

            // Durum 1: Aktif takip kaydı var
            if ($existingFollow && $existingFollow->deleted_at === null) {
                \Log::info("followUser: Active follow record found with status={$existingFollow->status}");
                
                if ($existingFollow->status === 'approved') {
                    return [
                        'success' => false,
                        'message' => 'Bu kullanıcıyı zaten takip ediyorsunuz',
                        'follow' => null,
                        'status' => 'already_following'
                    ];
                } else if ($existingFollow->status === 'pending') {
                    return [
                        'success' => false,
                        'message' => 'Takip isteğiniz hala beklemede',
                        'follow' => null,
                        'status' => 'pending'
                    ];
                } else if ($existingFollow->status === 'rejected') {
                    // Reddedilmiş isteği güncelle
                    \Log::info("followUser: Updating rejected follow request to pending/approved");
                    
                    $newStatus = $targetUser->is_private ? 'pending' : 'approved';
                    $now = now()->toDateTimeString();
                    
                    // Durumu güncelle
                    $updated = DB::table('follows')
                        ->where('id', $existingFollow->id)
                        ->update([
                            'status' => $newStatus,
                            'updated_at' => $now
                        ]);
                    
                    if ($updated !== 1) {
                        \Log::error("followUser: Failed to update rejected follow status");
                        return [
                            'success' => false,
                            'message' => 'Takip isteği güncellenemedi',
                            'follow' => null,
                            'status' => 'error'
                        ];
                    }
                    
                    // Approved ise istatistikleri güncelle
                    if ($newStatus === 'approved') {
                        try {
                            DB::statement(
                                "UPDATE user_stats SET following_count = following_count + 1 WHERE user_id = ?", 
                                [$user->id]
                            );
                            DB::statement(
                                "UPDATE user_stats SET follower_count = follower_count + 1 WHERE user_id = ?", 
                                [$targetUserId]
                            );
                            \Log::info("followUser: Stats updated successfully via direct SQL");
                            
                            // Event'i tetikle
                            try {
                                event(new UserFollowed($user, $targetUser));
                                \Log::info('followUser: UserFollowed event dispatched');
                            } catch (\Exception $e) {
                                \Log::error('followUser: Error dispatching event: ' . $e->getMessage());
                            }
                        } catch (\Exception $statsException) {
                            \Log::error("followUser: Error updating stats: {$statsException->getMessage()}");
                            // Devam et - istatistik güncellemesi başarısız olsa bile takip işlemi başarılı sayılabilir
                        }
                    }
                    
                    return [
                        'success' => true,
                        'message' => $newStatus === 'pending' ? 'Takip isteği yeniden gönderildi' : 'Kullanıcı takip edildi',
                        'follow' => null,
                        'status' => $newStatus === 'approved' ? 'followed' : 'pending'
                    ];
                }
            }
            
            // Durum 2: Silinmiş takip kaydı var (yeniden aktifleştir)
            if ($existingFollow && $existingFollow->deleted_at !== null) {
                \Log::info("followUser: Found soft-deleted follow record, reactivating");
                
                $newStatus = $targetUser->is_private ? 'pending' : 'approved';
                $now = now()->toDateTimeString();
                
                // Kaydı geri getir ve durumu güncelle
                $updated = DB::table('follows')
                    ->where('id', $existingFollow->id)
                    ->update([
                        'deleted_at' => null,
                        'status' => $newStatus,
                        'updated_at' => $now
                    ]);
                
                if ($updated !== 1) {
                    \Log::error("followUser: Failed to restore soft-deleted follow");
                    return [
                        'success' => false,
                        'message' => 'Takip kaydı geri getirilemedi',
                        'follow' => null,
                        'status' => 'error'
                    ];
                }
                
                // Approved ise istatistikleri güncelle
                if ($newStatus === 'approved') {
                    try {
                        DB::statement(
                            "UPDATE user_stats SET following_count = following_count + 1 WHERE user_id = ?", 
                            [$user->id]
                        );
                        DB::statement(
                            "UPDATE user_stats SET follower_count = follower_count + 1 WHERE user_id = ?", 
                            [$targetUserId]
                        );
                        \Log::info("followUser: Stats updated successfully via direct SQL");
                        
                        // Event'i tetikle
                        try {
                            event(new UserFollowed($user, $targetUser));
                            \Log::info('followUser: UserFollowed event dispatched');
                        } catch (\Exception $e) {
                            \Log::error('followUser: Error dispatching event: ' . $e->getMessage());
                        }
                    } catch (\Exception $statsException) {
                        \Log::error("followUser: Error updating stats: {$statsException->getMessage()}");
                        // Devam et - istatistik güncellemesi başarısız olsa bile takip işlemi başarılı sayılabilir
                    }
                }
                
                return [
                    'success' => true,
                    'message' => $newStatus === 'pending' ? 'Takip isteği gönderildi' : 'Kullanıcı takip edildi',
                    'follow' => null,
                    'status' => $newStatus === 'approved' ? 'followed' : 'pending'
                ];
            }
            
            // Durum 3: Hiç takip kaydı yok, yeni oluştur
            \Log::info("followUser: No existing follow record, creating new");
            
            $newStatus = $targetUser->is_private ? 'pending' : 'approved';
            $now = now()->toDateTimeString();
            
            // Yeni kayıt oluştur
            $followId = DB::table('follows')->insertGetId([
                'follower_id' => $user->id,
                'followed_id' => $targetUserId,
                'status' => $newStatus,
                'created_at' => $now,
                'updated_at' => $now
            ]);
            
            if (!$followId) {
                \Log::error("followUser: Failed to create new follow record");
                return [
                    'success' => false,
                    'message' => 'Takip kaydı oluşturulamadı',
                    'follow' => null,
                    'status' => 'error'
                ];
            }
            
            \Log::info("followUser: Created new follow record ID={$followId} with status={$newStatus}");
            
            // Approved ise istatistikleri güncelle
            if ($newStatus === 'approved') {
                try {
                    DB::statement(
                        "UPDATE user_stats SET following_count = following_count + 1 WHERE user_id = ?", 
                        [$user->id]
                    );
                    DB::statement(
                        "UPDATE user_stats SET follower_count = follower_count + 1 WHERE user_id = ?", 
                        [$targetUserId]
                    );
                    \Log::info("followUser: Stats updated successfully via direct SQL");
                    
                    // Event'i tetikle
                    try {
                        event(new UserFollowed($user, $targetUser));
                        \Log::info('followUser: UserFollowed event dispatched');
                    } catch (\Exception $e) {
                        \Log::error('followUser: Error dispatching event: ' . $e->getMessage());
                    }
                } catch (\Exception $statsException) {
                    \Log::error("followUser: Error updating stats: {$statsException->getMessage()}");
                    // Devam et - istatistik güncellemesi başarısız olsa bile takip işlemi başarılı sayılabilir
                }
            }
            
            return [
                'success' => true,
                'message' => $newStatus === 'pending' ? 'Takip isteği gönderildi' : 'Kullanıcı takip edildi',
                'follow' => null,
                'status' => $newStatus === 'approved' ? 'followed' : 'pending'
            ];
        } catch (\Exception $e) {
            \Log::error("followUser: Error during follow operation: {$e->getMessage()}");
            \Log::error($e->getTraceAsString());
            
            return [
                'success' => false,
                'message' => 'Takip işlemi sırasında bir hata oluştu: ' . $e->getMessage(),
                'follow' => null,
                'status' => 'error'
            ];
        }
    }

    /**
     * Update user stats for follower and followed using direct PostgreSQL-optimized queries
     * 
     * @param string $followerId
     * @param string $followedId
     * @param string $action 'increment' or 'decrement'
     * @return bool
     */
    public function updateUserStats(string $followerId, string $followedId, string $action = 'increment'): bool
    {
        \Log::info("updateUserStats: action={$action}, follower_id={$followerId}, followed_id={$followedId}");
        
        $increment = ($action === 'increment');
        \Log::info("updateUserStats: converted action to increment={$increment}");
        
        try {
            if ($increment) {
                UserStats::incrementFollowingCount($followerId);
                UserStats::incrementFollowerCount($followedId);
            } else {
                UserStats::decrementFollowingCount($followerId);
                UserStats::decrementFollowerCount($followedId);
            }
            
            \Log::info("updateUserStats: Successfully updated stats");
            return true;
        } catch (\Exception $e) {
            \Log::error("updateUserStats: Error updating stats: {$e->getMessage()}");
            \Log::error($e->getTraceAsString());
            return false;
        }
    }

    /**
     * Takipten çıkma (Unfollow a user)
     * 
     * @param mixed $rootValue
     * @param array $args Arguments from GraphQL query
     * @param GraphQLContext $context
     * @param ResolveInfo $resolveInfo
     * @return array Response containing success status, message, and follow object
     */
    public function unfollowUser($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();
        $targetUserId = $args['user_id'];
        
        \Log::info("unfollowUser: Processing unfollow for follower={$user->id}, followed={$targetUserId}");

        // Çok basit yaklaşım - transaction kullanmadan direk SQL ile silme
        try {
            // Önce takip durumunu kontrol et
            $follow = DB::table('follows')
                ->where('follower_id', $user->id)
                ->where('followed_id', $targetUserId)
                ->whereNull('deleted_at')
                ->first();

            if (!$follow) {
                \Log::warning("unfollowUser: No active follow record found");
                return [
                    'success' => false,
                    'message' => 'Bu kullanıcıyı takip etmiyorsunuz',
                    'follow' => null,
                    'status' => 'error'
                ];
            }

            \Log::info("unfollowUser: Found follow record ID={$follow->id}, status={$follow->status}");
            
            // Önce istatistikleri güncelle - hata olursa burada olsun
            if ($follow->status === 'approved') {
                \Log::info("unfollowUser: Updating stats for approved follow");
                try {
                    // Direk SQL ile güncelle - daha az hata çıkarma olasılığı var
                    DB::statement(
                        "UPDATE user_stats SET following_count = GREATEST(0, following_count - 1) WHERE user_id = ?", 
                        [$user->id]
                    );
                    DB::statement(
                        "UPDATE user_stats SET follower_count = GREATEST(0, follower_count - 1) WHERE user_id = ?", 
                        [$targetUserId]
                    );
                    \Log::info("unfollowUser: Stats updated successfully via direct SQL");
                } catch (\Exception $statsException) {
                    \Log::error("unfollowUser: Error updating stats: {$statsException->getMessage()}");
                    // Devam et - istatistik güncellemesi başarısız olsa bile takipten çıkma işlemini yapalım
                }
            } else {
                \Log::info("unfollowUser: Not updating stats because status is {$follow->status}");
            }

            // Şimdi follow kaydını direk SQL ile sil
            $now = now()->toDateTimeString();
            $affected = DB::table('follows')
                ->where('id', $follow->id)
                ->update(['deleted_at' => $now]);

            \Log::info("unfollowUser: Direct SQL delete affected {$affected} rows");
            
            if ($affected !== 1) {
                \Log::warning("unfollowUser: Unexpected number of rows affected: {$affected}");
                return [
                    'success' => false,
                    'message' => 'Takipten çıkma işlemi başarısız oldu',
                    'follow' => null,
                    'status' => 'error'
                ];
            }

            // Başarılı
            return [
                'success' => true,
                'message' => 'Kullanıcı takipten çıkarıldı',
                'follow' => null,  // Model yerine null döndürüyoruz çünkü direk SQL kullandık
                'status' => 'unfollowed'
            ];
        } catch (\Exception $e) {
            \Log::error("unfollowUser: Error during unfollow: {$e->getMessage()}");
            \Log::error($e->getTraceAsString());
            
            return [
                'success' => false,
                'message' => 'Bir hata oluştu: ' . $e->getMessage(),
                'follow' => null,
                'status' => 'error'
            ];
        }
    }

    /**
     * Takip isteğini işleme (kabul/red)
     */
    public function handleFollowRequest($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Get the authenticated user
        $user = Auth::user();
        if (!$user) {
            \Log::error('handleFollowRequest: Authentication failed');
            return [
                'success' => false,
                'message' => 'Kullanıcı kimliği doğrulanamadı',
                'follow' => null,
                'status' => 'error'
            ];
        }
        
        $requestId = $args['request_id'];
        $action = $args['action']; // 'accept' or 'reject'
        
        \Log::info("handleFollowRequest: Processing request ID={$requestId}, action={$action}");
        
        // Find the follow request
        try {
            $followRequest = Follow::where('id', $requestId)
                ->where('followed_id', $user->id)
                ->where('status', 'pending')
                ->first();
                
            if (!$followRequest) {
                \Log::warning("handleFollowRequest: No pending request found with ID={$requestId}");
                return [
                    'success' => false,
                    'message' => 'Takip isteği bulunamadı',
                    'follow' => null,
                    'status' => 'not_found'
                ];
            }
            
            \Log::info("handleFollowRequest: Found request from follower_id={$followRequest->follower_id}");
            
            // Process the request within a transaction
            DB::beginTransaction();
            try {
                if ($action === 'accept') {
                    \Log::info("handleFollowRequest: Accepting request");
                    $followRequest->status = 'approved';
                    $followRequest->save();
                    
                    // Update stats
                    $statsUpdated = $this->updateUserStats($followRequest->follower_id, $user->id, 'increment');
                    if (!$statsUpdated) {
                        \Log::warning("handleFollowRequest: Stats update failed but request was approved");
                    }
                    
                    $message = 'Takip isteği kabul edildi';
                    $status = 'approved';
                } else {
                    \Log::info("handleFollowRequest: Rejecting request");
                    $followRequest->status = 'rejected';
                    $followRequest->save();
                    
                    $message = 'Takip isteği reddedildi';
                    $status = 'rejected';
                }
                
                DB::commit();
                \Log::info("handleFollowRequest: Transaction committed successfully");
                
                return [
                    'success' => true,
                    'message' => $message,
                    'follow' => $followRequest,
                    'status' => $status
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error("handleFollowRequest: Error processing request: {$e->getMessage()}");
                
                return [
                    'success' => false,
                    'message' => 'İstek işlenirken bir hata oluştu: ' . $e->getMessage(),
                    'follow' => null,
                    'status' => 'error'
                ];
            }
        } catch (\Exception $e) {
            \Log::error("handleFollowRequest: Error finding request: {$e->getMessage()}");
            
            return [
                'success' => false,
                'message' => 'Takip isteği aranırken bir hata oluştu',
                'follow' => null,
                'status' => 'error'
            ];
        }
    }

    /**
     * Bekleyen takip isteklerini getir
     */
    public function getPendingRequests($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Get the authenticated user
        $user = Auth::user();
        if (!$user) {
            \Log::error('getPendingRequests: Authentication failed');
            return [
                'success' => false,
                'message' => 'Kullanıcı kimliği doğrulanamadı',
                'requests' => []
            ];
        }
        
        $type = $args['type'] ?? 'received'; // 'received' or 'sent'
        \Log::info("getPendingRequests: Getting {$type} requests for user_id={$user->id}");
        
        try {
            if ($type === 'received') {
                // Get requests received by the user
                $requests = Follow::where('followed_id', $user->id)
                    ->where('status', 'pending')
                    ->with('follower')
                    ->get();
            } else {
                // Get requests sent by the user
                $requests = Follow::where('follower_id', $user->id)
                    ->where('status', 'pending')
                    ->with('followed')
                    ->get();
            }
            
            \Log::info("getPendingRequests: Found {$requests->count()} {$type} requests");
            
            return [
                'success' => true,
                'message' => 'Takip istekleri başarıyla getirildi',
                'requests' => $requests
            ];
        } catch (\Exception $e) {
            \Log::error("getPendingRequests: Error getting requests: {$e->getMessage()}");
            
            return [
                'success' => false,
                'message' => 'Takip istekleri getirilirken bir hata oluştu',
                'requests' => []
            ];
        }
    }

    /**
     * Kullanıcının takipçilerini veya takip ettiklerini getir
     */
    public function getUserFollows($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Get the authenticated user
        $user = Auth::user();
        if (!$user) {
            \Log::error('getUserFollows: Authentication failed');
            return [
                'success' => false,
                'message' => 'Kullanıcı kimliği doğrulanamadı',
                'users' => []
            ];
        }
        
        $userId = $args['user_id'] ?? $user->id;
        $type = $args['type'] ?? 'followers'; // 'followers' or 'following'
        $status = $args['status'] ?? 'approved';
        
        \Log::info("getUserFollows: Getting {$type} for user_id={$userId} with status={$status}");
        
        try {
            // Find the target user
            $targetUser = $userId === $user->id ? $user : User::find($userId);
            if (!$targetUser) {
                \Log::warning("getUserFollows: Target user not found with ID={$userId}");
                return [
                    'success' => false,
                    'message' => 'Kullanıcı bulunamadı',
                    'users' => []
                ];
            }
            
            // Check privacy settings
            if ($targetUser->is_private && $userId !== $user->id) {
                // Check if the current user follows the target user
                $follows = Follow::where('follower_id', $user->id)
                    ->where('followed_id', $userId)
                    ->where('status', 'approved')
                    ->whereNull('deleted_at')
                    ->exists();
                    
                if (!$follows) {
                    \Log::warning("getUserFollows: Privacy restriction, user={$user->id} cannot view follows of private user={$userId}");
                    return [
                        'success' => false,
                        'message' => 'Bu kullanıcının takipçilerini görüntüleme izniniz yok',
                        'users' => []
                    ];
                }
            }
            
            // Get the follows based on type and status
            if ($type === 'followers') {
                // Get users who follow the target user
                $follows = Follow::where('followed_id', $userId)
                    ->where('status', $status)
                    ->whereNull('deleted_at')
                    ->with('follower')
                    ->get()
                    ->pluck('follower');
            } else {
                // Get users who the target user follows
                $follows = Follow::where('follower_id', $userId)
                    ->where('status', $status)
                    ->whereNull('deleted_at')
                    ->with('followed')
                    ->get()
                    ->pluck('followed');
            }
            
            \Log::info("getUserFollows: Found {$follows->count()} {$type}");
            
            return [
                'success' => true,
                'message' => 'Kullanıcı ' . ($type === 'followers' ? 'takipçileri' : 'takip ettikleri') . ' başarıyla getirildi',
                'users' => $follows
            ];
        } catch (\Exception $e) {
            \Log::error("getUserFollows: Error getting follows: {$e->getMessage()}");
            
            return [
                'success' => false,
                'message' => 'Takip bilgileri getirilirken bir hata oluştu',
                'users' => []
            ];
        }
    }
}
