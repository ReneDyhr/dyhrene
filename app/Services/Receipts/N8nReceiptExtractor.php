<?php

declare(strict_types=1);

namespace App\Services\Receipts;

use App\Services\Receipts\Exceptions\ReceiptExtractionException;
use Illuminate\Support\Facades\Http;

final class N8nReceiptExtractor
{
    /**
     * @return array<string, mixed> n8n "output" payload
     */
    public function extract(string $fileContents, string $filename): array
    {
        $webhookUrl = \config('n8n.webhook_url');

        if (!\is_string($webhookUrl) || \trim($webhookUrl) === '') {
            throw new ReceiptExtractionException('n8n webhook URL is not configured.');
        }

        if ($fileContents === '') {
            throw new ReceiptExtractionException('No file content to extract.');
        }

        try {
            $response = Http::timeout(120)
                ->attach('File', $fileContents, $filename)
                ->post($webhookUrl);
        } catch (\Throwable $e) {
            throw new ReceiptExtractionException('Error calling webhook: ' . $e->getMessage(), 0, $e);
        }

        if (!$response->successful()) {
            throw new ReceiptExtractionException('Failed to extract receipt data from webhook.');
        }

        return $this->normalizeOutput($response->json());
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeOutput(mixed $data): array
    {
        if (!\is_array($data)) {
            throw new ReceiptExtractionException('Invalid response from webhook.');
        }

        $payload = $this->unwrapPayload($data);

        if ($payload === null) {
            throw new ReceiptExtractionException('No output data in webhook response.');
        }

        if (isset($payload['items']) && \is_string($payload['items'])) {
            $decoded = \json_decode($payload['items'], true);

            if (\is_array($decoded)) {
                $payload['items'] = $decoded;
            }
        }

        if (!isset($payload['items']) || !\is_array($payload['items']) || $payload['items'] === []) {
            throw new ReceiptExtractionException('Could not extract items from receipt.');
        }

        return $payload;
    }

    /**
     * @param array<mixed> $data
     *
     * @return null|array<string, mixed>
     */
    private function unwrapPayload(array $data): ?array
    {
        if (isset($data['output'])) {
            $output = $data['output'];

            if (\is_array($output)) {
                if (isset($output['items']) && \is_array($output['items'])) {
                    return $this->stringKeyedArray($output);
                }

                $first = $output[0] ?? null;

                if (\is_array($first)) {
                    $nested = $this->unwrapPayload($first);

                    if ($nested !== null) {
                        return $nested;
                    }
                }

                return $this->stringKeyedArray($output);
            }
        }

        if (isset($data['items']) && \is_array($data['items'])) {
            return $this->stringKeyedArray($data);
        }

        if (isset($data['json']) && \is_array($data['json'])) {
            return $this->unwrapPayload($data['json']);
        }

        if (isset($data['data']) && \is_array($data['data'])) {
            return $this->unwrapPayload($data['data']);
        }

        if (isset($data[0]) && \is_array($data[0])) {
            return $this->unwrapPayload($data[0]);
        }

        return null;
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<string, mixed>
     */
    private function stringKeyedArray(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            if (\is_string($key) || \is_int($key)) {
                $normalized[(string) $key] = $value;
            }
        }

        return $normalized;
    }
}
