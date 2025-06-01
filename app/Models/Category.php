<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'icon_id',
        'slug',
        'name',
    ];

    /**
     * Get the user connected
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, Category>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get icon connected
     */
    public function icon()
    {
        return $this->belongsTo(Icon::class);
    }

    /**
     * Scope a query to only include categories of the authenticated user.
     */
    public function scopeForAuthUser($query)
    {
        return $query->where('user_id', Auth::id());
    }
}
