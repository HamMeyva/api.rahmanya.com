<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\WebpushConfig;
use Kreait\Laravel\Firebase\Facades\Firebase;

class FirebaseNotificationService
{
    /**
     * @var Messaging
     */
    protected $messaging;

    /**
     * FirebaseNotificationService constructor.
     */
    public function __construct()
    {
        $this->messaging = Firebase::messaging();
    }

    /**
     * Send notification to a specific device
     *
     * @param string $token
     * @param string $title
     * @param string $body
     * @param array $data
     * @param array $options
     * @return array
     */
    public function sendToDevice(string $token, string $title, string $body, array $data = [], array $options = []): array
    {
        // Validate token format first
        if (empty($token) || strlen($token) < 100) {
            Log::warning('Invalid FCM token format: ' . $token);
            return [
                'success' => false,
                'message' => 'Invalid FCM token format',
            ];
        }

        // Skip token validation if requested
        if (!isset($options['skip_validation']) || !$options['skip_validation']) {
            // Only do basic format validation, skip Firebase validation to avoid extra API calls
            if (strlen($token) < 100 || !preg_match('/^[a-zA-Z0-9:_-]+$/', $token)) {
                Log::warning('Invalid FCM token format detected: ' . substr($token, 0, 10) . '...');
                return [
                    'success' => false,
                    'message' => 'Invalid FCM token format',
                    'token_valid' => false
                ];
            }
        }

        try {
            // Log notification attempt
            Log::info('Attempting to send FCM notification', [
                'token' => substr($token, 0, 10) . '...',  // Log only part of the token for security
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);

            $notification = Notification::create($title, $body);

            $message = CloudMessage::fromArray([
                'token' => $token,
                'notification' => $notification,
                'data' => $data, // Pass the array directly, no casting
            ]);

            // Apply platform-specific configurations if provided
            $message = $this->applyPlatformConfigs($message, $options);

            // Determine if this is an iOS token (usually starts with 'd' and is longer)
            $isIosToken = strlen($token) > 160 && strpos($token, 'd') === 0;
            $isAndroidToken = !$isIosToken;
            
            // Add appropriate platform-specific configuration
            if ($isIosToken && !isset($options['apns']) && !isset($options['skip_apns'])) {
                // iOS specific configuration
                $message = $message->withApnsConfig(
                    ApnsConfig::fromArray([
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                        'payload' => [
                            'aps' => [
                                'content-available' => 1,
                                'sound' => 'default',
                                'badge' => 1,
                            ],
                        ],
                    ])
                );
            }

            // Add high priority for Android
            if ($isAndroidToken && !isset($options['android'])) {
                $message = $message->withAndroidConfig(
                    AndroidConfig::fromArray([
                        'priority' => 'high',
                        'notification' => [
                            'sound' => 'default',
                            'default_sound' => true,
                            'default_vibrate_timings' => true,
                            'default_light_settings' => true,
                        ],
                    ])
                );
            }

            $response = $this->messaging->send($message);

            Log::info('FCM notification sent successfully', [
                'token' => substr($token, 0, 10) . '...',
                'response' => $response,
            ]);

            return [
                'success' => true,
                'message' => 'Notification sent successfully',
                'data' => $response,
            ];
        } catch (MessagingException | FirebaseException $e) {
            Log::error('Firebase Notification Error: ' . $e->getMessage(), [
                'token' => substr($token, 0, 10) . '...',
                'exception' => get_class($e),
                'error_code' => method_exists($e, 'getCode') ? $e->getCode() : 'unknown',
            ]);
            
            // Clean invalid tokens
            if (strpos($e->getMessage(), 'The registration token is not a valid FCM registration token') !== false ||
                strpos($e->getMessage(), 'registration-token-not-registered') !== false ||
                strpos($e->getMessage(), 'invalid-argument') !== false) {
                Log::info('Cleaning invalid FCM token', ['token' => substr($token, 0, 10) . '...']);
                $this->cleanInvalidToken($token);
            }

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => method_exists($e, 'getCode') ? $e->getCode() : 'unknown',
            ];
        }
    }

    /**
     * Send notification to multiple devices
     *
     * @param array $tokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @param array $options
     * @return array
     */
    public function sendToMultipleDevices(array $tokens, string $title, string $body, array $data = [], array $options = []): array
    {
        $responses = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($tokens as $token) {
            $result = $this->sendToDevice($token, $title, $body, $data, $options);

            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }

            $responses[] = $result;
        }

