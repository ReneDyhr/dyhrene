<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'icon_id',
        'slug',
        'name',
    ];

    /**
     * Get the user connected.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get icon connected.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Icon, $this>
     */
    public function icon(): BelongsTo
    {
        return $this->belongsTo(Icon::class);
    }

    /**
     * Scope a query to only include categories of the authenticated user.
     *
     * @param  Builder<$this> $query
     * @return Builder<$this>
     */
    public function scopeForAuthUser(Builder $query): Builder
    {
        return $query->where('user_id', Auth::id());
    }
}
