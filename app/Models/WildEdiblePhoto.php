<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WildEdiblePhoto extends Model
{
    /** @use HasFactory<\Database\Factories\WildEdiblePhotoFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['wild_edible_id', 'storage_path', 'original_file_name', 'mime_type', 'size', 'metadata'];

    protected $casts = ['size' => 'integer', 'metadata' => 'array'];

    /** @return BelongsTo<WildEdible, $this> */
    public function wildEdible(): BelongsTo
    {
        return $this->belongsTo(WildEdible::class);
    }
}
