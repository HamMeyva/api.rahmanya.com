<?php

namespace App\Models\Demographic;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperGender
 */
class Gender extends Model
{
    public const
        FEMALE = 1,
        MALE = 2,
        OTHER = 3;

    public static array $genders = [
        self::FEMALE => 'Kadın',
        self::MALE => 'Erkek',
        self::OTHER => 'Diğer'
    ];

    protected $connection = 'pgsql';
    protected $table = 'genders';
    protected $fillable = [
        'name',
    ];
}
