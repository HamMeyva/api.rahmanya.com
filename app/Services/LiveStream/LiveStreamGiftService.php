<?php

namespace App\Services\LiveStream;

use App\Events\LiveStream\SentGiftToStream;
use Exception;
use Throwable;
use App\Models\User;
use App\Models\GiftBasket;
use App\Models\Agora\AgoraChannel;
use Illuminate\Support\Collection;
use App\Models\Agora\AgoraChannelGift;
use App\Models\Agora\AgoraChannelViewer;
use App\Notifications\AgoraChannelGiftNotification;
use App\Jobs\LiveStreamGift\StoreGiftInAgoraChannel;
use App\Jobs\LiveStreamGift\UpdateAgoraViewerGiftStats;
use App\Jobs\LiveStreamGift\UpdateAgoraChannelGiftStats;
use App\Jobs\LiveStreamGift\TransferCoinsToStreamerWallet;
use App\Services\LiveStream\GiftSenderRedisService;
use App\Services\LiveStream\LiveStreamChatService;

class LiveStreamGiftService
{
    public function __construct(public LiveStreamChatService $liveStreamChatService, public GiftSenderRedisService $giftSenderRedisService) {}

    public function sendGiftValidation(array $input, User $authUser): array
    {
        /* start::Validation */
        /** @var GiftBasket $giftBasket */
        $giftBasket = GiftBasket::with('gift')->find((int) $input['gift_basket_id']);

        if (!$giftBasket || $giftBasket->user_id !== $authUser->id) {
            throw new Exception("Hediye çantanızda bulunamadı. Başka bir hediye seçiniz.");
        }

        if ($giftBasket->quantity < $input['quantity']) {
            throw new Exception("Çantanızda yeterli hediye bulunamadı.");
        }

        $agoraChannel = AgoraChannel::find($input['agora_channel_id']);
        if (!$agoraChannel) {
            throw new Exception("Canlı yayın bulunamadı. Başka bir kanala hediye göndermeyi deneyiniz.");
        }
        if (!$agoraChannel->is_online) {
            throw new Exception("Canlı yayın sonlandıgı için hediye gönderilemiyor.");
        }

        // Gönderilecek yayın sahibi ya da misafir kullanıcı aktif mi?
        /** @var User $recipientUser */
        $recipientUser = User::find($input['recipient_user_id']);
        if (!$recipientUser) {
            throw new Exception("Hediye gönderilecek kullanıcı bulunamadı.");
        }
        $senderViewer = $agoraChannel->viewers()
            ->where('user_id', $authUser->id)
            ->where('status_id', AgoraChannelViewer::STATUS_ACTIVE)
            ->first();
        if (!$senderViewer) {
            throw new Exception("Hediye gönderebilmek için yayını izliyor olmanız gerekiyor.");
        }
        $recipientViewer = $agoraChannel->viewers()
            ->whereIn('role_id', [AgoraChannelViewer::ROLE_HOST, AgoraChannelViewer::ROLE_GUEST])
            ->where('status_id', AgoraChannelViewer::STATUS_ACTIVE)
            ->where('user_id', $input['recipient_user_id'])
            ->first();
        if (!$recipientViewer) {
            throw new Exception("Yayında hediye gönderilecek kullanıcı aktif değil.");
        }
        /* end::Validation */

        return [
            $agoraChannel,
            $authUser,
            $recipientUser,
            $giftBasket,
            $senderViewer,
            $recipientViewer,
        ];
    }

