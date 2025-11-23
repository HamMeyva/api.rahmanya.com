<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BroadcastTestController extends Controller
{
    /**
     * Test if broadcasting is working
     */
    public function testBroadcast(Request $request)
    {
        try {
            Log::info('🔧 BROADCAST TEST: Starting broadcast test', [
                'driver' => config('broadcasting.default'),
                'reverb_host' => config('reverb.servers.reverb.host'),
                'reverb_port' => config('reverb.servers.reverb.port'),
            ]);

            // Try to broadcast a test message
            $testData = [
                'type' => 'test',
                'message' => 'Broadcast test',
                'timestamp' => now()->timestamp
            ];

            // Try different methods
            $results = [];

            // Method 1: Direct broadcast helper
            try {
                broadcast(new \App\Events\TestBroadcastEvent($testData));
                $results['broadcast_helper'] = 'success';
            } catch (\Exception $e) {
                $results['broadcast_helper'] = 'failed: ' . $e->getMessage();
                Log::error('Broadcast helper failed', ['error' => $e->getMessage()]);
            }

            // Method 2: Using event helper
            try {
                event(new \App\Events\TestBroadcastEvent($testData));
                $results['event_helper'] = 'success';
            } catch (\Exception $e) {
                $results['event_helper'] = 'failed: ' . $e->getMessage();
                Log::error('Event helper failed', ['error' => $e->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'config' => [
                    'driver' => config('broadcasting.default'),
                    'reverb_configured' => config('reverb.servers.reverb') !== null,
                    'pusher_configured' => config('broadcasting.connections.pusher') !== null,
                ],
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('🔧 BROADCAST TEST: Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fix broadcasting configuration
     */
    public function fixBroadcast(Request $request)
    {
        try {
            // Update .env file to use log driver temporarily
            $envPath = base_path('.env');
            $envContent = file_get_contents($envPath);

            // Backup current .env
            file_put_contents($envPath . '.backup_' . time(), $envContent);

            // Update BROADCAST_CONNECTION to log
            $envContent = preg_replace('/BROADCAST_CONNECTION=.*/', 'BROADCAST_CONNECTION=log', $envContent);

            file_put_contents($envPath, $envContent);

            // Clear config cache
            \Artisan::call('config:clear');
            \Artisan::call('config:cache');

            Log::info('🔧 BROADCAST FIX: Changed to log driver');

            return response()->json([
                'success' => true,
                'message' => 'Broadcasting driver changed to log',
                'new_driver' => 'log'
            ]);

        } catch (\Exception $e) {
            Log::error('🔧 BROADCAST FIX: Failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}