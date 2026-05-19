<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Enums\MailClassificationSourceEnum;
use App\Enums\MailDocumentTypeEnum;
use App\Services\Mail\DTOs\MailDocumentClassificationResult;

final class MailDocumentKeywordScorer
{
    /**
     * @param list<string> $texts
     */
    public function classifyFromTexts(array $texts, MailClassificationSourceEnum $source): MailDocumentClassificationResult
    {
        $haystack = $this->buildHaystack($texts);
        $receiptScore = $this->scoreKeywords($haystack, $this->receiptKeywords());
        $payslipScore = $this->scoreKeywords($haystack, $this->payslipKeywords());
        $minScore = $this->minScore();

        if ($receiptScore >= $minScore && $receiptScore > $payslipScore) {
            return new MailDocumentClassificationResult(
                documentType: MailDocumentTypeEnum::Receipt,
                confidence: $this->confidenceFromScore($receiptScore, $payslipScore),
                source: $source,
                confident: true,
            );
        }

        if ($payslipScore >= $minScore && $payslipScore > $receiptScore) {
            return new MailDocumentClassificationResult(
                documentType: MailDocumentTypeEnum::Payslip,
                confidence: $this->confidenceFromScore($payslipScore, $receiptScore),
                source: $source,
                confident: true,
            );
        }

        $strongReceiptScore = $this->scoreKeywords($haystack, $this->strongReceiptKeywords());
        $strongPayslipScore = $this->scoreKeywords($haystack, $this->strongPayslipKeywords());

        if ($strongPayslipScore >= 1 && $strongPayslipScore > $strongReceiptScore && $receiptScore < $minScore) {
            return new MailDocumentClassificationResult(
                documentType: MailDocumentTypeEnum::Payslip,
                confidence: $this->confidenceFromScore(\max($payslipScore, 1), $receiptScore),
                source: $source,
                confident: true,
            );
        }

        if ($strongReceiptScore >= 1 && $strongReceiptScore > $strongPayslipScore && $payslipScore < $minScore) {
            return new MailDocumentClassificationResult(
                documentType: MailDocumentTypeEnum::Receipt,
                confidence: $this->confidenceFromScore(\max($receiptScore, 1), $payslipScore),
                source: $source,
                confident: true,
            );
        }

        return MailDocumentClassificationResult::unknown($source);
    }

    /**
     * @param list<string> $texts
     */
    private function buildHaystack(array $texts): string
    {
        $parts = [];

        foreach ($texts as $text) {
            $trimmed = \trim($text);

            if ($trimmed !== '') {
                $parts[] = $trimmed;
            }
        }

        return \mb_strtolower(\implode("\n", $parts));
    }

    /**
     * @param list<string> $keywords
     */
    private function scoreKeywords(string $haystack, array $keywords): int
    {
        if ($haystack === '') {
            return 0;
        }

        $score = 0;

        foreach ($keywords as $keyword) {
            $normalized = \mb_strtolower(\trim($keyword));

            if ($normalized === '') {
                continue;
            }

            if (\str_contains($haystack, $normalized)) {
                $score++;
            }
        }

        return $score;
    }

    private function confidenceFromScore(int $winnerScore, int $loserScore): float
    {
        $margin = $winnerScore - $loserScore;
        $base = 0.5 + ($winnerScore * 0.1) + ($margin * 0.05);

        return \min(1.0, \max(0.5, $base));
    }

    private function minScore(): int
    {
        $value = \config('mail_classification.min_score', 2);

        return \is_int($value) ? $value : 2;
    }

    /**
     * @return list<string>
     */
    private function receiptKeywords(): array
    {
        /** @var list<string> $keywords */
        $keywords = \config('mail_classification.receipt_keywords', []);

        return $keywords;
    }

    /**
     * @return list<string>
     */
    private function payslipKeywords(): array
    {
        /** @var list<string> $keywords */
        $keywords = \config('mail_classification.payslip_keywords', []);

        return $keywords;
    }

    /**
     * @return list<string>
     */
    private function strongReceiptKeywords(): array
    {
        /** @var list<string> $keywords */
        $keywords = \config('mail_classification.receipt_strong_keywords', []);

        return $keywords;
    }

    /**
     * @return list<string>
     */
    private function strongPayslipKeywords(): array
    {
        /** @var list<string> $keywords */
        $keywords = \config('mail_classification.payslip_strong_keywords', []);

        return $keywords;
    }
}
