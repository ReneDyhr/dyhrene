<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * @property int    $id
 * @property int    $user_id
 * @property string $name
 * @property int    $order
 * @property string $status
 */
class ShoppingList extends Model
{
    use SoftDeletes;

    protected $table = 'shopping_list';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'order',
        'status',
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
}
