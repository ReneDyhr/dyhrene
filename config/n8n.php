<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | n8n Webhook URL
    |--------------------------------------------------------------------------
    |
    | Here you may specify your n8n webhook URL. This will be used to send
    | receipt images for extraction.
     */

    'webhook_url' => \env('N8N_WEBHOOK_URL'),

    /*
    | Classify-only webhook: POST a file, receive document_type + confidence
    | (receipt | payslip | unknown). Separate from receipt line-item extraction.
     */
    'classify_webhook_url' => \env('N8N_CLASSIFY_WEBHOOK_URL'),
];
