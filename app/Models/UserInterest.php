<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use App\Models\Traits\MongoTimestamps;

/**
 * @mixin IdeHelperUserInterest
 */
class UserInterest extends Model
{
    use MongoTimestamps;
    
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'mongodb';

    /**
     * The collection associated with the model.
     *
     * @var string
     */
    protected $collection = 'user_interests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'tag',
        'weight',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'weight' => 'float',
    ];

    /**
     * Get the user that owns the interest.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
