<?php

namespace App\Models\Traits;

use App\Models\Morph\Payment;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait PaymentTrait
{
    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
