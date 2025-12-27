<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PrintSetting extends Model
{
    /** @use HasFactory<\Database\Factories\PrintSettingFactory> */
    use HasFactory;

    /**
     * Cache key for current settings.
     *
     * @var string
     */
    public const CACHE_KEY = 'settings.current';

    /**
     * Cache TTL in seconds (1 hour).
     *
     * @var int
     */
    public const CACHE_TTL = 3600;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'print_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'electricity_rate_dkk_per_kwh',
        'wage_rate_dkk_per_hour',
        'default_avance_pct',
        'first_time_fee_dkk',
    ];

    /**
     * Get or create the current settings row (id=1).
     * If it doesn't exist, create it with placeholder defaults.
     * Uses cache to improve performance.
     */
    public static function current(): self
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $setting = self::find(1);

            if ($setting === null) {
                $setting = self::create([
                    'id' => 1,
                    'electricity_rate_dkk_per_kwh' => null,
                    'wage_rate_dkk_per_hour' => null,
                    'default_avance_pct' => null,
                    'first_time_fee_dkk' => null,
                ]);
            }

            return $setting;
        });
    }

    /**
     * Clear the settings cache.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Clear cache when settings are saved or updated
        static::saved(function (PrintSetting $setting) {
            if ($setting->id === 1) {
                self::clearCache();
            }
        });

        static::deleted(function (PrintSetting $setting) {
            if ($setting->id === 1) {
                self::clearCache();
            }
        });
    }
}
