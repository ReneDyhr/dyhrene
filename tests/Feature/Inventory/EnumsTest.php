<?php

declare(strict_types=1);

use App\Enums\InventoryAcquisitionTypeEnum;
use App\Enums\InventoryOwnerEnum;
use App\Enums\InventoryStatusEnum;

\uses()->group('feature');

\covers(InventoryOwnerEnum::class);
\covers(InventoryAcquisitionTypeEnum::class);
\covers(InventoryStatusEnum::class);

\test('InventoryOwnerEnum has three cases with correct labels', function (): void {
    $cases = InventoryOwnerEnum::cases();

    \expect($cases)->toHaveCount(3);

    \expect(InventoryOwnerEnum::Shared->value)->toBe('shared');
    \expect(InventoryOwnerEnum::Shared->label())->toBe('Shared');

    \expect(InventoryOwnerEnum::Rene->value)->toBe('rene');
    \expect(InventoryOwnerEnum::Rene->label())->toBe('Rene');

    \expect(InventoryOwnerEnum::Jeanette->value)->toBe('jeanette');
    \expect(InventoryOwnerEnum::Jeanette->label())->toBe('Jeanette');
});

\test('InventoryAcquisitionTypeEnum has five cases with correct labels', function (): void {
    $cases = InventoryAcquisitionTypeEnum::cases();

    \expect($cases)->toHaveCount(5);

    \expect(InventoryAcquisitionTypeEnum::Bought->value)->toBe('bought');
    \expect(InventoryAcquisitionTypeEnum::Bought->label())->toBe('Bought');

    \expect(InventoryAcquisitionTypeEnum::Gift->value)->toBe('gift');
    \expect(InventoryAcquisitionTypeEnum::Gift->label())->toBe('Gift');

    \expect(InventoryAcquisitionTypeEnum::Inherited->value)->toBe('inherited');
    \expect(InventoryAcquisitionTypeEnum::Inherited->label())->toBe('Inherited');

    \expect(InventoryAcquisitionTypeEnum::Found->value)->toBe('found');
    \expect(InventoryAcquisitionTypeEnum::Found->label())->toBe('Found');

    \expect(InventoryAcquisitionTypeEnum::Built->value)->toBe('built');
    \expect(InventoryAcquisitionTypeEnum::Built->label())->toBe('Built');
});

\test('InventoryStatusEnum has seven cases with correct labels', function (): void {
    $cases = InventoryStatusEnum::cases();

    \expect($cases)->toHaveCount(7);

    \expect(InventoryStatusEnum::Owned->value)->toBe('owned');
    \expect(InventoryStatusEnum::Owned->label())->toBe('Owned');

    \expect(InventoryStatusEnum::Sold->value)->toBe('sold');
    \expect(InventoryStatusEnum::Sold->label())->toBe('Sold');

    \expect(InventoryStatusEnum::Stolen->value)->toBe('stolen');
    \expect(InventoryStatusEnum::Stolen->label())->toBe('Stolen');

    \expect(InventoryStatusEnum::Lost->value)->toBe('lost');
    \expect(InventoryStatusEnum::Lost->label())->toBe('Lost');

    \expect(InventoryStatusEnum::Donated->value)->toBe('donated');
    \expect(InventoryStatusEnum::Donated->label())->toBe('Donated');

    \expect(InventoryStatusEnum::LentOut->value)->toBe('lent_out');
    \expect(InventoryStatusEnum::LentOut->label())->toBe('Lent out');

    \expect(InventoryStatusEnum::InRepair->value)->toBe('in_repair');
    \expect(InventoryStatusEnum::InRepair->label())->toBe('In repair');
});

\test('InventoryStatusEnum is a backed string enum', function (): void {
    \expect(InventoryStatusEnum::class)->toImplement(BackedEnum::class);
    \expect(InventoryStatusEnum::Owned->value)->toBeString();
});

\test('all enums use tryFrom correctly', function (): void {
    \expect(InventoryOwnerEnum::tryFrom('shared'))->toBe(InventoryOwnerEnum::Shared);
    \expect(InventoryOwnerEnum::tryFrom('nonexistent'))->toBeNull();

    \expect(InventoryStatusEnum::tryFrom('owned'))->toBe(InventoryStatusEnum::Owned);
    \expect(InventoryStatusEnum::tryFrom('nonexistent'))->toBeNull();
});
