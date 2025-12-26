<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Printing\PrintJobCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property null|array<string, mixed> $calculation
 */
class PrintJob extends Model
{
    /** @use HasFactory<\Database\Factories\PrintJobFactory> */
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'print_jobs';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'date',
        'description',
        'internal_notes',
        'customer_id',
        'material_id',
        'pieces_per_plate',
        'plates',
        'grams_per_plate',
        'hours_per_plate',
        'labor_hours',
        'is_first_time_order',
        'avance_pct_override',
        'status',
        'locked_at',
        'calc_snapshot',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'is_first_time_order' => 'boolean',
        'calc_snapshot' => 'array',
        'locked_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<PrintCustomer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(PrintCustomer::class, 'customer_id');
    }

    /**
     * @return BelongsTo<PrintMaterial, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(PrintMaterial::class, 'material_id');
    }

    /**
     * @return HasMany<PrintActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(PrintActivityLog::class, 'print_job_id');
    }

    /**
     * Scope a query to only include draft print jobs.
     *
     * @param  Builder<PrintJob>                               $query
     * @return \Illuminate\Database\Eloquent\Builder<PrintJob>
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to only include locked print jobs.
     *
     * @param  Builder<PrintJob>                               $query
     * @return \Illuminate\Database\Eloquent\Builder<PrintJob>
     */
    public function scopeLocked(Builder $query): Builder
    {
        return $query->where('status', 'locked');
    }

    /**
     * Scope a query to only include active (non-deleted) print jobs.
     *
     * @param  Builder<PrintJob>                               $query
     * @return \Illuminate\Database\Eloquent\Builder<PrintJob>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Check if the print job is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if the print job is locked.
     */
    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    /**
     * Build a complete calculation snapshot for this print job.
     * Includes input values, rates used, derived totals, costs, pricing, and profit.
     *
     * @return array<string, mixed>
     */
    public function buildSnapshot(): array
    {
        // Ensure relationships are loaded
        $this->loadMissing(['material', 'material.materialType']);

        // Load current settings
        $settings = PrintSetting::current();

        // Build calculator input
        $calculator = new PrintJobCalculator();
        $input = [
            'pieces_per_plate' => $this->pieces_per_plate,
            'plates' => $this->plates,
            'grams_per_plate' => $this->grams_per_plate,
            'hours_per_plate' => $this->hours_per_plate,
            'labor_hours' => $this->labor_hours,
            'is_first_time_order' => $this->is_first_time_order,
            'avance_pct_override' => $this->avance_pct_override,
            'electricity_rate_dkk_per_kwh' => $settings->electricity_rate_dkk_per_kwh ?? 0,
            'wage_rate_dkk_per_hour' => $settings->wage_rate_dkk_per_hour ?? 0,
            'first_time_fee_dkk' => $settings->first_time_fee_dkk ?? 0,
            'default_avance_pct' => $settings->default_avance_pct ?? 0,
            'price_per_kg_dkk' => $this->material->price_per_kg_dkk ?? 0,
            'waste_factor_pct' => $this->material->waste_factor_pct ?? 0,
            'avg_kwh_per_hour' => $this->material->materialType->avg_kwh_per_hour ?? 0,
        ];

        // Calculate using calculator
        $calculation = $calculator->calculate($input);

        // Determine applied avance percentage
        $appliedAvancePct = $this->avance_pct_override ?? ($settings->default_avance_pct ?? 0);

        // Build complete snapshot
        return [
            // Input values
            'input' => [
                'pieces_per_plate' => $this->pieces_per_plate,
                'plates' => $this->plates,
                'grams_per_plate' => $this->grams_per_plate,
                'hours_per_plate' => $this->hours_per_plate,
                'labor_hours' => $this->labor_hours,
                'is_first_time_order' => $this->is_first_time_order,
                'avance_pct_override' => $this->avance_pct_override,
            ],
            // Rates used
            'rates' => [
                'electricity_rate_dkk_per_kwh' => $settings->electricity_rate_dkk_per_kwh ?? 0,
                'wage_rate_dkk_per_hour' => $settings->wage_rate_dkk_per_hour ?? 0,
                'first_time_fee_dkk' => $settings->first_time_fee_dkk ?? 0,
                'applied_avance_pct' => $appliedAvancePct,
                'material_price_per_kg_dkk' => $this->material->price_per_kg_dkk ?? 0,
                'material_waste_factor_pct' => $this->material->waste_factor_pct ?? 0,
                'material_type_avg_kwh_per_hour' => $this->material->materialType->avg_kwh_per_hour ?? 0,
                'material_name' => $this->material->name ?? '',
                'material_type_name' => $this->material->materialType->name ?? '',
            ],
            // Derived totals
            'totals' => $calculation['totals'],
            // Rounded cost breakdown
            'costs' => $calculation['costs'],
            // Pricing/profit
            'pricing' => $calculation['pricing'],
            'profit' => $calculation['profit'],
        ];
    }
}
