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

// Check FCM configuration
echo "Checking FCM Configuration...\n";
echo "FIREBASE_CREDENTIALS: " . (env('FIREBASE_CREDENTIALS') ? "Set" : "Not set") . "\n";
echo "FIREBASE_FCM_KEY: " . (env('FIREBASE_FCM_KEY') ? "Set" : "Not set") . "\n";
echo "FIREBASE_FCM_SENDER_ID: " . (env('FIREBASE_FCM_SENDER_ID') ? "Set" : "Not set") . "\n";
echo "FIREBASE_FCM_TOKEN_SERVER_KEY: " . (env('FIREBASE_FCM_TOKEN_SERVER_KEY') ? "Set" : "Not set") . "\n";

// Check if Firebase credentials file exists
$credentialsPath = base_path(env('FIREBASE_CREDENTIALS'));
echo "Firebase credentials file exists: " . (file_exists($credentialsPath) ? "Yes" : "No") . "\n";
if (file_exists($credentialsPath)) {
    echo "Firebase credentials file size: " . filesize($credentialsPath) . " bytes\n";
}

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

// Display first few users with tokens
echo "\nUsers with FCM tokens:\n";
$count = 0;
foreach ($usersWithTokens as $user) {
    if ($count < 3) {
        echo "User ID: " . $user->id . ", Token (first 10 chars): " . substr($user->fcm_token, 0, 10) . "...\n";
    }
    $count++;
}

// Validate tokens
echo "\nValidating tokens...\n";
$validTokens = [];
$invalidTokens = [];

foreach ($usersWithTokens as $user) {
    $isValid = $firebaseService->validateToken($user->fcm_token);
    if ($isValid) {
        $validTokens[] = $user;
        echo "User ID: " . $user->id . " has a valid token.\n";
    } else {
        $invalidTokens[] = $user;
        echo "User ID: " . $user->id . " has an invalid token.\n";
    }
    
    // Limit to first 5 users to avoid too many API calls
    if (count($validTokens) + count($invalidTokens) >= 5) {
        break;
    }
}

// Send test notification to valid tokens
if (count($validTokens) > 0) {
    echo "\nSending test notification to " . count($validTokens) . " users with valid tokens...\n";
    
    foreach ($validTokens as $user) {
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
            ]
        );
        
        echo "Result: " . ($result['success'] ? "Success" : "Failed") . "\n";
        if (!$result['success']) {
            echo "Error: " . $result['message'] . "\n";
        }
    }
} else {
    echo "\nNo valid tokens found to send notifications.\n";
}

echo "\nFCM Debug completed.\n";
