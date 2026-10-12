<?php

declare(strict_types=1);

use App\Models\User;

\covers(User::class);

/** @return array{DOMDocument, DOMXPath} */
function mobileWebAppDocument(string $html): array
{
    $document = new DOMDocument();
    $previous = \libxml_use_internal_errors(true);

    try {
        $loaded = $document->loadHTML($html);
    } finally {
        \libxml_clear_errors();
        \libxml_use_internal_errors($previous);
    }

    \expect($loaded)->toBeTrue();

    return [$document, new DOMXPath($document)];
}

/** @return list<DOMElement> */
function mobileWebAppElements(DOMXPath $xpath, string $query): array
{
    $nodes = $xpath->query($query);
    \expect($nodes)->not->toBeFalse();

    $elements = [];

    foreach ($nodes as $node) {
        if ($node instanceof DOMElement) {
            $elements[] = $node;
        }
    }

    return $elements;
}

/** @param array<string, string> $attributes */
function expectMobileWebAppSingleElement(DOMXPath $xpath, string $query, array $attributes): void
{
    $elements = \mobileWebAppElements($xpath, $query);
    \expect($elements)->toHaveCount(1);

    foreach ($attributes as $name => $value) {
        \expect($elements[0]->getAttribute($name))->toBe($value);
    }
}

/** @return array{DOMDocument, DOMXPath} */
function mobileWebAppPage(string $url, ?User $user = null): array
{
    $test = \test();

    if ($user !== null) {
        $test->actingAs($user);
    }

    $response = $test->get($url);
    $response->assertOk();

    return \mobileWebAppDocument((string) $response->getContent());
}

\it('serves the install identity and zoom-capable document head to login guests', function (): void {
    [$document, $xpath] = \mobileWebAppPage(\route('login'));

    \expectMobileWebAppSingleElement($xpath, '//head/title', []);
    \expect(\trim(\mobileWebAppElements($xpath, '//head/title')[0]->textContent))->not->toBeEmpty();
    \expectMobileWebAppSingleElement($xpath, '//head/meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="viewport"]', [
        'content' => 'width=device-width, initial-scale=1.0, viewport-fit=cover',
    ]);
    \expectMobileWebAppSingleElement($xpath, '//head/meta[@name="theme-color"]', ['content' => '#191e1a']);
    \expectMobileWebAppSingleElement($xpath, '//head/link[translate(@rel,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="manifest"]', ['href' => '/site.webmanifest']);
    \expectMobileWebAppSingleElement($xpath, '//head/meta[@name="apple-mobile-web-app-title"]', ['content' => 'Dyhrene']);
    \expectMobileWebAppSingleElement($xpath, '//head/meta[@name="apple-mobile-web-app-capable"]', ['content' => 'yes']);
    \expectMobileWebAppSingleElement($xpath, '//head/meta[@name="apple-mobile-web-app-status-bar-style"]', ['content' => 'black']);
    \expectMobileWebAppSingleElement($xpath, '//head/meta[@name="mobile-web-app-capable"]', ['content' => 'yes']);
    \expectMobileWebAppSingleElement($xpath, '//head/link[translate(@rel,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="apple-touch-icon"]', ['href' => '/apple-touch-icon.png']);
});

\it('preserves the authenticated home title and install identity in the document head', function (): void {
    $user = User::factory()->create();
    [$document, $xpath] = \mobileWebAppPage('/', $user);

    \expectMobileWebAppSingleElement($xpath, '//head/title', []);
    \expect(\trim(\mobileWebAppElements($xpath, '//head/title')[0]->textContent))->not->toBeEmpty();
    \expectMobileWebAppSingleElement($xpath, '//head/meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="viewport"]', [
        'content' => 'width=device-width, initial-scale=1.0, viewport-fit=cover',
    ]);
    \expectMobileWebAppSingleElement($xpath, '//head/meta[@name="theme-color"]', ['content' => '#191e1a']);
    \expectMobileWebAppSingleElement($xpath, '//head/link[translate(@rel,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="manifest"]', ['href' => '/site.webmanifest']);
    \expectMobileWebAppSingleElement($xpath, '//head/meta[@name="apple-mobile-web-app-title"]', ['content' => 'Dyhrene']);
    \expectMobileWebAppSingleElement($xpath, '//head/meta[@name="apple-mobile-web-app-capable"]', ['content' => 'yes']);
    \expectMobileWebAppSingleElement($xpath, '//head/meta[@name="apple-mobile-web-app-status-bar-style"]', ['content' => 'black']);
    \expectMobileWebAppSingleElement($xpath, '//head/meta[@name="mobile-web-app-capable"]', ['content' => 'yes']);
    \expectMobileWebAppSingleElement($xpath, '//head/link[translate(@rel,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="apple-touch-icon"]', ['href' => '/apple-touch-icon.png']);
});

