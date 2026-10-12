<?php

declare(strict_types=1);

use App\Livewire\Login;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

\covers(Login::class);

\it('renders accessible login controls in natural tab order', function (): void {
    $response = $this->get(\route('login'))->assertOk();
    $html = (string) $response->getContent();

    \expect($html)->toContain('for="email"')->toContain('for="password"')
        ->toContain('autocomplete="username"')->toContain('autocomplete="current-password"')
        ->not->toContain('tabindex=');
});

\it('rejects invalid credentials and honors remember off and on', function (bool $remember): void {
    $user = User::factory()->create(['email' => 'person@example.test', 'password' => 'correct-password']);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email']);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'correct-password')
        ->set('remember', $remember)
        ->call('login')
        ->assertRedirect(\route('index'));

    \expect($user->exists)->toBeTrue();
})->with([false, true]);

\it('returns a guest to the requested protected deep link after login', function (): void {
    $user = User::factory()->create(['email' => 'person@example.test', 'password' => 'correct-password']);

    $this->get('/kitchen/recipes')->assertRedirect(\route('login'));
    \expect(\session('url.intended'))->toContain('/kitchen/recipes');

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'correct-password')
        ->call('login')
        ->assertRedirect(\route('recipes.index'));
});

\it('logs out through the real logout route', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get(\route('logout'))->assertRedirect(\route('login'));
    \expect(\auth()->check())->toBeFalse();
});

\it('renders standalone recovery pages for common HTTP errors without leaking details', function (int $status): void {
    \config(['app.debug' => false]);

    if ($status === 404) {
        $response = $this->get('/__missing-mobile-recovery-test');
    } else {
        Route::get('/__mobile-recovery-test/' . $status, static function () use ($status): never {
            if ($status === 500) {
                throw new RuntimeException('secret exception detail');
            }

            \abort($status);
        });
        $response = $this->get('/__mobile-recovery-test/' . $status, ['Accept' => 'text/html']);
    }

    $response->assertStatus($status)
        ->assertSee('Gå til forsiden')
        ->assertSee('Log ind')
        ->assertDontSee('secret exception detail');
})->with([403, 404, 419, 500]);
