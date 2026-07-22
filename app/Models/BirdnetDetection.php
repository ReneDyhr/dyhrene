<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BirdnetDetection extends Model
{
    /** @use HasFactory<\Database\Factories\BirdnetDetectionFactory> */
    use HasFactory;

    protected $fillable = [
        'detection_uuid',
        'scientific_name',
        'common_name',
        'confidence',
        'start_time',
        'end_time',
        'recorded_at',
        'latitude',
        'longitude',
        'audio_path',
        'segment_id',
        'raw_metadata',
        'species_id',
        'observation_id',
        'user_id',
    ];

    /**
     * @return BelongsTo<Species, $this>
     */
    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    /**
     * @return BelongsTo<Observation, $this>
     */
    public function observation(): BelongsTo
    {
        return $this->belongsTo(Observation::class);
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
            'recorded_at' => 'datetime',
            'confidence' => 'decimal:4',
            'start_time' => 'float',
            'end_time' => 'float',
            'latitude' => 'float',
            'longitude' => 'float',
            'raw_metadata' => 'array',
        ];
    }
}
