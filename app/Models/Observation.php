<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Observation extends Model
{
    /** @use HasFactory<\Database\Factories\ObservationFactory> */
    use HasFactory;

    protected $fillable = [
        'species_id',
        'user_id',
        'observed_at',
        'observed_time',
        'count',
        'location',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'observed_at' => 'date',
            'distance_km' => 'float',
            'area_ha' => 'float',
            'complete_checklist' => 'boolean',
        ];
    }
}