\it('exposes the fixed install manifest identity and real PNG icon contracts', function (): void {
    $manifestPath = \public_path('site.webmanifest');
    \expect(\is_file($manifestPath))->toBeTrue('Expected public/site.webmanifest to exist.');

    if (!\is_file($manifestPath)) {
        return;
    }

    $manifestContents = \file_get_contents($manifestPath);
    \expect($manifestContents)->not->toBeFalse();

    if ($manifestContents === false) {
        return;
    }

    try {
        $manifest = \json_decode($manifestContents, true, 512, \JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        \expect(false)->toBeTrue('Manifest must be valid JSON: ' . $exception->getMessage());

        return;
    }

    \expect($manifest)->toBeArray();
    \expect($manifest)->toMatchArray([
        'id' => '/',
        'name' => 'Dyhrene',
        'short_name' => 'Dyhrene',
        'description' => 'Familiens samlested',
        'lang' => 'da',
        'start_url' => '/',
        'scope' => '/',
        'display' => 'standalone',
        'background_color' => '#111512',
        'theme_color' => '#191e1a',
        'prefer_related_applications' => false,
    ]);
    \expect($manifest)->not->toHaveKey('orientation');

    $icons = $manifest['icons'] ?? null;
    \expect($icons)->toBeArray();

    if (!\is_array($icons)) {
        return;
    }

    \expect($icons)->toHaveCount(3);

    $iconContracts = [
        ['/images/pwa/icon-192.png', '192x192', 'any'],
        ['/images/pwa/icon-512.png', '512x512', 'any'],
        ['/images/pwa/maskable-512.png', '512x512', 'maskable'],
    ];

    foreach ($iconContracts as [$src, $sizes, $purpose]) {
        $matches = \array_values(\array_filter(
            $icons,
            static fn(mixed $icon): bool => \is_array($icon) && ($icon['src'] ?? null) === $src,
        ));
        \expect($matches)->toHaveCount(1, "Expected exactly one manifest entry for {$src}.");

        if ($matches === []) {
            continue;
        }

        \expect($matches[0])->toMatchArray([
            'src' => $src,
            'sizes' => $sizes,
            'type' => 'image/png',
            'purpose' => $purpose,
        ]);

        $path = \public_path(\ltrim($src, '/'));
        \expect(\is_file($path))->toBeTrue("Expected real PNG asset {$path} to exist.");

        if (!\is_file($path)) {
            continue;
        }

        $image = @\getimagesize($path);
        \expect($image)->not->toBeFalse("Expected {$path} to be a decodable PNG.");

        if ($image !== false) {
            \expect([$image[0], $image[1]])->toBe(\array_map('intval', \explode('x', $sizes)));
            \expect($image['mime'] ?? null)->toBe('image/png');
        }
    }

    $appleIconPath = \public_path('apple-touch-icon.png');
    \expect(\is_file($appleIconPath))->toBeTrue('Expected the Apple touch icon to exist.');

    if (\is_file($appleIconPath)) {
        $appleIcon = @\getimagesize($appleIconPath);
        \expect($appleIcon)->not->toBeFalse('Expected a decodable Apple touch PNG.');

        if ($appleIcon !== false) {
            \expect([$appleIcon[0], $appleIcon[1]])->toBe([180, 180]);
            \expect($appleIcon['mime'] ?? null)->toBe('image/png');
        }
    }
});
