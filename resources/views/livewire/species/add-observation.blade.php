@section('title', 'Log Observation')
<div>
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content">
            <div class="col-12">
                <div class="new-recipe">
                    <div class="col-6">
                        @if (session()->has('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <form wire:submit="save">
                            <h1>Log Observation</h1>

                            <div class="form-group" style="margin-top:20px;">
                                <label for="speciesSearch">Species *</label>
                                @if ($selectedSpeciesId)
                                    <div style="padding:6px;background:#dff0d8;border:1px solid #d6e9c6;border-radius:3px;">
                                        <span>{{ $speciesSearch }}</span>
                                        <button type="button" wire:click="$set('selectedSpeciesId', null)"
                                                class="btn btn-xs btn-danger pull-right">&times;</button>
                                        <div class="clear"></div>
                                    </div>
                                @else
                                    <input type="text" id="speciesSearch"
                                           wire:model.live.debounce.200ms="speciesSearch"
                                           placeholder="Type species name (dansk or latin)..."
                                           class="form-control">
                                    @if (count($speciesResults))
                                        <ul class="list-group" style="margin-top:5px;">
                                            @foreach ($speciesResults as $r)
                                                <li wire:click="selectSpecies({{ $r['id'] }})"
                                                    class="list-group-item" style="cursor:pointer;">
                                                    {{ $r['common_name'] }}
                                                    <span style="font-style:italic;color:#999;font-size:0.8rem;">
                                                        {{ $r['scientific_name'] }}
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @elseif (strlen($speciesSearch) >= 2)
                                        <p class="help-block" style="margin-top:5px;">
                                            No match.
                                            <a href="javascript:void(0);" wire:click="createSpecies">
                                                Create "{{ $speciesSearch }}"
                                            </a>
                                        </p>
                                    @endif
                                @endif
                            </div>

                            <div class="form-group">
                                <label for="date">Date *</label>
                                <input type="date" id="date" wire:model="date" class="form-control">
                            </div>

                            <div class="form-group">
                                <label for="time">Time</label>
                                <input type="time" id="time" wire:model="time" class="form-control">
                            </div>

                            <div class="form-group">
                                <label for="count">Count</label>
                                <input type="text" id="count" wire:model="count"
                                       placeholder="X or number" class="form-control">
                            </div>

                            <div class="form-group">
                                <label for="location">Location</label>
                                <input type="text" id="location" wire:model="location"
                                       placeholder="e.g. Jels Skovvej 17" class="form-control">
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-success">
                                    <i class="fa fa-check"></i> Log Observation
                                </button>
                                <a href="{{ route('species.index') }}" class="btn btn-default">Cancel</a>
                            </div>
                            <div class="clear"></div>
                        </form>
                    </div>
                    <div class="clear"></div>
                </div>
            </div>

            <div class="alert alert-info" role="alert"><header>Information</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-success" role="alert"><header>Success</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-warning" role="alert"><header>Warning</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-danger" role="alert"><header>Error</header><main><span class="alert_text"></span></main></div>
        </div>
    </div>
</div>
