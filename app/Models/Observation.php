<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Observation extends Model
{
    /** @use HasFactory<\Database\Factories\ObservationFactory> */
    use HasFactory;

    protected $fillable = [
        'species_id',
        'user_id',
        'site_id',
        'observed_at',
        'observed_time',
        'count',
        'location',
        'location_raw',
        'local_date',
        'local_time',
        'minutes_from_sunrise',
        'minutes_from_sunset',
        'day_of_year',
        'state_province',
        'ebird_submission_id',
        'observation_type',
        'duration_min',
        'distance_km',
        'area_ha',
        'observer_count',
        'complete_checklist',
        'source',
    ];

    /**
     * @return BelongsTo<Species, $this>
     */
    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<BirdnetDetection, $this>
     */
    public function birdnetDetections(): HasMany
    {
        return $this->hasMany(BirdnetDetection::class);
    }

    protected function casts(): array
    {
        return [
            'observed_at' => 'date',
            'local_date' => 'date',
            'day_of_year' => 'integer',
            'distance_km' => 'float',
            'area_ha' => 'float',
            'complete_checklist' => 'boolean',
            'source' => \App\Enums\ObservationSourceEnum::class,
        ];
    }
}
