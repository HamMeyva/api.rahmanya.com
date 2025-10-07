<?php

namespace App\Services\Challenges;

use Exception;
use App\Models\User;
use App\Models\Agora\AgoraChannel;
use Illuminate\Support\Facades\Cache;
use App\Models\Agora\AgoraChannelViewer;
use App\Models\Challenge\ChallengeInvite;
use App\Events\Challenges\ChallengeStarted;
use App\Helpers\Variable;
use App\Models\Challenge\Challenge;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Challenges\ChallengeInviteNotification;
use App\Notifications\Challenges\ChallengeStartedNotification;
use App\Notifications\Challenges\ChallengeStartableNotification;
use App\Notifications\Challenges\ChallengeInviteAcceptedNotification;
use App\Notifications\Challenges\ChallengeInviteRejectedNotification;

class ChallengeService
{
    public function sendInvite(array $input, User $authUser): ChallengeInvite
    {
        //1. Yayını getir
        $stream = AgoraChannel::find($input['agora_channel_id']);
        if (!$stream) {
            throw new Exception('Canlı yayın bulunamadı.');
        }

        if (!$stream->is_online) {
            throw new Exception('Canlı yayın sonlandığı için meydan okuma daveti atılamaz.');
        }

        if ($stream->is_challenge_active) {
            throw new Exception("Meydan okuma devam ederken yenisi başlatılamaz.");
        }

        $authViewer = AgoraChannelViewer::where('user_id', $authUser->id)
            ->where('agora_channel_id', $stream->id)
            ->whereIn('role_id', [AgoraChannelViewer::ROLE_HOST, AgoraChannelViewer::ROLE_GUEST])
            ->where('status_id', AgoraChannelViewer::STATUS_ACTIVE)
            ->exists();

        if (!$authViewer) {
            throw new Exception('Aktif bulunmadığınız yayına meydan okuma daveti atamazsınız.');
        }

        //2. Rate limit kontrolü
        $rateLimitKey = "challenge_invite_lock_{$authUser->id}_{$stream->id}";
        if (Cache::has($rateLimitKey)) {
            throw new Exception('Arka arkaya davet atılamaz. Lütfen tekrar davet atmak için biraz bekleyin.');
        }
        Cache::put($rateLimitKey, true, 20);


        //3. Yayındaki host ve guestleri al
        $invitedUsers = AgoraChannelViewer::where('agora_channel_id', $stream->id)
            ->whereIn('role_id', [AgoraChannelViewer::ROLE_HOST, AgoraChannelViewer::ROLE_GUEST])
            ->where('status_id', AgoraChannelViewer::STATUS_ACTIVE)
            ->get();


        $invitedUsersCount = $invitedUsers->count();
        if ($invitedUsersCount !== 2 && $invitedUsersCount !== 4) {
            throw new Exception('Yayında 2 veya 4 aktif yayıncı bulunmalıdır.');
        }

        //4. Konuk daveti oluştur.
        $invitedUsersData = [];
        $invitedUsers->map(function ($invitedUser) use (&$invitedUsersData, $authUser) {
            $userData = $invitedUser->user_data;
            $userData['invite_response'] = $authUser->id == $invitedUser->user_id ? 'accepted' : 'waiting'; // waiting, accepted, rejected
            $invitedUsersData[] = $userData;
        });

        $invite = ChallengeInvite::create([
            'agora_channel_id' => $stream->id,
            'sender_user_id' => $authUser->id,
            'status_id' => ChallengeInvite::STATUS_WAITING,
            'invited_users_data' => $invitedUsersData,
            'teammate_user_id' => $input['teammate_user_id'] ?? null,
            'round_duration' => $input['round_duration'] ?? 120,
            'coin_amount' => $input['coin_amount'] ?? 500,
        ]);

        //5. Bildirim gönder.
        foreach ($invitedUsers as $invitedUser) {
            if ($invitedUser->user_id == $authUser->id) continue;

            $user = User::find($invitedUser->user_id);
            if (!$user) continue;

            $user->notify(new ChallengeInviteNotification($invite, $user->id));
        }

        return $invite;
    }

