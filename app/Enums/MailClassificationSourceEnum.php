<?php

declare(strict_types=1);

namespace App\Enums;

enum MailClassificationSourceEnum: string
{
    case Metadata = 'metadata';
    case AttachmentText = 'attachment_text';
    case N8n = 'n8n';
    case Manual = 'manual';
}
