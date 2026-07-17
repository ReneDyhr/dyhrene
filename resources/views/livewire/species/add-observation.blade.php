<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Log Observation</h2>
    </x-slot>

    <div class="max-w-lg mx-auto p-4">
        @if (session()->has('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">{{ session('success') }}</div>
        @endif

        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Species *</label>
                @if ($selectedSpeciesId)
                    <div class="flex items-center gap-2 p-2 border rounded bg-green-50">
                        <span>{{ $speciesSearch }}</span>
                        <button type="button" wire:click="$set('selectedSpeciesId', null)"
                                class="text-red-500 text-sm">&times; Clear</button>
                    </div>
                @else
                    <input type="text" wire:model.live.debounce.200ms="speciesSearch"
                           placeholder="Type species name (dansk or latin)..."
                           class="w-full px-3 py-2 border rounded">
                    @if (count($speciesResults))
                        <ul class="border rounded mt-1 max-h-40 overflow-y-auto">
                            @foreach ($speciesResults as $r)
                                <li wire:click="selectSpecies({{ $r['id'] }})"
                                    class="px-3 py-2 hover:bg-gray-100 cursor-pointer">
                                    {{ $r['common_name'] }}
                                    <span class="text-gray-400 italic text-sm">{{ $r['scientific_name'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @elseif (strlen($speciesSearch) >= 2)
                        <p class="text-sm text-gray-500 mt-1">
                            No match.
                            <button type="button" wire:click="createSpecies"
                                    class="text-blue-600 underline">Create "{{ $speciesSearch }}"</button>
                        </p>
                    @endif
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Date *</label>
                <input type="date" wire:model="date" class="w-full px-3 py-2 border rounded">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Time</label>
                <input type="time" wire:model="time" class="w-full px-3 py-2 border rounded">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Count</label>
                <input type="text" wire:model="count" placeholder="X or number"
                       class="w-full px-3 py-2 border rounded">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Location</label>
                <input type="text" wire:model="location" placeholder="e.g. Jels Skovvej 17"
                       class="w-full px-3 py-2 border rounded">
            </div>

            <button type="submit"
                    class="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 font-medium">
                Log Observation
            </button>
        </form>
    </div>
</div>
