@section('title', 'Bird Species')
<div>
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            @if (session()->has('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session()->has('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            <div class="col-12">
                <div class="recipe" style="overflow:hidden;">
                    <div style="float:right;">
                        <button wire:click="importEbird" class="btn btn-info" style="color:#fff;">
                            <i class="fa fa-refresh"></i> Import eBird
                        </button>
                        <a href="{{ route('species.add') }}" class="btn btn-success" style="color:#fff;">
                            <i class="fa fa-plus"></i> Log Observation
                        </a>
                    </div>
                    <h1>Bird Species</h1>

                    <div class="form-group" style="margin-top:20px;">
                        <input type="text" wire:model.live.debounce.300ms="search"
                               placeholder="Search species (dansk or latin)..."
                               class="form-control">
                    </div>
                    <div class="clear"></div>
                </div>

                <div class="notes">
                    <table class="table table-striped" style="margin-bottom:0;">
                        <thead>
                            <tr>
                                <th style="cursor:pointer;" wire:click="sortBy('common_name')">
                                    Species
                                    @if ($sortField === 'common_name')
                                        <i class="fa fa-sort-{{ $sortDirection === 'asc' ? 'asc' : 'desc' }}"></i>
                                    @endif
                                </th>
                                <th style="cursor:pointer;" wire:click="sortBy('scientific_name')">
                                    Scientific Name
                                    @if ($sortField === 'scientific_name')
                                        <i class="fa fa-sort-{{ $sortDirection === 'asc' ? 'asc' : 'desc' }}"></i>
                                    @endif
                                </th>
                                <th class="text-center" style="cursor:pointer;" wire:click="sortBy('observations_count')">
                                    Observations
                                    @if ($sortField === 'observations_count')
                                        <i class="fa fa-sort-{{ $sortDirection === 'asc' ? 'asc' : 'desc' }}"></i>
                                    @endif
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($speciesList as $s)
                                <tr>
                                    <td>
                                        <a href="{{ route('species.show', $s) }}" wire:navigate style="font-size:0.9rem;">
                                            {{ $s->common_name }}
                                        </a>
                                    </td>
                                    <td style="font-style:italic;color:#888;">{{ $s->scientific_name }}</td>
                                    <td class="text-center">{{ $s->observations_count }}</td>
                                </tr>
                            @endforeach
                            @if ($speciesList->isEmpty())
                                <tr>
                                    <td colspan="3" class="text-center text-muted" style="padding:30px;">
                                        @if ($search)
                                            No species match "{{ $search }}".
                                        @else
                                            No species yet — <a href="{{ route('species.add') }}">log your first observation</a>.
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="text-center" style="margin-top:20px;">
                    {{ $speciesList->links('pagination::bootstrap-4') }}
                </div>
            </div>

            <div class="alert alert-info" role="alert"><header>Information</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-success" role="alert"><header>Success</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-warning" role="alert"><header>Warning</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-danger" role="alert"><header>Error</header><main><span class="alert_text"></span></main></div>
        </div>
    </div>
</div>
