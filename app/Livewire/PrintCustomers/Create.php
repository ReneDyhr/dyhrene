<?php

declare(strict_types=1);

namespace App\Livewire\PrintCustomers;

use App\Models\PrintCustomer;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Create extends Component
{
    public string $name = '';
    public ?string $email = null;
    public ?string $phone = null;
    public ?string $notes = null;

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        PrintCustomer::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'notes' => $this->notes,
        ]);

        \session()->flash('success', 'Customer created successfully.');

        return $this->redirect(\route('print-customers.index'));
    }

    public function render(): View
    {
        return \view('livewire.print-customers.create');
    }
}

