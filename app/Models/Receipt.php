<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receipt extends Model
{
    /** @use HasFactory<\Database\Factories\ReceiptFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'vendor',
        'description',
        'currency',
        'date',
        'user_id',
        'file_path',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function getTotalAttribute(): float
    {
        $sum = 0.0;

        foreach ($this->items as $item) {
            if ($item instanceof ReceiptItem) {
                $sum += $item->total;
            }
        }

        return $sum;
    }

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
