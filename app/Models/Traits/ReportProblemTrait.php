<?php

namespace App\Models\Traits;

use App\Models\Morph\ReportProblem;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait ReportProblemTrait
{

    public function reported_problems(): MorphMany
    {
        return $this->morphMany(ReportProblem::class, 'entity');
    }
}
