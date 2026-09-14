<x-layouts.app-shell :area="\App\Enums\AppArea::Family">
    <div class="view-head">
        <h1>Familien</h1>
        <p>Profiler, post og adgang. Det tekniske ligger her, så det ikke fylder i resten af huset.</p>
    </div>

    <div class="subnav">
        <a href="{{ route('mail.inbox') }}" wire:navigate aria-current="page">Post</a>
        <a href="{{ route('settings.mcp') }}" wire:navigate>AI-adgang</a>
    </div>

    <div class="grid g3">
        <article class="card">
            <h3>Post</h3>
            <div class="meta">Klassificeret mail fra Fastmail</div>
        </article>
        <article class="card">
            <h3>AI-adgang</h3>
            <div class="meta">MCP-servere til eksterne klienter</div>
        </article>
    </div>
</x-layouts.app-shell>
