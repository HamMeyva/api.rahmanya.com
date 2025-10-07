<?php

return [
    // BunnyCDN Storage API Key
    'api_key' => env('BUNNYCDN_STORAGE_API_KEY', ''),

    // BunnyCDN Storage Zone Name
    'zone_name' => env('BUNNYCDN_STORAGE_ZONE_NAME', ''),

    // BunnyCDN Storage CDN URL
    'cdn_url' => env('BUNNYCDN_STORAGE_CDN_URL', ''),
    
    // BunnyCDN Storage Region (Falkenstein, DE uses the base endpoint without region prefix)
    'region' => '',
    
    // BunnyCDN Storage Base Hostname
    'base_hostname' => 'storage.bunnycdn.com',
];
