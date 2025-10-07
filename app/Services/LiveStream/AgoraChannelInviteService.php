<?php

namespace App\Services\LiveStream;

use Exception;
use App\Models\User;
use App\Models\Agora\AgoraChannel;
use App\Services\AgoraTokenService;
use App\Models\Agora\AgoraChannelInvite;
use App\Models\Agora\AgoraChannelViewer;
use App\Services\LiveStream\AgoraChannelService;
use App\Notifications\LiveStream\AgoraChannelInviteNotification;


class AgoraChannelInviteService
{
    protected AgoraChannelService $agoraChannelService;

    public function __construct(AgoraChannelService $agoraChannelService)
    {
        $this->agoraChannelService = $agoraChannelService;
    }

    public function inviteUserToChannel(array $input, User $authUser): AgoraChannelInvite
    {
        $agoraChannelId = $input['agora_channel_id'] ?? null;
        $invitedUserId = $input['invited_user_id'] ?? null;


        //1. Canlı yayın kontorlü?
        $stream = AgoraChannel::find($agoraChannelId);
        if (!$stream) {
            throw new Exception('Canlı yayın bulunamadı.');
        }

        if (!$stream->is_online) {
            throw new Exception('Canlı yayın sonlandığı için konuk davet edilemez.');
        }

        if ($stream->user_id !== $authUser->id) {
            throw new Exception('Konuk daveti için yetkiniz yok.');
        }

        //2. Konuk id geçerli mi?
        $invitedUser = User::find($invitedUserId);
        if (!$invitedUser) {
            throw new Exception('Konuk bulunamadı.');
        }

        //3. Aynı konuk için bekleyen bir davet isteği var mı son 3 dakika içinde?
        $pendingInviteExists = AgoraChannelInvite::where('agora_channel_id', $stream->id)
            ->where('user_id', $authUser->id)
            ->where('invited_user_id', $invitedUser->id)
            ->where('status_id', AgoraChannelInvite::STATUS_PENDING)
            ->where('created_at', '>=', now()->subMinutes(3))
            ->exists();

        if ($pendingInviteExists) {
            throw new Exception('Arka arkaya davet gönderemezsiniz. Bir süre sonra tekrar deneyin.');
        }

        //4. Yayın kapasitesi yeterli mi? (Yayında 4 yayıncı var ise daha fazla konuk eklenemez.)
        $viewerCount = AgoraChannelViewer::where('agora_channel_id', $stream->id)
            ->whereIn('role_id', [AgoraChannelViewer::ROLE_HOST, AgoraChannelViewer::ROLE_GUEST])
            ->count();

        if ($viewerCount >= 4) {
            throw new Exception('Yayın kapasitesi yeni konuk için yeterli değil.');
        }

        //5. Konuk daveti oluştur.
        $invite = AgoraChannelInvite::create([
            'agora_channel_id' => $stream->id,
            'user_id' => $authUser->id,
            'invited_user_id' => $invitedUser->id,
            'status_id' => AgoraChannelInvite::STATUS_PENDING,
        ]);

        if (!$invite) {
            throw new Exception('Sistemsel bir hata oluştu, davet oluşturulamadı.');
        }

        //6. Davetliye bildirim gönder.
        $invitedUser->notify(new AgoraChannelInviteNotification($invite));

        return $invite;
    }

    public function respondToInvite(array $input, User $authUser): array
    {
        $inviteId = $input['invite_id'] ?? null;
        $response = $input['response'] ?? null;

        //1. Davet kontrolü
        $invite = AgoraChannelInvite::find($inviteId);
        if (!$invite) {
            throw new Exception('Davet bulunamadı.');
        }

        if ($invite->invited_user_id !== $authUser->id) {
            throw new Exception('Bu davete cevap veremezsiniz.');
        }

        if ($invite->status_id !== AgoraChannelInvite::STATUS_PENDING) {
            throw new Exception('Davet zaten onaylandı.');
        }


        //2. Yayın kontrolü.
        /** @var \App\Models\Agora\AgoraChannel $stream */
        $stream = AgoraChannel::find($invite->agora_channel_id);
        if (!$stream) {
            throw new Exception('Canlı yayın bulunamadı.');
        }

        if (!$stream->is_online) {
            throw new Exception('Canlı yayın sonlandığı için katılamıyorsunuz.');
        }

        //3. Daveti onayla
        if ($response === true) {
            $viewer = $this->agoraChannelService->joinStream($stream, $authUser, AgoraChannelViewer::ROLE_GUEST, AgoraTokenService::RoleAttendee);
        } else {
            // Reddedildi
        }

        $invite->update([
            'status_id' => $response === true ? AgoraChannelInvite::STATUS_ACCEPTED : AgoraChannelInvite::STATUS_REJECTED,
            'responded_at' => now(),
        ]);

        //4. Bu kanala ait katılma davetleri rejected olarak güncellenebilir. (job açıp queue da)
        //...

        return [
            'agora_channel' => $stream,
            'token' => $viewer->token
        ];
    }
}
