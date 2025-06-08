<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Recipe extends Model
{
    /** @use HasFactory<\Database\Factories\RecipeIngredientFactory> */
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'note',
        'public',
        'favourite',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'public' => 'boolean',
    ];

    /**
     * Scope a query to only include recipes of the authenticated user.
     *
     * @param  Builder<$this> $query
     * @return Builder<$this>
     */
    public function scopeForAuthUser(Builder $query): Builder
    {
        return $query->where('user_id', Auth::id());
    }

    /**
     * Get the categories.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * Get the ingredients.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<RecipeIngredient, $this>
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    /**
     * Get the tags.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<RecipeTag, $this>
     */
    public function tags(): HasMany
    {
        return $this->hasMany(RecipeTag::class);
    }

    public function toggleFavourite(): void
    {
        $this->favourite = !$this->favourite;
        $this->save();
    }

    /**
     * Scope a query to only include favourite recipes.
     *
     * @param  Builder<$this> $query
     * @return Builder<$this>
     */
    public function scopeFavourites(Builder $query): Builder
    {
        return $query->where('favourite', true);
    }
}
