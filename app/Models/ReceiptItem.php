<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptItem extends Model
{
    protected $fillable = [
        'receipt_id',
        'name',
        'quantity',
        'amount',
        'category_id',
    ];

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
