<?php

namespace App\Services\LiveStream;

use App\Models\LiveStream;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class StreamRecoveryService
{
    /**
     * Ensure co-host stream stays active
     */
    public function keepCohostStreamAlive($cohostStreamId, $hostStreamId)
    {
        try {
            // First, ensure the related_streams table exists
            $this->ensureTablesExist();

            // Register the relationship if not exists
            $exists = DB::table('related_streams')
                ->where('cohost_stream_id', $cohostStreamId)
                ->exists();

            if (!$exists) {
                DB::table('related_streams')->insert([
                    'host_stream_id' => $hostStreamId,
                    'cohost_stream_id' => $cohostStreamId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info('Co-host stream registered successfully', [
                    'host_stream_id' => $hostStreamId,
                    'cohost_stream_id' => $cohostStreamId
                ]);
            }

            // Update stream status to keep it active
            LiveStream::where('stream_id', $cohostStreamId)
                ->update([
                    'is_active' => true,
                    'status' => 'active',
                    'updated_at' => now()
                ]);

            // Also update in multi_streams table if exists
            DB::table('multi_streams')
                ->where('stream_id', $cohostStreamId)
                ->update([
                    'is_active' => true,
                    'updated_at' => now()
                ]);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to keep co-host stream alive', [
                'error' => $e->getMessage(),
                'cohost_stream_id' => $cohostStreamId,
                'host_stream_id' => $hostStreamId
            ]);

            // Attempt to create tables if missing
            if (str_contains($e->getMessage(), 'relation') && str_contains($e->getMessage(), 'does not exist')) {
                $this->createMissingTables();
                // Retry the operation
                return $this->keepCohostStreamAlive($cohostStreamId, $hostStreamId);
            }

            return false;
        }
    }

    /**
     * Ensure required tables exist
     */
    private function ensureTablesExist()
    {
        try {
            // Check if related_streams table exists
            $relatedStreamsExists = DB::select("SELECT to_regclass('public.related_streams') as exists");

            if (!$relatedStreamsExists[0]->exists) {
                $this->createMissingTables();
            }
        } catch (Exception $e) {
            Log::error('Error checking tables existence', ['error' => $e->getMessage()]);
            $this->createMissingTables();
        }
    }

    /**
     * Create missing tables immediately
     */
    private function createMissingTables()
    {
        try {
            // Create related_streams table
            DB::statement('
                CREATE TABLE IF NOT EXISTS related_streams (
                    id SERIAL PRIMARY KEY,
                    host_stream_id VARCHAR(255) NOT NULL,
                    cohost_stream_id VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
                )
            ');

            DB::statement('CREATE INDEX IF NOT EXISTS idx_related_streams_host_id ON related_streams(host_stream_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_related_streams_cohost_id ON related_streams(cohost_stream_id)');

            // Create multi_streams table
            DB::statement('
                CREATE TABLE IF NOT EXISTS multi_streams (
                    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    room_id VARCHAR(255) NOT NULL,
                    stream_id VARCHAR(255) UNIQUE NOT NULL,
                    user_id UUID NOT NULL,
                    user_name VARCHAR(255) NOT NULL,
                    stream_type VARCHAR(255) DEFAULT \'HOST\' CHECK (stream_type IN (\'HOST\', \'COHOST\', \'GUEST\')),
                    is_active BOOLEAN DEFAULT true,
                    viewer_count INTEGER DEFAULT 0,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
                )
            ');

            DB::statement('CREATE INDEX IF NOT EXISTS idx_multi_streams_room_id ON multi_streams(room_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_multi_streams_stream_id ON multi_streams(stream_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_multi_streams_user_id ON multi_streams(user_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_multi_streams_room_active ON multi_streams(room_id, is_active)');

            Log::info('Missing tables created successfully');

        } catch (Exception $e) {
            Log::error('Failed to create missing tables', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Recover offline co-host stream
     */
    public function recoverOfflineStream($streamId)
    {
        try {
            // Mark stream as active again
            LiveStream::where('stream_id', $streamId)
                ->update([
                    'is_active' => true,
                    'status' => 'active',
                    'updated_at' => now()
                ]);

            // Update multi_streams table
            DB::table('multi_streams')
                ->where('stream_id', $streamId)
                ->update([
                    'is_active' => true,
                    'updated_at' => now()
                ]);

            Log::info('Stream recovered successfully', ['stream_id' => $streamId]);
            return true;

        } catch (Exception $e) {
            Log::error('Failed to recover stream', [
                'error' => $e->getMessage(),
                'stream_id' => $streamId
            ]);
            return false;
        }
    }

    /**
     * Monitor and auto-recover streams
     */
    public function monitorAndRecover($roomId)
    {
        try {
            // Find all streams in the room that went offline unexpectedly
            $offlineStreams = DB::table('multi_streams')
                ->where('room_id', $roomId)
                ->where('is_active', false)
                ->where('updated_at', '>', now()->subMinutes(5)) // Recently went offline
                ->get();

            foreach ($offlineStreams as $stream) {
                if ($stream->stream_type === 'COHOST') {
                    $this->recoverOfflineStream($stream->stream_id);
                }
            }

            return true;

        } catch (Exception $e) {
            Log::error('Failed to monitor and recover streams', [
                'error' => $e->getMessage(),
                'room_id' => $roomId
            ]);
            return false;
        }
    }
}