        return [
            'success' => $successCount > 0,
            'message' => "Sent {$successCount} notifications successfully, {$failureCount} failed",
            'data' => $responses,
        ];
    }

    /**
     * Send notification to a topic
     *
     * @param string $topic
     * @param string $title
     * @param string $body
     * @param array $data
     * @param array $options
     * @return array
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = [], array $options = []): array
    {
        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::fromArray([
                'topic' => $topic,
                'notification' => $notification,
                'data' => $data, // Pass the array directly
            ]);

            // Apply platform-specific configurations if provided
            $message = $this->applyPlatformConfigs($message, $options);

            $response = $this->messaging->send($message);

            return [
                'success' => true,
                'message' => 'Notification sent to topic successfully',
                'data' => $response,
            ];
        } catch (MessagingException | FirebaseException $e) {
            Log::error('Firebase Topic Notification Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send notification to a condition (multiple topics with logic)
     *
     * @param string $condition
     * @param string $title
     * @param string $body
     * @param array $data
     * @param array $options
     * @return array
     */
    public function sendToCondition(string $condition, string $title, string $body, array $data = [], array $options = []): array
    {
        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::fromArray([
                'condition' => $condition,
                'notification' => $notification,
                'data' => $data, // Pass the array directly
            ]);

            // Apply platform-specific configurations if provided
            $message = $this->applyPlatformConfigs($message, $options);

            $response = $this->messaging->send($message);

            return [
                'success' => true,
                'message' => 'Notification sent to condition successfully',
                'data' => $response,
            ];
        } catch (MessagingException | FirebaseException $e) {
            Log::error('Firebase Condition Notification Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clean invalid FCM token from user records
     *
     * @param string $token
     * @return void
     */
    protected function cleanInvalidToken(string $token): void
    {
        try {
            // Find users with this invalid token and clear it
            $users = \App\Models\User::where('fcm_token', $token)->get();
            
            if ($users->isEmpty()) {
                Log::info('No users found with the invalid FCM token', [
                    'token' => substr($token, 0, 10) . '...'
                ]);
                return;
            }
            
            foreach ($users as $user) {
                Log::info('Clearing invalid FCM token for user', [
                    'user_id' => $user->id,
                    'token' => substr($token, 0, 10) . '...'
                ]);
                
                $user->fcm_token = null;
                $user->save();
            }
        } catch (\Exception $e) {
            Log::error('Error cleaning invalid FCM token: ' . $e->getMessage(), [
                'token' => substr($token, 0, 10) . '...',
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Validate FCM token
     *
     * @param string $token
     * @param bool $useDryRun Whether to use dry run mode (doesn't actually send a message)
     * @return bool
     */
    public function validateToken(string $token, bool $useDryRun = true): bool
    {
        if (empty($token) || strlen($token) < 100) {
            Log::warning('Invalid FCM token format during validation: ' . substr($token, 0, 10) . '...');
            return false;
        }
        
        try {
            Log::info('Validating FCM token', ['token' => substr($token, 0, 10) . '...', 'dry_run' => $useDryRun]);
            
            // Create a message for validation
            $message = CloudMessage::withTarget('token', $token)
                ->withData(['validate' => 'true', 'silent' => 'true']);
                
            // Add high priority and content-available for iOS
            $message = $message->withApnsConfig(
                ApnsConfig::fromArray([
                    'headers' => [
                        'apns-priority' => '5', // Use lower priority for validation
                    ],
                    'payload' => [
                        'aps' => [
                            'content-available' => 1,
                            'sound' => '', // No sound for validation
                        ],
                    ],
                ])
            );
            
            // Add Android config for better compatibility
            $message = $message->withAndroidConfig(
                AndroidConfig::fromArray([
                    'priority' => 'normal',
                    'ttl' => '0s', // Expire immediately if not delivered
                ])
            );

            // Send a validation message with validateOnly parameter
            $this->messaging->send($message, $useDryRun);
            // Note: The second parameter to send() is the validateOnly flag
            
            Log::info('FCM token validation successful', ['token' => substr($token, 0, 10) . '...']);
            return true;
        } catch (MessagingException | FirebaseException $e) {
            $errorMessage = $e->getMessage();
            
            Log::warning('FCM token validation failed: ' . $errorMessage, [
                'token' => substr($token, 0, 10) . '...',
                'exception' => get_class($e)
            ]);
            
            // Clean invalid tokens
            if (strpos($errorMessage, 'The registration token is not a valid FCM registration token') !== false ||
                strpos($errorMessage, 'registration-token-not-registered') !== false ||
                strpos($errorMessage, 'invalid-argument') !== false) {
                $this->cleanInvalidToken($token);
            }

            return false;
        }
    }

    /**
     * Apply platform-specific configurations to the message
     *
     * @param CloudMessage $message
     * @param array $options
     * @return CloudMessage
     */
    protected function applyPlatformConfigs(CloudMessage $message, array $options = []): CloudMessage
    {
        // Apply APNS (iOS) configuration if provided and not explicitly skipped
        if (isset($options['apns']) && !isset($options['skip_apns'])) {
            $message = $message->withApnsConfig(ApnsConfig::fromArray($options['apns']));
        }

        // Apply Android configuration if provided
        if (isset($options['android'])) {
            $message = $message->withAndroidConfig(AndroidConfig::fromArray($options['android']));
        }

        // Apply Webpush configuration if provided
        if (isset($options['webpush'])) {
            $message = $message->withWebPushConfig(WebpushConfig::fromArray($options['webpush']));
        }

        return $message;
    }

    /**
     * Send a notification based on a template
     *
     * @param string|array $target
     * @param string $targetType
     * @param string $templateName
     * @param array $templateData
     * @param array $options
     * @return array
     */
    public function sendTemplateNotification($target, string $targetType, string $templateName, array $templateData = [], array $options = []): array
    {
        // Get template configuration
        $template = $this->getNotificationTemplate($templateName);
        
        if (!$template) {
            return [
                'success' => false,
                'message' => "Template '{$templateName}' not found",
            ];
        }
        
        // Replace placeholders in title and body
        $title = $this->replacePlaceholders($template['title'], $templateData);
        $body = $this->replacePlaceholders($template['body'], $templateData);
        
        // Add template data to notification data
        $data = array_merge($template['data'] ?? [], $templateData, ['template' => $templateName]);
        
        // Merge template options with provided options
        $mergedOptions = array_merge($template['options'] ?? [], $options);
        
        // Send notification based on target type
        switch ($targetType) {
            case 'token':
                return $this->sendToDevice($target, $title, $body, $data, $mergedOptions);
                
            case 'tokens':
                return $this->sendToMultipleDevices($target, $title, $body, $data, $mergedOptions);
                
            case 'topic':
                return $this->sendToTopic($target, $title, $body, $data, $mergedOptions);
                
            case 'condition':
                return $this->sendToCondition($target, $title, $body, $data, $mergedOptions);
                
            default:
                return [
                    'success' => false,
                    'message' => "Invalid target type: {$targetType}",
                ];
        }
    }
    
    /**
     * Get notification template
     *
     * @param string $templateName
     * @return array|null
     */
    protected function getNotificationTemplate(string $templateName): ?array
    {
        // Define templates
        $templates = [
            'welcome' => [
                'title' => 'Hoş Geldiniz!',
                'body' => 'Shoot90\'a hoş geldiniz, {{name}}! Hemen keşfetmeye başlayın.',
                'data' => [
                    'type' => 'system',
                    'action' => 'open_app',
                ],
                'options' => [
                    'android' => [
                        'priority' => 'high',
                    ],
                ],
            ],
            'new_follower' => [
                'title' => 'Yeni Takipçi',
                'body' => '{{follower_name}} sizi takip etmeye başladı.',
                'data' => [
                    'type' => 'social',
                    'action' => 'open_profile',
                ],
            ],
            'new_like' => [
                'title' => 'Yeni Beğeni',
                'body' => '{{user_name}} videonuzu beğendi.',
                'data' => [
                    'type' => 'engagement',
                    'action' => 'open_video',
                ],
            ],
            'new_comment' => [
                'title' => 'Yeni Yorum',
                'body' => '{{user_name}} videonuza yorum yaptı: "{{comment}}"',
                'data' => [
                    'type' => 'engagement',
                    'action' => 'open_video',
                ],
            ],
            'new_message' => [
                'title' => 'Yeni Mesaj',
                'body' => '{{sender_name}}: {{message}}',
                'data' => [
                    'type' => 'chat',
                    'action' => 'open_chat',
                ],
                'options' => [
                    'android' => [
                        'priority' => 'high',
                    ],
                ],
            ],
            'live_started' => [
                'title' => '{{streamer_name}} Yayında!',
                'body' => '{{streamer_name}} canlı yayına başladı. Hemen katılın!',
                'data' => [
                    'type' => 'live',
                    'action' => 'open_live',
                ],
                'options' => [
                    'android' => [
                        'priority' => 'high',
                    ],
                ],
            ],
        ];
        
        return $templates[$templateName] ?? null;
    }
    
    /**
     * Replace placeholders in a string with actual values
     *
     * @param string $text
     * @param array $data
     * @return string
     */
    protected function replacePlaceholders(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $text = str_replace('{{' . $key . '}}', $value, $text);
            }
        }
        
        return $text;
    }
}
