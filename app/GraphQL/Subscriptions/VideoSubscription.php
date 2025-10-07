<?php

namespace App\GraphQL\Subscriptions;

use App\Models\Relations\VideoLike; // Doğru namespace kullanıldı

class VideoSubscription
{
    /**
     * videoLiked aboneliği – bir video beğeni eklendiğinde tetiklenecek.
     */
    public function videoLiked($root, array $args)
    {
        // Gerçek projede, canlı yayın (broadcast) mekanizmasıyla tetiklenen olayları yakalamanız gerekecektir.
        // Bu örnekte, sabit bir VideoLike örneği döndürülüyor.
        $videoLike = new VideoLike([
            'id' => 1,
            'user_id' => 1,
            'created_at' => now(),
        ]);

        return $videoLike;
    }
}
