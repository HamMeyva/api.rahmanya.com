<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize Laravel application
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Initialize Firebase Notification Service
try {
    echo "Initializing Firebase Notification Service...\n";
    $firebaseService = new FirebaseNotificationService();
    echo "Firebase Notification Service initialized successfully.\n";
} catch (\Exception $e) {
    echo "Error initializing Firebase Notification Service: " . $e->getMessage() . "\n";
    exit(1);
}

// Get users with FCM tokens
echo "\nFetching users with FCM tokens...\n";
$usersWithTokens = DB::table('users')->whereNotNull('fcm_token')->get();
echo "Found " . count($usersWithTokens) . " users with FCM tokens.\n";

if (count($usersWithTokens) === 0) {
    echo "No users with FCM tokens found. Please make sure at least one user has a valid FCM token.\n";
    exit(1);
}

// Send test notification to all tokens with Android-only configuration
echo "\nSending Android-only test notification to all users...\n";

foreach ($usersWithTokens as $user) {
    echo "Sending to User ID: " . $user->id . "...\n";
    
    $result = $firebaseService->sendToDevice(
        $user->fcm_token,
        'Test Notification',
        'This is a test notification from Shoot90 backend.',
        [
            'type' => 'system',
            'action' => 'open_app',
            'id' => '1',
            'timestamp' => time(),
        ],
        [
            'skip_validation' => true, // Skip token validation
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'default_sound' => true,
                ],
            ],
            // Explicitly skip APNS configuration
            'skip_apns' => true,
        ]
    );
    
    echo "Result: " . ($result['success'] ? "Success" : "Failed") . "\n";
    if (!$result['success']) {
        echo "Error: " . $result['message'] . "\n";
    }
}

echo "\nFCM Android Test completed.\n";
