<?php

namespace App\GraphQL\Traits;

use Carbon\Carbon;
use App\Models\Punishment;
use App\Models\UserPunishment;
use GraphQL\Error\Error;

trait ChecksRedCardPunishment
{
    /**
     * Check if user has an active red card punishment and throw an error if so.
     *
     * @param int|string $userId
     * @throws Error
     */
    protected function checkRedCardPunishment(int|string $userId): void
    {
        // Don't cast to int - user_id is UUID string
        $activeRedCardPunishment = UserPunishment::with('punishment')
            ->where('user_id', $userId)
            ->whereHas('punishment', function ($query) {
                $query->where('card_type_id', Punishment::RED_CARD);
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->first();

        if ($activeRedCardPunishment) {
            $expiresAt = $activeRedCardPunishment->expires_at;
            $remainingTime = $expiresAt ? $expiresAt->diffForHumans() : 'süresiz';

            throw new Error(
                "Kırmızı kart cezanız nedeniyle 7 gün süre ile mesaj gönderemez, yorum yazamaz, video çekemez ve canlı yayın açamazsınız. Kalan süre: {$remainingTime}"
            );
        }
    }

    /**
     * Check if user has an active red card punishment and return the punishment if exists.
     *
     * @param int|string $userId
     * @return UserPunishment|null
     */
    protected function getActiveRedCardPunishment(int|string $userId): ?UserPunishment
    {
        // Don't cast to int - user_id is UUID string
        return UserPunishment::with('punishment')
            ->where('user_id', $userId)
            ->whereHas('punishment', function ($query) {
                $query->where('card_type_id', Punishment::RED_CARD);
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->first();
    }
}
