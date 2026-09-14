<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Picking extends Model
{
    /** @use HasFactory<\Database\Factories\PickingFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['wild_edible_id', 'user_id', 'picked_at', 'latitude', 'longitude', 'comment'];

    protected $casts = [
        'picked_at' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    /** @return BelongsTo<WildEdible, $this> */
    public function wildEdible(): BelongsTo
    {
        return $this->belongsTo(WildEdible::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<Picking> $query
     * @return Builder<Picking>
     */
    public function scopeForAuthUser(Builder $query): Builder
    {
        return $query->where('user_id', Auth::id());
    }
}
