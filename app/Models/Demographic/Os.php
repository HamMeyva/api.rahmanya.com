<?php

namespace App\Models\Demographic;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperOs
 */
class Os extends Model
{
    public const IOS = 1,
        ANDROID = 2,
        HARMONY_OS = 3;

    public static array $oses = [
        self::IOS => 'iOS',
        self::ANDROID => 'Android',
        self::HARMONY_OS => 'HarmonyOS',
    ];
    protected $connection = 'pgsql';
    protected $table = 'oses';
    protected $fillable = [
        'name',
    ];
}
