<?php

declare(strict_types=1);

namespace App\Livewire\Receipts;

use App\Models\Receipt;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    public function render(): View
    {
        $receipts = Receipt::query()->with('user')->latest()->get();

        return \view('receipts.index', \compact('receipts'));
    }
}
