<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SiteTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    /** @use HasFactory<\Database\Factories\SiteFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'type',
        'timezone',
        'user_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Observation, $this>
     */
    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class);
    }

    /**
     * @return HasMany<ObservationWindow, $this>
     */
    public function observationWindows(): HasMany
    {
        return $this->hasMany(ObservationWindow::class);
    }

    /**
     * @return HasMany<DailySpeciesSummary, $this>
     */
    public function dailySpeciesSummaries(): HasMany
    {
        return $this->hasMany(DailySpeciesSummary::class);
    }

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'type' => SiteTypeEnum::class,
        ];
    }
}
