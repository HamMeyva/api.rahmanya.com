<?php

namespace App\Models\Traits;

use Carbon\Carbon;
use MongoDB\BSON\UTCDateTime;

trait MongoTimestamps
{
    public function getCreatedAtAttribute($value)
    {
        return $this->castMongoDate($value);
    }

    public function getUpdatedAtAttribute($value)
    {
        return $this->castMongoDate($value);
    }

    public function getDeletedAtAttribute($value)
    {
        return $this->castMongoDate($value);
    }

    protected function castMongoDate($value)
    {
        if ($value instanceof UTCDateTime) {
            return Carbon::createFromTimestampMs($value->toDateTime()->getTimestamp() * 1000)->setTimezone('Europe/Istanbul');
        }

        if ($value instanceof Carbon) {
            return $value->setTimezone('Europe/Istanbul');
        }
        
        return $value;
    }
}