    public function respondToInvite(array $input, User $authUser): void
    {
        $challengeInviteId = $input['challenge_invite_id'];
        $response = $input['response'];

        //1. Daveti kontrol et
        /** @var ChallengeInvite $invite */
        $invite = ChallengeInvite::find($challengeInviteId);
        if (!$invite) {
            throw new Exception('Davet bulunamadı.');
        }

        $collectInvitedUsersData = collect($invite->invited_users_data);
        $invitedUserIds = $collectInvitedUsersData->pluck('id')->toArray();
        if (!in_array($authUser->id, $invitedUserIds)) {
            throw new Exception('Bu davete cevap veremezsiniz.');
        }

        //davetin genel durumu kontrol edilir.
        if ($invite->status_id == ChallengeInvite::STATUS_REJECTED || $invite->status_id == ChallengeInvite::STATUS_ACCEPTED) {
            throw new Exception('Davet zaten yanıtlanmış.');
        }

        if ($invite->status_id == ChallengeInvite::STATUS_CANCELLED) {
            throw new Exception('Davet iptal edilmiş.');
        }

        if ($invite->expires_at < now()) {
            throw new Exception('Davet süresi dolmuş.');
        }

        //auth olan kullanıcı bu davete yanıt vermiş mi?
        $inviteUser = $collectInvitedUsersData->where('id', $authUser->id)->first();
        if (!$inviteUser) {
            throw new Exception('Bu davete cevap veremezsiniz.');
        }

        if (isset($inviteUser['invite_response']) && $inviteUser['invite_response'] == 'accepted') {
            throw new Exception('Bu daveti zaten kabul etmişsiniz.');
        }

        if (isset($inviteUser['invite_response']) && $inviteUser['invite_response'] == 'rejected') {
            throw new Exception('Bu daveti zaten reddetmişsiniz.');
        }


        //davetteki yayıncılar ile şuan aktif yayıncıları karşılaştır. farklı ise karşılaşma başlatılamaz.
        $streamerIds = AgoraChannelViewer::where('agora_channel_id', $invite->agora_channel_id)
            ->whereIn('role_id', [AgoraChannelViewer::ROLE_HOST, AgoraChannelViewer::ROLE_GUEST])
            ->where('status_id', AgoraChannelViewer::STATUS_ACTIVE)
            ->pluck('user_id');

        if (!$streamerIds->count() || $streamerIds->diff($invitedUserIds)->isNotEmpty()) {
            $invite->update(['status_id' => ChallengeInvite::STATUS_CANCELLED]);
            throw new Exception('Yayında aktif yayıncılar değiştiği için davet iptal edildi.');
        }

        //2. Yayını kontrol et
        $stream = AgoraChannel::find($invite->agora_channel_id);
        if (!$stream) {
            throw new Exception('Yayın bulunamadı.');
        }
        if (!$stream->is_online) {
            throw new Exception('Canlı yayın sonlanmış.');
        }
        if ($stream->is_challenge_active) {
            $invite->update(['status_id' => ChallengeInvite::STATUS_CANCELLED]);
            throw new Exception("Yayında aktif bir meydan okuma olduğu için davetiniz geçersiz kalmıştır.");
        }

        /** @var User $hostUser */
        $hostUser = User::find($stream->user_id);
        if (!$hostUser) {
            throw new Exception('Yayın sahibi bulunamadı.');
        }

        //3. Davet eden kullanıcıyı kontrol et.
        $senderUser = User::find($invite->sender_user_id);
        if (!$senderUser) {
            throw new Exception('Davet eden kullanıcı bulunamadığı için davet geçersiz.');
        }

        //4. Daveti güncelle
        $newInvitedUsersData = $invite->invited_users_data;
        $newInvitedUsersData = collect($newInvitedUsersData)->map(function ($invitedUser) use ($authUser, $response) {
            if ($invitedUser['id'] == $authUser->id) {
                $invitedUser['invite_response'] = $response ? 'accepted' : 'rejected';
            }
            return $invitedUser;
        });

        $invite->invited_users_data = $newInvitedUsersData->toArray();

        //eğer tüm davetliler kabul etti ise davetin ana durumunu güncelle
        $allAccepted = $newInvitedUsersData->every(fn($invitedUser) => $invitedUser['invite_response'] === 'accepted');
        if ($allAccepted) {
            $invite->status_id = ChallengeInvite::STATUS_ACCEPTED;
        }

        //Invite collectionını kaydet
        $invite->save();

        //5. Bildirim gönder
        if ($response) {
            $hostUser->notify(new ChallengeInviteAcceptedNotification($invite, $authUser));
            if ($hostUser->id != $senderUser->id) {
                $senderUser->notify(new ChallengeInviteAcceptedNotification($invite, $authUser));
            }
        } else {
            $hostUser->notify(new ChallengeInviteRejectedNotification($invite, $authUser));
            if ($hostUser->id != $senderUser->id) {
                $senderUser->notify(new ChallengeInviteRejectedNotification($invite, $authUser));
            }
        }

        //Host'a Meydan okuma başlatılabilir bildirimini gönder
        if ($allAccepted) {
            $hostUser->notify(new ChallengeStartableNotification($invite, $hostUser));
        }
    }

