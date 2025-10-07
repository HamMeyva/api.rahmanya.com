<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateVideosWithEmbeddedUserData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:update-user-data {--chunk=100 : Number of videos to process at once}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all videos with embedded user data from PostgreSQL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $chunkSize = (int) $this->option('chunk');
        
        $this->info('Starting to update videos with embedded user data...');
        $this->info("Processing in chunks of {$chunkSize}");
        
        $totalVideos = Video::count();
        $this->info("Total videos to process: {$totalVideos}");
        
        $progressBar = $this->output->createProgressBar($totalVideos);
        $progressBar->start();
        
        $updated = 0;
        $errors = 0;
        
        // Process videos in chunks to avoid memory issues
        Video::chunkById($chunkSize, function ($videos) use (&$updated, &$errors, $progressBar) {
            foreach ($videos as $video) {
                try {
                    // Get the user from PostgreSQL
                    $user = User::find($video->user_id);
                    
                    if ($user) {
                        // Create user data array
                        $userData = [
                            'id' => $user->id,
                            'name' => $user->name,
                            'surname' => $user->surname,
                            'nickname' => $user->nickname,
                            'avatar' => $user->avatar,
                            'is_private' => $user->is_private,
                            'is_frozen' => $user->is_frozen,
                            'collection_uuid' => $user->collection_uuid,
                            'email' => $user->email,
                            'phone' => $user->phone,
                        ];
                        
                        // Update the video with embedded user data
                        $video->user_data = $userData;
                        $video->save();
                        
                        $updated++;
                    } else {
                        Log::warning("User not found for video ID: {$video->_id}, user_id: {$video->user_id}");
                        $errors++;
                    }
                } catch (\Exception $e) {
                    Log::error("Error updating video ID: {$video->_id}, Error: {$e->getMessage()}");
                    $errors++;
                }
                
                $progressBar->advance();
            }
        }, '_id');
        
        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("Completed updating videos with embedded user data.");
        $this->info("Updated: {$updated}");
        $this->info("Errors: {$errors}");
        
        return Command::SUCCESS;
    }
}
