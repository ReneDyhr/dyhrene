<?php

declare(strict_types=1);

namespace App\Livewire\Receipts;

use App\Models\Receipt;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Show extends Component
{
    public Receipt $receipt;

    public function mount(Receipt $receipt): void
    {
        $this->receipt = $receipt->load('items');
    }

    public function render(): View
    {
        return \view('receipts.show', [
            'receipt' => $this->receipt,
        ]);
    }
}
