<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ObservationSourceEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObservationWindow extends Model
{
    /** @use HasFactory<\Database\Factories\ObservationWindowFactory> */
    use HasFactory;

    protected $fillable = [
        'site_id',
        'species_id',
        'window_start',
        'source',
        'records',
        'max_confidence',
    ];

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return BelongsTo<Species, $this>
     */
    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    protected function casts(): array
    {
        return [
            'window_start' => 'datetime',
            'source' => ObservationSourceEnum::class,
            'max_confidence' => 'decimal:4',
        ];
    }
}
