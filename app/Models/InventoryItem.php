<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InventoryAcquisitionTypeEnum;
use App\Enums\InventoryOwnerEnum;
use App\Enums\InventoryStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class InventoryItem extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryItemFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'category_id', 'name', 'brand', 'model', 'serial_number', 'owner',
        'price', 'current_value', 'acquisition_type', 'acquisition_date', 'acquired_from',
        'status', 'status_change_date', 'status_reason',
    ];

    protected $casts = [
        'owner' => InventoryOwnerEnum::class,
        'acquisition_type' => InventoryAcquisitionTypeEnum::class,
        'status' => InventoryStatusEnum::class,
        'acquisition_date' => 'date',
        'status_change_date' => 'date',
        'price' => 'decimal:2',
        'current_value' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<InventoryCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    /**
     * @return HasMany<InventoryAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(InventoryAttachment::class, 'inventory_item_id');
    }

    /**
     * @param  Builder<$this> $query
     * @return Builder<$this>
     */
    public function scopeForAuthUser(Builder $query): Builder
    {
        return $query->where('user_id', Auth::id());
    }
}
