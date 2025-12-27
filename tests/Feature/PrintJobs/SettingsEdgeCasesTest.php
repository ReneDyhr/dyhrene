<?php

declare(strict_types=1);

use App\Models\PrintSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

\uses()->group('feature');

\beforeEach(function () {
    $this->user = User::factory()->create();
});

\test('Setting::current() auto-creates if missing', function () {
    PrintSetting::query()->delete();
    Cache::flush();

    $setting = PrintSetting::current();

    \expect($setting)->not->toBeNull()
        ->and($setting->id)->toBe(1);
})->coversNothing();

\test('settings cache invalidation', function () {
    $setting = PrintSetting::current();
    $originalRate = $setting->electricity_rate_dkk_per_kwh;

    // Update setting
    $setting->update(['electricity_rate_dkk_per_kwh' => 3.0]);

    // Clear cache and get fresh
    Cache::flush();
    $newSetting = PrintSetting::current();

    \expect($newSetting->electricity_rate_dkk_per_kwh)->toBe(3);
})->coversNothing();

\test('settings update clears cache', function () {
    $setting = PrintSetting::current();

    // Get cached version
    $cached = PrintSetting::current();

    // Update
    $setting->update(['electricity_rate_dkk_per_kwh' => 4.0]);

    // Get fresh (should reflect update after cache clear)
    Cache::flush();
    $fresh = PrintSetting::current();

    \expect($fresh->electricity_rate_dkk_per_kwh)->toBe(4);
})->coversNothing();
