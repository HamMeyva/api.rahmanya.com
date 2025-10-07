<?php

namespace App\Models\Relations;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperVisitHistory
 */
class VisitHistory extends Model
{
    protected $table = 'visit_histories';

    protected $fillable = [
        'user_id',
        'action_id',
        'visited_at',
        'action_id',
        'action_taken',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'visited_at' => DatetimeTz::class,
        ];
    }

    public function visited_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_id');
    }
}
