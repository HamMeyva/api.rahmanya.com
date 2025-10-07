<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateEmbeddedUserData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Kullanıcı ID'si
     *
     * @var string
     */
    protected $userId;

    /**
     * Güncellenecek kullanıcı verileri
     *
     * @var array
     */
    protected $userData;

    /**
     * İşlem başına güncellenecek video sayısı
     *
     * @var int
     */
    protected $chunkSize;

    /**
     * Create a new job instance.
     *
     * @param string $userId
     * @param array $userData
     * @param int $chunkSize
     * @return void
     */
    public function __construct(string $userId, array $userData, int $chunkSize = 100)
    {
        $this->userId = $userId;
        $this->userData = $userData;
        $this->chunkSize = $chunkSize;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Kullanıcının tüm videolarını chunk'lar halinde işle
            Video::where('user_id', $this->userId)
                ->chunkById($this->chunkSize, function ($videos) {
                    foreach ($videos as $video) {
                        // Kullanıcı verilerini kaydet
                        $video->user_data = $this->userData;
                        $video->save();
                    }
                }, '_id');

            Log::info("User {$this->userId} için embedded user verileri güncellendi");
        } catch (\Exception $e) {
            Log::error("UpdateEmbeddedUserData job hatası: " . $e->getMessage(), [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Hatada yeniden dene
            $this->release(30); // 30 saniye sonra yeniden dene
        }
    }
}