    public function startChallenge(User $authUser, $roundDuration = null, $maxCoins = null, $teammateId = null): void
    {
        $round = 2;
        $roundDuration = $roundDuration ?? 120;
        $maxCoins = $maxCoins ?? 1000;

        // 1. Yayın var mı? ve authUser host mu?
        $stream = AgoraChannel::where('user_id', $authUser->id)->first();
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
        $invitedUserIds = $collectInvitedUsersData->toArray();

        if (!$streamerIds->count() || $streamerIds->diff($invitedUserIds)->isNotEmpty()) {
            throw new Exception('Yayında aktif yayıncılar değiştiği için meydan okuma başlatılamadı.');
        }

        // 4. Toplam katılımcı sayısını kontrol et
        if (!in_array($collectInvitedUsersData->count(), [2, 4]) || !in_array($streamerIds->count(), [2, 4])) {
            throw new Exception("Meydan okuma sadece 2 veya 4 kişiyle başlatılabilir.");
        }

        // 5. Meydan okuma takımlarını belirle.
        $teamsData = null;

        //1v1 ise
        if ($collectInvitedUsersData->count() === 2) {
            $opponent = $collectInvitedUsersData->firstWhere('id', '!=', $authUser->id);
            $teamsData = [
                'team_1' => [[
                    'user_id' => $authUser->id,
                    'nickname' => $authUser->nickname,
                    'gift_count' => 0,
                    'coins_earned' => 0,
                ]],
                'team_2' => [[
                    'user_id' => $opponent['id'],
                    'nickname' => $opponent['nickname'],
                    'gift_count' => 0,
                    'coins_earned' => 0,
                ]],
            ];
        }

        //2v2 ise
        if ($collectInvitedUsersData->count() === 4) {
            if (!in_array($teammateId, $collectInvitedUsersData->pluck('id')->toArray())) {
                throw new Exception("Geçersiz takım arkadaşı seçimi.");
            }

            $team1Ids = [$authUser->id, $teammateId];

            $teamsData = [
                'team_1' => [],
                'team_2' => [],
            ];

            foreach ($collectInvitedUsersData as $userData) {
                $teamKey = in_array($userData['id'], $team1Ids) ? 'team_1' : 'team_2';
                $teamsData[$teamKey][] = [
                    'user_id' => $userData['id'],
                    'nickname' => $userData['nickname'],
                    'gift_count' => 0,
                    'coins_earned' => 0,
                ];
            }
        }

        // 6. Meydan okuma başlat
        $challenge = Challenge::create([
            'agora_channel_id' => $stream->id,
            'type_id' => $collectInvitedUsersData->count() === 2 ? (int)Challenge::TYPE_1v1 : (int)Challenge::TYPE_2v2,
            'status_id' => (int)Challenge::STATUS_ACTIVE,
            'started_at' => now(),
            'teams' => $teamsData,
        ]);

        $stream->update([
            'is_challenge_active' => true,
        ]);

        //7. YAYINA Meydan okuma başlatıldı bildirimini gönder.
        event(new ChallengeStarted($stream));

        //8. Meydan okumadaki yayıncıların takipçilerine bildirim gönder.
        User::query()
            ->whereHas('followers', fn($q) => $q->whereIn('followed_id', $streamerIds->toArray()))
            ->chunk(1000, function ($users) use ($stream) {
                foreach ($users as $user) {
                    Notification::send($user, new ChallengeStartedNotification($stream));
                }
            });
    }
}
