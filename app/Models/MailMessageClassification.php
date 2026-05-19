<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MailClassificationSourceEnum;
use App\Enums\MailDocumentTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailMessageClassification extends Model
{
    protected $fillable = [
        'fastmail_email_id',
        'document_type',
        'confidence',
        'source',
        'classified_at',
        'receipt_id',
        'processed_at',
    ];

    protected $casts = [
        'document_type' => MailDocumentTypeEnum::class,
        'source' => MailClassificationSourceEnum::class,
        'confidence' => 'float',
        'classified_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Receipt, $this>
     */
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }
}
