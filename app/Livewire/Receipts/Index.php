<?php

declare(strict_types=1);

namespace App\Livewire\Receipts;

use App\Models\Receipt;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    /**
     * @var array<int, string>
     */
    protected $listeners = ['deleteReceipt'];

    /**
     * This method must be public for Livewire event handling.
     */
    public function deleteReceipt(int $id): \Livewire\Features\SupportRedirects\Redirector
    {
        $receipt = Receipt::query()->findOrFail($id);
        \app(\App\Actions\DeleteReceiptAction::class)->handle($receipt);
        \session()->flash('success', 'Receipt deleted!');

        // @phpstan-ignore return.type
        return \redirect()->route('receipts.index');
    }

    public function render(): View
    {
        $receipts = Receipt::query()->with('user')->orderByDesc('date')->get();

        return \view('receipts.index', \compact('receipts'));
    }
}
