<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Casts\DatetimeTz;

/**
 * @mixin IdeHelperTwoFactorAuth
 */
class TwoFactorAuth extends Model
{
    use HasFactory;

    /**
     * Tablo adı
     *
     * @var string
     */
    protected $table = 'two_factor_auth';

    /**
     * Toplu atama yapılabilecek özellikler
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'verified',
        'secret',
        'backup_codes',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'verified' => 'boolean',
            'backup_codes' => 'array',
            'last_used_at' => DatetimeTz::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
