<?php

namespace App\Models\Traits;

use App\Models\Chat\Conversation;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait ChatTrait
{

    public function sentConversations(): MorphMany
    {
        return $this->morphMany(Conversation::class, 'sender');
    }

    public function receivedConversations(): MorphMany
    {
        return $this->morphMany(Conversation::class, 'receiver');
    }

}
