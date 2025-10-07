<?php

namespace App\Models\Demographic;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperLanguage
 */
class Language extends Model
{
    public const
        TR = 1,
        EN = 2;

    public static array $languages = [
        self::TR => 'Türkçe',
        self::EN => 'İngilizce'
    ];
    
    protected $connection = 'pgsql';
    protected $table = 'languages';
    protected $fillable = [
        'name',
    ];
}
