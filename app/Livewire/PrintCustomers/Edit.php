<?php

declare(strict_types=1);

namespace App\Livewire\PrintCustomers;

use App\Models\PrintCustomer;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

class Edit extends Component
{
    public PrintCustomer $customer;

    public string $name = '';

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $notes = null;

    public function mount(PrintCustomer $customer): void
    {
        $this->customer = $customer;
        $this->name = $customer->name;
        $this->email = $customer->email;
        $this->phone = $customer->phone;
        $this->notes = $customer->notes;
    }

    public function save(): ?Redirector
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $this->customer->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'notes' => $this->notes,
        ]);

        \session()->flash('success', 'Customer updated successfully.');

        return $this->redirect(\route('print-customers.index'));
    }

    public function render(): View
    {
        return \view('livewire.print-customers.edit');
    }
}
