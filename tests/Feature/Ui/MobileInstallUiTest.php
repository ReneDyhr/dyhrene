<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Blade;

\covers(User::class);

\it('does not render account logout in a non-family app shell', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(\route('index'));
    $response->assertOk();

    $document = new DOMDocument();
    $previous = \libxml_use_internal_errors(true);

    try {
        $document->loadHTML((string) $response->getContent());
    } finally {
        \libxml_clear_errors();
        \libxml_use_internal_errors($previous);
    }

    $xpath = new DOMXPath($document);
    \expect($xpath->evaluate('count(//*[@data-mobile-account])'))->toBe(0.0)
        ->and($xpath->evaluate('count(//*[@href="' . \route('logout') . '"])'))->toBe(0.0)
        ->and($xpath->evaluate('count(//*[contains(concat(" ", normalize-space(@class), " "), " rail-foot ")])'))->toBe(0.0);
});

\it('renders account logout inside the family page content', function (): void {
    $user = User::factory()->create(['name' => 'René Dyhr']);

    $response = $this->actingAs($user)->get(\route('family.index'));
    $response->assertOk();

    $document = new DOMDocument();
    $previous = \libxml_use_internal_errors(true);

    try {
        $document->loadHTML((string) $response->getContent());
    } finally {
        \libxml_clear_errors();
        \libxml_use_internal_errors($previous);
    }

    $xpath = new DOMXPath($document);
    \expect($xpath->evaluate('count(//main[@id="main-content"]//*[@data-mobile-account])'))->toBe(1.0)
        ->and($xpath->evaluate('count(//main[@id="main-content"]//*[@data-mobile-account]//a[@href="' . \route('logout') . '" and normalize-space(.)="Log ud"])'))->toBe(1.0)
        ->and($xpath->evaluate('count(//header//*[@data-mobile-account])'))->toBe(0.0)
        ->and($xpath->evaluate('count(//*[contains(concat(" ", normalize-space(@class), " "), " rail-foot ")])'))->toBe(0.0);
});

\it('does not render an account logout control for guests', function (): void {
    $html = Blade::render('<x-layouts.app-shell><p>Indhold</p></x-layouts.app-shell>');

    $document = new DOMDocument();
    $previous = \libxml_use_internal_errors(true);

    try {
        $document->loadHTML($html);
    } finally {
        \libxml_clear_errors();
        \libxml_use_internal_errors($previous);
    }

    $xpath = new DOMXPath($document);
    \expect($xpath->evaluate('count(//header[contains(concat(" ", normalize-space(@class), " "), " topbar ")]//*[@data-mobile-account])'))
        ->toBe(0.0)
        ->and($xpath->evaluate('count(//*[@data-app-install-controls])'))->toBe(1.0)
        ->and($xpath->evaluate('count(//*[@data-app-install-help])'))->toBe(1.0);
});

\it('keeps install controls separate from the mobile-only account wrapper', function (): void {
    $user = User::factory()->create();
    $html = $this->actingAs($user)->get(\route('index'))->getContent();

    $document = new DOMDocument();
    $previous = \libxml_use_internal_errors(true);

    try {
        $document->loadHTML((string) $html);
    } finally {
        \libxml_clear_errors();
        \libxml_use_internal_errors($previous);
    }

    $xpath = new DOMXPath($document);
    \expect($xpath->evaluate('count(//*[@data-app-install-controls and ancestor::*[@data-mobile-account]])'))->toBe(0.0)
        ->and($xpath->evaluate('count(//*[@data-app-install-controls]//*[@data-app-install-help])'))->toBe(1.0)
        ->and($xpath->evaluate('count(//*[@data-app-install][@hidden])'))->toBe(1.0);
});
