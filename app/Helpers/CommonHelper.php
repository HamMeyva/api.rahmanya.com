<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Str;

class CommonHelper
{
    public function limitText(?string $text = null, int $length = 30, $ellipsis = '...'): ?string
    {
        return $text ? Str::limit($text, $length, $ellipsis) : null;
    }

    public function getFirstCharacter(?string $string = null, bool $uppercase = true): ?string
    {
        if (!$string) return '';

        $firstCharacter = $string[0];
        return $uppercase ? strtoupper($firstCharacter) : $firstCharacter;
    }

    public function defaultDateFormat(): string
    {
        return "d-m-Y";
    }

    public function defaultDateTimeFormat($seconds = true): string
    {
        $format = "d-m-Y H:i"; //d F Y H:i

        if ($seconds) {
            $format .= ':s';
        }
        return $format;
    }

    public function formatDecimalInput($value): ?float
    {
        if ($value === null) {
            return null;
        }

        return (float) str_replace(['.', ','], ['', '.'], $value);
    }

    public function getIyzicoCallbackUrl(): string
    {
        return route('payments.iyzico.threed-callback');
    }

    public function generateTransactionId(): string
    {
        return random_int(10, 99) . $this->generateRandomChars(2) . time() . $this->generateRandomChars(2) . random_int(10, 99);
    }

    public function generateRandomChars($length = 1): string
    {
        $chars = '';
        for ($i = 0; $i < $length; $i++) {
            $chars .= chr(random_int(65, 90));
        }
        return $chars;
    }

    public function formatDuration(int $seconds, ?int $limit = null): string
    {
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        $days = floor($hours / 24);
        $months = floor($days / 30);
        $years = floor($months / 12);

        $months = $months % 12;
        $days = $days % 30;
        $hours = $hours % 24;
        $minutes = $minutes % 60;

        $parts = [];

        if ($years > 0)     $parts[] = "$years yıl";
        if ($months > 0)    $parts[] = "$months ay";
        if ($days > 0)      $parts[] = "$days gün";
        if ($hours > 0)     $parts[] = "$hours saat";
        if ($minutes > 0)   $parts[] = "$minutes dakika";

        // Sınırlama varsa parçaları kısalt
        if ($limit !== null && $limit > 0) {
            $parts = array_slice($parts, 0, $limit);
        }

        return count($parts) ? implode(' ', $parts) : '0 dakika';
    }


    public function formatToHourMinute(?int $seconds = null, ?int $limit = null): string
    {
        if ($seconds === null || $seconds <= 0) {
            return '0 saniye';
        }

        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        $minutes = $minutes % 60;
        $remainingSeconds = $seconds % 60;

        $parts = [];

        if ($hours > 0) {
            $parts[] = "{$hours} saat";
        }

        if ($minutes > 0) {
            $parts[] = "{$minutes} dakika";
        }

        if ($remainingSeconds > 0 || empty($parts)) {
            $parts[] = "{$remainingSeconds} saniye";
        }

        if ($limit !== null) {
            $parts = array_slice($parts, 0, $limit);
        }

        return implode(' ', $parts);
    }


    public function formatNumber($number, string $format = 'short'): string
    {
        if ($format === 'short') {
            if ($number >= 1000000000) {
                return round($number / 1000000000, 1) . 'B';
            }

            if ($number >= 1000000) {
                return round($number / 1000000, 1) . 'M';
            }

            if ($number >= 1000) {
                return round($number / 1000, 1) . 'K';
            }

            return (string) $number;
        }

        if ($format === 'dot') {
            return number_format($number, 0, '', '.');
        }

        return (string) $number;
    }

    public function parseMentions(?string $text = null): ?array
    {
        if (!$text) {
            return null;
        }

        preg_match_all('/@([a-zA-Z0-9_]+)/', $text, $matches, PREG_OFFSET_CAPTURE);

        $usernamesRaw = $matches[1] ?? [];
        $uniqueUsernames = array_unique(array_column($usernamesRaw, 0));

        $mentionedUsers = User::whereIn('nickname', $uniqueUsernames)->get()->keyBy('nickname');

        $mentions = [];

        foreach ($usernamesRaw as [$nickname, $start]) {
            if ($mentionedUsers->has($nickname)) {
                $mentions[] = [
                    'user_id' => $mentionedUsers[$nickname]->id,
                    'nickname' => $nickname,
                    'start' => $start,
                    'length' => strlen("@{$nickname}"),
                ];
            }
        }

        return $mentions;
    }

    public function parseTags(?string $text = null): ?array
    {
        if (!$text) {
            return null;
        }

      //  preg_match_all('/#([a-zA-Z0-9_]+)/', $text, $matches);
        preg_match_all('/#([\p{L}\p{N}_]+)/u', $text, $matches);

        $tags = $matches[1] ?? [];

        return array_unique($tags);
    }
}
