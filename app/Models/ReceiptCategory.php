<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceiptCategory extends Model
{
    protected $fillable = [
        'name',
        'color',
        'user_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<ReceiptItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ReceiptItem::class);
    }
}
