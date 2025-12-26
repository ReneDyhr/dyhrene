<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrintMaterialType extends Model
{
    /** @use HasFactory<\Database\Factories\PrintMaterialTypeFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'print_material_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'avg_kwh_per_hour',
    ];

    /**
     * @return HasMany<PrintMaterial, $this>
     */
    public function materials(): HasMany
    {
        return $this->hasMany(PrintMaterial::class, 'material_type_id');
    }
}

