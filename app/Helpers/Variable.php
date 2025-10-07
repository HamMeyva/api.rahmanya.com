<?php

namespace App\Helpers;


class Variable
{
    public const
        WALLET_TYPE_DEFAULT = 1, // users: coin_balance
        WALLET_TYPE_EARNED = 2; // users: earned_coin_balance
    public static array $walletTypes = [
        self::WALLET_TYPE_DEFAULT => 'Varsayılan Bakiye',
        self::WALLET_TYPE_EARNED => 'Kazanılan Bakiye',
    ];

    public const COUNTRY_ID_TURKEY = 27;

    public const DEFAULT_START_DATE = '2025-01-01'; // tarih filtrelerinde varsayılan başlangıç tarihi


    public const
        NOTIFICATION_TYPE_BULK = 'bulk',
        NOTIFICATION_TYPE_EFT_PAYMENT_CREATED = 'eft_payment_created',
        NOTIFICATION_TYPE_AGORA_CHANNEL_INVITE = 'agora_channel_invite',
        NOTIFICATION_TYPE_CHALLENGE_INVITE_ACCEPTED = 'challenge_invite_accepted',
        NOTIFICATION_TYPE_CHALLENGE_INVITE = 'challenge_invite',
        NOTIFICATION_TYPE_CHALLENGE_INVITE_REJECTED = 'challenge_invite_rejected',
        NOTIFICATION_TYPE_CHALLENGE_STARTABLE = 'challenge_startable',
        NOTIFICATION_TYPE_CHALLENGE_STARTED = 'challenge_started',
        NOTIFICATION_TYPE_ADDED_GIFT_TO_BASKET = 'added_gift_to_basket',
        NOTIFICATION_TYPE_AGORA_CHANNEL_GIFT = 'agora_channel_gift',
        NOTIFICATION_TYPE_COIN_DEPOSIT = 'coin_deposit',
        NOTIFICATION_TYPE_COIN_WITHDRAWAL_REQUEST_APPROVED = 'coin_withdrawal_request_approved',
        NOTIFICATION_TYPE_EFT_PAYMENT_REJECTED = 'eft_payment_rejected',
        NOTIFICATION_TYPE_VIDEO_COMMENTED = 'video_commented',
        NOTIFICATION_TYPE_VIDEO_LIKED = 'video_liked',
        NOTIFICATION_TYPE_COMMENT_REPLYED = 'comment_reply',
        NOTIFICATION_TYPE_USER_MENTIONED_IN_COMMENT = 'user_mentioned_in_comment',
        NOTIFICATION_TYPE_COMMENT_DISLIKED = 'comment_disliked',
        NOTIFICATION_TYPE_COIN_WITHDRAWAL_REQUEST_REJECTED = 'coin_withdrawal_request_rejected',
        NOTIFICATION_TYPE_COMMENT_LIKED = 'comment_liked',
        NOTIFICATION_TYPE_USER_PUNISHED = 'user_punished',
        NOTIFICATION_TYPE_CONVERSATION_UPDATED = 'conversation_updated',
        NOTIFICATION_TYPE_VIDEO_STATUS_UPDATED = 'video_status_updated',
        NOTIFICATION_TYPE_LIVE_STREAM_STARTED = 'live_stream_started',
        NOTIFICATION_TYPE_MESSAGE_SENT_TO_FOLLOWERS = 'message_sent_to_followers',
        NOTIFICATION_TYPE_USER_BANNED = 'user_banned',
        NOTIFICATION_TYPE_VIDEO_READY_FOR_DOWNLOAD = 'video_ready_for_download';

    public static array $notificationTypes = [
        self::NOTIFICATION_TYPE_BULK => 'Toplu Bildirim',
        self::NOTIFICATION_TYPE_EFT_PAYMENT_CREATED => 'Onay Bekeyen Havale!',
        self::NOTIFICATION_TYPE_AGORA_CHANNEL_INVITE => 'Canlı Yayın Konuk Daveti',
        self::NOTIFICATION_TYPE_CHALLENGE_INVITE_ACCEPTED => 'Meydan Okuma Kabul Edildi!',
        self::NOTIFICATION_TYPE_CHALLENGE_INVITE => 'Meydan Okuma Daveti!',
        self::NOTIFICATION_TYPE_CHALLENGE_INVITE_REJECTED => 'Meydan Okuma Reddedildi!',
        self::NOTIFICATION_TYPE_CHALLENGE_STARTABLE => 'Meydan Okuma Başlatılabilir!',
        self::NOTIFICATION_TYPE_CHALLENGE_STARTED => 'Meydan Okuma Başladı!',
        self::NOTIFICATION_TYPE_ADDED_GIFT_TO_BASKET => 'Hediye Sepetine Eklendi!',
        self::NOTIFICATION_TYPE_AGORA_CHANNEL_GIFT => 'Hediye Geldi',
        self::NOTIFICATION_TYPE_COIN_DEPOSIT => 'Shoot Coin Alımı',
        self::NOTIFICATION_TYPE_COIN_WITHDRAWAL_REQUEST_APPROVED => 'Shoot Coin Çekim Talebi Onayı',
        self::NOTIFICATION_TYPE_EFT_PAYMENT_REJECTED => 'Havale Rededildi',
        self::NOTIFICATION_TYPE_VIDEO_COMMENTED => 'Yeni Yorum',
        self::NOTIFICATION_TYPE_VIDEO_LIKED => 'Yeni Beğeni',
        self::NOTIFICATION_TYPE_COMMENT_REPLYED => 'Yeni Yanıt',
        self::NOTIFICATION_TYPE_USER_MENTIONED_IN_COMMENT => 'Senden Bahsetti',
        self::NOTIFICATION_TYPE_COMMENT_DISLIKED => 'Yorumun Beğenilmedi',
        self::NOTIFICATION_TYPE_COIN_WITHDRAWAL_REQUEST_REJECTED => 'Shoot Coin Çekim Talebi Reddedildi',
        self::NOTIFICATION_TYPE_COMMENT_LIKED => 'Yorumun Beğenildi',
        self::NOTIFICATION_TYPE_USER_PUNISHED => 'Ceza Alındı',
        self::NOTIFICATION_TYPE_CONVERSATION_UPDATED => 'Yeni Mesaj',
        self::NOTIFICATION_TYPE_VIDEO_STATUS_UPDATED => 'Video Durumu Güncellendi',
        self::NOTIFICATION_TYPE_LIVE_STREAM_STARTED => 'Canlı Yayın Başladı',
        self::NOTIFICATION_TYPE_MESSAGE_SENT_TO_FOLLOWERS => 'Tüm Takipçilere Mesaj Şutlandı',
        self::NOTIFICATION_TYPE_USER_BANNED => 'Hesabınız Askıya Alındı',
        self::NOTIFICATION_TYPE_VIDEO_READY_FOR_DOWNLOAD => 'Video İndirilmeye Hazır',
    ];
}
