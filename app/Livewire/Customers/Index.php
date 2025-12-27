<?php

declare(strict_types=1);

namespace App\Livewire\Customers;

use App\Models\PrintCustomer;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    /**
     * Delete a customer (soft delete).
     */
    public function delete(int $id): void
    {
        $customer = PrintCustomer::query()->findOrFail($id);
        $customer->delete();
        \session()->flash('success', 'Customer deleted successfully.');
    }

    public function render(): View
    {
        $query = PrintCustomer::query()->orderBy('name');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        $customers = $query->paginate(25);

        return \view('livewire.customers.index', \compact('customers'));
    }
}
