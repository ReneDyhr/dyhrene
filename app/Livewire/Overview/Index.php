<?php

declare(strict_types=1);

namespace App\Livewire\Overview;

use App\Domain\Overview\Contracts\OverviewSnapshotReaderInterface;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public function render(OverviewSnapshotReaderInterface $reader): View
    {
        $userId = \auth()->id();

        if (!\is_int($userId)) {
            \abort(401);
        }

        return \view('livewire.overview.index', [
            'snapshot' => $reader->read($userId, \now('Europe/Copenhagen')->toDateTimeImmutable()),
        ]);
    }
}
