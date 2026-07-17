<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Bird Species</h2>
    </x-slot>

    <div class="p-4">
        <div class="flex gap-3 mb-4">
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Search species (dansk or latin)..."
                   class="w-full px-3 py-2 border rounded shadow-sm">
            <a href="{{ route('species.add') }}"
               class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 whitespace-nowrap">
                + Log Observation
            </a>
        </div>

        <div class="grid gap-3">
            @foreach ($speciesList as $s)
                <a href="{{ route('species.show', $s) }}" wire:navigate
                   class="block p-3 border rounded hover:bg-gray-50 transition">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="font-semibold">{{ $s->common_name }}</span>
                            <span class="text-gray-500 italic ml-2">{{ $s->scientific_name }}</span>
                        </div>
                        <span class="text-sm text-gray-500">{{ $s->observations_count }} obs</span>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-4">{{ $speciesList->links() }}</div>
    </div>
</div>
