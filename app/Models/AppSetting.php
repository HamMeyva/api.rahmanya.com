<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @mixin IdeHelperAppSetting
 */
class AppSetting extends Model
{
    public const
        TYPE_INTEGER = 'integer',
        TYPE_STRING = 'string',
        TYPE_TEXTAREA = 'textarea',
        TYPE_TEXT_EDITOR = 'text-editor',
        TYPE_ARRAY = 'array',
        TYPE_BOOLEAN = 'boolean';

    protected $fillable = [
        'key',
        'value',
        'type'
    ];
    protected $appends = ['get_label'];

    protected function casts(): array
    {
        return [
            'value' => 'string',
        ];
    }

    public static function getSetting($key, $getValue = true)
    {
        $data = Cache::remember("app_setting_{$key}", 86400, fn() => AppSetting::query()->where('key', $key)->first());
        if ($getValue) {
            $value = $data->value;

            return match ($data->type) {
                self::TYPE_INTEGER => (int) $value,
                self::TYPE_ARRAY => json_decode($value, true),
                self::TYPE_TEXT_EDITOR, self::TYPE_TEXTAREA, self::TYPE_STRING => $value,
                self::TYPE_BOOLEAN => $value === '1',
                default => $value
            };
        }
        return $data;
    }


    public static function set($key, $value): bool|Model
    {
        $model = self::query()->where('key', $key)->first();
        if (!$model) return false;
        $model->update([
            'key' => $key,
            'value' => $value
        ]);
        Cache::forget("app_setting_{$key}");
        return $model;
    }

    public function getLabel(): Attribute
    {
        return Attribute::get(
            fn() => __("app-settings.{$this->key}")
        );
    }
}
