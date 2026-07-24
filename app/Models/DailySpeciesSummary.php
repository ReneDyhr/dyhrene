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

    /**
     * Decode the sources JSON column to a string array.
     *
     * @return list<string>
     */
    public function getSourcesArrayAttribute(): array
    {
        $raw = $this->getRawOriginal('sources');

        if ($raw === null || $raw === '') {
            return [];
        }

        if (\is_string($raw)) {
            $decoded = \json_decode($raw, true);

            if (\is_array($decoded)) {
                // @phpstan-ignore-next-line return.type
                return \array_map(fn(mixed $v): string => (string) $v, $decoded);
            }
        }

        if (\is_array($raw)) {
            // @phpstan-ignore-next-line return.type
            return \array_map(fn(mixed $v): string => (string) $v, $raw);
        }

        return [];
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
