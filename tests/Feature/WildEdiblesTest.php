<?php

declare(strict_types=1);

use App\Actions\WildEdibles\CreatePickingAction;
use App\Actions\WildEdibles\DeleteWildEdiblePermanentlyAction;
use App\Actions\WildEdibles\StoreWildEdiblePhotoAction;
use App\Http\Controllers\WildEdiblePhotoController;
use App\Livewire\WildEdibles\Create;
use App\Livewire\WildEdibles\Edit;
use App\Livewire\WildEdibles\Index;
use App\Models\Picking;
use App\Models\User;
use App\Models\WildEdible;
use App\Models\WildEdiblePhoto;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

\covers(WildEdible::class);
\covers(WildEdiblePhoto::class);
\covers(Picking::class);
\covers(CreatePickingAction::class);
\covers(StoreWildEdiblePhotoAction::class);
\covers(DeleteWildEdiblePermanentlyAction::class);
\covers(Edit::class);
\covers(WildEdiblePhotoController::class);

\it('keeps the wild edible map private to the owner', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $edible = WildEdible::factory()->for($owner)->create();

    \expect(WildEdible::query()->forAuthUser()->whereKey($edible)->exists())->toBeFalse();
    $this->actingAs($other)->get(\route('wild-edibles.show', $edible))->assertNotFound();
});

\it('requires authentication for wild edible routes', function (): void {
    $this->get(\route('wild-edibles.index'))->assertRedirect(\route('login'));
});

\it('creates an edible and preserves an uploaded source image', function (): void {
    $disk = Storage::fake('local');
    Storage::set('wasabi', $disk);
    Storage::set('tmp-for-tests', $disk);
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('foraged.jpg', 120, 80);

    Livewire::actingAs($user)->test(Create::class)
        ->set('type', 'mushroom')->set('name', 'Chanterelle')->set('location_name', 'Forest edge')
        ->set('latitude', '55.4')->set('longitude', '9.1')->set('photo', $file)->call('save')->assertHasNoErrors();

    $edible = WildEdible::query()->where('user_id', $user->id)->firstOrFail();
    $photo = WildEdiblePhoto::query()->where('wild_edible_id', $edible->id)->firstOrFail();
    Storage::disk('wasabi')->assertExists($photo->storage_path);
    \expect($photo->original_file_name)->toBe('foraged.jpg')
        ->and($photo->metadata['width'])->toBe(120)
        ->and($photo->metadata['height'])->toBe(80);
});

\it('mounts the edit form with the enum-backed type as a string', function (): void {
    $user = User::factory()->create();
    $edible = WildEdible::factory()->for($user)->create(['type' => 'mushroom']);

    Livewire::actingAs($user)->test(Edit::class, ['wildEdible' => $edible])
        ->assertSet('type', 'mushroom')
        ->assertHasNoErrors();
});

\it('uses the configured default center for a new edible', function (): void {
    $user = User::factory()->create();
    \config()->set('wild-edibles.default_center', ['latitude' => 56.1234567, 'longitude' => 10.7654321]);

    Livewire::actingAs($user)->test(Create::class)
        ->assertSet('latitude', '56.1234567')
        ->assertSet('longitude', '10.7654321');
});

\it('renders the configured default map zoom', function (): void {
    $user = User::factory()->create();
    \config()->set('wild-edibles.default_zoom', 11);

    Livewire::actingAs($user)->test(Index::class)
        ->assertSee('data-zoom="11"', false);
});

\it('dispatches a marker refresh after deleting an edible', function (): void {
    $user = User::factory()->create();
    $edible = WildEdible::factory()->for($user)->create();

    Livewire::actingAs($user)->test(Index::class)
        ->call('delete', $edible->id)
        ->assertDispatched('wild-edibles-updated');
});

\it('serves photos only to their owner and never after soft deletion', function (): void {
    $disk = Storage::fake('local');
    Storage::set('wasabi', $disk);
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $edible = WildEdible::factory()->for($owner)->create();
    $photo = WildEdiblePhoto::factory()->for($edible)->create();
    Storage::disk('wasabi')->put($photo->storage_path, 'private image bytes');

    $this->actingAs($owner)->get(\route('wild-edibles.photo', $photo))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->actingAs($other)->get(\route('wild-edibles.photo', $photo))->assertForbidden();

    $edible->delete();
    $this->actingAs($owner)->get(\route('wild-edibles.photo', $photo))->assertForbidden();
    $edible->restore();

    $photo->delete();
    $this->actingAs($owner)->get(\route('wild-edibles.photo', $photo))->assertNotFound();
});

