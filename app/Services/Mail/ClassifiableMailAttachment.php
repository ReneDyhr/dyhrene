<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Services\Fastmail\DTOs\EmailAttachment;
use App\Services\Fastmail\DTOs\EmailMessage;

final class ClassifiableMailAttachment
{
    private const IMAGE_MIMES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    public static function firstFromMessage(EmailMessage $message): ?EmailAttachment
    {
        foreach ($message->attachments as $attachment) {
            if (self::isClassifiable($attachment)) {
                return $attachment;
            }
        }

        return null;
    }

    public static function isClassifiable(EmailAttachment $attachment): bool
    {
        $mime = \mb_strtolower($attachment->type);
        $name = \mb_strtolower($attachment->name);

        if (\in_array($mime, self::IMAGE_MIMES, true)) {
            return true;
        }

        if (\str_contains($mime, 'pdf') || \str_ends_with($name, '.pdf')) {
            return true;
        }

        return false;
    }
}
