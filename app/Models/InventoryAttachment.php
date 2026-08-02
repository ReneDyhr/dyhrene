<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAttachment extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryAttachmentFactory> */
    use HasFactory;

    protected $fillable = ['inventory_item_id', 'file_path', 'file_name', 'mime_type', 'size'];

    /**
     * @var array<string, string>
     */
    protected $casts = ['size' => 'integer'];

    /**
     * @return BelongsTo<InventoryItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
