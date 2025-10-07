<?php

namespace App\Services;

use App\Models\Follow;
use App\Models\User;
use Carbon\Carbon;

class FollowService
{
    /**
     * Kullanıcının belirli bir tarih aralığında kazandığı yeni takipçi sayısını döndürür
     *
     * @param string $userId Kullanıcı ID
     * @param string|Carbon $startDate Başlangıç tarihi
     * @param string|Carbon|null $endDate Bitiş tarihi (null ise şimdiki zaman kullanılır)
     * @return int Takipçi sayısı
     */
    public function getNewFollowersCount($userId, $startDate, $endDate = null)
    {
        $query = Follow::where('followed_id', $userId)
            ->where('status', 'approved')
            ->where('created_at', '>=', $startDate);
            
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
        
        return $query->count();
    }
    
    /**
     * Kullanıcının belirli bir tarih aralığında takip etmeye başladığı kişi sayısını döndürür
     *
     * @param string $userId Kullanıcı ID
     * @param string|Carbon $startDate Başlangıç tarihi
     * @param string|Carbon|null $endDate Bitiş tarihi (null ise şimdiki zaman kullanılır)
     * @return int Takip edilen kişi sayısı
     */
    public function getNewFollowingsCount($userId, $startDate, $endDate = null)
    {
        $query = Follow::where('follower_id', $userId)
            ->where('status', 'approved')
            ->where('created_at', '>=', $startDate);
            
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
        
        return $query->count();
    }
    
    /**
     * Kullanıcının toplam takipçi sayısını döndürür
     *
     * @param string $userId Kullanıcı ID
     * @return int Takipçi sayısı
     */
    public function getTotalFollowersCount($userId)
    {
        return Follow::where('followed_id', $userId)
            ->where('status', 'approved')
            ->count();
    }
    
    /**
     * Kullanıcının toplam takip ettiği kişi sayısını döndürür
     *
     * @param string $userId Kullanıcı ID
     * @return int Takip edilen kişi sayısı
     */
    public function getTotalFollowingsCount($userId)
    {
        return Follow::where('follower_id', $userId)
            ->where('status', 'approved')
            ->count();
    }
    
    /**
     * İki kullanıcı arasında takip ilişkisi var mı kontrol eder
     *
     * @param string $followerId Takip eden kullanıcı ID
     * @param string $followedId Takip edilen kullanıcı ID
     * @return bool Takip ilişkisi varsa true, yoksa false
     */
    public function isFollowing($followerId, $followedId)
    {
        return Follow::where('follower_id', $followerId)
            ->where('followed_id', $followedId)
            ->where('status', 'approved')
            ->exists();
    }
}
