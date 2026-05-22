<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'description'];

    protected $casts = [
        'value' => 'json',
    ];

    /**
     * Get setting value by key
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        return match($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($setting->value) ? floatval($setting->value) : $setting->value,
            'json' => is_string($setting->value) ? json_decode($setting->value, true) : $setting->value,
            default => $setting->value,
        };
    }

    /**
     * Set setting value
     */
    public static function set(string $key, $value, string $type = 'string', ?string $description = null): self
    {
        $setting = self::where('key', $key)->first();
        
        if ($setting) {
            $setting->update([
                'value' => is_array($value) ? json_encode($value) : $value,
                'type' => $type,
                'description' => $description ?? $setting->description,
            ]);
        } else {
            $setting = self::create([
                'key' => $key,
                'value' => is_array($value) ? json_encode($value) : $value,
                'type' => $type,
                'description' => $description,
            ]);
        }

        return $setting;
    }
}
