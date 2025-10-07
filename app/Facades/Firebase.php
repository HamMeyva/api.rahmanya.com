<?php

namespace App\Facades;

use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array sendToDevice(string $token, string $title, string $body, array $data = [], array $options = [])
 * @method static array sendToMultipleDevices(array $tokens, string $title, string $body, array $data = [], array $options = [])
 * @method static array sendToTopic(string $topic, string $title, string $body, array $data = [], array $options = [])
 * @method static array sendToCondition(string $condition, string $title, string $body, array $data = [], array $options = [])
 * @method static array subscribeToTopic(array $tokens, string $topic)
 * @method static array unsubscribeFromTopic(array $tokens, string $topic)
 * @method static bool validateToken(string $token)
 * @method static array sendTemplateNotification($target, string $targetType, string $templateName, array $templateData = [], array $options = [])
 * 
 * @see \App\Services\FirebaseNotificationService
 */
class Firebase extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'firebase.notification';
    }
}
