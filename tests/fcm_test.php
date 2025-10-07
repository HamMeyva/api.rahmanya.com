<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\DB;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize Laravel application
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get FCM token for the specific user ID
$userId = '9edb9a24-7ddd-44b9-8bd4-9df43eae3cf1';
echo "Fetching FCM token for user ID: {$userId}\n";

$user = DB::table('users')->where('id', $userId)->first();
$fcmToken = $user->fcm_token ?? null;

if (!$fcmToken) {
    echo "No FCM token found for specified user, trying to find any user with a token...\n";
    // Try to find any user with a non-null FCM token as fallback
    $userWithToken = DB::table('users')->whereNotNull('fcm_token')->first();
    if ($userWithToken) {
        echo "Found another user with FCM token, using that instead.\n";
        $user = $userWithToken;
        $fcmToken = $user->fcm_token;
    }
}

if (!$fcmToken) {
    echo "No FCM token found for user. Please make sure the user has a valid FCM token.\n";
    exit(1);
}

// Initialize Firebase Notification Service
$firebaseService = new FirebaseNotificationService();

// Send test notification
$result = $firebaseService->sendToDevice(
    $fcmToken,
    'Test Notification',
    'This is a test notification from Shoot90 backend.',
    [
        'type' => 'system',
        'action' => 'open_app',
        'id' => '1',
        'timestamp' => time(),
    ]
);

// Print result
echo "Notification sent with result:\n";
print_r($result);

// Validate token
echo "\nValidating token...\n";
$isValid = $firebaseService->validateToken($fcmToken);
echo "Token is " . ($isValid ? "valid" : "invalid") . "\n";

// Print token for debugging
echo "\nToken (first 10 chars): " . substr($fcmToken, 0, 10) . "...\n";
