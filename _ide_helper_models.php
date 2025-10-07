<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $user_id
 * @property string|null $reason
 * @property string $status
 * @property string|null $admin_notes
 * @property string|null $processed_by
 * @property $processed_at
 * @property $created_at
 * @property $updated_at
 * @property-read \App\Models\User|null $processedBy
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest whereAdminNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest whereProcessedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAccountDeletionRequest {}
}

namespace App\Models\Ad{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $advertiser_id
 * @property string|null $title
 * @property string|null $description
 * @property string|null $redirect_url
 * @property int|null $media_type_id
 * @property string|null $media_path
 * @property int $status_id
 * @property int $payment_status_id
 * @property $paid_at
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $show_start_time
 * @property \Illuminate\Support\Carbon|null $show_end_time
 * @property string $total_budget
 * @property int $total_hours
 * @property string $bid_amount
 * @property int|null $target_country_id
 * @property int|null $target_language_id
 * @property int $impressions
 * @property int $clicks
 * @property string $ctr
 * @property string|null $video_guid
 * @property-read \App\Models\Ad\Advertiser|null $advertiser
 * @property-read mixed $draw_bid_amount
 * @property-read mixed $draw_total_budget
 * @property-read mixed $draw_total_hours
 * @property-read mixed $get_bid_amount
 * @property-read mixed $get_created_at
 * @property-read mixed $get_media_type
 * @property-read mixed $get_payment_status
 * @property-read mixed $get_payment_status_color
 * @property-read mixed $get_start_date
 * @property-read mixed $get_status
 * @property-read mixed $get_status_color
 * @property-read mixed $get_total_budget
 * @property-read mixed $media_url
 * @property-read \App\Models\Morph\Payment|null $payment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Morph\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Demographic\Placement> $placements
 * @property-read int|null $placements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Demographic\AgeRange> $target_age_ranges
 * @property-read int|null $target_age_ranges_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Common\City> $target_cities
 * @property-read int|null $target_cities_count
 * @property-read \App\Models\Common\Country|null $target_country
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Demographic\Gender> $target_genders
 * @property-read int|null $target_genders_count
 * @property-read \App\Models\Demographic\Language|null $target_language
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Demographic\Os> $target_oses
 * @property-read int|null $target_oses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Relations\Team> $target_teams
 * @property-read int|null $target_teams_count
 * @property-read mixed $thumbnail_url
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereAdvertiserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereBidAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereClicks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereCtr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereImpressions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereMediaPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereMediaTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad wherePaymentStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereRedirectUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereShowEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereShowStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereTargetCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereTargetLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereTotalBudget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereTotalHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad whereVideoGuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ad withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAd {}
}