    public function sendGift(
        array $input,
        User $authUser,
    ): void {
        try {
            [$agoraChannel, $user, $recipientUser, $giftBasket, $senderViewer, $recipientViewer] = $this->sendGiftValidation($input, $authUser);

            $quantity = $input['quantity'] ?? 1;

            $totalCost = $giftBasket->gift->get_final_price * $quantity;
            if ($giftBasket->gift->is_custom_gift) {
                $totalCost = $giftBasket->custom_unit_price * $quantity;
            }

            // Yayın hediyelerine hediyeyi kaydet
            dispatch(new StoreGiftInAgoraChannel($user->id, $recipientUser->id, $giftBasket->gift->id, $agoraChannel, $totalCost, $quantity));

            // Kullanıcının hediye sepetinden hediyeyi kaldır
            $giftBasket->decrement('quantity', $quantity);
            if ($giftBasket->quantity <= 0) {
                $giftBasket->delete();
            }

            //Hediyeyi redise yazıyoruz. (eğer pk ise onları da ayarlıyoruz)
            $this->giftSenderRedisService->handleGift($agoraChannel, $recipientUser->id, $user->id, $totalCost);

            //Hediyeyi chate mesaj olarak gönderiyoruz.
            $message = "{$user->nickname}, {$recipientUser->nickname}'e {$quantity} x {$giftBasket->gift->name} gönderdi.";
            $this->liveStreamChatService->sendMessage($agoraChannel, $user, $message, [
                'gift_id' => $giftBasket->gift->id,
                'gift_quantity' => $quantity,
            ]);

            // Yayıncı ve izleyiciler için bildirim (queue)
            event(new SentGiftToStream($user, $giftBasket, $agoraChannel));
            $recipientUser->notify(new AgoraChannelGiftNotification($user, $giftBasket, $agoraChannel));

            // Yayıncı bakiyesine coin transferi (queue)
            dispatch(new TransferCoinsToStreamerWallet($agoraChannel, $recipientUser, $user->id, $giftBasket->gift->id, $totalCost));

            // Agora channel viewer (alıcı ve gönderen için) hediye istatistiklerini güncelle (queue)
            dispatch(new UpdateAgoraViewerGiftStats($senderViewer, $recipientViewer, $quantity, $totalCost));

            // Agora channel hediye istatistiklerini güncelle (queue)
            dispatch(new UpdateAgoraChannelGiftStats($agoraChannel, $quantity, $totalCost));
        } catch (Throwable $e) {
            throw new Exception($e->getMessage());
        }
    }






    public function getStreamGifts(string $streamId, int $limit = 50, int $offset = 0): Collection
    {
        return AgoraChannelGift::where('agora_channel_id', $streamId)
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->limit($limit)
            ->get();
    }

    public function getTopDonators(string $streamId, int $limit = 10): Collection
    {
        $gifts = AgoraChannelGift::where('agora_channel_id', $streamId)
            ->get();

        // Kullanıcıya göre grupla ve toplam değerleri hesapla
        $donators = [];
        foreach ($gifts as $gift) {
            $userId = $gift->user_id;
            $value = ($gift->coin_value ?? 0) * ($gift->quantity ?? 1);

            if (!isset($donators[$userId])) {
                $donators[$userId] = [
                    'user_id' => $userId,
                    'user_data' => $gift->user_data,
                    'total_value' => 0,
                    'gift_count' => 0
                ];
            }

            $donators[$userId]['total_value'] += $value;
            $donators[$userId]['gift_count'] += $gift->quantity ?? 1;
        }

        // Toplam değere göre sırala
        usort($donators, function ($a, $b) {
            return $b['total_value'] <=> $a['total_value'];
        });

        // Collection'a dönüştür ve limit uygula
        return collect(array_slice($donators, 0, $limit));
    }

    public function getUserGifts(int $userId, int $limit = 50, int $offset = 0): Collection
    {
        return AgoraChannelGift::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->limit($limit)
            ->get();
    }

    public function canSendGifts(AgoraChannel $stream, User $user): bool
    {
        // Kullanıcı izleyici mi?
        $isViewer = AgoraChannelViewer::where('agora_channel_id', $stream->id)
            ->where('user_id', $user->id)
            ->where('status', AgoraChannelViewer::STATUS_ACTIVE)
            ->exists();

        if (!$isViewer) {
            return false;
        }

        // Yayın hediye göndermeye açık mı?
        $settings = $stream->settings ?? [];
        $allowGifts = $settings['allow_gifts'] ?? true;

        if (!$allowGifts) {
            return false;
        }

        // Kullanıcı yasaklı mı?
        $blockedUsers = $settings['blocked_users'] ?? [];
        if (in_array($user->id, $blockedUsers)) {
            return false;
        }

        return true;
    }
}
