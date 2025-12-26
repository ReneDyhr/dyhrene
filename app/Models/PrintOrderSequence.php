<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintOrderSequence extends Model
{
    /** @use HasFactory<\Database\Factories\PrintOrderSequenceFactory> */
    use HasFactory;
    
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'print_order_sequences';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'year',
        'last_number',
    ];

    /**
     * Get or create the order sequence for a given year.
     *
     * @param  int  $year
     * @return PrintOrderSequence
     */
    public static function getOrCreateForYear(int $year): self
    {
        return self::firstOrCreate(
            ['year' => $year],
            ['last_number' => 0]
        );
    }
}

