<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class ShoppingList extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'shopping_list';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'order',
        'status',
    ];

    /**
     * Scope a query to only include recipes of the authenticated user.
     */
    public function scopeForAuthUser($query)
    {
        return $query->where('user_id', Auth::id());
    }
}
