<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintSetting extends Model
{
    /** @use HasFactory<\Database\Factories\PrintSettingFactory> */
    use HasFactory;

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
     *
     * @return PrintSetting
     */
    public static function current(): self
    {
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
    }
}

