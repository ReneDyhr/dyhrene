<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}
