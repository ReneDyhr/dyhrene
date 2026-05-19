<?php

declare(strict_types=1);

namespace App\Services\Mail\DTOs;

use App\Enums\MailClassificationSourceEnum;
use App\Enums\MailDocumentTypeEnum;

final readonly class MailDocumentClassificationResult
{
    public function __construct(
        public MailDocumentTypeEnum $documentType,
        public float $confidence,
        public MailClassificationSourceEnum $source,
        public bool $confident,
    ) {}

    public static function unknown(MailClassificationSourceEnum $source): self
    {
        return new self(
            documentType: MailDocumentTypeEnum::Unknown,
            confidence: 0.0,
            source: $source,
            confident: false,
        );
    }
}
