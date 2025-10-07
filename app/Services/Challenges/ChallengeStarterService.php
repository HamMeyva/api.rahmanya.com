<?php

namespace App\Services\Challenges;

use Exception;
use App\Models\User;
use App\Models\Agora\AgoraChannel;
use App\Models\Challenge\Challenge;
use App\Models\Challenge\ChallengeTeam;
use App\Models\Agora\AgoraChannelViewer;
use App\Models\Challenge\ChallengeRound;
use App\Models\Challenge\ChallengeInvite;
use App\Events\Challenges\ChallengeStarted;
use Illuminate\Support\Facades\Notification;
use App\Jobs\Challenges\ProcessChallengeRound;
use App\Notifications\Challenges\ChallengeStartedNotification;

class ChallengeStarterService
{
    public function start(User $authUser): void
    {
        // 1. Yayın var mı? ve authUser host mu?
        $stream = AgoraChannel::where('user_id', $authUser->id)->where('is_online', true)->first();
        if (!$stream) {
            throw new Exception("Yayın bulunamadı.");
        }

        if (!$stream->is_online) {
            throw new Exception("Yayın çevrimdışı.");
        }

        // 2. Zaten başlamış mı? tekrar başlatılamasın
        if ($stream->is_challenge_active) {
            throw new Exception("Bu yayında zaten aktif bir meydan okuma var.");
        }

        // 3. Daveti kontrol et!
        $invite = ChallengeInvite::query()
            ->where('agora_channel_id', $stream->id)
            ->where('status_id', ChallengeInvite::STATUS_ACCEPTED)
            ->latest()->first();

        if (!$invite) {
            throw new Exception("Bu yayında aktif bir meydan okuma daveti bulunamadı.");
        }

        //4. Yayının aktif yayıncılarını kontrol et. Davetdekiler ile eşleşiyor mu?
        $streamerIds = AgoraChannelViewer::where('agora_channel_id', $invite->agora_channel_id)
            ->whereIn('role_id', [AgoraChannelViewer::ROLE_HOST, AgoraChannelViewer::ROLE_GUEST])
            ->where('status_id', AgoraChannelViewer::STATUS_ACTIVE)
            ->pluck('user_id');

        $collectInvitedUsersData = collect($invite->invited_users_data);
        $invitedUserCount = $collectInvitedUsersData->count();
        $invitedUserIds = $collectInvitedUsersData->pluck('id');

        if (!$streamerIds->count() || $streamerIds->diff($invitedUserIds->toArray())->isNotEmpty()) {
            throw new Exception('Yayında aktif yayıncılar değiştiği için meydan okuma başlatılamadı.');
        }

        // 5. Toplam katılımcı sayısını kontrol et
        if (!in_array($collectInvitedUsersData->count(), [2, 4]) || !in_array($streamerIds->count(), [2, 4])) {
            throw new Exception("Meydan okuma sadece 2 veya 4 kişiyle başlatılabilir.");
        }

        //6. Takımları Ayarla.
        $teamSize = $invitedUserCount / 2;
        $teams = [];
        if ($teamSize === 2 && $invite->teammate_user_id) {
            //teammate id yi dikkate alarak takımları olustur
            //@todo bu if içini test edemedim bakacagım hata olabilir (4 kişiyi yayına alıp izleyici ekleyip pk başlatmak testi cok uzun :)  )
            $teams = [
                collect([$invite->sender_user_id, $invite->teammate_user_id]),
                $invitedUserIds->reject(fn($id) => $teams[0]->contains($id))->values(),
            ];
        } else {
            $teams = $invitedUserIds->chunk($teamSize);
        }

        //7. Challenge Oluştur.
        $challenge = Challenge::create([
            'agora_channel_id' => $stream->id,
            'type_id' => $teamSize === 1 ? (int)Challenge::TYPE_1v1 : (int)Challenge::TYPE_2v2,
            'status_id' => (int)Challenge::STATUS_ACTIVE,
            'started_at' => now(),
            'round_count' => 2,
            'current_round' => 1,
            'round_duration' => $invite->round_duration ?? 120,
            'max_coins' => $invite->coin_amount ?? 500,
        ]);

        //8. Takımları kaydet.
        foreach ($teams as $index => $users) {
            foreach ($users as $userId) {
                ChallengeTeam::create([
                    'challenge_id' => $challenge->_id,
                    'team_no' => $index + 1,
                    'user_id' => $userId,
                ]);
            }
        }

        $challengeRound = ChallengeRound::create([
            'challenge_id' => $challenge->_id,
            'round_number' => 1,
            'start_at' => now(),
            'end_at' => now()->addSeconds($invite->round_duration ?? 120),
        ]);

        $invite->status_id = ChallengeInvite::STATUS_EXPIRED;
        $invite->save();

        $stream->update([
            'is_challenge_active' => true,
        ]);

        //9. Round bitimini takip eden job'u kuyruğa at.
        ProcessChallengeRound::dispatch($challenge->_id, 1)
            ->delay($challengeRound->end_at);

        //10. YAYINA Meydan okuma başlatıldı bildirimini gönder.
        event(new ChallengeStarted($stream));

        //11. Meydan okumadaki yayıncıların takipçilerine bildirim gönder.
        User::query()
            ->whereHas('followers', fn($q) => $q->whereIn('followed_id', $streamerIds->toArray()))
            ->chunk(1000, function ($users) use ($stream) {
                foreach ($users as $user) {
                    Notification::send($user, new ChallengeStartedNotification($stream));
                }
            });
    }
}
