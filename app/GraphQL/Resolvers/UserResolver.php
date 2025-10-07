<?php

namespace App\GraphQL\Resolvers;

use App\Models\User;
use App\Models\Block;
use App\Models\Follow;
use App\Models\UserStats;
use Illuminate\Support\Facades\Auth;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class UserResolver
{

    /**
     * Get a single user by ID
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return \App\Models\User|null
     */
    public function getUser($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        try {
            // Find the user by ID and ensure only one result is returned
            $user = User::where('id', $args['id'])->firstOrFail();

            // PostgreSQL'den kullanıcı istatistiklerini al
            $userStats = UserStats::where('user_id', $user->id)->first();

            // Eğer istatistikler henüz oluşturulmamışsa, varsayılan değerlerle oluştur
            if (!$userStats) {
                $followerCount = Follow::getFollowerCount($user->id);
                $followingCount = Follow::getFollowingCount($user->id);

                $userStats = UserStats::create([
                    'user_id' => $user->id,
                    'follower_count' => $followerCount,
                    'following_count' => $followingCount,
                    'video_count' => 0,
                    'total_views' => 0,
                    'total_likes' => 0,
                    'total_comments' => 0
                ]);
            }

            // Add video count to user object
            $user->video_count = $userStats->video_count;

            return $user;
        } catch (ModelNotFoundException $e) {
            return null;
        }
    }

    /**
     * Get the currently authenticated user's profile
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return \App\Models\User|null
     */
    public function getAuthenticatedUser($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = $context->user();
        if (!$user) {
            return null;
        }

        // PostgreSQL'den kullanıcı istatistiklerini al
        $userStats = UserStats::where('user_id', $user->id)->first();

        // Eğer istatistikler henüz oluşturulmamışsa, varsayılan değerlerle oluştur
        if (!$userStats) {
            $followerCount = Follow::getFollowerCount($user->id);
            $followingCount = Follow::getFollowingCount($user->id);

            $userStats = UserStats::create([
                'user_id' => $user->id,
                'follower_count' => $followerCount,
                'following_count' => $followingCount,
                'video_count' => 0,
                'total_views' => 0,
                'total_likes' => 0,
                'total_comments' => 0
            ]);
        }

        // Add video count to user object
        $user->video_count = $userStats->video_count;

        return $user;
    }
    
    /**
     * Bağlantılı hesapları getirir
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLinkedAccounts($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = $context->user();
        if (!$user) {
            return collect([]);
        }
        
        $accounts = collect([$user]); // Mevcut hesabı ekle
        
        // Kullanıcı ana hesap ise, alt hesapları getir
        if ($user->account_type === 'primary') {
            $secondaryAccounts = User::where('parent_user_id', $user->id)
                ->where('account_type', 'secondary')
                ->get();
            
            $accounts = $accounts->concat($secondaryAccounts);
        }
        // Kullanıcı alt hesap ise, ana hesabı ve diğer alt hesapları getir
        elseif ($user->account_type === 'secondary' && $user->parent_user_id) {
            $primaryAccount = User::where('id', $user->parent_user_id)
                ->where('account_type', 'primary')
                ->first();
            
            if ($primaryAccount) {
                $accounts->push($primaryAccount);
                
                $otherSecondaryAccounts = User::where('parent_user_id', $primaryAccount->id)
                    ->where('account_type', 'secondary')
                    ->where('id', '!=', $user->id)
                    ->get();
                
                $accounts = $accounts->concat($otherSecondaryAccounts);
            }
        }
        
        return $accounts->values();
    }

    /**
     * Kullanıcının engellediği kullanıcıları getir
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return array
     */
    public function getUserBlockedUsers($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();

        // PostgreSQL'den engellenen kullanıcıları al
        $blockedIds = Block::getBlockedUsers($user->id)->get()->pluck('blocked_id')->toArray();

        // Engellenen kullanıcıların bilgilerini getir
        $blockedUsers = !empty($blockedIds) ? User::whereIn('id', $blockedIds)->get() : collect([]);

        return [
            'blocked_users' => $blockedUsers,
            'total' => $blockedUsers->count()
        ];
    }

    /**
     * Kullanıcının engellediği kullanıcıları getir
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserBlocks($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Oturum açmış kullanıcıyı al
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }
        
        // Kullanıcının engellediği kullanıcıları getir
        return $user->blocked_users()->get();
    }

    /**
     * Kullanıcıyı engelle
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return array
     */
    public function blockUser($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();
        $blockedId = $args['input']['blocked_id'];
        $reason = $args['input']['reason'] ?? null;

        // Kendini engellemeyi önle
        if ($user->id == $blockedId) {
            return [
                'success' => false,
                'message' => 'Kendinizi engelleyemezsiniz',
                'block' => null
            ];
        }

        // Kullanıcının varlığını kontrol et
        $blockedUser = User::find($blockedId);
        if (!$blockedUser) {
            return [
                'success' => false,
                'message' => 'Engellenecek kullanıcı bulunamadı',
                'block' => null
            ];
        }

        // Zaten engellendi mi kontrol et
        if (Block::isBlocking($user->id, $blockedId)) {
            return [
                'success' => false,
                'message' => 'Bu kullanıcıyı zaten engellediniz',
                'block' => null
            ];
        }

        // Transaction başlat
        DB::beginTransaction();
        try {
            // Engelle
            $block = new Block();
            $block->blocker_id = $user->id;
            $block->blocked_id = $blockedId;
            $block->reason = $reason;
            $block->save();

            // Eğer takip ediyorsa takipten çıkar
            Follow::query()
                ->where('follower_id', $user->id)
                ->where('followed_id', $blockedId)
                ->delete();

            Follow::query()
                ->where('follower_id', $blockedId)
                ->where('followed_id', $user->id)
                ->delete();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Kullanıcı başarıyla engellendi',
                'block' => $block
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'İşlem sırasında bir hata oluştu',
                'block' => null
            ];
        }
    }

    /**
     * Kullanıcı engelini kaldır
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return array
     */
    public function unblockUser($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();
        $blockedId = $args['blockedId'];

        // Engel kaydını bul
        $block = Block::query()
            ->where('blocker_id', $user->id)
            ->where('blocked_id', $blockedId)
            ->first();

        if (!$block) {
            return [
                'success' => false,
                'message' => 'Bu kullanıcıyı engellememişsiniz',
            ];
        }

        // Engel kaldır
        $block->delete();

        return [
            'success' => true,
            'message' => 'Kullanıcı engeli başarıyla kaldırıldı',
        ];
    }

    /**
     * Kullanıcı profilini güncelle
     *
     * @param  null  $rootValue
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo
     * @return \App\Models\User
     */
    public function updateUserProfile($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();
        $input = $args['input'];

        // Nickname değişikliği varsa, benzersiz olduğunu kontrol et
        if (isset($input['nickname']) && $input['nickname'] !== $user->nickname) {
            $existingUser = User::where('nickname', $input['nickname'])->where('id', '!=', $user->id)->first();
            if ($existingUser) {
                throw new \GraphQL\Error\Error('Bu kullanıcı adı zaten kullanılıyor.');
            }
        }

        // Email değişikliği varsa, benzersiz olduğunu kontrol et
        if (isset($input['email']) && $input['email'] !== $user->email) {
            $existingUser = User::where('email', $input['email'])->where('id', '!=', $user->id)->first();
            if ($existingUser) {
                throw new \GraphQL\Error\Error('Bu e-posta adresi zaten kullanılıyor.');
            }
        }

        // Phone değişikliği varsa, benzersiz olduğunu kontrol et
        if (isset($input['phone']) && $input['phone'] !== $user->phone) {
            $existingUser = User::where('phone', $input['phone'])->where('id', '!=', $user->id)->first();
            if ($existingUser) {
                throw new \GraphQL\Error\Error('Bu telefon numarası zaten kullanılıyor.');
            }
        }

        // Avatar değişikliği varsa, dosya olarak kaydet
        if (isset($input['avatar']) && !empty($input['avatar'])) {
            try {
                // Validate and process base64 file
                if (!preg_match('/^data:([\w\/]+);base64,/', $input['avatar'], $matches)) {
                    throw new \GraphQL\Error\Error('Invalid base64 format');
                }

                $mimeType = $matches[1];

                // Get extension from mime type
                $extensions = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                ];

                $extension = $extensions[$mimeType] ?? 'jpg';

                // Process base64 file
                $fileData = base64_decode(preg_replace('#^data:' . $mimeType . ';base64,#i', '', $input['avatar']));

                if ($fileData === false) {
                    throw new \GraphQL\Error\Error('Invalid base64 content');
                }

                // Generate unique filename
                $fileName = \Illuminate\Support\Str::uuid() . '.' . $extension;
                $path = 'users/' . $user->id . '/avatar/' . $fileName;

                // Store file in storage
                \Illuminate\Support\Facades\Storage::disk('public')->put($path, $fileData);

                // Update avatar path in input
                $input['avatar'] = $path;

            } catch (\Exception $e) {
                throw new \GraphQL\Error\Error('Avatar güncellenirken bir hata oluştu: ' . $e->getMessage());
            }
        }

        // Kullanıcı takımlarını güncelle
        if (isset($input['user_team_ids'])) {
            $user->user_teams()->sync($input['user_team_ids']);
            unset($input['user_team_ids']);
        }

        // Ana takımı güncelle
        if (isset($input['primary_team_id'])) {
            $user->primary_team_id = $input['primary_team_id'];
            unset($input['primary_team_id']);
        }

        try {
            // Diğer alanları güncelle
            $user->fill($input);
            $user->save();

            // Takipçi ve takip edilen sayılarını ekle
            $userStats = UserStats::where('user_id', $user->id)->first();
            if ($userStats) {
                $user->followers_count = $userStats['follower_count'] ?? 0;
                $user->following_count = $userStats['following_count'] ?? 0;
            } else {
                $user->followers_count = 0;
                $user->following_count = 0;
            }

            return $user;
        } catch (\Exception $e) {
            throw new \GraphQL\Error\Error('Profil güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
    }

    public function user($liveStream)
    {
        $userId = $liveStream['user_id'];
        if (!$userId) return null;

        return User::find($userId);
    }
}