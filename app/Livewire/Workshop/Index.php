<?php

declare(strict_types=1);

namespace App\Livewire\Workshop;

use App\Domain\Workshop\Contracts\WorkshopSnapshotReaderInterface;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public function render(WorkshopSnapshotReaderInterface $reader): View
    {
        return \view('livewire.workshop.index', [
            'snapshot' => $reader->read(),
        ]);
    }
}
