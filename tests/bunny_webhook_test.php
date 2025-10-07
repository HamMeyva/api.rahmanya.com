<?php
/**
 * This is a simple test script to simulate a BunnyCDN webhook call to the application.
 * It sends a POST request to the webhook endpoint with sample data.
 */

// Configuration
$baseUrl = 'http://localhost:9000'; // Change this to your local development URL
$webhookEndpoint = '/webhook/bunny-video-status';
$url = $baseUrl . $webhookEndpoint;

// Sample video ID - replace with a real video ID from your database
$videoGuid = 'YOUR_VIDEO_GUID'; // Replace this with an actual video GUID from your database

// Sample webhook payload for a video that has finished processing
$payload = [
    'VideoGuid' => $videoGuid,
    'Status' => 3, // 3 = Finished processing
    'LibraryId' => '12345',
    'DateCreated' => date('c'),
    'AvailableResolutions' => '360p,480p,720p',
    'Width' => 1280,
    'Height' => 720,
    'Duration' => 120.5,
    'ThumbnailCount' => 5,
    'HasMP4Fallback' => true,
    'Framerate' => 30,
    'EncodingTime' => 60,
    'StorageSize' => 1024000,
    'TranscodedStorageSize' => 2048000,
    'CaptionsEnabled' => false,
    'MimeType' => 'video/mp4',
    'OriginalFilename' => 'sample_video.mp4',
    'Title' => 'Sample Video',
    'MetaData' => [
        'Description' => 'This is a sample video for testing',
        'Tags' => ['test', 'sample', 'video']
    ]
];

// Initialize cURL session
$ch = curl_init($url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'User-Agent: BunnyCDN-Webhook-Simulator/1.0'
]);

// Execute cURL session
echo "Sending webhook request to: $url\n";
echo "Payload: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for errors
if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch) . "\n";
} else {
    echo "HTTP Status Code: $httpCode\n";
    echo "Response: $response\n";
}

// Close cURL session
curl_close($ch);

// Instructions for use
echo "\n\nTo use this script:\n";
echo "1. Replace 'YOUR_VIDEO_GUID' with an actual video GUID from your database\n";
echo "2. Make sure your Laravel server is running\n";
echo "3. Run this script with: php tests/bunny_webhook_test.php\n";
echo "4. Check your application logs for webhook processing details\n";
