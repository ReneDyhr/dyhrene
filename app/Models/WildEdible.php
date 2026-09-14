<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\WildEdibles\SeasonMatcher;
use App\Enums\WildEdibleTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class WildEdible extends Model
{
    /** @use HasFactory<\Database\Factories\WildEdibleFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'type', 'name', 'description', 'location_name', 'latitude', 'longitude',
        'season_start_month', 'season_end_month', 'season_all_year',
    ];

    protected $casts = [
        'type' => WildEdibleTypeEnum::class,
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'season_start_month' => 'integer',
        'season_end_month' => 'integer',
        'season_all_year' => 'boolean',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<WildEdiblePhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(WildEdiblePhoto::class);
    }

    /** @return HasMany<Picking, $this> */
    public function pickings(): HasMany
    {
        return $this->hasMany(Picking::class)->orderByDesc('picked_at')->orderByDesc('id');
    }

    /**
     * @param  Builder<WildEdible> $query
     * @return Builder<WildEdible>
     */
    public function scopeForAuthUser(Builder $query): Builder
    {
        return $query->where('user_id', Auth::id());
    }

    /**
     * @param  Builder<WildEdible> $query
     * @param  list<string>        $types
     * @param  list<int>           $months
     * @return Builder<WildEdible>
     */
    public function scopeWithFilters(Builder $query, array $types = [], array $months = []): Builder
    {
        if ($types !== []) {
            $query->whereIn('type', $types);
        }

        if ($months === []) {
            return $query;
        }

        return $query->where(function (Builder $seasonQuery) use ($months): void {
            $seasonQuery->where('season_all_year', true);

            foreach ($months as $month) {
                $seasonQuery->orWhere(function (Builder $monthQuery) use ($month): void {
                    $monthQuery->whereNotNull('season_start_month')
                        ->whereNotNull('season_end_month')
                        ->where(function (Builder $interval) use ($month): void {
                            $interval->where(function (Builder $normal) use ($month): void {
                                $normal->whereColumn('season_start_month', '<=', 'season_end_month')
                                    ->where('season_start_month', '<=', $month)
                                    ->where('season_end_month', '>=', $month);
                            })->orWhere(function (Builder $wrapped) use ($month): void {
                                $wrapped->whereColumn('season_start_month', '>', 'season_end_month')
                                    ->where(function (Builder $boundary) use ($month): void {
                                        $boundary->where('season_start_month', '<=', $month)
                                            ->orWhere('season_end_month', '>=', $month);
                                    });
                            });
                        });
                });
            }
        });
    }

    public function seasonLabel(): string
    {
        if ($this->season_all_year) {
            return 'All year';
        }

        if ($this->season_start_month === null || $this->season_end_month === null) {
            return 'Season unknown';
        }

        return (new \DateTimeImmutable(\sprintf('2000-%02d-01', $this->season_start_month)))->format('F')
            . ' – ' . (new \DateTimeImmutable(\sprintf('2000-%02d-01', $this->season_end_month)))->format('F');
    }

    public function isReadyInMonth(int $month): bool
    {
        return SeasonMatcher::matches($this->season_start_month, $this->season_end_month, $month, $this->season_all_year);
    }

    protected static function booted(): void
    {
        static::forceDeleting(function (self $edible): void {
            $edible->photos()->withTrashed()->get()->each(function (WildEdiblePhoto $photo): void {
                Storage::disk('wasabi')->delete($photo->storage_path);
                $photo->forceDelete();
            });
        });
    }
}
