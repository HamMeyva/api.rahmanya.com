<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BunnyCdnService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateAvatarsToBunnyCDN extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avatars:migrate-to-bunnycdn {--dry-run : Run without actually uploading}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing avatar files from local storage to BunnyCDN';

    protected BunnyCdnService $bunnyCdn;

    public function __construct()
    {
        parent::__construct();
        $this->bunnyCdn = new BunnyCdnService();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in DRY-RUN mode. No files will be uploaded.');
        }

        $this->info('Starting avatar migration to BunnyCDN...');

        // Get all users with avatars that are not full URLs
        $users = User::whereNotNull('avatar')
            ->where('avatar', '!=', '')
            ->get()
            ->filter(function ($user) {
                // Filter only users with relative paths (not full URLs)
                return !filter_var($user->getRawOriginal('avatar'), FILTER_VALIDATE_URL);
            });

        $this->info("Found {$users->count()} users with local avatar files.");

        $successCount = 0;
        $failCount = 0;
        $skippedCount = 0;

        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        foreach ($users as $user) {
            $avatarPath = $user->getRawOriginal('avatar');

            // Check if file exists in local storage
            if (!Storage::disk('public')->exists($avatarPath)) {
                $this->newLine();
                $this->warn("File not found: {$avatarPath} for user {$user->id}");
                $skippedCount++;
                $progressBar->advance();
                continue;
            }

            try {
                if (!$dryRun) {
                    // Get file content
                    $fileContent = Storage::disk('public')->get($avatarPath);

                    // Upload to BunnyCDN
                    $uploadSuccess = $this->bunnyCdn->uploadToStorage($avatarPath, $fileContent);

                    if ($uploadSuccess) {
                        // Get the CDN URL
                        $cdnUrl = $this->bunnyCdn->getStorageUrl($avatarPath);

                        // Update user avatar to full CDN URL
                        $user->updateQuietly(['avatar' => $cdnUrl]);

                        $successCount++;
                    } else {
                        $this->newLine();
                        $this->error("Failed to upload: {$avatarPath} for user {$user->id}");
                        $failCount++;
                    }
                } else {
                    $this->newLine();
                    $this->info("Would migrate: {$avatarPath} for user {$user->id}");
                    $successCount++;
                }
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Error migrating {$avatarPath} for user {$user->id}: {$e->getMessage()}");
                $failCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Migration completed!");
        $this->info("Successfully migrated: {$successCount}");
        $this->info("Failed: {$failCount}");
        $this->info("Skipped (file not found): {$skippedCount}");

        return 0;
    }
}
