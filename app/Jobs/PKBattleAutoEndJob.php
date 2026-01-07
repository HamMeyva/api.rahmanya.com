<?php

namespace App\Jobs;

use App\Models\PKBattle;
use App\Services\LiveStream\ComprehensivePKBattleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Auto-end PK Battle when duration expires
 */
class PKBattleAutoEndJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $battleId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param int $battleId
     */
    public function __construct(int $battleId)
    {
        $this->battleId = $battleId;
    }

    /**
     * Execute the job.
     *
     * @param ComprehensivePKBattleService $pkBattleService
     * @return void
     */
    public function handle(ComprehensivePKBattleService $pkBattleService): void
    {
        try {
            $battle = PKBattle::find($this->battleId);

            if (!$battle) {
                Log::warning('PKBattleAutoEndJob: Battle not found', [
                    'battle_id' => $this->battleId,
                ]);
                return;
            }

            // Check if battle is still active
            if ($battle->status !== 'ACTIVE') {
                Log::info('PKBattleAutoEndJob: Battle already ended', [
                    'battle_id' => $this->battleId,
                    'status' => $battle->status,
                ]);
                return;
            }

            // Check if battle duration has expired
            $config = $battle->battle_config ?? [];
            $endTime = isset($config['end_time']) ? new \DateTime($config['end_time']) : null;

            if (!$endTime) {
                // Fallback: calculate end time from started_at and duration_seconds
                $endTime = $battle->started_at?->copy()->addSeconds($battle->duration_seconds ?? 300);
            }

            $now = now();

            if ($now->lt($endTime)) {
                // Battle hasn't expired yet, reschedule
                $delay = $endTime->diffInSeconds($now);

                Log::info('PKBattleAutoEndJob: Battle not yet expired, rescheduling', [
                    'battle_id' => $this->battleId,
                    'delay_seconds' => $delay,
                    'end_time' => $endTime->toISOString(),
                ]);

                self::dispatch($this->battleId)->delay($delay);
                return;
            }

            // End the battle
            Log::info('PKBattleAutoEndJob: Auto-ending battle', [
                'battle_id' => $this->battleId,
                'end_time' => $endTime->toISOString(),
            ]);

            $result = $pkBattleService->endPKBattle($this->battleId, true);

            if ($result['success']) {
                Log::info('PKBattleAutoEndJob: Battle auto-ended successfully', [
                    'battle_id' => $this->battleId,
                    'winner_id' => $result['winner_id'],
                    'final_scores' => [
                        'challenger' => $result['battle']->challenger_score,
                        'opponent' => $result['battle']->opponent_score,
                    ],
                ]);
            } else {
                Log::error('PKBattleAutoEndJob: Failed to auto-end battle', [
                    'battle_id' => $this->battleId,
                    'error' => $result['message'],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('PKBattleAutoEndJob: Exception occurred', [
                'battle_id' => $this->battleId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('PKBattleAutoEndJob: Job failed permanently', [
            'battle_id' => $this->battleId,
            'error' => $exception->getMessage(),
        ]);

        // Attempt to force-end the battle to prevent it from being stuck
        try {
            $battle = PKBattle::find($this->battleId);

            if ($battle && $battle->status === 'ACTIVE') {
                $battle->update([
                    'status' => 'CANCELLED',
                    'battle_phase' => 'ENDED',
                    'ended_at' => now(),
                ]);

                Log::info('PKBattleAutoEndJob: Battle force-cancelled after job failure', [
                    'battle_id' => $this->battleId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('PKBattleAutoEndJob: Failed to force-cancel battle', [
                'battle_id' => $this->battleId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
