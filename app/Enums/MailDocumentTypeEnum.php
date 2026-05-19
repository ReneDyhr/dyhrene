<?php

declare(strict_types=1);

namespace App\Enums;

enum MailDocumentTypeEnum: string
{
    case Receipt = 'receipt';
    case Payslip = 'payslip';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Receipt => 'Receipt',
            self::Payslip => 'Payslip',
            self::Unknown => 'Unknown',
        };
    }
}
