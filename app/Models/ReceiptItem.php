<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptItem extends Model
{
    /** @use HasFactory<\Database\Factories\ReceiptItemFactory> */
    use HasFactory;

    protected $fillable = [
        'receipt_id',
        'name',
        'quantity',
        'amount',
        'category_id',
    ];

    /**
     * Get the total by multiplying amount and quantity.
     */
    public function getTotalAttribute(): float
    {
        return $this->amount * $this->quantity;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Receipt, $this>
     */
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<ReceiptCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ReceiptCategory::class);
    }
}
