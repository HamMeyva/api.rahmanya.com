<?php

namespace App\Observers;

use App\Models\Relations\Team;
use App\Services\Team\TeamService;
use Illuminate\Support\Facades\Storage;

class TeamObserver
{
    /**
     * @var TeamService
     */
    protected $teamService;
    
    /**
     * TeamObserver constructor.
     * 
     * @param TeamService $teamService
     */
    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }
    
    /**
     * Handle the Team "created" event.
     */
    public function created(Team $team): void
    {
        // Update team cache when a new team is created
        $this->teamService->updateTeamCache($team);
    }
    
    /**
     * Handle the Team "updated" event.
     */
    public function updated(Team $team): void
    {
        $this->handleLogoUpdate($team);
        
        // Update team cache when a team is updated
        $this->teamService->updateTeamCache($team);
    }

    /**
     * Handle the Team "deleted" event.
     */
    public function deleted(Team $team): void
    {
        $this->handleLogoDeletion($team);
        
        // Clear team cache when a team is deleted
        $this->teamService->clearTeamCache($team->id);
    }



    private function handleLogoUpdate(Team $team): void
    {
        $originalLogo = $team->getOriginal('logo');

        if ($originalLogo && $originalLogo !== $team->logo) {
            $baseUrl = config('app.url') . '/storage/';
            $originalLogoPath = str_replace($baseUrl, '', $originalLogo);
            if (Storage::disk('public')->exists($originalLogoPath)) {
                Storage::disk('public')->delete($originalLogoPath);
            }
        }
    }

    private function handleLogoDeletion(Team $team): void
    {
        $baseUrl = config('app.url') . '/storage/';
        if ($team->logo && Storage::disk('public')->exists(str_replace($baseUrl, '', $team->logo))) {
            Storage::disk('public')->delete($team->logo);
        }
    }
}
