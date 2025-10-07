<?php

namespace App\Models;

use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Casts\DatetimeTz;
use App\Helpers\CommonHelper;

/**
 * @mixin IdeHelperAdmin
 */
class Admin extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable, HasRoles;

    protected $connection = 'pgsql';

    public $incrementing = false;

    protected $keyType = 'string';


    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => DatetimeTz::class,
            'updated_at' => DatetimeTz::class,
            'password' => 'hashed',
        ];
    }

    protected $appends = [
        'timezone',
    ];

    public function fullName(): Attribute
    {
        return Attribute::get(
            fn() => $this->first_name . ' ' . $this->last_name
        );
    }

    public function routeNotificationForDatabase(): string
    {
        return 'pgsql';
    }

    public function timezone(): Attribute
    {
        return Attribute::get(
            fn() => 'Europe/Istanbul'
        );
    }

    public function getCreatedAt(): Attribute
    {
        return Attribute::get(
            fn() => $this->created_at?->translatedFormat((new CommonHelper)->defaultDateTimeFormat(true))
        );
    }
}
