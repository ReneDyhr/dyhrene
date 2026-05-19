<?php

declare(strict_types=1);

namespace App\Services\Fastmail\DTOs;

use Illuminate\Support\Collection;

final readonly class EmailMessage
{
    /**
     * @param list<array{name: ?string, email: ?string}> $from
     * @param list<array{name: ?string, email: ?string}> $to
     * @param Collection<int, EmailAttachment>           $attachments
     */
    public function __construct(
        public EmailSummary $summary,
        public array $from,
        public array $to,
        public ?string $textBody,
        public ?string $htmlBody,
        public Collection $attachments,
    ) {}
}
