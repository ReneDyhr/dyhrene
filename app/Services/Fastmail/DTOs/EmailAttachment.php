<?php

declare(strict_types=1);

namespace App\Services\Fastmail\DTOs;

final readonly class EmailAttachment
{
    public function __construct(
        public string $partId,
        public string $blobId,
        public string $name,
        public string $type,
        public int $size,
    ) {}
}
