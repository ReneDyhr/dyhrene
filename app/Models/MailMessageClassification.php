<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MailClassificationSourceEnum;
use App\Enums\MailDocumentTypeEnum;
use Illuminate\Database\Eloquent\Model;

class MailMessageClassification extends Model
{
    protected $fillable = [
        'fastmail_email_id',
        'document_type',
        'confidence',
        'source',
        'classified_at',
    ];

    protected $casts = [
        'document_type' => MailDocumentTypeEnum::class,
        'source' => MailClassificationSourceEnum::class,
        'confidence' => 'float',
        'classified_at' => 'datetime',
    ];
}
