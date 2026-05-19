<?php

declare(strict_types=1);

namespace App\Services\Fastmail;

use App\Services\Fastmail\Exceptions\FastmailApiException;
use App\Services\Fastmail\Exceptions\FastmailConfigurationException;
use App\Services\Fastmail\Support\JmapCasts;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FastmailJmapClient
{
    private const CAPABILITY_CORE = 'urn:ietf:params:jmap:core';

    private const CAPABILITY_MAIL = 'urn:ietf:params:jmap:mail';

    private const CAPABILITY_SUBMISSION = 'urn:ietf:params:jmap:submission';

    private ?FastmailSession $session = null;

    /**
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function call(string $method, array $arguments): array
    {
        $responses = $this->callMany([
            [$method, $arguments, 'c0'],
        ]);

        return $responses[0];
    }

    /**
     * @param list<array{0: string, 1: array<string, mixed>, 2?: string}> $methodCalls
     *
     * @return list<array<string, mixed>>
     */
    public function callMany(array $methodCalls): array
    {
        $session = $this->resolveSession();

        $calls = [];

        foreach ($methodCalls as $index => $call) {
            $method = $call[0];
            $arguments = $call[1];
            $arguments['accountId'] = $session->accountId;
            $callId = $call[2] ?? 'c' . $index;
            $calls[] = [$method, $arguments, $callId];
        }

        $response = Http::withToken($this->requireToken())
            ->acceptJson()
            ->asJson()
            ->post($session->apiUrl, [
                'using' => $this->usingForMethodCalls($calls),
                'methodCalls' => $calls,
            ]);

        if (!$response->successful()) {
            throw new FastmailApiException(
                'Fastmail JMAP request failed: HTTP ' . $response->status(),
            );
        }

        /** @var array{methodResponses?: list<array<int, mixed>>} $body */
        $body = $response->json();

        $methodResponses = $body['methodResponses'] ?? [];

        /** @var array<string, array<string, mixed>> $resultsByCallId */
        $resultsByCallId = [];

        foreach ($methodResponses as $methodResponse) {
            $status = $methodResponse[0] ?? null;
            $callId = \is_string($methodResponse[2] ?? null) ? $methodResponse[2] : null;

            if ($callId === null) {
                throw new FastmailApiException('Fastmail JMAP response missing call id.');
            }

            if ($status === 'error') {
                $errorPayload = JmapCasts::associativeArray($methodResponse[1] ?? null);
                $errorType = JmapCasts::string($errorPayload['type'] ?? null, 'unknown');
                $description = JmapCasts::string($errorPayload['description'] ?? null);

                throw new FastmailApiException(
                    'Fastmail JMAP error: ' . $errorType . ($description !== '' ? ' — ' . $description : ''),
                );
            }

            /** @var array<string, mixed> $result */
            $result = \is_array($methodResponse[1] ?? null) ? $methodResponse[1] : [];
            $resultsByCallId[$callId] = $result;
        }

        $results = [];

        foreach ($calls as $index => $call) {
            $callId = $call[2];

            if (!isset($resultsByCallId[$callId])) {
                throw new FastmailApiException(
                    'Fastmail JMAP response missing result for call "' . $callId . '".',
                );
            }

            $results[] = $resultsByCallId[$callId];
        }

        return $results;
    }

    public function resolveSession(): FastmailSession
    {
        if ($this->session !== null) {
            return $this->session;
        }

        $email = $this->requireEmail();
        $cacheKey = 'fastmail.session.' . \md5($email);
        $ttlConfig = \config('fastmail.session_cache_ttl', 3600);
        $ttl = \is_int($ttlConfig) ? $ttlConfig : 3600;

        $cached = Cache::get($cacheKey);

        if (\is_array($cached)) {
            $this->session = FastmailSession::fromArray(JmapCasts::associativeArray($cached));

            return $this->session;
        }

        $sessionUrlConfig = \config('fastmail.session_url', 'https://api.fastmail.com/jmap/session');
        $sessionUrl = \is_string($sessionUrlConfig) ? $sessionUrlConfig : 'https://api.fastmail.com/jmap/session';

        $response = Http::withToken($this->requireToken())
            ->acceptJson()
            ->get($sessionUrl);

        if (!$response->successful()) {
            throw new FastmailApiException(
                'Failed to load Fastmail JMAP session: HTTP ' . $response->status(),
            );
        }

        /** @var array{apiUrl?: string, downloadUrl?: string, primaryAccounts?: array<string, string>} $data */
        $data = $response->json();

        $apiUrl = $data['apiUrl'] ?? null;

        if (!\is_string($apiUrl) || $apiUrl === '') {
            throw new FastmailApiException('Fastmail session response missing apiUrl.');
        }

        $accountId = $this->resolveAccountId($data, $email);

        if ($accountId === '') {
            throw new FastmailConfigurationException(
                $this->accountResolutionErrorMessage($data, $email),
            );
        }

        $downloadUrl = JmapCasts::string($data['downloadUrl'] ?? null);

        $this->session = new FastmailSession(
            accountId: $accountId,
            apiUrl: $apiUrl,
            email: $email,
            downloadUrl: $downloadUrl,
        );

        Cache::put($cacheKey, $this->session->toArray(), $ttl);

        return $this->session;
    }

    public function downloadBlob(string $blobId, string $filename, string $mimeType): string
    {
        $session = $this->resolveSession();

        if ($session->downloadUrl === '') {
            throw new FastmailApiException(
                'Fastmail session has no downloadUrl; cannot download attachment.',
            );
        }

        $url = \str_replace(
            ['{accountId}', '{blobId}', '{name}', '{type}'],
            [
                $session->accountId,
                $blobId,
                \rawurlencode($filename),
                \rawurlencode($mimeType),
            ],
            $session->downloadUrl,
        );

        $response = Http::withToken($this->requireToken())
            ->get($url);

        if (!$response->successful()) {
            throw new FastmailApiException(
                'Failed to download Fastmail blob: HTTP ' . $response->status(),
            );
        }

        return $response->body();
    }

    public function clearSessionCache(): void
    {
        $email = \config('fastmail.email');

        if (\is_string($email) && $email !== '') {
            Cache::forget('fastmail.session.' . \md5($email));
        }

        $this->session = null;
    }

    private function requireToken(): string
    {
        $token = \config('fastmail.token');

        if (!\is_string($token) || $token === '') {
            throw new FastmailConfigurationException(
                'FASTMAIL_API_TOKEN is not configured.',
            );
        }

        return $token;
    }

    private function requireEmail(): string
    {
        $email = \config('fastmail.email');

        if (!\is_string($email) || $email === '') {
            throw new FastmailConfigurationException(
                'FASTMAIL_EMAIL is not configured.',
            );
        }

        return $email;
    }

    /**
     * @param list<array{0: string, 1: array<string, mixed>, 2: string}> $calls
     *
     * @return list<string>
     */
    private function usingForMethodCalls(array $calls): array
    {
        $using = [self::CAPABILITY_CORE];

        foreach ($calls as $call) {
            $method = $call[0];

            if (
                \str_starts_with($method, 'Email/')
                || \str_starts_with($method, 'Mailbox/')
                || \str_starts_with($method, 'Thread/')
            ) {
                $using[] = self::CAPABILITY_MAIL;
            }

            if (
                \str_starts_with($method, 'Identity/')
                || \str_starts_with($method, 'EmailSubmission/')
            ) {
                $using[] = self::CAPABILITY_SUBMISSION;
            }

            if (\str_starts_with($method, 'Blob/')) {
                $using[] = self::CAPABILITY_MAIL;
            }
        }

        return \array_values(\array_unique($using));
    }

    /**
     * @param array<string, mixed> $sessionData
     */
    private function resolveAccountId(array $sessionData, string $configuredEmail): string
    {
        /** @var array<string, array<string, mixed>> $accounts */
        $accounts = $sessionData['accounts'] ?? [];

        foreach ($accounts as $accountId => $account) {
            $name = $account['name'] ?? null;

            if (\is_string($name) && \strcasecmp($name, $configuredEmail) === 0) {
                return $accountId;
            }
        }

        $username = $sessionData['username'] ?? null;

        if (\is_string($username) && \strcasecmp($username, $configuredEmail) === 0) {
            return $this->primaryMailAccountId($sessionData);
        }

        /** @var array<string, string> $primaryAccounts */
        $primaryAccounts = $sessionData['primaryAccounts'] ?? [];

        // Legacy JMAP servers: primaryAccounts keyed by email address.
        $legacyAccountId = $primaryAccounts[$configuredEmail] ?? null;

        if (\is_string($legacyAccountId) && $legacyAccountId !== '') {
            return $legacyAccountId;
        }

        // Single-account tokens: use that account (aliases may differ from FASTMAIL_EMAIL).
        if (\count($accounts) === 1) {
            foreach ($accounts as $onlyAccountId => $account) {
                return $onlyAccountId;
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $sessionData
     */
    private function primaryMailAccountId(array $sessionData): string
    {
        /** @var array<string, string> $primaryAccounts */
        $primaryAccounts = $sessionData['primaryAccounts'] ?? [];

        return JmapCasts::string($primaryAccounts['urn:ietf:params:jmap:mail'] ?? null);
    }

    /**
     * @param array<string, mixed> $sessionData
     */
    private function accountResolutionErrorMessage(array $sessionData, string $configuredEmail): string
    {
        /** @var array<string, array<string, mixed>> $accounts */
        $accounts = $sessionData['accounts'] ?? [];
        $available = [];

        foreach ($accounts as $account) {
            $name = $account['name'] ?? null;

            if (\is_string($name) && $name !== '') {
                $available[] = $name;
            }
        }

        $username = $sessionData['username'] ?? null;

        if (\is_string($username) && $username !== '' && !\in_array($username, $available, true)) {
            $available[] = $username;
        }

        $hint = $available !== []
            ? ' Available accounts: ' . \implode(', ', $available) . '.'
            : '';

        return 'FASTMAIL_EMAIL ("' . $configuredEmail . '") does not match any account on this API token.'
            . $hint
            . ' Use the primary Fastmail address shown in your account settings.';
    }
}
