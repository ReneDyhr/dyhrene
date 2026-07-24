<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailySpeciesSummary extends Model
{
    /** @use HasFactory<\Database\Factories\DailySpeciesSummaryFactory> */
    use HasFactory;

    protected $fillable = [
        'site_id',
        'date',
        'species_id',
        'windows_present',
        'records',
        'sources',
        'first_seen_at',
        'last_seen_at',
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
            'date' => 'date',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }
}