namespace App\Models\Ad{
/**
 * 
 *
 * @property mixed $id 6 occurrences
 * @property int|null $ad_id 6 occurrences
 * @property $click_at 6 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 6 occurrences
 * @property string|null $ip_address 6 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 6 occurrences
 * @property string|null $user_agent 6 occurrences
 * @property string|null $user_id 6 occurrences
 * @property-read mixed $deleted_at
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdClick addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdClick aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdClick getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdClick insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdClick insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdClick newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdClick newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdClick query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdClick raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdClick whereAdId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdClick whereClickAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdClick whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdClick whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdClick whereIpAddress($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdClick whereUpdatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdClick whereUserAgent($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdClick whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAdClick {}
}

namespace App\Models\Ad{
/**
 * 
 *
 * @property mixed $id 19 occurrences
 * @property int|null $ad_id 19 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 19 occurrences
 * @property int|null $duration 19 occurrences
 * @property $impression_at 19 occurrences
 * @property string|null $ip_address 19 occurrences
 * @property bool|null $is_completed 19 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 19 occurrences
 * @property string|null $user_agent 19 occurrences
 * @property string|null $user_id 19 occurrences
 * @property-read mixed $deleted_at
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdImpression addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdImpression aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdImpression getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdImpression insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdImpression insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdImpression newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdImpression newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdImpression query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdImpression raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdImpression whereAdId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdImpression whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdImpression whereDuration($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdImpression whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdImpression whereImpressionAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdImpression whereIpAddress($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdImpression whereIsCompleted($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdImpression whereUpdatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdImpression whereUserAgent($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AdImpression whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAdImpression {}
}

namespace App\Models\Ad{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $type_id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property int $status_id
 * @property string|null $collection_uuid
 * @property string|null $logo_path
 * @property-read mixed $get_created_at
 * @property-read mixed $get_status
 * @property-read mixed $get_type
 * @property-read mixed $logo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Advertiser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Advertiser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Advertiser onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Advertiser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Advertiser whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Advertiser whereCollectionUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Advertiser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Advertiser whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Advertiser whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Advertiser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Advertiser whereLogoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Advertiser whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Advertiser wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Advertiser whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Advertiser whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Advertiser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Advertiser withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Advertiser withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAdvertiser {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property $created_at
 * @property $updated_at
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password
 * @property-read mixed $full_name
 * @property-read mixed $get_created_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read mixed $timezone
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin withoutRole($roles, $guard = null)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAdmin {}
}

namespace App\Models\Agora{
/**
 * 
 *
 * @property mixed $id 24 occurrences
 * @property string|null $category_id 24 occurrences
 * @property string|null $channel_name 24 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 24 occurrences
 * @property string|null $description 24 occurrences
 * @property int $duration 7 occurrences
 * @property $ended_at 24 occurrences
 * @property bool|null $is_challenge_active 5 occurrences
 * @property bool $is_online 24 occurrences
 * @property int|null $language_id 24 occurrences
 * @property int $max_viewer_count 7 occurrences
 * @property string|null $playback_url 24 occurrences
 * @property string|null $rtmp_url 24 occurrences
 * @property string|null $settings 24 occurrences
 * @property $started_at 24 occurrences
 * @property int|null $status_id 24 occurrences
 * @property string|null $stream_key 24 occurrences
 * @property string|null $tags 24 occurrences
 * @property string|null $team_id 1 occurrences
 * @property string|null $thumbnail_url 24 occurrences
 * @property string|null $title 24 occurrences
 * @property int $total_coins_earned 5 occurrences
 * @property int $total_gifts 5 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 24 occurrences
 * @property string|null $user_id 24 occurrences
 * @property int $viewer_count 7 occurrences
 * @property-read \App\Models\Challenge\Challenge|null $activeChallenge
 * @property-read \App\Models\LiveStreamCategory|null $category
 * @property-read mixed $deleted_at
 * @property-read mixed $get_ended_at
 * @property-read string $formatted_viewer_count
 * @property-read bool $is_featured
 * @property-read mixed $get_started_at
 * @property-read string $status
 * @property-read int $total_likes
 * @property-read int $total_messages
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Morph\ReportProblem> $reported_problems
 * @property-read int|null $reported_problems_count
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel active()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel byCategory($categoryId)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel featured()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraChannel onlyTrashed()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereCategoryId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereChannelName($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereDescription($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereDuration($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereEndedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereIsChallengeActive($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereIsOnline($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereLanguageId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereMaxViewerCount($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel wherePlaybackUrl($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereRtmpUrl($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereSettings($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereStartedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereStatusId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereStreamKey($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereTags($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereTeamId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereThumbnailUrl($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereTitle($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereTotalCoinsEarned($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereTotalGifts($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereUpdatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereUserId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannel whereViewerCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraChannel withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraChannel withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAgoraChannel {}
}

namespace App\Models\Agora{
/**
 * 
 *
 * @property mixed $id 43 occurrences
 * @property string|null $agora_channel_data 43 occurrences
 * @property string|null $agora_channel_id 43 occurrences
 * @property string|null $challenge_id 43 occurrences
 * @property int|null $coin_value 43 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 43 occurrences
 * @property string|null $gift_data 43 occurrences
 * @property int|null $gift_id 43 occurrences
 * @property int|null $quantity 43 occurrences
 * @property string|null $recipient_user_data 43 occurrences
 * @property string|null $recipient_user_id 43 occurrences
 * @property int|null $streak 43 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 43 occurrences
 * @property string|null $user_data 43 occurrences
 * @property string|null $user_id 43 occurrences
 * @property-read mixed $get_created_at
 * @property-read mixed $deleted_at
 * @property-read int $total_value
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift byUser(int $userId)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift featured()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift forChannel(string $channelId)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift whereAgoraChannelData($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift whereAgoraChannelId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift whereChallengeId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift whereCoinValue($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift whereGiftData($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift whereGiftId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift whereQuantity($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift whereRecipientUserData($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift whereRecipientUserId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift whereStreak($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift whereUpdatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift whereUserData($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelGift whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAgoraChannelGift {}
}

namespace App\Models\Agora{
/**
 * 
 *
 * @property mixed $id 5 occurrences
 * @property string|null $agora_channel_data 5 occurrences
 * @property string|null $agora_channel_id 5 occurrences
 * @property $created_at 5 occurrences
 * @property string|null $invited_user_data 5 occurrences
 * @property string|null $invited_user_id 5 occurrences
 * @property $responded_at 5 occurrences
 * @property int|null $status_id 5 occurrences
 * @property $updated_at 5 occurrences
 * @property string|null $user_data 5 occurrences
 * @property string|null $user_id 5 occurrences
 * @property $invited_at
 * @property-read mixed $get_status
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelInvite addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelInvite aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelInvite getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelInvite insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelInvite insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelInvite newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelInvite newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelInvite query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelInvite raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelInvite whereAgoraChannelData($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelInvite whereAgoraChannelId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelInvite whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelInvite whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelInvite whereInvitedUserData($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelInvite whereInvitedUserId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelInvite whereRespondedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelInvite whereStatusId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelInvite whereUpdatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelInvite whereUserData($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelInvite whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAgoraChannelInvite {}
}

namespace App\Models\Agora{
/**
 * 
 *
 * @property mixed $id 45 occurrences
 * @property string|null $admin_data 2 occurrences
 * @property string|null $admin_id 2 occurrences
 * @property string|null $agora_channel_data 45 occurrences
 * @property string|null $agora_channel_id 45 occurrences
 * @property $created_at 45 occurrences
 * @property string|null $gift_data 34 occurrences
 * @property int|null $gift_id 34 occurrences
 * @property bool|null $has_banned_word 1 occurrences
 * @property string|null $message 45 occurrences
 * @property string|null $original_message 1 occurrences
 * @property string|null $timestamp 45 occurrences
 * @property $updated_at 45 occurrences
 * @property string|null $user_data 43 occurrences
 * @property string|null $user_id 43 occurrences
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage forChannel(string $channelId)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage notBlocked()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage pinned()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage whereAdminData($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage whereAdminId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage whereAgoraChannelData($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage whereAgoraChannelId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage whereGiftData($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage whereGiftId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage whereHasBannedWord($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage whereMessage($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage whereOriginalMessage($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage whereTimestamp($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage whereUpdatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage whereUserData($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage whereUserId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelMessage withGift()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAgoraChannelMessage {}
}

namespace App\Models\Agora{
/**
 * 
 *
 * @property mixed $id 54 occurrences
 * @property string|null $agora_channel_data 54 occurrences
 * @property string|null $agora_channel_id 54 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 54 occurrences
 * @property bool|null $is_following_streamer 30 occurrences
 * @property $joined_at 54 occurrences
 * @property $left_at 18 occurrences
 * @property int|null $role_id 54 occurrences
 * @property int|null $status_id 54 occurrences
 * @property string|null $token 30 occurrences
 * @property int|null $total_received_coin_value 9 occurrences
 * @property int|null $total_received_gift_count 9 occurrences
 * @property int|null $total_sent_coin_value 6 occurrences
 * @property int|null $total_sent_gift_count 6 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 54 occurrences
 * @property string|null $user_data 54 occurrences
 * @property string|null $user_id 54 occurrences
 * @property float|null $watch_duration 2 occurrences
 * @property-read mixed $deleted_at
 * @property-read mixed $get_role
 * @property-read mixed $get_status
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer whereAgoraChannelData($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer whereAgoraChannelId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer whereIsFollowingStreamer($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer whereJoinedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer whereLeftAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer whereRoleId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer whereStatusId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer whereToken($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer whereTotalReceivedCoinValue($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer whereTotalReceivedGiftCount($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer whereTotalSentCoinValue($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer whereTotalSentGiftCount($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer whereUpdatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer whereUserData($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer whereUserId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|AgoraChannelViewer whereWatchDuration($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAgoraChannelViewer {}
}

namespace App\Models\Agora{
/**
 * 
 *
 * @property int $id
 * @property string $user_id
 * @property \Illuminate\Support\Carbon $date
 * @property int $total_stream_duration
 * @property int $total_viewers
 * @property int $unique_viewers
 * @property int $max_concurrent_viewers
 * @property int $avg_watch_time
 * @property int $total_comments
 * @property int $total_likes
 * @property int $total_gifts
 * @property int $total_coins_earned
 * @property int $new_followers_gained
 * @property $created_at
 * @property $updated_at
 * @property string $agora_channel_id
 * @property-read \App\Models\Agora\AgoraChannel|null $agoraChannel
 * @property-read string $formatted_stream_duration
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic byUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic inDateRange($startDate, $endDate)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic topEarners()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic topViewers()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic whereAgoraChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic whereAvgWatchTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic whereMaxConcurrentViewers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic whereNewFollowersGained($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic whereTotalCoinsEarned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic whereTotalComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic whereTotalGifts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic whereTotalLikes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic whereTotalStreamDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic whereTotalViewers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic whereUniqueViewers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgoraStreamStatistic whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAgoraStreamStatistic {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $key
 * @property string|null $value
 * @property string|null $type
 * @property-read mixed $get_label
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSetting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSetting whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppSetting whereValue($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAppSetting {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $word
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BannedWord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BannedWord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BannedWord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BannedWord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BannedWord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BannedWord whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BannedWord whereWord($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperBannedWord {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 * @property string $blocker_id
 * @property string $blocked_id
 * @property string|null $reason
 * @property-read \App\Models\User $blocked
 * @property-read \App\Models\User $blocker
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Block newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Block newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Block onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Block query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Block whereBlockedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Block whereBlockerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Block whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Block whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Block whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Block whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Block whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Block withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Block withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperBlock {}
}

namespace App\Models\Challenge{
/**
 * 
 *
 * @property mixed $id 11 occurrences
 * @property string|null $agora_channel_data 11 occurrences
 * @property string|null $agora_channel_id 11 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 11 occurrences
 * @property int|null $current_round 11 occurrences
 * @property $ended_at 1 occurrences
 * @property int|null $max_coins 11 occurrences
 * @property int|null $round_count 11 occurrences
 * @property int|null $round_duration 11 occurrences
 * @property $started_at 11 occurrences
 * @property int|null $status_id 11 occurrences
 * @property int|null $total_coins_earned 11 occurrences
 * @property int|null $total_gifts_count 1 occurrences
 * @property int|null $type_id 11 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 11 occurrences
 * @property-read \App\Models\Agora\AgoraChannel|null $agoraChannel
 * @property-read mixed $deleted_at
 * @property-read mixed $get_ended_at
 * @property-read mixed $get_started_at
 * @property-read mixed $get_status
 * @property-read mixed $get_status_color
 * @property-read mixed $get_type
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Challenge\ChallengeRound> $rounds
 * @property-read int|null $rounds_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Challenge\ChallengeTeam> $teams
 * @property-read int|null $teams_count
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge whereAgoraChannelData($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge whereAgoraChannelId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge whereCurrentRound($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge whereEndedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge whereMaxCoins($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge whereRoundCount($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge whereRoundDuration($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge whereStartedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge whereStatusId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge whereTotalCoinsEarned($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge whereTotalGiftsCount($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge whereTypeId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Challenge whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperChallenge {}
}

namespace App\Models\Challenge{
/**
 * 
 *
 * @property mixed $id 11 occurrences
 * @property string|null $agora_channel_data 11 occurrences
 * @property string|null $agora_channel_id 11 occurrences
 * @property int|null $coin_amount 11 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 11 occurrences
 * @property $expires_at 11 occurrences
 * @property string|null $invited_users_data 11 occurrences
 * @property int|null $round_duration 11 occurrences
 * @property string|null $sender_user_data 11 occurrences
 * @property string|null $sender_user_id 11 occurrences
 * @property int|null $status_id 11 occurrences
 * @property string|null $teammate_user_id 11 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 11 occurrences
 * @property-read mixed $deleted_at
 * @property-read mixed $get_status
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite whereAgoraChannelData($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite whereAgoraChannelId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite whereCoinAmount($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite whereExpiresAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite whereInvitedUsersData($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite whereRoundDuration($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite whereSenderUserData($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite whereSenderUserId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite whereStatusId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite whereTeammateUserId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeInvite whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperChallengeInvite {}
}

namespace App\Models\Challenge{
/**
 * 
 *
 * @property mixed $id 22 occurrences
 * @property string|null $challenge_id 22 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 22 occurrences
 * @property $end_at 22 occurrences
 * @property int|null $round_number 22 occurrences
 * @property $start_at 22 occurrences
 * @property string|null $team_total_coins 12 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 22 occurrences
 * @property string|null $winner_team_id 3 occurrences
 * @property string|null $winner_team_no 8 occurrences
 * @property-read \App\Models\Challenge\Challenge|null $challenge
 * @property-read mixed $deleted_at
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeRound addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeRound aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeRound getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeRound insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeRound insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeRound newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeRound newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeRound query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeRound raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeRound whereChallengeId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeRound whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeRound whereEndAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeRound whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeRound whereRoundNumber($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeRound whereStartAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeRound whereTeamTotalCoins($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeRound whereUpdatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeRound whereWinnerTeamId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeRound whereWinnerTeamNo($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperChallengeRound {}
}

namespace App\Models\Challenge{
/**
 * 
 *
 * @property mixed $id 22 occurrences
 * @property string|null $challenge_id 22 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 22 occurrences
 * @property int|null $team_no 22 occurrences
 * @property int|null $total_coins_earned 22 occurrences
 * @property int|null $total_gifts_count 2 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 22 occurrences
 * @property string|null $user_data 22 occurrences
 * @property string|null $user_id 22 occurrences
 * @property int|null $win_count 22 occurrences
 * @property-read \App\Models\Challenge\Challenge|null $challenge
 * @property-read mixed $deleted_at
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeTeam addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeTeam aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeTeam getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeTeam insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeTeam insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeTeam newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeTeam newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeTeam query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeTeam raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeTeam whereChallengeId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeTeam whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeTeam whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeTeam whereTeamNo($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeTeam whereTotalCoinsEarned($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeTeam whereTotalGiftsCount($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeTeam whereUpdatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeTeam whereUserData($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeTeam whereUserId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|ChallengeTeam whereWinCount($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperChallengeTeam {}
}

namespace App\Models\Chat{
/**
 * Conversation model for storing conversations in MongoDB
 *
 * @property mixed $id 3 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 3 occurrences
 * @property bool|null $is_active 3 occurrences
 * @property string|null $last_message 3 occurrences
 * @property string|null $metadata 3 occurrences
 * @property string|null $participants 3 occurrences
 * @property string|null $unread_count 3 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 3 occurrences
 * @property-read mixed $deleted_at
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Conversation addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Conversation aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Conversation getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Conversation insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Conversation insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Conversation newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Conversation newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Conversation query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Conversation raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Conversation whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Conversation whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Conversation whereIsActive($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Conversation whereLastMessage($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Conversation whereMetadata($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Conversation whereParticipants($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Conversation whereUnreadCount($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Conversation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperConversation {}
}

namespace App\Models\Chat{
/**
 * 
 *
 * @property $created_at
 * @property $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeletedMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeletedMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeletedMessage query()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperDeletedMessage {}
}

namespace App\Models\Chat{
/**
 * Message model for storing messages in MongoDB
 *
 * @property mixed $id 254 occurrences
 * @property string|null $content 254 occurrences
 * @property string|null $conversation_id 254 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 254 occurrences
 * @property string|null $duration 254 occurrences
 * @property bool|null $has_banned_word 9 occurrences
 * @property bool|null $is_read 254 occurrences
 * @property string|null $media_url 254 occurrences
 * @property string|null $original_content 9 occurrences
 * @property $read_at 7 occurrences
 * @property string|null $reply_to 254 occurrences
 * @property string|null $sender_id 254 occurrences
 * @property string|null $thumbnail_url 254 occurrences
 * @property string|null $type 254 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 254 occurrences
 * @property-read mixed $deleted_at
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message whereContent($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message whereConversationId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message whereDuration($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message whereHasBannedWord($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message whereIsRead($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message whereMediaUrl($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message whereOriginalContent($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message whereReadAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message whereReplyTo($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message whereSenderId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message whereThumbnailUrl($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message whereType($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Message whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperMessage {}
}

namespace App\Models\Coin{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 * @property int $coin_amount
 * @property string $price
 * @property bool $is_discount
 * @property string|null $discounted_price
 * @property int $currency_id
 * @property bool $is_active
 * @property int $country_id
 * @property-read \App\Models\Common\Country $country
 * @property-read \App\Models\Common\Currency $currency
 * @property-read mixed $draw_discount_amount
 * @property-read mixed $draw_discounted_price
 * @property-read mixed $draw_final_price
 * @property-read mixed $draw_price
 * @property-read mixed $get_discount_amount
 * @property-read mixed $get_final_price
 * @property-read mixed $get_price
 * @property-read \App\Models\Morph\Payment|null $payment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Morph\Payment> $payments
 * @property-read int|null $payments_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinPackage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinPackage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinPackage onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinPackage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinPackage whereCoinAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinPackage whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinPackage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinPackage whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinPackage whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinPackage whereDiscountedPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinPackage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinPackage whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinPackage whereIsDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinPackage wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinPackage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinPackage withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinPackage withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCoinPackage {}
}

namespace App\Models\Coin{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property int $currency_id
 * @property string $coin_unit_price
 * @property-read \App\Models\Common\Currency $currency
 * @property-read mixed $get_coin_unit_price
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalPrice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalPrice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalPrice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalPrice whereCoinUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalPrice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalPrice whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalPrice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalPrice whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCoinWithdrawalPrice {}
}

namespace App\Models\Coin{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property string|null $user_id
 * @property int $coin_amount
 * @property string $coin_unit_price
 * @property string $coin_total_price
 * @property int|null $currency_id
 * @property int $wallet_type_id
 * @property int $status_id
 * @property string|null $reject_reason
 * @property $approved_at
 * @property $rejected_at
 * @property-read \App\Models\Common\Currency|null $currency
 * @property-read mixed $draw_coin_total_price
 * @property-read mixed $draw_coin_unit_price
 * @property-read mixed $get_approved_at
 * @property-read mixed $get_coin_total_price
 * @property-read mixed $get_coin_unit_price
 * @property-read mixed $get_created_at
 * @property-read mixed $get_rejected_at
 * @property-read mixed $get_status
 * @property-read mixed $get_status_color
 * @property-read mixed $get_wallet_type
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalRequest whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalRequest whereCoinAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalRequest whereCoinTotalPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalRequest whereCoinUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalRequest whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalRequest whereRejectReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalRequest whereRejectedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalRequest whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalRequest whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinWithdrawalRequest whereWalletTypeId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCoinWithdrawalRequest {}
}

namespace App\Models\Common{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property int $country_id
 * @property string|null $code
 * @property string|null $latitude
 * @property string|null $longitude
 * @property-read \App\Models\Common\Country $country
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Common\District> $districts
 * @property-read int|null $districts_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City whereName($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCity {}
}

namespace App\Models\Common{
/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $user_id
 * @property string|null $full_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $message
 * @property bool $is_read
 * @property string|null $read_at
 * @property string|null $editor_id
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactForm newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactForm newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactForm query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactForm whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactForm whereEditorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactForm whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactForm whereFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactForm whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactForm whereIsRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactForm whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactForm wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactForm whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactForm whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactForm whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperContactForm {}
}

namespace App\Models\Common{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string|null $iso2
 * @property string|null $iso3
 * @property string|null $numeric_code
 * @property string|null $phone_code
 * @property string|null $capital
 * @property string|null $currency
 * @property string|null $currency_symbol
 * @property string|null $tld
 * @property string|null $native
 * @property string|null $region
 * @property string|null $latitude
 * @property string|null $longitude
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Common\City> $cities
 * @property-read int|null $cities_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereCapital($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereCurrencySymbol($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereIso2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereIso3($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereNative($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereNumericCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country wherePhoneCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereRegion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Country whereTld($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCountry {}
}

namespace App\Models\Common{
/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $code
 * @property string $symbol
 * @property string $name
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereSymbol($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCurrency {}
}

namespace App\Models\Common{
/**
 * 
 *
 * @property int $id
 * @property int $city_id
 * @property string $name
 * @property string|null $latitude
 * @property string|null $longitude
 * @property-read \App\Models\Common\City $city
 * @method static \Illuminate\Database\Eloquent\Builder<static>|District newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|District newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|District query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|District whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|District whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|District whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|District whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|District whereName($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperDistrict {}
}

namespace App\Models\Common{
/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $name
 * @property string|null $question
 * @property string|null $answer
 * @property bool $is_published
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq findSimilarSlugs(string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq whereAnswer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq whereIsPublished($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq whereQuestion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Faq withUniqueSlugConstraints(\Illuminate\Database\Eloquent\Model $model, string $attribute, array $config, string $slug)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperFaq {}
}

namespace App\Models\Common{
/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $cover_image
 * @property string|null $title
 * @property string|null $slug
 * @property string|null $short_body
 * @property string|null $long_body
 * @property bool $is_published
 * @property bool $is_pinned
 * @property bool $menu_show
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page findSimilarSlugs(string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereCoverImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereIsPinned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereIsPublished($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereLongBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereMenuShow($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereShortBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page withUniqueSlugConstraints(\Illuminate\Database\Eloquent\Model $model, string $attribute, array $config, string $slug)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPage {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property string $code
 * @property string $discount_type
 * @property int|null $currency_id
 * @property int $discount_amount
 * @property $start_date
 * @property $end_date
 * @property bool $is_active
 * @property int|null $max_usage
 * @property int $usage_count
 * @property int $country_id
 * @property-read \App\Models\Common\Country $country
 * @property-read \App\Models\Common\Currency|null $currency
 * @property-read mixed $draw_discount_amount
 * @property-read mixed $get_discount_type
 * @property-read mixed $get_end_date
 * @property-read mixed $get_start_date
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereDiscountAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereDiscountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereMaxUsage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereUsageCount($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCoupon {}
}

namespace App\Models\Demographic{
/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgeRange newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgeRange newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgeRange query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgeRange whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgeRange whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgeRange whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgeRange whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAgeRange {}
}

namespace App\Models\Demographic{
/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gender newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gender newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gender query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gender whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gender whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gender whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gender whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperGender {}
}

namespace App\Models\Demographic{
/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperLanguage {}
}

namespace App\Models\Demographic{
/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Os newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Os newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Os query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Os whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Os whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Os whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Os whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperOs {}
}

namespace App\Models\Demographic{
/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Placement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Placement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Placement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Placement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Placement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Placement whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Placement whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPlacement {}
}

namespace App\Models\Fake{
/**
 * 
 *
 * @property int $id
 * @property string|null $name
 * @property string|null $slug
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Morph\ReportProblem> $reported_problems
 * @property-read int|null $reported_problems_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportProblemCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportProblemCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportProblemCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportProblemCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportProblemCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportProblemCategory whereSlug($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperReportProblemCategory {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 * @property string $follower_id
 * @property string $followed_id
 * @property string $status
 * @property bool $notify_on_accept
 * @property-read \App\Models\User $followed
 * @property-read \App\Models\User $follower
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow whereFollowedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow whereFollowerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow whereNotifyOnAccept($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follow withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperFollow {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneralProblem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneralProblem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneralProblem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneralProblem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneralProblem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneralProblem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperGeneralProblem {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property bool $is_active
 * @property string|null $name
 * @property string|null $slug
 * @property int $price
 * @property bool $is_discount
 * @property int|null $discounted_price
 * @property bool $is_custom_gift
 * @property int $queue
 * @property int $total_usage
 * @property int $total_sales
 * @property bool $has_variants
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GiftAsset> $assets
 * @property-read int|null $assets_count
 * @property-read mixed $get_final_price
 * @property-read mixed $image_url
 * @property-read mixed $video_url
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift findSimilarSlugs(string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereDiscountedPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereHasVariants($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereIsCustomGift($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereIsDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereQueue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereTotalSales($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereTotalUsage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gift withUniqueSlugConstraints(\Illuminate\Database\Eloquent\Model $model, string $attribute, array $config, string $slug)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperGift {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property int $gift_id
 * @property int|null $team_id
 * @property string|null $image_path
 * @property string|null $video_path
 * @property-read \App\Models\Gift $gift
 * @property-read mixed $image_url
 * @property-read mixed $video_url
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftAsset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftAsset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftAsset query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftAsset whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftAsset whereGiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftAsset whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftAsset whereImagePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftAsset whereTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftAsset whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftAsset whereVideoPath($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperGiftAsset {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 * @property string $user_id
 * @property int|null $gift_id
 * @property int|null $custom_unit_price
 * @property int $quantity
 * @property-read \App\Models\Gift|null $gift
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftBasket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftBasket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftBasket onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftBasket query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftBasket whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftBasket whereCustomUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftBasket whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftBasket whereGiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftBasket whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftBasket whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftBasket whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftBasket whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftBasket withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GiftBasket withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperGiftBasket {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 * @property string $name
 * @property string $slug
 * @property string|null $icon
 * @property string|null $description
 * @property int|null $parent_id
 * @property int $display_order
 * @property bool $is_active
 * @property-read int $active_streams_count
 * @property-read string $full_path
 * @property-read int|null $subcategories_count
 * @property-read mixed $icon_url
 * @property-read LiveStreamCategory|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Agora\AgoraChannel> $streams
 * @property-read int|null $streams_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, LiveStreamCategory> $subcategories
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory byParent(int $parentId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory findSimilarSlugs(string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory mainCategories()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory whereDisplayOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory withUniqueSlugConstraints(\Illuminate\Database\Eloquent\Model $model, string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LiveStreamCategory withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperLiveStreamCategory {}
}

namespace App\Models\Morph{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property string $payable_type
 * @property string $payable_id
 * @property string $discount_amount
 * @property string $total_amount
 * @property $paid_at
 * @property int $status_id
 * @property string|null $transaction_id
 * @property string|null $refund_id
 * @property int|null $channel_id
 * @property string|null $failure_reason
 * @property string|null $user_id
 * @property int|null $advertiser_id
 * @property int|null $currency_id
 * @property string|null $iyzico_payment_id
 * @property string|null $conversation_data
 * @property string $sub_total
 * @property array<array-key, mixed>|null $payable_data
 * @property-read \App\Models\Ad\Advertiser|null $advertiser
 * @property-read \App\Models\Common\Currency|null $currency
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PaymentDiscount> $discounts
 * @property-read int|null $discounts_count
 * @property-read mixed $draw_total_amount
 * @property-read mixed $get_channel
 * @property-read mixed $get_created_at
 * @property-read mixed $get_status
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $payable
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereAdvertiserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereConversationData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereDiscountAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereFailureReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereIyzicoPaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePayableData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePayableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePayableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereRefundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereSubTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPayment {}
}

namespace App\Models\Morph{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property string $entity_type
 * @property string $entity_id
 * @property string $user_id
 * @property int $status_id
 * @property int|null $report_problem_category_id
 * @property string|null $message
 * @property string|null $admin_id
 * @property string|null $admin_response
 * @property-read \App\Models\Admin|null $admin
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $entity
 * @property-read mixed $get_created_at
 * @property-read mixed $get_entity_type
 * @property-read mixed $get_entity_url
 * @property-read mixed $get_status
 * @property-read mixed $get_status_color
 * @property-read \App\Models\Fake\ReportProblemCategory|null $report_problem_category
 * @property-read \App\Models\User|null $reporter
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportProblem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportProblem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportProblem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportProblem whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportProblem whereAdminResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportProblem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportProblem whereEntityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportProblem whereEntityType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportProblem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportProblem whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportProblem whereReportProblemCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportProblem whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportProblem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportProblem whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperReportProblem {}
}

namespace App\Models\Music{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property string $name
 * @property string $slug
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Music\Music> $musics
 * @property-read int|null $musics_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist findSimilarSlugs(string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artist withUniqueSlugConstraints(\Illuminate\Database\Eloquent\Model $model, string $attribute, array $config, string $slug)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperArtist {}
}

namespace App\Models\Music{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property string $title
 * @property string $slug
 * @property int|null $artist_id
 * @property int|null $music_category_id
 * @property string|null $music_path
 * @property-read \App\Models\Music\Artist|null $artist
 * @property-read \App\Models\Music\MusicCategory|null $category
 * @property-read mixed $music_url
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Music newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Music newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Music query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Music whereArtistId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Music whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Music whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Music whereMusicCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Music whereMusicPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Music whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Music whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Music whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperMusic {}
}

namespace App\Models\Music{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property string $name
 * @property string $slug
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Music\Music> $musics
 * @property-read int|null $musics_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicCategory findSimilarSlugs(string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicCategory whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicCategory withUniqueSlugConstraints(\Illuminate\Database\Eloquent\Model $model, string $attribute, array $config, string $slug)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperMusicCategory {}
}

namespace App\Models{
/**
 * 
 *
 * @property mixed $id 420 occurrences
 * @property string|null $body 140 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 420 occurrences
 * @property string|null $data 420 occurrences
 * @property string|null $notification_type 140 occurrences
 * @property string|null $title 140 occurrences
 * @property string|null $type 420 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 420 occurrences
 * @property string|null $user_id 420 occurrences
 * @property $read_at
 * @property-read mixed $deleted_at
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Notification addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Notification aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Notification getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Notification insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Notification insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Notification newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Notification newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Notification query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Notification raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Notification whereBody($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Notification whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Notification whereData($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Notification whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Notification whereNotificationType($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Notification whereTitle($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Notification whereType($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Notification whereUpdatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Notification whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperNotification {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $payment_id
 * @property string $source_id
 * @property string|null $coupon_code
 * @property string|null $description
 * @property string $amount
 * @property-read mixed $get_source
 * @property-read \App\Models\Morph\Payment $payment
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentDiscount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentDiscount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentDiscount query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentDiscount whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentDiscount whereCouponCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentDiscount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentDiscount whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentDiscount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentDiscount wherePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentDiscount whereSourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentDiscount whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPaymentDiscount {}
}

namespace App\Models{
/**
 * PerformanceMetric - Uzun vadeli performans metrik takibi için model
 * 
 * Bu model, önemli sistem operasyonlarının (feed üretme, cache işlemleri vb.)
 * performans metriklerini kaydeder ve analiz için uzun vadeli depolama sağlar.
 *
 * @property mixed $id 268 occurrences
 * @property array<array-key, mixed>|null $context 268 occurrences
 * @property float|null $duration 268 occurrences
 * @property string|null $operation_name 268 occurrences
 * @property string|null $operation_type 268 occurrences
 * @property string|null $status 268 occurrences
 * @property $timestamp 268 occurrences
 * @property string|null $trace_id 268 occurrences
 * @property-read mixed $created_at
 * @property-read mixed $deleted_at
 * @property-read mixed $updated_at
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PerformanceMetric addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PerformanceMetric aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PerformanceMetric getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PerformanceMetric insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PerformanceMetric insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PerformanceMetric newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PerformanceMetric newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PerformanceMetric query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PerformanceMetric raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PerformanceMetric whereContext($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PerformanceMetric whereDuration($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PerformanceMetric whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PerformanceMetric whereOperationName($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PerformanceMetric whereOperationType($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PerformanceMetric whereStatus($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PerformanceMetric whereTimestamp($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|PerformanceMetric whereTraceId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPerformanceMetric {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property string|null $title
 * @property string|null $image_path
 * @property bool $is_active
 * @property int|null $queue
 * @property-read mixed $image_url
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PopularSearch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PopularSearch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PopularSearch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PopularSearch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PopularSearch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PopularSearch whereImagePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PopularSearch whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PopularSearch whereQueue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PopularSearch whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PopularSearch whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPopularSearch {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property string|null $description
 * @property int|null $card_type_id
 * @property bool $is_direct_punishment
 * @property int|null $punishment_category_id
 * @property-read \App\Models\PunishmentCategory|null $category
 * @property-read mixed $get_card_type
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Punishment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Punishment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Punishment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Punishment whereCardTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Punishment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Punishment whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Punishment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Punishment whereIsDirectPunishment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Punishment wherePunishmentCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Punishment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPunishment {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @property int|null $parent_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PunishmentCategory> $children
 * @property-read int|null $children_count
 * @property-read PunishmentCategory|null $parent
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PunishmentCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PunishmentCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PunishmentCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PunishmentCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PunishmentCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PunishmentCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PunishmentCategory whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PunishmentCategory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPunishmentCategory {}
}

namespace App\Models\Relations{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property string|null $name
 * @property array<array-key, mixed>|null $colors
 * @property string|null $logo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereColors($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperTeam {}
}

namespace App\Models\Relations{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property string|null $deleted_at
 * @property string $user_id
 * @property int $amount
 * @property int $wallet_type
 * @property int $transaction_type
 * @property int|null $coin_package_id
 * @property int|null $gift_id
 * @property string|null $related_user_id
 * @property int|null $gift_basket_id
 * @property-read \App\Models\Coin\CoinPackage|null $coinPackage
 * @property-read mixed $get_transaction_type
 * @property-read mixed $get_wallet_type
 * @property-read \App\Models\Gift|null $gift
 * @property-read \App\Models\GiftBasket|null $giftBasket
 * @property-read \App\Models\User|null $relatedUser
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoinTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoinTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoinTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoinTransaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoinTransaction whereCoinPackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoinTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoinTransaction whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoinTransaction whereGiftBasketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoinTransaction whereGiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoinTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoinTransaction whereRelatedUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoinTransaction whereTransactionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoinTransaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoinTransaction whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserCoinTransaction whereWalletType($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUserCoinTransaction {}
}

namespace App\Models\Relations{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property string $user_id
 * @property bool $is_banned
 * @property $banned_at
 * @property string|null $device_type
 * @property string|null $device_unique_id
 * @property string|null $device_os
 * @property string|null $device_os_version
 * @property string|null $device_model
 * @property string|null $device_brand
 * @property string|null $device_ip
 * @property string|null $token
 * @property-read mixed $get_banned_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereBannedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereDeviceBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereDeviceIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereDeviceModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereDeviceOs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereDeviceOsVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereDeviceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereDeviceUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereIsBanned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUserDevice {}
}

namespace App\Models\Relations{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property string|null $user_id
 * @property string $action_type
 * @property int $action_id
 * @property string|null $action_taken
 * @property bool $is_read
 * @property $visited_at
 * @property-read \App\Models\User|null $visited_by
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VisitHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VisitHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VisitHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VisitHistory whereActionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VisitHistory whereActionTaken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VisitHistory whereActionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VisitHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VisitHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VisitHistory whereIsRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VisitHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VisitHistory whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperVisitHistory {}
}

namespace App\Models{
/**
 * 
 *
 * @property-read mixed $created_at
 * @property-read mixed $deleted_at
 * @property-read mixed $id
 * @property-read mixed $updated_at
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|SearchCard addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|SearchCard aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|SearchCard getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|SearchCard insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|SearchCard insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|SearchCard newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|SearchCard newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SearchCard onlyTrashed()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|SearchCard query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|SearchCard raw($value = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SearchCard withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SearchCard withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperSearchCard {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $short_code
 * @property string $original_url
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShortUrl newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShortUrl newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShortUrl query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShortUrl whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShortUrl whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShortUrl whereOriginalUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShortUrl whereShortCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShortUrl whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperShortUrl {}
}

namespace App\Models{
/**
 * 
 *
 * @property mixed $id 1 occurrences
 * @property string|null $user_id 1 occurrences
 * @property string|null $views_count 1 occurrences
 * @property $expires_at
 * @property-read mixed $get_created_at
 * @property-read mixed $created_at
 * @property-read mixed $get_deleted_at
 * @property-read mixed $deleted_at
 * @property-read mixed $media_url
 * @property-read int|null $remaining_time
 * @property-read mixed $thumbnail_url
 * @property-read mixed $get_updated_at
 * @property-read mixed $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Morph\ReportProblem> $reported_problems
 * @property-read int|null $reported_problems_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StoryLike> $story_likes
 * @property-read int|null $story_likes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StoryView> $story_views
 * @property-read int|null $story_views_count
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Story active()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Story addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Story aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Story fromFollowing($userId)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Story fromUser($userId)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Story getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Story insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Story insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Story newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Story newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story onlyTrashed()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Story public()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Story query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Story raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Story whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Story whereUserId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Story whereViewsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperStory {}
}

namespace App\Models{
/**
 * StoryLike model for tracking story likes
 *
 * @property-read mixed $get_created_at
 * @property-read mixed $created_at
 * @property-read mixed $deleted_at
 * @property-read mixed $id
 * @property-read mixed $updated_at
 * @property-read mixed $user_data
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|StoryLike addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|StoryLike aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|StoryLike getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|StoryLike insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|StoryLike insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|StoryLike newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|StoryLike newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|StoryLike query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|StoryLike raw($value = null)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperStoryLike {}
}

namespace App\Models{
/**
 * StoryView model for tracking story views
 *
 * @property-read mixed $get_created_at
 * @property-read mixed $created_at
 * @property-read mixed $deleted_at
 * @property-read mixed $id
 * @property-read mixed $updated_at
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|StoryView addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|StoryView aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|StoryView getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|StoryView insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|StoryView insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|StoryView newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|StoryView newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|StoryView query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|StoryView raw($value = null)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperStoryView {}
}

namespace App\Models{
/**
 * 
 *
 * @property $created_at
 * @property $updated_at
 * @property $last_used_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwoFactorAuth newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwoFactorAuth newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwoFactorAuth query()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperTwoFactorAuth {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property $created_at
 * @property $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $collection_uuid
 * @property string|null $avatar
 * @property string|null $name
 * @property string|null $surname
 * @property string $nickname
 * @property string $email
 * @property string|null $phone
 * @property int $coin_balance
 * @property int $earned_coin_balance
 * @property int|null $gender_id
 * @property \Illuminate\Support\Carbon|null $birthday
 * @property string|null $bio
 * @property string|null $slogan
 * @property int|null $preferred_language_id
 * @property int|null $primary_team_id
 * @property int|null $agora_uid
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $fcm_token
 * @property bool $is_frozen
 * @property bool $is_private
 * @property bool $is_banned
 * @property $banned_at
 * @property string|null $ban_reason
 * @property array<array-key, mixed>|null $privacy_settings
 * @property bool $general_email_notification
 * @property bool $general_sms_notification
 * @property bool $general_push_notification
 * @property bool $like_notification
 * @property bool $comment_notification
 * @property bool $follower_notification
 * @property bool $taggable_notification
 * @property int $taggable 0: Everyone, 1: Followings, 2: Mutual Followers, 3: Nobody
 * @property $email_verified_at
 * @property $phone_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property int|null $country_id
 * @property int|null $city_id
 * @property int $age
 * @property string|null $parent_user_id
 * @property string $account_type
 * @property bool $is_approved
 * @property $approved_at
 * @property string|null $approved_by
 * @property $deletion_requested_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountDeletionRequest> $accountDeletionRequests
 * @property-read int|null $account_deletion_requests_count
 * @property-read mixed $active_live_stream
 * @property-read mixed $active_stories_count
 * @property-read \App\Models\Agora\AgoraChannel|null $agora_channel
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserApprovalLog> $approvalLogs
 * @property-read int|null $approval_logs_count
 * @property-read \App\Models\Admin|null $approvedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $approvedFollowers
 * @property-read int|null $approved_followers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $approvedFollowing
 * @property-read int|null $approved_following_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $blocked_users
 * @property-read int|null $blocked_users_count
 * @property-read \App\Models\Common\City|null $city
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Relations\UserCoinTransaction> $coin_transactions
 * @property-read int|null $coin_transactions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Coin\CoinWithdrawalRequest> $coin_withdrawal_requests
 * @property-read int|null $coin_withdrawal_requests_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $commentable_users
 * @property-read int|null $commentable_users_count
 * @property-read \App\Models\Common\Country|null $country
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserDeviceLogin> $deviceLogins
 * @property-read int|null $device_logins_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Relations\UserDevice> $devices
 * @property-read int|null $devices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Follow> $followers
 * @property-read int|null $followers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $following
 * @property-read int|null $following_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $follows
 * @property-read int|null $follows_count
 * @property-read mixed $full_name
 * @property-read \App\Models\Demographic\Gender|null $gender
 * @property-read mixed $get_approved_at
 * @property-read mixed $get_avatar
 * @property-read mixed $get_created_at
 * @property-read mixed $two_factor_enabled
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GiftBasket> $gift_baskets
 * @property-read int|null $gift_baskets_count
 * @property-read mixed $has_active_live_stream
 * @property-read mixed $has_active_punishment
 * @property-read mixed $has_active_stories
 * @property-read mixed $is_online
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read User|null $parentUser
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $pendingFollowRequests
 * @property-read int|null $pending_follow_requests_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $pendingFollowingRequests
 * @property-read int|null $pending_following_requests_count
 * @property-read \App\Models\Relations\Team|null $primary_team
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserPunishment> $punishments
 * @property-read int|null $punishments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Morph\ReportProblem> $reported_problems
 * @property-read int|null $reported_problems_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $secondaryAccounts
 * @property-read int|null $secondary_accounts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $taggable_users
 * @property-read int|null $taggable_users_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read \App\Models\TwoFactorAuth|null $twoFactorAuth
 * @property-read \App\Models\UserStats|null $user_stats
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Relations\Team> $user_teams
 * @property-read int|null $user_teams_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users_i_can_tag
 * @property-read int|null $users_i_can_tag_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Video> $videos
 * @property-read int|null $videos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Relations\VisitHistory> $visit_histories
 * @property-read int|null $visit_histories_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAccountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAgoraUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBanReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBannedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBirthday($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCoinBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCollectionUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCommentNotification($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEarnedCoinBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFcmToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFollowerNotification($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereGenderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereGeneralEmailNotification($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereGeneralPushNotification($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereGeneralSmsNotification($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsApproved($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsBanned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsFrozen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsPrivate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLikeNotification($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereParentUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhoneVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePreferredLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePrimaryTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePrivacySettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSlogan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSurname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTaggable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTaggableNotification($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUser {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property string $user_id
 * @property string|null $admin_id
 * @property bool $approved
 * @property-read \App\Models\Admin|null $admin
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserApprovalLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserApprovalLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserApprovalLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserApprovalLog whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserApprovalLog whereApproved($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserApprovalLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserApprovalLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserApprovalLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserApprovalLog whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUserApprovalLog {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property string $user_id
 * @property string|null $device_type
 * @property string $device_unique_id
 * @property string|null $device_os
 * @property string|null $device_os_version
 * @property string|null $device_model
 * @property string|null $device_brand
 * @property string|null $device_ip
 * @property string|null $access_token
 * @property $last_activity_at
 * @property-read mixed $get_last_activity_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDeviceLogin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDeviceLogin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDeviceLogin query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDeviceLogin whereAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDeviceLogin whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDeviceLogin whereDeviceBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDeviceLogin whereDeviceIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDeviceLogin whereDeviceModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDeviceLogin whereDeviceOs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDeviceLogin whereDeviceOsVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDeviceLogin whereDeviceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDeviceLogin whereDeviceUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDeviceLogin whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDeviceLogin whereLastActivityAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDeviceLogin whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDeviceLogin whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUserDeviceLogin {}
}

namespace App\Models{
/**
 * 
 *
 * @property-read mixed $created_at
 * @property-read mixed $deleted_at
 * @property-read mixed $id
 * @property-read mixed $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserInterest addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserInterest aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserInterest getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserInterest insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserInterest insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserInterest newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserInterest newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserInterest query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserInterest raw($value = null)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUserInterest {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property $created_at
 * @property $updated_at
 * @property string $user_id
 * @property int $punishment_id
 * @property $applied_at
 * @property $expires_at
 * @property-read mixed $get_applied_at
 * @property-read mixed $get_expires_at
 * @property-read \App\Models\Punishment $punishment
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPunishment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPunishment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPunishment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPunishment whereAppliedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPunishment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPunishment whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPunishment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPunishment wherePunishmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPunishment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPunishment whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUserPunishment {}
}

namespace App\Models{
/**
 * 
 *
 * @property mixed $id 1000 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 1000 occurrences
 * @property int|null $duration 1000 occurrences
 * @property $end_at 1000 occurrences
 * @property $start_at 1000 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 1000 occurrences
 * @property string|null $user_id 1000 occurrences
 * @property-read mixed $deleted_at
 * @property-read mixed $get_end_at
 * @property-read mixed $get_start_at
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserSessionLog addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserSessionLog aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserSessionLog getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserSessionLog insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserSessionLog insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserSessionLog newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserSessionLog newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserSessionLog query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserSessionLog raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserSessionLog whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserSessionLog whereDuration($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserSessionLog whereEndAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserSessionLog whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserSessionLog whereStartAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserSessionLog whereUpdatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|UserSessionLog whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUserSessionLog {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property $deleted_at
 * @property $created_at
 * @property $updated_at
 * @property string $user_id
 * @property int $follower_count
 * @property int $following_count
 * @property int $video_count
 * @property int $total_views
 * @property int $total_likes
 * @property int $total_comments
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStats newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStats newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStats onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStats query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStats whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStats whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStats whereFollowerCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStats whereFollowingCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStats whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStats whereTotalComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStats whereTotalLikes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStats whereTotalViews($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStats whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStats whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStats whereVideoCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStats withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStats withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUserStats {}
}

namespace App\Models{
/**
 * 
 *
 * @property mixed $id 244 occurrences
 * @property string|null $collection_uuid 172 occurrences
 * @property int|null $comments_count 42 occurrences
 * @property int|null $completed_count 1 occurrences
 * @property string|null $content_rating 41 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 244 occurrences
 * @property \Illuminate\Support\Carbon|null $deleted_at 5 occurrences
 * @property string|null $description 41 occurrences
 * @property float|null $duration 1 occurrences
 * @property float|null $engagement_score 244 occurrences
 * @property bool|null $is_commentable 41 occurrences
 * @property bool|null $is_featured 41 occurrences
 * @property bool|null $is_private 41 occurrences
 * @property bool|null $is_sport 41 occurrences
 * @property string|null $language 41 occurrences
 * @property int|null $likes_count 43 occurrences
 * @property string|null $location 41 occurrences
 * @property int|null $play_count 3 occurrences
 * @property int|null $report_count 1 occurrences
 * @property string|null $status 41 occurrences
 * @property string|null $tags 41 occurrences
 * @property int|null $temp_thumbnail_duration 75 occurrences
 * @property string|null $temp_thumbnail_image 8 occurrences
 * @property string|null $thumbnail_filename 4 occurrences
 * @property string|null $title 41 occurrences
 * @property float|null $trending_score 239 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 244 occurrences
 * @property string|null $user_id 172 occurrences
 * @property string|null $video_guid 71 occurrences
 * @property int|null $views_count 42 occurrences
 * @property-read mixed $get_created_at
 * @property-read mixed $get_deleted_at
 * @property-read mixed $thumbnail_url
 * @property-read mixed $get_updated_at
 * @property-read mixed $user_data
 * @property-read int|null $video_comments_count
 * @property-read int|null $video_likes_count
 * @property-read mixed $video_url
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Morph\ReportProblem> $reported_problems
 * @property-read int|null $reported_problems_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VideoComment> $video_comments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VideoLike> $video_likes
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video active()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video byEngagement()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video onlyTrashed()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video public()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video trending()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereCollectionUuid($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereCommentsCount($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereCompletedCount($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereContentRating($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereDeletedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereDescription($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereDuration($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereEngagementScore($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereIsCommentable($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereIsFeatured($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereIsPrivate($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereIsSport($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereLanguage($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereLikesCount($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereLocation($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video wherePlayCount($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereReportCount($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereStatus($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereTags($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereTempThumbnailDuration($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereTempThumbnailImage($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereThumbnailFilename($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereTitle($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereTrendingScore($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereUpdatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereUserId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereVideoGuid($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video whereViewsCount($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video withTags(array $tags)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|Video withTeamTags(array $teamTags)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperVideo {}
}

namespace App\Models{
/**
 * 
 *
 * @property mixed $id 25 occurrences
 * @property string|null $comment 25 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 25 occurrences
 * @property int|null $dislikes_count 25 occurrences
 * @property int|null $likes_count 25 occurrences
 * @property string|null $parent_id 2 occurrences
 * @property-read int|null $replies_count 25 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 25 occurrences
 * @property string|null $user_id 25 occurrences
 * @property string|null $video_id 25 occurrences
 * @property mixed $content
 * @property-read mixed $get_created_at
 * @property-read mixed $deleted_at
 * @property-read VideoComment|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VideoCommentReaction> $reactions
 * @property-read int|null $reactions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, VideoComment> $replies
 * @property-read \App\Models\Video|null $video
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoComment addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoComment aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoComment getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoComment insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoComment insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoComment newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoComment newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoComment query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoComment raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoComment whereComment($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoComment whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoComment whereDislikesCount($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoComment whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoComment whereLikesCount($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoComment whereParentId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoComment whereRepliesCount($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoComment whereUpdatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoComment whereUserId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoComment whereVideoId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperVideoComment {}
}

namespace App\Models{
/**
 * 
 *
 * @property mixed $id 1 occurrences
 * @property string|null $comment_id 1 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 1 occurrences
 * @property string|null $reaction_type 1 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 1 occurrences
 * @property string|null $user_id 1 occurrences
 * @property-read \App\Models\VideoComment|null $comment
 * @property-read mixed $deleted_at
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoCommentReaction addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoCommentReaction aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoCommentReaction getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoCommentReaction insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoCommentReaction insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoCommentReaction newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoCommentReaction newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoCommentReaction query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoCommentReaction raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoCommentReaction whereCommentId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoCommentReaction whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoCommentReaction whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoCommentReaction whereReactionType($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoCommentReaction whereUpdatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoCommentReaction whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperVideoCommentReaction {}
}

namespace App\Models{
/**
 * 
 *
 * @property mixed $id 90 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 90 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 90 occurrences
 * @property string|null $user_id 90 occurrences
 * @property string|null $video_id 90 occurrences
 * @property-read mixed $get_created_at
 * @property-read mixed $deleted_at
 * @property-read \App\Models\Video|null $video
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoLike addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoLike aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoLike getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoLike insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoLike insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoLike newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoLike newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoLike query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoLike raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoLike whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoLike whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoLike whereUpdatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoLike whereUserId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoLike whereVideoId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperVideoLike {}
}

namespace App\Models{
/**
 * 
 *
 * @property mixed $id 239 occurrences
 * @property int|null $comments_count 239 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 239 occurrences
 * @property float|null $engagement_score 239 occurrences
 * @property $last_updated_at 239 occurrences
 * @property int|null $likes_count 239 occurrences
 * @property float|null $trending_score 239 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 239 occurrences
 * @property string|null $video_id 239 occurrences
 * @property int|null $views_count 239 occurrences
 * @property-read mixed $deleted_at
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoMetrics addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoMetrics aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoMetrics getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoMetrics insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoMetrics insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoMetrics newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoMetrics newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoMetrics query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoMetrics raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoMetrics whereCommentsCount($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoMetrics whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoMetrics whereEngagementScore($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoMetrics whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoMetrics whereLastUpdatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoMetrics whereLikesCount($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoMetrics whereTrendingScore($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoMetrics whereUpdatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoMetrics whereVideoId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoMetrics whereViewsCount($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperVideoMetrics {}
}

namespace App\Models{
/**
 * 
 *
 * @property mixed $id 13 occurrences
 * @property bool|null $completed 13 occurrences
 * @property \Illuminate\Support\Carbon|null $created_at 13 occurrences
 * @property int|null $duration_watched 13 occurrences
 * @property \Illuminate\Support\Carbon|null $updated_at 13 occurrences
 * @property string|null $user_id 13 occurrences
 * @property string|null $video_id 13 occurrences
 * @property $viewed_at 13 occurrences
 * @property-read mixed $deleted_at
 * @property-read \App\Models\Video|null $video
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoView addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, string $operator = '>=', string $count = 1, string $boolean = 'and', ?\Closure $callback = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoView aggregate($function = null, $columns = [])
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoView getConnection()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoView insert(array $values)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoView insertGetId(array $values, $sequence = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoView newModelQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoView newQuery()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoView query()
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoView raw($value = null)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoView whereCompleted($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoView whereCreatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoView whereDurationWatched($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoView whereId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoView whereUpdatedAt($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoView whereUserId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoView whereVideoId($value)
 * @method static \MongoDB\Laravel\Eloquent\Builder<static>|VideoView whereViewedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperVideoView {}
}

