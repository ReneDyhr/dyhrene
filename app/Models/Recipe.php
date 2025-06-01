<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Recipe extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'note',
        'public',
        'favourite'
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
     */
    public function scopeForAuthUser($query)
    {
        return $query->where('user_id', Auth::id());
    }

    /**
     * Get the categories
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<Category, Recipe>
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * Get the ingredients
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasMany<RecipeIngredient, Recipe>
     */
    public function ingredients()
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    /**
     * Get the tags
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasMany<RecipeTag, Recipe>
     */
    public function tags()
    {
        return $this->hasMany(RecipeTag::class);
    }

    public function toggleFavourite()
    {
        $this->favourite = !$this->favourite;
        $this->save();
    }

    public function scopeFavourites($query)
    {
        return $query->where('favourite', true);
    }
}
