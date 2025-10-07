<?php

namespace App\Models\Demographic;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperAgeRange
 */
class AgeRange extends Model
{
    public const
        AGE_1_10 = 1,
        AGE_11_20 = 2,
        AGE_21_30 = 3,
        AGE_31_40 = 4,
        AGE_41_50 = 5,
        AGE_51_60 = 6,
        AGE_61_PLUS = 7;

    public static array $ageRanges = [
        self::AGE_1_10 => '1-10',
        self::AGE_11_20 => '11-20',
        self::AGE_21_30 => '21-30',
        self::AGE_31_40 => '31-40',
        self::AGE_41_50 => '41-50',
        self::AGE_51_60 => '51-60',
        self::AGE_61_PLUS => '61+',
    ];

    protected $connection = 'pgsql';
    protected $table = 'age_ranges';
    protected $fillable = [
        'name',
    ];

    // Kullanıcının yaşını veriyoruz hangi aralıkta oldğunu döndürüyor.
    public static function getAgeRangeByAge(int $age): int|null
    {
        if ($age >= 1 && $age <= 10) {
            return self::AGE_1_10;
        } elseif ($age >= 11 && $age <= 20) {
            return self::AGE_11_20;
        } elseif ($age >= 21 && $age <= 30) {
            return self::AGE_21_30;
        } elseif ($age >= 31 && $age <= 40) {
            return self::AGE_31_40;
        } elseif ($age >= 41 && $age <= 50) {
            return self::AGE_41_50;
        } elseif ($age >= 51 && $age <= 60) {
            return self::AGE_51_60;
        } elseif ($age >= 61) {
            return self::AGE_61_PLUS;
        }

        return null;
    }
}
