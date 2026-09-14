<?php

declare(strict_types=1);

namespace App\Livewire\Kitchen;

use App\Domain\Kitchen\Contracts\KitchenSnapshotReaderInterface;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public function render(KitchenSnapshotReaderInterface $reader): View
    {
        $userId = \auth()->id();

        if (!\is_int($userId)) {
            \abort(401);
        }

        return \view('livewire.kitchen.index', [
            'snapshot' => $reader->read($userId),
        ]);
    }
}
