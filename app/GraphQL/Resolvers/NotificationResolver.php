<?php

namespace App\GraphQL\Resolvers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class NotificationResolver
{
    public function notifications($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();
        $query = Notification::where('user_id', $user->id);

        // Filtreler uygulanır
        if (isset($args['filter'])) {
            $filter = $args['filter'];

            // Okunma durumuna göre filtreleme
            if (isset($filter['read'])) {
                $filter['read']
                    ? $query->whereNotNull('read_at')
                    : $query->whereNull('read_at');
            }

            // Bildirim türüne göre filtreleme
            if (isset($filter['type']) && is_array($filter['type']) && !empty($filter['type'])) {
                $query->whereIn('type', $filter['type']);
            }
        }

        // Sıralama
        $query->orderBy('created_at', 'desc');

        // Sorgu oluşturucuyu döndür
        return $query;
    }
    
    public function getAllNotifications($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();
        $query = Notification::where('user_id', $user->id);
        
        // Sıralama
        $query->orderBy('created_at', 'desc');
        
        // Sayfalama
        $limit = $args['limit'] ?? 15;
        $page = $args['page'] ?? 1;
        $skip = ($page - 1) * $limit;
        
        // Sorguyu çalıştır ve sonuçları döndür
        return $query->skip($skip)->take($limit)->get();
    }


    public function markNotificationAsRead($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();
        $notification = Notification::where('_id', $args['id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$notification) {
            throw new \Exception('Notification not found');
        }

        $notification->markAsRead();
        return $notification;
    }


    public function markAllNotificationsAsRead($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();
        Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return true;
    }

    public function deleteNotification($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();
        $notification = Notification::where('_id', $args['id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$notification) {
            throw new \Exception('Notification not found');
        }

        $notification->delete();
        return true;
    }


    public function deleteAllNotifications($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();
        Notification::where('user_id', $user->id)->delete();
        return true;
    }
}