\it('soft deletes an edible while retaining photo storage until force deletion', function (): void {
    $disk = Storage::fake('local');
    Storage::set('wasabi', $disk);
    Storage::set('tmp-for-tests', $disk);
    $user = User::factory()->create();
    $edible = WildEdible::factory()->for($user)->create();
    $photo = WildEdiblePhoto::factory()->for($edible)->create();
    Storage::disk('wasabi')->put($photo->storage_path, 'source');

    \expect(function () use ($user, $edible): void {
        \app(DeleteWildEdiblePermanentlyAction::class)->handle($user, $edible);
    })->toThrow(AuthorizationException::class);

    $edible->delete();
    \expect($edible->fresh()->trashed())->toBeTrue();
    Storage::disk('wasabi')->assertExists($photo->storage_path);
    \app(DeleteWildEdiblePermanentlyAction::class)->handle($user, $edible);
    Storage::disk('wasabi')->assertMissing($photo->storage_path);
});

\it('keeps the picking factory owner aligned with its edible', function (): void {
    $picking = Picking::factory()->create();

    \expect($picking->user_id)->toBe($picking->wildEdible->user_id);
});

\it('does not save an invalid partial season', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(Create::class)
        ->set('type', 'berry')->set('name', 'Bilberry')->set('latitude', '55.4')->set('longitude', '9.1')
        ->set('season_start_month', 5)->call('save')->assertHasErrors('season_start_month');

    \expect(WildEdible::query()->where('user_id', $user->id)->count())->toBe(0);
});

\it('persists all-year and unspecified seasons distinctly', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(Create::class)
        ->set('type', 'fruit')->set('name', 'Apples')->set('latitude', '55.4')->set('longitude', '9.1')
        ->set('season_all_year', true)->call('save')->assertHasNoErrors();

    Livewire::actingAs($user)->test(Create::class)
        ->set('type', 'other')->set('name', 'Unknown edible')->set('latitude', '55.4')->set('longitude', '9.1')
        ->call('save')->assertHasNoErrors();

    $edibles = WildEdible::query()->where('user_id', $user->id)->orderBy('name')->get();
    \expect($edibles[0]->season_all_year)->toBeTrue()
        ->and($edibles[0]->season_start_month)->toBeNull()
        ->and($edibles[1]->season_all_year)->toBeFalse()
        ->and($edibles[1]->season_start_month)->toBeNull();
});

\it('filters owned edibles by type and month with wrapped season semantics', function (): void {
    $user = User::factory()->create();
    WildEdible::factory()->for($user)->create(['type' => 'mushroom', 'name' => 'Winter mushroom', 'season_start_month' => 11, 'season_end_month' => 2]);
    WildEdible::factory()->for($user)->create(['type' => 'berry', 'name' => 'Summer berry', 'season_start_month' => 6, 'season_end_month' => 8]);
    WildEdible::factory()->for($user)->create(['type' => 'mushroom', 'name' => 'Unknown mushroom', 'season_start_month' => null, 'season_end_month' => null]);
    WildEdible::factory()->create(['type' => 'mushroom', 'season_start_month' => 11, 'season_end_month' => 2]);

    $this->actingAs($user);
    $winter = WildEdible::query()->forAuthUser()->withFilters(['mushroom'], [1])->get();
    $summer = WildEdible::query()->forAuthUser()->withFilters([], [7])->get();

    \expect($winter->pluck('name')->all())->toBe(['Winter mushroom'])
        ->and($summer->pluck('name')->all())->toBe(['Summer berry']);
});

\it('stores picking coordinates as a historical snapshot and orders logs newest first', function (): void {
    $user = User::factory()->create();
    $edible = WildEdible::factory()->for($user)->create(['latitude' => 55.4, 'longitude' => 9.1]);
    Picking::factory()->for($edible)->for($user)->create(['picked_at' => '2026-01-01', 'latitude' => 55.5, 'longitude' => 9.2]);
    Picking::factory()->for($edible)->for($user)->create(['picked_at' => '2026-02-01', 'latitude' => 55.6, 'longitude' => 9.3]);

    $edible->update(['latitude' => 56.0]);
    $pickings = $edible->fresh()->pickings;
    \expect($pickings->first()->picked_at->toDateString())->toBe('2026-02-01')
        ->and($pickings->last()->picked_at->toDateString())->toBe('2026-01-01')
        ->and($pickings->last()->latitude)->toBe('55.5000000')
        ->and($pickings->last()->longitude)->toBe('9.2000000');
});

\it('blocks cross-user picking and photo actions', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $edible = WildEdible::factory()->for($owner)->create();
    $file = UploadedFile::fake()->image('private.jpg');

    \expect(fn(): Picking => \app(CreatePickingAction::class)->handle($other, $edible, [
        'picked_at' => '2026-01-01', 'latitude' => 55.4, 'longitude' => 9.1,
    ]))->toThrow(AuthorizationException::class);

    \expect(fn() => \app(DeleteWildEdiblePermanentlyAction::class)->handle($other, $edible))
        ->toThrow(AuthorizationException::class);

    $this->actingAs($other);
    \expect(fn(): WildEdiblePhoto => \app(StoreWildEdiblePhotoAction::class)->handle($other, $edible, $file))
        ->toThrow(AuthorizationException::class);
});
