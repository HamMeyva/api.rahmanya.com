<?php

namespace App\Observers;

use App\Models\Music\Music;
use Illuminate\Support\Str;

class MusicObserver
{
    public function creating(Music $music): void
    {
        if (empty($music->slug)) {
            $music->slug = Str::slug($music->artist?->name . '-' . $music->title);
        }
    }

    public function updating(Music $music): void
    {
        if ($music->isDirty('artist_id') || $music->isDirty('title') || empty($music->slug)) {
            $music->slug = Str::slug($music->artist?->name . '-' . $music->title);
        }
    }
}