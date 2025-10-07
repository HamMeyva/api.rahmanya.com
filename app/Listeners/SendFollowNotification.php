<?php

namespace App\Listeners;

use App\Models\User;
use App\Models\Notification;
use App\Services\FirebaseNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendFollowNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @var FirebaseNotificationService
     */
    protected $firebaseService;

    /**
     * Create the event listener.
     */
    public function __construct(FirebaseNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        try {
            $follow = $event->follow;
            
            // Get the follower
            $follower = User::find($follow->follower_id);
            if (!$follower) {
                Log::error('Follower not found for follow: ' . $follow->id);
                return;
            }
            
            // Get the followed user
            $followed = User::find($follow->followed_id);
            if (!$followed) {
                Log::error('Followed user not found for follow: ' . $follow->id);
                return;
            }
            
            // Check if the recipient has follower notifications enabled
            if (!$followed->follower_notification) {
                return;
            }
            
            // Skip if FCM token is not available
            if (empty($followed->fcm_token)) {
                return;
            }
            
            // Prepare notification content
            $followerName = $follower->nickname ?? ($follower->name . ' ' . $follower->surname);
            $title = 'Yeni Takipçi';
            $body = $followerName . ' seni takip etmeye başladı';
            
            // Prepare data for deep linking
            $data = [
                'type' => 'follow',
                'follower_id' => $follower->id,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'sound' => 'default'
            ];
            
            // Send push notification
            $this->firebaseService->sendToDevice(
                $followed->fcm_token,
                $title,
                $body,
                $data,
                [
                    'android' => [
                        'notification' => [
                            'sound' => 'default',
                            'channel_id' => 'follows',
                            'priority' => 'high',
                        ],
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'badge' => 1,
                                'content-available' => 1,
                            ],
                        ],
                    ],
                ]
            );
            
            // Store notification in database
            Notification::create([
                'user_id' => $followed->id,
                'type' => 'follow',
                'data' => [
                    'title' => $title,
                    'body' => $body,
                    'sender_id' => $follower->id,
                    'sender_name' => $followerName,
                    'sender_avatar' => $follower->avatar,
                    'action' => 'follow',
                ]
            ]);
            
            Log::info('Push notification sent for new follower', [
                'recipient_id' => $followed->id,
                'follower_id' => $follower->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending follow notification: ' . $e->getMessage(), [
                'exception' => $e,
                'follow_id' => $event->follow->id ?? 'unknown'
            ]);
        }
    }
}
