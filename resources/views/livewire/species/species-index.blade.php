@section('title', 'Bird Species')
<div>
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="recipe" style="overflow:hidden;">
                    <div style="float:right;">
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

                <div class="recipe-list">
                    <div class="list">
                        @foreach ($speciesList as $s)
                            <div class="recipe">
                                <h1>
                                    <a href="{{ route('species.show', $s) }}" wire:navigate>{{ $s->common_name }}</a>
                                </h1>
                                <div class="tags">
                                    <span class="pull-right" style="font-size:0.8rem;color:#888;">
                                        {{ $s->observations_count }} obs
                                    </span>
                                    <span style="font-style:italic;font-size:0.8rem;">{{ $s->scientific_name }}</span>
                                    <div class="clear"></div>
                                </div>
                            </div>
                        @endforeach
                        <div class="clear"></div>
                    </div>
                </div>

                <div class="text-center" style="margin-top:20px;">
                    {{ $speciesList->links() }}
                </div>
            </div>

            <div class="alert alert-info" role="alert"><header>Information</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-success" role="alert"><header>Success</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-warning" role="alert"><header>Warning</header><main><span class="alert_text"></span></main></div>
            <div class="alert alert-danger" role="alert"><header>Error</header><main><span class="alert_text"></span></main></div>
        </div>
    </div>
</div>
