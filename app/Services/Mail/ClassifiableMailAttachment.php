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

    private const NON_RECEIPT_PATTERNS = [
        'fortrydel',   // Danish: right of withdrawal (fortrydelsesret, fortrydelse)
        'withdrawal',  // English
        'cancellation',
        'widerruf',    // German
        'agb',         // German: Allgemeine Geschäftsbedingungen
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
            return !self::isNonReceiptDocument($name);
        }

        if (\str_contains($mime, 'pdf') || \str_ends_with($name, '.pdf')) {
            return !self::isNonReceiptDocument($name);
        }

        return false;
    }

    private static function isNonReceiptDocument(string $lowercaseName): bool
    {
        foreach (self::NON_RECEIPT_PATTERNS as $pattern) {
            if (\str_contains($lowercaseName, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
