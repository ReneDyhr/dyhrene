<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SpeciesStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Species extends Model
{
    /** @use HasFactory<\Database\Factories\SpeciesFactory> */
    use HasFactory;

    protected $fillable = [
        'common_name',
        'scientific_name',
        'ebird_code',
        'taxonomic_order',
        'status',
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
        return $this->hasMany(Observation::class)->orderBy('observed_at', 'desc')->orderBy('observed_time', 'desc');
    }

    /**
     * Scope to species that are not rejected.
     *
     * @param  Builder<Species> $query
     * @return Builder<Species>
     */
    public function scopeNotRejected(Builder $query): Builder
    {
        return $query->where('status', '!=', SpeciesStatusEnum::Rejected->value);
    }

    /**
     * Scope to species that are expected.
     *
     * @param  Builder<Species> $query
     * @return Builder<Species>
     */
    public function scopeExpected(Builder $query): Builder
    {
        return $query->where('status', SpeciesStatusEnum::Expected->value);
    }

    protected function casts(): array
    {
        return [
            'status' => SpeciesStatusEnum::class,
        ];
    }
}
