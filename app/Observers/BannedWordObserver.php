<?php

namespace App\Observers;

use App\Models\BannedWord;

class BannedWordObserver
{
    public function created(BannedWord $bannedWord): void
    {
        BannedWord::refreshCache();
    }

    public function updated(BannedWord $bannedWord): void
    {
        BannedWord::refreshCache();
    }

    public function deleted(BannedWord $bannedWord): void
    {
        BannedWord::refreshCache();
    }
}
