<?php

declare(strict_types=1);

return [
    // | Minimum score (sum of keyword hits) required to classify from metadata or text.
    'min_score' => (int) \env('MAIL_CLASSIFICATION_MIN_SCORE', 2),

    // | Minimum confidence (0–1) returned from n8n to accept its classification.
    'n8n_min_confidence' => (float) \env('MAIL_CLASSIFICATION_N8N_MIN_CONFIDENCE', 0.5),

    'receipt_keywords' => [
        'kvittering',
        'receipt',
        'faktura',
        'invoice',
        'ordrebekræftelse',
        'order confirmation',
        'orderbekræftelse',
        'purchase',
        'køb',
        'betaling',
        'payment confirmation',
        'your order',
        'din ordre',
        'ordernumber',
        'ordrenummer',
        'total incl',
        'total inkl',
        'moms',
        'dkk',
        'bon',
    ],

    'payslip_keywords' => [
        'lønseddel',
        'lonseddel',
        'lønsedlen',
        'payslip',
        'pay slip',
        'salary slip',
        'løn',
        'lon',
        'salary',
        'payroll',
        'lønudbetaling',
        'lønperiode',
        'wage',
        'feriepenge',
        'feriekonto',
        'ferieperiode',
        'feriepengeinfo',
        'skattekort',
        'danløn',
        'danlon',
        'timeløn',
    ],

    // | One match is enough when the other document type has no hits at min_score.
    'payslip_strong_keywords' => [
        'feriepenge',
        'lønseddel',
        'lønsedlen',
    ],

    'receipt_strong_keywords' => [
        'kvittering',
        'faktura',
        'invoice',
    ],

    /*
    | MobilePay screenshot PDFs (numeric filename, e.g. 4527847806.pdf).
    | Sent/Paid layouts are larger than Received; used when PDF text is not extractable.
     */
    // | Thresholds apply to the first PDF page as rendered by Imagick (not raw PDF image pixels).
    'mobilepay_sent_min_width' => (int) \env('MAIL_CLASSIFICATION_MOBILEPAY_SENT_MIN_WIDTH', 500),
    'mobilepay_sent_min_height' => (int) \env('MAIL_CLASSIFICATION_MOBILEPAY_SENT_MIN_HEIGHT', 650),

    // | Inline JPEG/PNG screenshots (e.g. forwarded MobilePay) must exceed this height or width.
    'mobilepay_image_min_height' => (int) \env('MAIL_CLASSIFICATION_MOBILEPAY_IMAGE_MIN_HEIGHT', 800),

    'mobilepay_incoming_keywords' => [
        'received',
        'modtaget',
        'du har modtaget',
        'you have received',
        'you received',
    ],

    'mobilepay_outgoing_keywords' => [
        'sent',
        'sendt',
        'du har sendt',
        'du har betalt',
        'paid by',
        'betalt af',
        'withdrawn from',
        'trukket fra',
        'your total',
        'dit total',
    ],
];
