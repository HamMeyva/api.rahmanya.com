<?php

namespace App\Services;

final class Timezone
{
    private static ?string $tz = 'Europe/Istanbul';

    public static function get(): ?string
    {
        return self::$tz;
    }

    public static function set(?string $tz = null): void
    {
        self::$tz = $tz;
    }
}