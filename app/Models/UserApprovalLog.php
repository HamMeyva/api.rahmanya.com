<?php

namespace App\Models;

use App\Models\User;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperUserApprovalLog
 */
class UserApprovalLog extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'user_approval_logs';

    protected $fillable = [
        'user_id',
        'admin_id',
        'approved',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'approved' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
