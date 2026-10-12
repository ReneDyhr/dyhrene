<?php

declare(strict_types=1);

use App\Models\User;

\covers(User::class);

\it('preserves MCP consent controls while exposing the shared install identity', function (): void {
    $client = (object) ['id' => 'synthetic-client-42', 'name' => 'Synthetic MCP Client'];
    $user = (object) ['email' => 'synthetic@example.test'];
    $scopes = [(object) ['description' => 'Read synthetic records']];
    $html = \view('mcp.authorize', [
        'client' => $client,
        'user' => $user,
        'scopes' => $scopes,
        'authToken' => 'synthetic-auth-token',
    ])->render();

    $document = new DOMDocument();
    $previous = \libxml_use_internal_errors(true);

    try {
        $loaded = $document->loadHTML($html);
    } finally {
        \libxml_clear_errors();
        \libxml_use_internal_errors($previous);
    }
    \expect($loaded)->toBeTrue();
    $xpath = new DOMXPath($document);

    \expect($xpath->evaluate('count(//head/link[@rel="manifest" and @href="/site.webmanifest"])'))->toBe(1.0);
    \expect($xpath->evaluate('count(//head/meta[@name="apple-mobile-web-app-title" and @content="Dyhrene"])'))->toBe(1.0);
    \expect(\trim((string) $xpath->evaluate('string(//head/title)')))->toBe('Authorize Application - ' . \config('app.name', 'MCP Server'));

    \expect($xpath->evaluate('count(//form[@method="POST"])'))->toBe(2.0);
    \expect($xpath->evaluate('count(//form[@action="' . \route('passport.authorizations.deny') . '" and @method="POST"])'))->toBeGreaterThanOrEqual(1.0);
    \expect($xpath->evaluate('count(//form[@action="' . \route('passport.authorizations.approve') . '" and @method="POST" and @id="authorizeForm"])'))->toBe(1.0);
    \expect($xpath->evaluate('count(//form/input[@name="_token"])'))->toBe(2.0);
    \expect($xpath->evaluate('count(//form/input[@name="_method" and @value="DELETE"])'))->toBe(1.0);

    foreach (['state', 'client_id', 'auth_token'] as $field) {
        \expect($xpath->evaluate('count(//form/input[@name="' . $field . '"])'))->toBe(2.0);
    }
    \expect($xpath->evaluate('count(//form[@id="authorizeForm"]//button[@id="authorizeButton"])'))->toBe(1.0);
    \expect($xpath->evaluate('count(//span[@id="authorizeText"])'))->toBe(1.0);
    \expect($xpath->evaluate('count(//li/span[normalize-space(.)="Read synthetic records"])'))->toBe(1.0);
    \expect($xpath->evaluate('count(//h3[contains(., "Synthetic MCP Client")])'))->toBe(1.0);
});
