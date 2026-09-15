<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Domain\Search\ReceiptSearch;
use App\Domain\Search\SearchQuery;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class SearchReceipt extends Component
{
    #[Url(as: 'q')]
    public string $query;

    public function render(ReceiptSearch $search): View
    {
        $user = \auth()->user();

        $receipts = $user instanceof User
            ? $search->searchFor($user, new SearchQuery($this->query))
            : [];

        return \view('livewire.receipts.search', ['title' => 'Søg: ' . $this->query, 'receipts' => $receipts]);
    }
}
