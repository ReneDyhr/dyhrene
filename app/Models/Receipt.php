<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receipt extends Model
{
    protected $fillable = [
        'name',
        'vendor',
        'description',
        'currency',
        'total',
        'date',
        'user_id',
        'file_path',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ReceiptItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ReceiptItem::class);
    }
}
