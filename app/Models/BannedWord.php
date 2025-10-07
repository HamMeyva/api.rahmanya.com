<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @mixin IdeHelperBannedWord
 */
class BannedWord extends Model
{
    public const CACHE_KEY = 'banned_words';

    protected $connection = 'pgsql';

    protected $fillable = [
        'word',
    ];

    public static function getCachedWords()
    {
        return Cache::rememberForever(self::CACHE_KEY, fn() => self::pluck('word'));
    }

    public static function censor(string $text): string
    {
        $bannedWords = self::getCachedWords();

        foreach ($bannedWords as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
            $replacement = str_repeat('*', mb_strlen($word));
            $text = preg_replace($pattern, $replacement, $text);
        }

        return $text;
    }

    public static function hasBannedWord(string $text): bool
    {
        $bannedWords = self::getCachedWords();

        foreach ($bannedWords as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    public static function refreshCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        self::getCachedWords();
    }
}
