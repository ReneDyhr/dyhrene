<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int                             $id
 * @property int                             $material_type_id
 * @property string                          $name
 * @property float                           $price_per_kg_dkk
 * @property float                           $waste_factor_pct
 * @property string                          $notes
 * @property null|\Illuminate\Support\Carbon $deleted_at
 * @property null|\Illuminate\Support\Carbon $created_at
 * @property null|\Illuminate\Support\Carbon $updated_at
 */
class PrintMaterial extends Model
{
    /** @use HasFactory<\Database\Factories\PrintMaterialFactory> */
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'print_materials';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'material_type_id',
        'name',
        'price_per_kg_dkk',
        'waste_factor_pct',
        'notes',
    ];

    /**
     * @return BelongsTo<PrintMaterialType, $this>
     */
    public function materialType(): BelongsTo
    {
        return $this->belongsTo(PrintMaterialType::class, 'material_type_id');
    }

    /**
     * @return HasMany<PrintJob, $this>
     */
    public function printJobs(): HasMany
    {
        return $this->hasMany(PrintJob::class, 'material_id');
    }

    /**
     * Scope a query to only include active (non-deleted) materials.
     *
     * @param  Builder<PrintMaterial>                               $query
     * @return \Illuminate\Database\Eloquent\Builder<PrintMaterial>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }
}
