<?php

require __DIR__.'/vendor/autoload.php';

// Load environment variables
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Log;

// Check BunnyCDN Storage configuration
$storageApiKey = env('BUNNYCDN_STORAGE_API_KEY', '');
$storageZoneName = env('BUNNYCDN_STORAGE_ZONE_NAME', '');
$storageCdnUrl = env('BUNNYCDN_STORAGE_CDN_URL', '');

echo "BunnyCDN Storage Configuration Check:\n";
echo "------------------------------------\n";
echo "Storage API Key set: " . (!empty($storageApiKey) ? 'YES' : 'NO') . "\n";
echo "Storage Zone Name set: " . (!empty($storageZoneName) ? 'YES' : 'NO') . "\n";
echo "Storage CDN URL set: " . (!empty($storageCdnUrl) ? 'YES' : 'NO') . "\n";
echo "\n";

// Test connectivity to BunnyCDN Storage
if (!empty($storageApiKey) && !empty($storageZoneName)) {
    echo "Testing BunnyCDN Storage connectivity...\n";
    
    // Create a test file
    $testContent = "Test content " . date('Y-m-d H:i:s');
    $testPath = "test/connectivity-test-" . time() . ".txt";
    
    // Build the storage URL
    $region = config('bunnycdn-storage.region', '');
    $baseHostname = config('bunnycdn-storage.base_hostname', 'storage.bunnycdn.com');
    $hostname = !empty($region) ? "{$region}.{$baseHostname}" : $baseHostname;
    $storageUrl = "https://{$hostname}";
    
    echo "Storage URL: {$storageUrl}\n";
    echo "Storage Zone: {$storageZoneName}\n";
    
    // Set up cURL request
    $ch = curl_init();
    $url = $storageUrl . '/' . $storageZoneName . '/' . $testPath;
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => $testContent,
        CURLOPT_HTTPHEADER => [
            "AccessKey: {$storageApiKey}",
            'Content-Type: application/octet-stream'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_VERBOSE => true
    ]);
    
    // Disable SSL verification in local environment
    if (app()->environment('local')) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    curl_close($ch);
    
    echo "HTTP Code: {$httpCode}\n";
    
    if ($httpCode === 200 || $httpCode === 201) {
        echo "✅ Connection successful! Test file uploaded.\n";
        
        // Try to get the file to verify it was uploaded
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "AccessKey: {$storageApiKey}"
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        if (app()->environment('local')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        
        $getResponse = curl_exec($ch);
        $getHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($getHttpCode === 200 && $getResponse === $testContent) {
            echo "✅ File verification successful!\n";
        } else {
            echo "❌ File verification failed. HTTP Code: {$getHttpCode}\n";
        }
    } else {
        echo "❌ Connection failed!\n";
        echo "Error: {$error}\n";
        echo "Error Code: {$errno}\n";
    }
} else {
    echo "❌ Cannot test connectivity: Missing API key or zone name.\n";
}

// Check BunnyCDN Video API configuration
$videoApiKey = env('BUNNYCDN_API_KEY', '');
$videoLibraryId = env('BUNNYCDN_LIBRARY_ID', '');
$videoCdnUrl = env('BUNNYCDN_CDN_URL', '');

echo "\nBunnyCDN Video API Configuration Check:\n";
echo "--------------------------------------\n";
echo "Video API Key set: " . (!empty($videoApiKey) ? 'YES' : 'NO') . "\n";
echo "Video Library ID set: " . (!empty($videoLibraryId) ? 'YES' : 'NO') . "\n";
echo "Video CDN URL set: " . (!empty($videoCdnUrl) ? 'YES' : 'NO') . "\n";

echo "\nDone.\n";
